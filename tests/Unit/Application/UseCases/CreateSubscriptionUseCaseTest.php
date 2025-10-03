<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use DateTimeImmutable;
use PaymentIntegrations\Application\DTOs\CreateSubscriptionDTO;
use PaymentIntegrations\Application\UseCases\Subscription\CreateSubscription\CardStorageService;
use PaymentIntegrations\Application\UseCases\Subscription\CreateSubscription\CreateSubscriptionUseCase;
use PaymentIntegrations\Domain\Shared\ValueObjects\Money;
use PaymentIntegrations\Domain\Subscription\Entities\Plan;
use PaymentIntegrations\Domain\Subscription\Repositories\InvoiceRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\Repositories\PlanRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\Repositories\SubscriptionRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\ValueObjects\BillingPeriod;
use PaymentIntegrations\Infrastructure\Notifications\Contracts\NotificationServiceInterface;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CardResult;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CustomerResult;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentGatewayInterface;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentResult;
use PHPUnit\Framework\TestCase;

final class CreateSubscriptionUseCaseTest extends TestCase
{
    private PlanRepositoryInterface $planRepository;
    private SubscriptionRepositoryInterface $subscriptionRepository;
    private InvoiceRepositoryInterface $invoiceRepository;
    private PaymentGatewayInterface $paymentGateway;
    private NotificationServiceInterface $notificationService;
    private CardStorageService $cardStorage;
    private CreateSubscriptionUseCase $useCase;

    protected function setUp(): void
    {
        $this->planRepository = $this->createMock(PlanRepositoryInterface::class);
        $this->subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->notificationService = $this->createMock(NotificationServiceInterface::class);
        $this->cardStorage = $this->createMock(CardStorageService::class);

        $this->useCase = new CreateSubscriptionUseCase(
            $this->planRepository,
            $this->subscriptionRepository,
            $this->invoiceRepository,
            $this->paymentGateway,
            $this->notificationService,
            $this->cardStorage
        );
    }

    public function test_creates_subscription_successfully(): void
    {
        $plan = Plan::create(
            id: '1',
            name: 'Basic Plan',
            price: Money::fromCents(10000),
            ordersLimit: 100,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(50)
        );

        $dto = new CreateSubscriptionDTO(
            userId: 1,
            planId: '1',
            cardToken: 'tok_123',
            cardNumber: '4111111111111111',
            cardholderName: 'John Doe',
            cpfCnpj: '62887357018',
            expiryMonth: 12,
            expiryYear: 2025,
            cvv: '123'
        );

        $this->planRepository
            ->expects($this->once())
            ->method('findById')
            ->with('1')
            ->willReturn($plan);

        $this->invoiceRepository
            ->expects($this->once())
            ->method('nextIdentity')
            ->willReturn('inv_123');

        $this->paymentGateway
            ->expects($this->once())
            ->method('createCustomer')
            ->willReturn(new CustomerResult('cus_123', 'user@example.com'));

        $this->paymentGateway
            ->expects($this->once())
            ->method('createCard')
            ->willReturn(new CardResult(
                id: 'card_123',
                brand: 'Visa',
                lastFourDigits: '1111',
                firstSixDigits: '411111',
                expirationMonth: 12,
                expirationYear: 2025
            ));

        $this->cardStorage
            ->expects($this->once())
            ->method('store')
            ->willReturn('stored_card_123');

        $this->paymentGateway
            ->expects($this->once())
            ->method('processPayment')
            ->willReturn(PaymentResult::success('pay_123'));

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('save');

        $this->notificationService
            ->expects($this->once())
            ->method('sendNewSubscriptionNotification');

        $result = $this->useCase->execute($dto);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->subscriptionId);
    }

    public function test_handles_payment_failure(): void
    {
        $plan = Plan::create(
            id: '1',
            name: 'Basic Plan',
            price: Money::fromCents(10000),
            ordersLimit: 100,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(50)
        );

        $dto = new CreateSubscriptionDTO(
            userId: 1,
            planId: '1',
            cardToken: 'tok_123',
            cardNumber: '4111111111111111',
            cardholderName: 'John Doe',
            cpfCnpj: '62887357018',
            expiryMonth: 12,
            expiryYear: 2025,
            cvv: '123'
        );

        $this->planRepository
            ->method('findById')
            ->willReturn($plan);

        $this->invoiceRepository
            ->method('nextIdentity')
            ->willReturn('inv_123');

        $this->paymentGateway
            ->method('createCustomer')
            ->willReturn(new CustomerResult('cus_123', 'user@example.com'));

        $this->paymentGateway
            ->method('createCard')
            ->willReturn(new CardResult(
                id: 'card_123',
                brand: 'Visa',
                lastFourDigits: '1111',
                firstSixDigits: '411111',
                expirationMonth: 12,
                expirationYear: 2025
            ));

        $this->cardStorage
            ->method('store')
            ->willReturn('stored_card_123');

        $this->paymentGateway
            ->method('processPayment')
            ->willReturn(PaymentResult::failure('rejected', 'cc_rejected_insufficient_amount'));

        $this->notificationService
            ->expects($this->once())
            ->method('sendFailureNotification');

        $result = $this->useCase->execute($dto);

        $this->assertFalse($result->success);
        $this->assertNotNull($result->errorMessage);
    }
}