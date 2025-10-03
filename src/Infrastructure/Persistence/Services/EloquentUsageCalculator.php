<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Persistence\Services;

use DateTimeImmutable;
use PaymentIntegrations\Application\UseCases\Subscription\RenewSubscription\UsageBasedChargeCalculator;

final class EloquentUsageCalculator implements UsageBasedChargeCalculator
{
    public function getOrdersCount(int $userId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): int
    {
        // Simplified - in production would query Order model
        // return Order::where('user_id', $userId)
        //     ->whereBetween('created_at', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
        //     ->where('status', 'APPROVED')
        //     ->count();

        return 0; // Placeholder for showcase
    }
}
