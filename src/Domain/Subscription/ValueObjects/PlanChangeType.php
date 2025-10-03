<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\ValueObjects;

enum PlanChangeType: string
{
    case IMMEDIATE = 'immediate';
    case SCHEDULED = 'scheduled';
}