<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\UseCases\Subscription\ChangeSubscriptionPlan;

final readonly class ChangeSubscriptionPlanResult
{
    private function __construct(
        public bool $success,
        public bool $immediate,
        public ?string $message
    ) {
    }

    public static function immediate(): self
    {
        return new self(true, true, 'Plan changed immediately');
    }

    public static function scheduled(string $message): self
    {
        return new self(true, false, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, false, $message);
    }
}
