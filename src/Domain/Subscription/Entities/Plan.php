<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Entities;

use PaymentIntegrations\Domain\Shared\ValueObjects\Money;
use PaymentIntegrations\Domain\Subscription\ValueObjects\BillingPeriod;

final readonly class Plan
{
    private function __construct(
        private string $id,
        private string $name,
        private Money $price,
        private int $ordersLimit,
        private BillingPeriod $billingPeriod,
        private Money $extraOrderCharge,
        private bool $isActive
    ) {}

    public static function create(
        string $id,
        string $name,
        Money $price,
        int $ordersLimit,
        BillingPeriod $billingPeriod,
        Money $extraOrderCharge
    ): self {
        return new self(
            $id,
            $name,
            $price,
            $ordersLimit,
            $billingPeriod,
            $extraOrderCharge,
            true
        );
    }

    public static function reconstitute(
        string $id,
        string $name,
        Money $price,
        int $ordersLimit,
        BillingPeriod $billingPeriod,
        Money $extraOrderCharge,
        bool $isActive
    ): self {
        return new self(
            $id,
            $name,
            $price,
            $ordersLimit,
            $billingPeriod,
            $extraOrderCharge,
            $isActive
        );
    }

    public function calculateTotalAmount(int $ordersCount): Money
    {
        $baseAmount = $this->price;

        if ($ordersCount <= $this->ordersLimit) {
            return $baseAmount;
        }

        $extraOrders = $ordersCount - $this->ordersLimit;
        $extraCharge = $this->extraOrderCharge->multiply($extraOrders);

        return $baseAmount->add($extraCharge);
    }

    public function hasExtraOrders(int $ordersCount): bool
    {
        return $ordersCount > $this->ordersLimit;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function ordersLimit(): int
    {
        return $this->ordersLimit;
    }

    public function billingPeriod(): BillingPeriod
    {
        return $this->billingPeriod;
    }

    public function extraOrderCharge(): Money
    {
        return $this->extraOrderCharge;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}