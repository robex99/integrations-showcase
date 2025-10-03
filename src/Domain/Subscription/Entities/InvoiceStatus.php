<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Entities;

enum InvoiceStatus: string
{
    case STARTED = 'started';
    case APPROVED = 'approved';
    case FAILED = 'failed';
}
