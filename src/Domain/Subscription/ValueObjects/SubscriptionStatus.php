<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\ValueObjects;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';
    case TRIAL = 'trial';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canBeCharged(): bool
    {
        return in_array($this, [self::ACTIVE, self::PAST_DUE]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::ACTIVE, self::PAST_DUE, self::TRIAL]);
    }
}