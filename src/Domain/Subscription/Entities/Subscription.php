<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use PaymentIntegrations\Domain\Shared\ValueObjects\Money;
use PaymentIntegrations\Domain\Subscription\ValueObjects\BillingCycle;
use PaymentIntegrations\Domain\Subscription\ValueObjects\PaymentStatus;
use PaymentIntegrations\Domain\Subscription\ValueObjects\SubscriptionStatus;

final class Subscription
{
    private const MAX_RETRY_ATTEMPTS = 3;
    private const MIN_DAYS_BETWEEN_PLAN_CHANGES = 15;

    private function __construct(
        private readonly string $id,
        private int $userId,
        private string $planId,
        private Money $planPrice,
        private ?string $cardId,
        private SubscriptionStatus $status,
        private PaymentStatus $paymentStatus,
        private BillingCycle $currentCycle,
        private DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $lastChargeAt,
        private ?DateTimeImmutable $lastPlanChangeAt,
        private int $retryCount,
        private ?string $customerId,
        private ?string $firstPaymentId,
        private ?string $newPlanId,
        private ?string $cancelReason,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {
    }

    public static function create(
        string $id,
        int $userId,
        Plan $plan,
        string $cardId,
        string $customerId,
        DateTimeImmutable $startDate
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            planId: $plan->id(),
            planPrice: $plan->price(),
            cardId: $cardId,
            status: SubscriptionStatus::ACTIVE,
            paymentStatus: PaymentStatus::PAID,
            currentCycle: BillingCycle::create($plan->billingPeriod(), $startDate),
            startedAt: $startDate,
            lastChargeAt: $startDate,
            lastPlanChangeAt: null,
            retryCount: 0,
            customerId: $customerId,
            firstPaymentId: null,
            newPlanId: null,
            cancelReason: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );
    }

    public static function reconstitute(
        string $id,
        int $userId,
        string $planId,
        Money $planPrice,
        ?string $cardId,
        SubscriptionStatus $status,
        PaymentStatus $paymentStatus,
        BillingCycle $currentCycle,
        DateTimeImmutable $startedAt,
        ?DateTimeImmutable $lastChargeAt,
        ?DateTimeImmutable $lastPlanChangeAt,
        int $retryCount,
        ?string $customerId,
        ?string $firstPaymentId,
        ?string $newPlanId,
        ?string $cancelReason,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            $id,
            $userId,
            $planId,
            $planPrice,
            $cardId,
            $status,
            $paymentStatus,
            $currentCycle,
            $startedAt,
            $lastChargeAt,
            $lastPlanChangeAt,
            $retryCount,
            $customerId,
            $firstPaymentId,
            $newPlanId,
            $cancelReason,
            $createdAt,
            $updatedAt
        );
    }

    public function recordSuccessfulPayment(string $paymentId, DateTimeImmutable $paidAt): void
    {
        if ($this->firstPaymentId === null) {
            $this->firstPaymentId = $paymentId;
        }

        $this->status = SubscriptionStatus::ACTIVE;
        $this->paymentStatus = PaymentStatus::PAID;
        $this->lastChargeAt = $paidAt;
        $this->retryCount = 0;

        if ($this->newPlanId !== null) {
            $this->planId = $this->newPlanId;
            $this->newPlanId = null;
            $this->lastPlanChangeAt = $paidAt;
        }

        $this->updatedAt = new DateTimeImmutable();
    }

    public function recordFailedPayment(DateTimeImmutable $failedAt): void
    {
        $this->paymentStatus = PaymentStatus::FAILED;
        $this->status = SubscriptionStatus::PAST_DUE;
        $this->lastChargeAt = $failedAt;
        $this->retryCount++;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function renewCycle(DateTimeImmutable $renewalDate): void
    {
        $this->currentCycle = $this->currentCycle->nextCycle();
        $this->updatedAt = $renewalDate;
    }

    public function schedulePlanChange(string $newPlanId): void
    {
        if (!$this->canChangePlan()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot change plan before %d days since last change',
                    self::MIN_DAYS_BETWEEN_PLAN_CHANGES
                )
            );
        }

        $this->newPlanId = $newPlanId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function applyImmediatePlanChange(Plan $newPlan, Money $proratedAmount): void
    {
        if (!$this->canChangePlan()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot change plan before %d days since last change',
                    self::MIN_DAYS_BETWEEN_PLAN_CHANGES
                )
            );
        }

        $this->planId = $newPlan->id();
        $this->planPrice = $newPlan->price();
        $this->lastPlanChangeAt = new DateTimeImmutable();
        $this->newPlanId = null;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function changeCard(string $newCardId): void
    {
        $this->cardId = $newCardId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function cancel(string $reason): void
    {
        if (!$this->status->canBeCancelled()) {
            throw new InvalidArgumentException('Subscription cannot be cancelled in current status');
        }

        $this->status = SubscriptionStatus::CANCELLED;
        $this->paymentStatus = PaymentStatus::ENDED;
        $this->cancelReason = $reason;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function end(): void
    {
        $this->status = SubscriptionStatus::ENDED;
        $this->paymentStatus = PaymentStatus::ENDED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function canChangePlan(): bool
    {
        if ($this->lastPlanChangeAt === null) {
            return true;
        }

        $daysSinceLastChange = $this->lastPlanChangeAt->diff(new DateTimeImmutable())->days;
        return $daysSinceLastChange >= self::MIN_DAYS_BETWEEN_PLAN_CHANGES;
    }

    public function hasReachedMaxRetries(): bool
    {
        return $this->retryCount >= self::MAX_RETRY_ATTEMPTS;
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE;
    }

    public function needsRenewal(DateTimeImmutable $currentDate): bool
    {
        return !$this->currentCycle->isActive($currentDate)
            && $this->status->canBeCharged();
    }

    public function hasPendingPlanChange(): bool
    {
        return $this->newPlanId !== null;
    }

    // Getters
    public function id(): string
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function planId(): string
    {
        return $this->planId;
    }

    public function planPrice(): Money
    {
        return $this->planPrice;
    }

    public function cardId(): ?string
    {
        return $this->cardId;
    }

    public function status(): SubscriptionStatus
    {
        return $this->status;
    }

    public function paymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function currentCycle(): BillingCycle
    {
        return $this->currentCycle;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function lastChargeAt(): ?DateTimeImmutable
    {
        return $this->lastChargeAt;
    }

    public function lastPlanChangeAt(): ?DateTimeImmutable
    {
        return $this->lastPlanChangeAt;
    }

    public function retryCount(): int
    {
        return $this->retryCount;
    }

    public function customerId(): ?string
    {
        return $this->customerId;
    }

    public function firstPaymentId(): ?string
    {
        return $this->firstPaymentId;
    }

    public function newPlanId(): ?string
    {
        return $this->newPlanId;
    }

    public function cancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
