<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\UseCases\Subscription\CreateSubscription;

use DateTimeImmutable;
use PaymentIntegrations\Application\DTOs\CreateSubscriptionDTO;
use PaymentIntegrations\Domain\Shared\ValueObjects\Document;
use PaymentIntegrations\Domain\Shared\ValueObjects\Email;
use PaymentIntegrations\Domain\Subscription\Entities\Invoice;
use PaymentIntegrations\Domain\Subscription\Entities\Plan;       
use PaymentIntegrations\Domain\Subscription\Entities\Subscription;
use PaymentIntegrations\Domain\Subscription\Exceptions\PlanNotFoundException;
use PaymentIntegrations\Domain\Subscription\Repositories\InvoiceRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\Repositories\PlanRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\Repositories\SubscriptionRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\ValueObjects\CreditCard;
use PaymentIntegrations\Infrastructure\Invoicing\Contracts\FiscalDocumentData;    
use PaymentIntegrations\Infrastructure\Invoicing\Spedy\SpedyFiscalDocumentService;
use PaymentIntegrations\Infrastructure\Notifications\Contracts\NotificationServiceInterface;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CardData;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CustomerData;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentData;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentGatewayInterface;

final class CreateSubscriptionUseCase
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly NotificationServiceInterface $notificationService,
        private readonly CardStorageService $cardStorageService
    ) {
    }

    public function execute(CreateSubscriptionDTO $dto): CreateSubscriptionResult
    {
        $plan = $this->planRepository->findById($dto->planId);
        if ($plan === null) {
            throw PlanNotFoundException::withId($dto->planId);
        }

        $invoice = Invoice::create(
            id: $this->invoiceRepository->nextIdentity(),
            userId: $dto->userId,
            planId: $plan->id(),
            amount: $plan->price()
        );

        $this->invoiceRepository->save($invoice);

        try {
            $document = Document::fromString($dto->cpfCnpj);

            $customerData = new CustomerData(
                email: "user_{$dto->userId}@testuser.com",
                firstName: $this->extractFirstName($dto->cardholderName),
                lastName: $this->extractLastName($dto->cardholderName),
                documentType: $document->type()->value,
                documentNumber: $document->number(),
                phoneAreaCode: '55',
                phoneNumber: '11999999999'
            );

            $customerResult = $this->paymentGateway->createCustomer($customerData);

            $cardData = new CardData(
                token: $dto->cardToken,
                cardNumber: $dto->cardNumber,
                cardholderName: $dto->cardholderName,
                expirationMonth: $dto->expiryMonth,
                expirationYear: $dto->expiryYear,
                securityCode: $dto->cvv
            );

            $cardResult = $this->paymentGateway->createCard($customerResult->customerId, $cardData);

            $storedCardId = $this->cardStorageService->store(
                userId: $dto->userId,
                cardResult: $cardResult,
                customerId: $customerResult->customerId
            );

            $invoice->attachCardInfo(
                $storedCardId,
                $cardResult->lastFourDigits,
                $cardResult->brand
            );
            $this->invoiceRepository->save($invoice);

            $paymentData = new PaymentData(
                customerId: $customerResult->customerId,
                cardToken: $cardResult->id,
                amountInCents: $plan->price()->amountInCents(),
                description: "Assinatura {$plan->name()}",
                externalReference: $invoice->id(),
                isRecurring: true,
                subscriptionId: null
            );

            $paymentResult = $this->paymentGateway->processPayment($paymentData);

            if ($paymentResult->success) {
                $invoice->markAsApproved($paymentResult->transactionId);
                $this->invoiceRepository->save($invoice);

                $subscription = Subscription::create(
                    id: uniqid('sub_', true),
                    userId: $dto->userId,
                    plan: $plan,
                    cardId: $storedCardId,
                    customerId: $customerResult->customerId,
                    startDate: new DateTimeImmutable()
                );

                $subscription->recordSuccessfulPayment($paymentResult->transactionId, new DateTimeImmutable());
                $this->subscriptionRepository->save($subscription);

                $this->issueFiscalDocument($subscription, $plan, $invoice, $paymentResult->transactionId);

                $this->notificationService->sendNewSubscriptionNotification([
                    'user_id' => $dto->userId,
                    'plan_name' => $plan->name(),
                    'amount' => $plan->price()->formatted(),
                    'invoice_id' => $invoice->id()
                ]);

                return CreateSubscriptionResult::success($subscription->id());
            }

            $invoice->markAsFailed($paymentResult->errorMessage ?? 'Payment failed', $paymentResult->transactionId);
            $this->invoiceRepository->save($invoice);

            $this->notificationService->sendFailureNotification([
                'user_id' => $dto->userId,
                'action' => 'nova assinatura',
                'reason' => $paymentResult->errorMessage,
                'invoice_id' => $invoice->id()
            ]);

            return CreateSubscriptionResult::failure($paymentResult->errorMessage ?? 'Payment failed');

        } catch (\Exception $e) {
            $invoice->markAsFailed($e->getMessage());
            $this->invoiceRepository->save($invoice);

            $this->notificationService->sendFailureNotification([
                'user_id' => $dto->userId,
                'action' => 'nova assinatura',
                'reason' => $e->getMessage(),
                'invoice_id' => $invoice->id()
            ]);

            return CreateSubscriptionResult::failure($e->getMessage());
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
                amountInCents: $plan->price()->amountInCents(),
                itemDescription: "Assinatura - {$plan->name()}",
                itemCode: "PLAN-{$plan->id()}",
                sendEmailToCustomer: true
            );

            $spedyService = new SpedyFiscalDocumentService(
                apiKey: '14c37a1a-d12f-4fff-ffff-cfffffffff'
            );

            $result = $spedyService->issueDocument($fiscalData);

            if (!$result->success) {
                error_log("Failed to issue fiscal document: {$result->errorMessage}");
            }
        } catch (\Exception $e) {
            error_log("Exception issuing fiscal document: {$e->getMessage()}");
        }
    }

    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', $fullName);
        return $parts[0] ?? '';
    }

    private function extractLastName(string $fullName): string
    {
        $parts = explode(' ', $fullName);
        array_shift($parts);
        return implode(' ', $parts);
    }
}
