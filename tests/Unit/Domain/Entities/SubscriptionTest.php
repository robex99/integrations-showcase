<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use PaymentIntegrations\Domain\Shared\ValueObjects\Money;
use PaymentIntegrations\Domain\Subscription\Entities\Plan;
use PaymentIntegrations\Domain\Subscription\Entities\Subscription;
use PaymentIntegrations\Domain\Subscription\ValueObjects\BillingPeriod;
use PaymentIntegrations\Domain\Subscription\ValueObjects\PaymentStatus;
use PaymentIntegrations\Domain\Subscription\ValueObjects\SubscriptionStatus;
use PHPUnit\Framework\TestCase;

final class SubscriptionTest extends TestCase
{
    private Plan $plan;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2025-01-01');
        $this->plan = Plan::create(
            id: '1',
            name: 'Basic Plan',
            price: Money::fromCents(10000),
            ordersLimit: 100,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(50)
        );
    }

    public function test_creates_new_subscription(): void
    {
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $this->plan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $this->now
        );

        $this->assertEquals('sub_123', $subscription->id());
        $this->assertEquals(1, $subscription->userId());
        $this->assertEquals('1', $subscription->planId());
        $this->assertEquals('card_456', $subscription->cardId());
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status());
        $this->assertEquals(PaymentStatus::PAID, $subscription->paymentStatus());
        $this->assertTrue($subscription->isActive());
    }

    public function test_records_successful_payment(): void
    {
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $this->plan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $this->now
        );

        $paymentDate = $this->now->modify('+1 day');
        $subscription->recordSuccessfulPayment('pay_123', $paymentDate);

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status());
        $this->assertEquals(PaymentStatus::PAID, $subscription->paymentStatus());
        $this->assertEquals($paymentDate, $subscription->lastChargeAt());
        $this->assertEquals(0, $subscription->retryCount());
    }

    public function test_records_failed_payment(): void
    {
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $this->plan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $this->now
        );

        $failureDate = $this->now->modify('+1 day');
        $subscription->recordFailedPayment($failureDate);

        $this->assertEquals(SubscriptionStatus::PAST_DUE, $subscription->status());
        $this->assertEquals(PaymentStatus::FAILED, $subscription->paymentStatus());
        $this->assertEquals(1, $subscription->retryCount());
    }

    public function test_detects_max_retries_reached(): void
    {
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $this->plan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $this->now
        );

        $subscription->recordFailedPayment($this->now);
        $subscription->recordFailedPayment($this->now);
        $this->assertFalse($subscription->hasReachedMaxRetries());

        $subscription->recordFailedPayment($this->now);
        $this->assertTrue($subscription->hasReachedMaxRetries());
    }

    public function test_schedules_plan_change(): void
    {
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $this->plan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $this->now
        );

        $subscription->schedulePlanChange('plan_2');

        $this->assertTrue($subscription->hasPendingPlanChange());
        $this->assertEquals('plan_2', $subscription->newPlanId());
    }

    public function test_cannot_change_plan_before_15_days(): void
    {
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $this->plan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $this->now
        );

        $subscription->schedulePlanChange('plan_2');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot change plan before 15 days since last change');

        $subscription->schedulePlanChange('plan_3');
    }

    public function test_applies_immediate_plan_change(): void
    {
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $this->plan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $this->now
        );

        $newPlan = Plan::create(
            id: '2',
            name: 'Pro Plan',
            price: Money::fromCents(20000),
            ordersLimit: 500,
            billingPeriod: BillingPeriod::MONTHLY,
            extraOrderCharge: Money::fromCents(40)
        );

        $subscription->applyImmediatePlanChange($newPlan, Money::fromCents(5000));

        $this->assertEquals('2', $subscription->planId());
        $this->assertEquals(20000, $subscription->planPrice()->amountInCents());
        $this->assertNull($subscription->newPlanId());
    }

    public function test_changes_credit_card(): void
    {
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $this->plan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $this->now
        );

        $subscription->changeCard('card_new');

        $this->assertEquals('card_new', $subscription->cardId());
    }

    public function test_cancels_subscription(): void
    {
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $this->plan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $this->now
        );

        $subscription->cancel('User requested');

        $this->assertEquals(SubscriptionStatus::CANCELLED, $subscription->status());
        $this->assertEquals(PaymentStatus::ENDED, $subscription->paymentStatus());
        $this->assertEquals('User requested', $subscription->cancelReason());
    }

    public function test_ends_subscription(): void
    {
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $this->plan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $this->now
        );

        $subscription->end();

        $this->assertEquals(SubscriptionStatus::ENDED, $subscription->status());
        $this->assertEquals(PaymentStatus::ENDED, $subscription->paymentStatus());
    }

    public function test_renews_cycle(): void
    {
        $subscription = Subscription::create(
            id: 'sub_123',
            userId: 1,
            plan: $this->plan,
            cardId: 'card_456',
            customerId: 'cus_789',
            startDate: $this->now
        );

        $oldEndDate = $subscription->currentCycle()->endDate();
        $renewalDate = $this->now->modify('+1 month');
        
        $subscription->renewCycle($renewalDate);

        $this->assertEquals($oldEndDate, $subscription->currentCycle()->startDate());
    }
}