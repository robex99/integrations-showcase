<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\UseCases\Subscription\ChangeSubscriptionPlan;

use DateTimeImmutable;
use PaymentIntegrations\Application\DTOs\ChangePlanDTO;
use PaymentIntegrations\Application\UseCases\Subscription\CreateSubscription\CardStorageService;
use PaymentIntegrations\Domain\Subscription\Entities\Invoice;
use PaymentIntegrations\Domain\Subscription\Exceptions\InvalidPlanChangeException;
use PaymentIntegrations\Domain\Subscription\Exceptions\PlanNotFoundException;
use PaymentIntegrations\Domain\Subscription\Exceptions\SubscriptionNotFoundException;
use PaymentIntegrations\Domain\Subscription\Repositories\InvoiceRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\Repositories\PlanRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\Repositories\SubscriptionRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\Services\PlanChangeEvaluator;
use PaymentIntegrations\Domain\Subscription\Services\ProrationCalculator;
use PaymentIntegrations\Domain\Subscription\ValueObjects\PlanChangeType;
use PaymentIntegrations\Infrastructure\Notifications\Contracts\NotificationServiceInterface;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CardData;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentData;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentGatewayInterface;

final class ChangeSubscriptionPlanUseCase
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PlanRepositoryInterface $planRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly NotificationServiceInterface $notificationService,
        private readonly PlanChangeEvaluator $planChangeEvaluator,
        private readonly ProrationCalculator $prorationCalculator,
        private readonly CardStorageService $cardStorageService
    ) {}

    public function execute(ChangePlanDTO $dto): ChangeSubscriptionPlanResult
    {
        $subscription = $this->subscriptionRepository->findByUserId($dto->userId);
        if ($subscription === null) {
            throw SubscriptionNotFoundException::forUser($dto->userId);
        }

        if (!$subscription->canChangePlan()) {
            throw InvalidPlanChangeException::tooSoon(15);
        }

        $currentPlan = $this->planRepository->findById($subscription->planId());
        $newPlan = $this->planRepository->findById($dto->newPlanId);

        if ($newPlan === null) {
            throw PlanNotFoundException::withId($dto->newPlanId);
        }

        if ($dto->hasNewCard()) {
            $this->updateSubscriptionCard($subscription, $dto);
        }

        $changeType = $this->planChangeEvaluator->evaluateChangeType($currentPlan, $newPlan);

        if ($changeType === PlanChangeType::SCHEDULED) {
            return $this->handleScheduledChange($subscription, $newPlan);
        }

        return $this->handleImmediateChange($subscription, $currentPlan, $newPlan);
    }

    private function handleScheduledChange($subscription, $newPlan): ChangeSubscriptionPlanResult
    {
        $subscription->schedulePlanChange($newPlan->id());
        $this->subscriptionRepository->save($subscription);

        return ChangeSubscriptionPlanResult::scheduled(
            "Plan change scheduled for next billing cycle"
        );
    }

    private function handleImmediateChange($subscription, $currentPlan, $newPlan): ChangeSubscriptionPlanResult
    {
        $proratedAmount = $this->prorationCalculator->calculateProrationForPlanChange(
            $subscription,
            $currentPlan,
            $newPlan,
            new DateTimeImmutable()
        );

        $invoice = Invoice::create(
            id: $this->invoiceRepository->nextIdentity(),
            userId: $subscription->userId(),
            planId: $newPlan->id(),
            amount: $proratedAmount
        );
        $this->invoiceRepository->save($invoice);

        try {
            $paymentData = new PaymentData(
                customerId: $subscription->customerId(),
                cardToken: $subscription->cardId(),
                amountInCents: $proratedAmount->amountInCents(),
                description: "Upgrade para {$newPlan->name()}",
                externalReference: $invoice->id(),
                isRecurring: true,
                subscriptionId: $subscription->id(),
                sequenceNumber: $subscription->retryCount() + 1,
                firstPaymentId: $subscription->firstPaymentId()
            );

            $paymentResult = $this->paymentGateway->processPayment($paymentData);

            if ($paymentResult->success) {
                $now = new DateTimeImmutable();

                $invoice->markAsApproved($paymentResult->transactionId);
                $this->invoiceRepository->save($invoice);

                $subscription->applyImmediatePlanChange($newPlan, $proratedAmount);
                $subscription->recordSuccessfulPayment($paymentResult->transactionId, $now);
                $this->subscriptionRepository->save($subscription);

                $this->notificationService->sendPlanChangeNotification([
                    'user_id' => $subscription->userId(),
                    'old_plan_name' => $currentPlan->name(),
                    'new_plan_name' => $newPlan->name(),
                    'amount' => $proratedAmount->formatted(),
                    'invoice_id' => $invoice->id()
                ]);

                return ChangeSubscriptionPlanResult::immediate();
            }

            $invoice->markAsFailed($paymentResult->errorMessage ?? 'Payment failed', $paymentResult->transactionId);
            $this->invoiceRepository->save($invoice);

            $this->notificationService->sendFailureNotification([
                'user_id' => $subscription->userId(),
                'action' => 'upgrade de plano',
                'reason' => $paymentResult->errorMessage,
                'invoice_id' => $invoice->id()
            ]);

            return ChangeSubscriptionPlanResult::failure($paymentResult->errorMessage ?? 'Payment failed');

        } catch (\Exception $e) {
            $invoice->markAsFailed($e->getMessage());
            $this->invoiceRepository->save($invoice);

            return ChangeSubscriptionPlanResult::failure($e->getMessage());
        }
    }

    private function updateSubscriptionCard($subscription, ChangePlanDTO $dto): void
    {
        $cardData = new CardData(
            token: $dto->cardToken,
            cardNumber: $dto->cardNumber,
            cardholderName: $dto->cardholderName,
            expirationMonth: $dto->expiryMonth,
            expirationYear: $dto->expiryYear,
            securityCode: $dto->cvv
        );

        $cardResult = $this->paymentGateway->createCard($subscription->customerId(), $cardData);

        $storedCardId = $this->cardStorageService->store(
            userId: $subscription->userId(),
            cardResult: $cardResult,
            customerId: $subscription->customerId()
        );

        $subscription->changeCard($storedCardId);
        $this->subscriptionRepository->save($subscription);
    }
}