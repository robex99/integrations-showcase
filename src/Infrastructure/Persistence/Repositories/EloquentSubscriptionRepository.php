<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use PaymentIntegrations\Domain\Shared\ValueObjects\Money;
use PaymentIntegrations\Domain\Subscription\Entities\Subscription;
use PaymentIntegrations\Domain\Subscription\Repositories\SubscriptionRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\ValueObjects\BillingCycle;
use PaymentIntegrations\Domain\Subscription\ValueObjects\BillingPeriod;
use PaymentIntegrations\Domain\Subscription\ValueObjects\PaymentStatus;
use PaymentIntegrations\Domain\Subscription\ValueObjects\SubscriptionStatus;
use PaymentIntegrations\Infrastructure\Persistence\Eloquent\Models\EloquentSubscription;

final class EloquentSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function findById(string $id): ?Subscription
    {
        $eloquent = EloquentSubscription::find($id);

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomain($eloquent);
    }

    public function findByUserId(int $userId): ?Subscription
    {
        $eloquent = EloquentSubscription::where('user_id', $userId)->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomain($eloquent);
    }

    public function save(Subscription $subscription): void
    {
        $data = [
            'user_id' => $subscription->userId(),
            'plan_id' => $subscription->planId(),
            'plan_price' => $subscription->planPrice()->amountInCents(),
            'card_id' => $subscription->cardId(),
            'status' => $subscription->status()->value,
            'pay_status' => $subscription->paymentStatus()->value,
            'type' => $subscription->currentCycle()->period()->value,
            'started_at' => $subscription->startedAt()->format('Y-m-d'),
            'last_due' => $subscription->currentCycle()->startDate()->format('Y-m-d'),
            'next_due' => $subscription->currentCycle()->endDate()->format('Y-m-d'),
            'last_charge' => $subscription->lastChargeAt()?->format('Y-m-d'),
            'last_change' => $subscription->lastPlanChangeAt()?->format('Y-m-d'),
            'customer_id' => $subscription->customerId(),
            'first_code' => $subscription->firstPaymentId(),
            'count_charge' => $subscription->retryCount(),
            'new_plan_id' => $subscription->newPlanId(),
            'cancel_reason' => $subscription->cancelReason(),
            'updated_at' => $subscription->updatedAt()->format('Y-m-d H:i:s'),
        ];

        EloquentSubscription::updateOrCreate(
            ['id' => $subscription->id()],
            $data
        );
    }

    public function findDueForRenewal(DateTimeImmutable $date): array
    {
        $eloquentSubscriptions = EloquentSubscription::where('next_due', '<=', $date->format('Y-m-d'))
            ->whereIn('status', ['active', 'past_due'])
            ->get();

        return array_map(
            fn ($eloquent) => $this->toDomain($eloquent),
            $eloquentSubscriptions->all()
        );
    }

    private function toDomain(EloquentSubscription $eloquent): Subscription
    {
        $billingPeriod = BillingPeriod::from($eloquent->type);
        $lastDue = new DateTimeImmutable($eloquent->last_due);
        $nextDue = new DateTimeImmutable($eloquent->next_due);

        return Subscription::reconstitute(
            id: (string) $eloquent->id,
            userId: $eloquent->user_id,
            planId: (string) $eloquent->plan_id,
            planPrice: Money::fromCents($eloquent->plan_price),
            cardId: $eloquent->card_id,
            status: SubscriptionStatus::from($eloquent->status),
            paymentStatus: PaymentStatus::from($eloquent->pay_status),
            currentCycle: new BillingCycle($billingPeriod, $lastDue, $nextDue),
            startedAt: new DateTimeImmutable($eloquent->started_at),
            lastChargeAt: $eloquent->last_charge ? new DateTimeImmutable($eloquent->last_charge) : null,
            lastPlanChangeAt: $eloquent->last_change ? new DateTimeImmutable($eloquent->last_change) : null,
            retryCount: $eloquent->count_charge ?? 0,
            customerId: $eloquent->customer_id,
            firstPaymentId: $eloquent->first_code,
            newPlanId: $eloquent->new_plan_id,
            cancelReason: $eloquent->cancel_reason,
            createdAt: new DateTimeImmutable($eloquent->created_at),
            updatedAt: new DateTimeImmutable($eloquent->updated_at)
        );
    }
}
