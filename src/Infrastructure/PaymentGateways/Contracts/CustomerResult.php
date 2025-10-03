<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\PaymentGateways\Contracts;

final readonly class CustomerResult
{
    public function __construct(
        public string $customerId,
        public string $email
    ) {
    }
}
