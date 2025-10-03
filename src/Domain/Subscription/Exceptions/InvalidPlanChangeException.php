<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Exceptions;

use RuntimeException;

final class InvalidPlanChangeException extends RuntimeException
{
    public static function tooSoon(int $minDays): self
    {
        return new self("Plan cannot be changed before {$minDays} days since last change");
    }

    public static function invalidTransition(string $reason): self
    {
        return new self("Invalid plan change: {$reason}");
    }
}