<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\DTOs;

final readonly class ChangePlanDTO
{
    public function __construct(
        public int $userId,
        public string $newPlanId,
        public ?string $cardToken = null,
        public ?string $cardNumber = null,
        public ?string $cardholderName = null,
        public ?string $cpfCnpj = null,
        public ?int $expiryMonth = null,
        public ?int $expiryYear = null,
        public ?string $cvv = null
    ) {
    }

    public function hasNewCard(): bool
    {
        return $this->cardToken !== null;
    }
}
