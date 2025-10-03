<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Exceptions;

use RuntimeException;

final class PlanNotFoundException extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("Plan with ID '{$id}' not found");
    }
}
