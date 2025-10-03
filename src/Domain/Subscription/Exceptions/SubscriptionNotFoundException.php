<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Exceptions;

use RuntimeException;

final class SubscriptionNotFoundException extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("Subscription with ID '{$id}' not found");
    }

    public static function forUser(int $userId): self
    {
        return new self("Subscription for user '{$userId}' not found");
    }
}