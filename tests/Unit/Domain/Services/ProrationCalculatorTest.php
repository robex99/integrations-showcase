<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Services;

use DateTimeImmutable;
use PaymentIntegrations\Domain\Shared\ValueObjects\Money;
use PaymentIntegrations\Domain\Subscription\Entities\Plan;
use PaymentIntegrations\Domain\Subscription\Entities\Subscription;
use PaymentIntegrations\Domain\Subscription\Services\ProrationCalculator;
use PaymentIntegrations\Domain\Subscription\ValueObjects\BillingPeriod;
use PHPUnit\Framework\TestCase;

final class ProrationCalculatorTest extends TestCase
{
    private ProrationCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ProrationCalculator();
    }

    public function test_calculates_proration_at_mid_cycle(): void
    {
        $currentPlan = Plan::create(
            id: '1',
            name: 'Basic',
            price: Money::fromCents(10000),
            ordersLimit: 100,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(50)
        );

        $newPlan = Plan::create(
            id: '2',
            name: 'Pro',
            price: Money::fromCents(20000),
            ordersLimit: 500,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(40)
        );

        $startDate = new DateTimeImmutable('2025-01-01');
        
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $currentPlan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $startDate
        );

        $changeDate = new DateTimeImmutable('2025-01-16');

        $prorated = $this->calculator->calculateProrationForPlanChange(
            $subscription,
            $currentPlan,
            $newPlan,
            $changeDate
        );

        $this->assertGreaterThan(0, $prorated->amountInCents());
        $this->assertLessThan(20000, $prorated->amountInCents());
    }

    public function test_proration_is_zero_when_downgrading(): void
    {
        $currentPlan = Plan::create(
            id: '2',
            name: 'Pro',
            price: Money::fromCents(20000),
            ordersLimit: 500,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(40)
        );

        $newPlan = Plan::create(
            id: '1',
            name: 'Basic',
            price: Money::fromCents(10000),
            ordersLimit: 100,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(50)
        );

        $startDate = new DateTimeImmutable('2025-01-01');
        
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $currentPlan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $startDate
        );

        $changeDate = new DateTimeImmutable('2025-01-16');

        $prorated = $this->calculator->calculateProrationForPlanChange(
            $subscription,
            $currentPlan,
            $newPlan,
            $changeDate
        );

        $this->assertEquals(0, $prorated->amountInCents());
    }
}