<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\UseCases\Subscription\ChangeCreditCard;

final readonly class ChangeCreditCardResult
{
    private function __construct(
        public bool $success,
        public ?string $errorMessage
    ) {
    }

    public static function success(): self
    {
        return new self(true, null);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(false, $errorMessage);
    }
}
