<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\ValueObjects;

enum PaymentStatus: string
{
    case PAID = 'paid';
    case FAILED = 'failed';
    case PENDING = 'pending';
    case ENDED = 'ended';

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    public function hasFailed(): bool
    {
        return $this === self::FAILED;
    }
}