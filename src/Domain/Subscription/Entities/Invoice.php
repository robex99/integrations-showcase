<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Entities;

use DateTimeImmutable;
use PaymentIntegrations\Domain\Shared\ValueObjects\Money;

final class Invoice
{
    private function __construct(
        private readonly string $id,
        private int $userId,
        private string $planId,
        private Money $amount,
        private InvoiceStatus $status,
        private ?string $transactionId,
        private ?string $cardId,
        private ?string $cardLastDigits,
        private ?string $cardBrand,
        private ?int $ordersCount,
        private ?string $statusReason,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {}

    public static function create(
        string $id,
        int $userId,
        string $planId,
        Money $amount
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            planId: $planId,
            amount: $amount,
            status: InvoiceStatus::STARTED,
            transactionId: null,
            cardId: null,
            cardLastDigits: null,
            cardBrand: null,
            ordersCount: null,
            statusReason: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );
    }

    public static function reconstitute(
        string $id,
        int $userId,
        string $planId,
        Money $amount,
        InvoiceStatus $status,
        ?string $transactionId,
        ?string $cardId,
        ?string $cardLastDigits,
        ?string $cardBrand,
        ?int $ordersCount,
        ?string $statusReason,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt
    ): self {
        return new self(
            $id,
            $userId,
            $planId,
            $amount,
            $status,
            $transactionId,
            $cardId,
            $cardLastDigits,
            $cardBrand,
            $ordersCount,
            $statusReason,
            $createdAt,
            $updatedAt
        );
    }

    public function markAsApproved(string $transactionId): void
    {
        $this->status = InvoiceStatus::APPROVED;
        $this->transactionId = $transactionId;
        $this->statusReason = 'Payment approved';
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsFailed(string $reason, ?string $transactionId = null): void
    {
        $this->status = InvoiceStatus::FAILED;
        $this->statusReason = $reason;
        $this->transactionId = $transactionId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function attachCardInfo(string $cardId, string $lastDigits, string $brand): void
    {
        $this->cardId = $cardId;
        $this->cardLastDigits = $lastDigits;
        $this->cardBrand = $brand;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setOrdersCount(int $count): void
    {
        $this->ordersCount = $count;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function id(): string
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function planId(): string
    {
        return $this->planId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function status(): InvoiceStatus
    {
        return $this->status;
    }

    public function transactionId(): ?string
    {
        return $this->transactionId;
    }

    public function cardId(): ?string
    {
        return $this->cardId;
    }

    public function cardLastDigits(): ?string
    {
        return $this->cardLastDigits;
    }

    public function cardBrand(): ?string
    {
        return $this->cardBrand;
    }

    public function ordersCount(): ?int
    {
        return $this->ordersCount;
    }

    public function statusReason(): ?string
    {
        return $this->statusReason;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}