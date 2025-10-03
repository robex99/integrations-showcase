<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use PaymentIntegrations\Domain\Shared\ValueObjects\Money;
use PaymentIntegrations\Domain\Subscription\Entities\Plan;
use PaymentIntegrations\Domain\Subscription\ValueObjects\BillingPeriod;
use PHPUnit\Framework\TestCase;

final class PlanTest extends TestCase
{
    public function test_creates_plan(): void
    {
        $plan = Plan::create(
            id: '1',
            name: 'Basic Plan',
            price: Money::fromCents(10000),
            ordersLimit: 100,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(50)
        );

        $this->assertEquals('1', $plan->id());
        $this->assertEquals('Basic Plan', $plan->name());
        $this->assertEquals(10000, $plan->price()->amountInCents());
        $this->assertEquals(100, $plan->ordersLimit());
        $this->assertTrue($plan->isActive());
    }

    public function test_calculates_total_without_extra_orders(): void
    {
        $plan = Plan::create(
            id: '1',
            name: 'Basic Plan',
            price: Money::fromCents(10000),
            ordersLimit: 100,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(50)
        );

        $total = $plan->calculateTotalAmount(50);

        $this->assertEquals(10000, $total->amountInCents());
    }

    public function test_calculates_total_with_extra_orders(): void
    {
        $plan = Plan::create(
            id: '1',
            name: 'Basic Plan',
            price: Money::fromCents(10000),
            ordersLimit: 100,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(50)
        );

        $total = $plan->calculateTotalAmount(120);

        // Base: 10000 + (20 extra orders * 50) = 11000
        $this->assertEquals(11000, $total->amountInCents());
    }

    public function test_detects_extra_orders(): void
    {
        $plan = Plan::create(
            id: '1',
            name: 'Basic Plan',
            price: Money::fromCents(10000),
            ordersLimit: 100,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(50)
        );

        $this->assertFalse($plan->hasExtraOrders(50));
        $this->assertFalse($plan->hasExtraOrders(100));
        $this->assertTrue($plan->hasExtraOrders(101));
        $this->assertTrue($plan->hasExtraOrders(200));
    }
}