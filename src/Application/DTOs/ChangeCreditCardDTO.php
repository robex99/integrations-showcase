<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\DTOs;

final readonly class ChangeCreditCardDTO
{
    public function __construct(
        public int $userId,
        public string $cardToken,
        public string $cardNumber,
        public string $cardholderName,
        public string $cpfCnpj,
        public int $expiryMonth,
        public int $expiryYear,
        public string $cvv
    ) {
    }
}
