<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\PaymentGateways\Contracts;

final readonly class CardData
{
    public function __construct(
        public string $token,
        public string $cardNumber,
        public string $cardholderName,
        public int $expirationMonth,
        public int $expirationYear,
        public string $securityCode
    ) {
    }
}
