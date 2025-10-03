<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\UseCases\Subscription\RenewSubscription;

use DateTimeImmutable;

interface UsageBasedChargeCalculator
{
    public function getOrdersCount(int $userId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): int;
}
