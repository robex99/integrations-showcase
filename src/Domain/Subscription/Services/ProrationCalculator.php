<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Services;

use DateTimeImmutable;
use PaymentIntegrations\Domain\Shared\ValueObjects\Money;
use PaymentIntegrations\Domain\Subscription\Entities\Plan;
use PaymentIntegrations\Domain\Subscription\Entities\Subscription;

final class ProrationCalculator
{
    public function calculateProrationForPlanChange(
        Subscription $subscription,
        Plan $currentPlan,
        Plan $newPlan,
        DateTimeImmutable $changeDate
    ): Money {
        $cycle = $subscription->currentCycle();
        $totalDays = $cycle->totalDays();
        $remainingDays = $cycle->remainingDays($changeDate);
        $usedDays = $cycle->usedDays($changeDate);

        if ($totalDays === 0 || $remainingDays === 0) {
            return $newPlan->price();
        }

        $currentDailyRate = $this->calculateDailyRate($currentPlan->price(), $totalDays);
        $newDailyRate = $this->calculateDailyRate($newPlan->price(), $totalDays);

        $usedAmount = $currentDailyRate->multiply($usedDays);
        $remainingCredit = $currentPlan->price()->subtract($usedAmount);

        $newPlanProportionalCost = $newDailyRate->multiply($remainingDays);

        return $newPlanProportionalCost->subtract($remainingCredit);
    }

    private function calculateDailyRate(Money $totalAmount, int $days): Money
    {
        if ($days === 0) {
            return $totalAmount;
        }

        return Money::fromCents(
            (int) round($totalAmount->amountInCents() / $days)
        );
    }
}
