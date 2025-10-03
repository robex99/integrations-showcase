<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\UseCases\Subscription\RenewSubscription;

use DateTimeImmutable;
use PaymentIntegrations\Domain\Subscription\Entities\Invoice;
use PaymentIntegrations\Domain\Subscription\Entities\Plan;
use PaymentIntegrations\Domain\Subscription\Entities\Subscription;
use PaymentIntegrations\Domain\Subscription\Exceptions\PlanNotFoundException;
use PaymentIntegrations\Domain\Subscription\Exceptions\SubscriptionNotFoundException;
use PaymentIntegrations\Domain\Subscription\Repositories\InvoiceRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\Repositories\PlanRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\Repositories\SubscriptionRepositoryInterface;
use PaymentIntegrations\Infrastructure\Invoicing\Contracts\FiscalDocumentData;
use PaymentIntegrations\Infrastructure\Invoicing\Spedy\SpedyFiscalDocumentService;
use PaymentIntegrations\Infrastructure\Notifications\Contracts\NotificationServiceInterface;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentData;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentGatewayInterface;

final class RenewSubscriptionUseCase
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PlanRepositoryInterface $planRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly NotificationServiceInterface $notificationService,
        private readonly UsageBasedChargeCalculator $chargeCalculator
    ) {}

    public function execute(string $subscriptionId): RenewSubscriptionResult
    {
        $subscription = $this->subscriptionRepository->findById($subscriptionId);
        if ($subscription === null) {
            throw SubscriptionNotFoundException::withId($subscriptionId);
        }

        $planId = $subscription->hasPendingPlanChange()
            ? $subscription->newPlanId()
            : $subscription->planId();

        $plan = $this->planRepository->findById($planId);
        if ($plan === null) {
            throw PlanNotFoundException::withId($planId);
        }

        $ordersCount = $this->chargeCalculator->getOrdersCount(
            $subscription->userId(),
            $subscription->currentCycle()->startDate(),
            $subscription->currentCycle()->endDate()
        );

        $totalAmount = $plan->calculateTotalAmount($ordersCount);

        $invoice = Invoice::create(
            id: $this->invoiceRepository->nextIdentity(),
            userId: $subscription->userId(),
            planId: $plan->id(),
            amount: $totalAmount
        );
        $invoice->setOrdersCount($ordersCount);
        $this->invoiceRepository->save($invoice);

        try {
            $paymentData = new PaymentData(
                customerId: $subscription->customerId(),
                cardToken: $subscription->cardId(),
                amountInCents: $totalAmount->amountInCents(),
                description: "Renovação Assinatura {$plan->name()}",
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

                $subscription->recordSuccessfulPayment($paymentResult->transactionId, $now);
                $subscription->renewCycle($now);
                $this->subscriptionRepository->save($subscription);

                $this->issueFiscalDocument($subscription, $plan, $invoice, $paymentResult->transactionId);

                $this->notificationService->sendRenewalNotification([
                    'user_id' => $subscription->userId(),
                    'plan_name' => $plan->name(),
                    'amount' => $totalAmount->formatted(),
                    'invoice_id' => $invoice->id(),
                    'orders_count' => $ordersCount
                ]);

                return RenewSubscriptionResult::success();
            }

            $invoice->markAsFailed($paymentResult->errorMessage ?? 'Payment failed', $paymentResult->transactionId);
            $this->invoiceRepository->save($invoice);

            $subscription->recordFailedPayment(new DateTimeImmutable());
            $this->subscriptionRepository->save($subscription);

            $this->notificationService->sendFailureNotification([
                'user_id' => $subscription->userId(),
                'action' => 'renovação',
                'reason' => $paymentResult->errorMessage,
                'invoice_id' => $invoice->id()
            ]);

            return RenewSubscriptionResult::failure($paymentResult->errorMessage ?? 'Payment failed');

        } catch (\Exception $e) {
            $invoice->markAsFailed($e->getMessage());
            $this->invoiceRepository->save($invoice);

            $subscription->recordFailedPayment(new DateTimeImmutable());
            $this->subscriptionRepository->save($subscription);

            return RenewSubscriptionResult::failure($e->getMessage());
        }
    }

    private function issueFiscalDocument(
        Subscription $subscription,
        Plan $plan,
        Invoice $invoice,
        string $transactionId
    ): void {
        try {
            $fiscalData = new FiscalDocumentData(
                transactionId: $transactionId,
                customerName: 'Customer Name',
                customerEmail: 'customer@email.com',
                customerDocument: '62887357018',
                customerStreet: null,
                customerDistrict: null,
                customerPostalCode: null,
                customerNumber: null,
                customerCity: null,
                customerState: 'SP',
                amountInCents: $invoice->amount()->amountInCents(),
                itemDescription: "Renovação Assinatura - {$plan->name()}",
                itemCode: "PLAN-{$plan->id()}",
                sendEmailToCustomer: true
            );

            $spedyService = new SpedyFiscalDocumentService(
                apiKey: '14c37a1a-d12f-4b7e-ffff-fffffffff'
            );

            $result = $spedyService->issueDocument($fiscalData);

            if (!$result->success) {
                error_log("SPEDY_ERROR: Failed to issue fiscal document for invoice {$invoice->id()}: {$result->errorMessage}");
            } else {
                error_log("SPEDY_SUCCESS: Fiscal document issued for invoice {$invoice->id()}, document ID: {$result->documentId}");
            }
        } catch (\Exception $e) {
            error_log("SPEDY_EXCEPTION: Error issuing fiscal document for invoice {$invoice->id()}: {$e->getMessage()}");
        }
    }
}