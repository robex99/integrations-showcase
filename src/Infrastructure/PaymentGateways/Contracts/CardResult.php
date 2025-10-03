<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\PaymentGateways\Contracts;

final readonly class CardResult
{
    public function __construct(
        public string $id,
        public string $brand,
        public string $lastFourDigits,
        public string $firstSixDigits,
        public int $expirationMonth,
        public int $expirationYear,
        public ?string $issuerId = null
    ) {
    }
}
