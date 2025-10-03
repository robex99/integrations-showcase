<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\PaymentGateways\Contracts;

final readonly class CustomerData
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $documentType,
        public string $documentNumber,
        public string $phoneAreaCode,
        public string $phoneNumber
    ) {
    }
}
