<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\UseCases\Subscription\CreateSubscription;

final readonly class CreateSubscriptionResult
{
    private function __construct(
        public bool $success,
        public ?string $subscriptionId,
        public ?string $errorMessage
    ) {
    }

    public static function success(string $subscriptionId): self
    {
        return new self(true, $subscriptionId, null);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(false, null, $errorMessage);
    }
}
