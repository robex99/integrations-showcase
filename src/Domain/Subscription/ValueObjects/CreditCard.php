<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\ValueObjects;

use InvalidArgumentException;

final readonly class CreditCard
{
    private function __construct(
        private string $token,
        private string $brand,
        private string $lastFourDigits,
        private string $firstSixDigits,
        private int $expirationMonth,
        private int $expirationYear
    ) {
        if ($expirationMonth < 1 || $expirationMonth > 12) {
            throw new InvalidArgumentException('Invalid expiration month');
        }

        if ($expirationYear < 2000) {
            throw new InvalidArgumentException('Invalid expiration year');
        }

        if (strlen($lastFourDigits) !== 4) {
            throw new InvalidArgumentException('Last four digits must be 4 characters');
        }

        if (strlen($firstSixDigits) !== 6) {
            throw new InvalidArgumentException('First six digits must be 6 characters');
        }
    }

    public static function create(
        string $token,
        string $brand,
        string $lastFourDigits,
        string $firstSixDigits,
        int $expirationMonth,
        int $expirationYear
    ): self {
        return new self(
            $token,
            $brand,
            $lastFourDigits,
            $firstSixDigits,
            $expirationMonth,
            $expirationYear
        );
    }

    public function token(): string
    {
        return $this->token;
    }

    public function brand(): string
    {
        return $this->brand;
    }

    public function lastFourDigits(): string
    {
        return $this->lastFourDigits;
    }

    public function firstSixDigits(): string
    {
        return $this->firstSixDigits;
    }

    public function expirationMonth(): int
    {
        return $this->expirationMonth;
    }

    public function expirationYear(): int
    {
        return $this->expirationYear;
    }

    public function isExpired(): bool
    {
        $now = new \DateTimeImmutable();
        $expirationDate = new \DateTimeImmutable(
            sprintf('%d-%02d-01', $this->expirationYear, $this->expirationMonth)
        );
        $expirationDate = $expirationDate->modify('last day of this month');

        return $now > $expirationDate;
    }

    public function maskedNumber(): string
    {
        return sprintf('%s****%s', $this->firstSixDigits, $this->lastFourDigits);
    }
}
