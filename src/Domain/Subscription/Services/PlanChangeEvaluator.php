<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Services;

use PaymentIntegrations\Domain\Subscription\Entities\Plan;
use PaymentIntegrations\Domain\Subscription\ValueObjects\PlanChangeType;

final class PlanChangeEvaluator
{
    public function evaluateChangeType(Plan $currentPlan, Plan $newPlan): PlanChangeType
    {
        if ($this->requiresScheduledChange($currentPlan, $newPlan)) {
            return PlanChangeType::SCHEDULED;
        }

        return PlanChangeType::IMMEDIATE;
    }

    private function requiresScheduledChange(Plan $currentPlan, Plan $newPlan): bool
    {
        if ($currentPlan->billingPeriod() !== $newPlan->billingPeriod()) {
            return true;
        }

        if ($newPlan->ordersLimit() < $currentPlan->ordersLimit()) {
            return true;
        }

        return false;
    }
}