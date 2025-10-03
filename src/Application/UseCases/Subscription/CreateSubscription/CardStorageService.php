<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\UseCases\Subscription\CreateSubscription;

use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CardResult;

interface CardStorageService
{
    public function store(int $userId, CardResult $cardResult, string $customerId): string;
}
