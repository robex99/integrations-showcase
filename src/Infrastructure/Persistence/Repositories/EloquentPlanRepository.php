<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Persistence\Repositories;

use PaymentIntegrations\Domain\Shared\ValueObjects\Money;
use PaymentIntegrations\Domain\Subscription\Entities\Plan;
use PaymentIntegrations\Domain\Subscription\Repositories\PlanRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\ValueObjects\BillingPeriod;
use PaymentIntegrations\Infrastructure\Persistence\Eloquent\Models\EloquentPlan;

final class EloquentPlanRepository implements PlanRepositoryInterface
{
    private const EXTRA_ORDER_RATES = [
        1 => 125,
        2 => 109,
        3 => 60,
        4 => 53,
        5 => 42,
    ];

    public function findById(string $id): ?Plan
    {
        $eloquent = EloquentPlan::find($id);

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomain($eloquent);
    }

    public function findAllActive(): array
    {
        $eloquentPlans = EloquentPlan::where('status', 'active')->get();

        return array_map(
            fn ($eloquent) => $this->toDomain($eloquent),
            $eloquentPlans->all()
        );
    }

    private function toDomain(EloquentPlan $eloquent): Plan
    {
        $extraOrderRate = self::EXTRA_ORDER_RATES[$eloquent->id] ?? 0;

        return Plan::reconstitute(
            id: (string) $eloquent->id,
            name: $eloquent->name,
            price: Money::fromCents($eloquent->price),
            ordersLimit: $eloquent->orders,
            billingPeriod: BillingPeriod::from($eloquent->billing),
            extraOrderCharge: Money::fromCents($extraOrderRate),
            isActive: $eloquent->status === 'active'
        );
    }
}
