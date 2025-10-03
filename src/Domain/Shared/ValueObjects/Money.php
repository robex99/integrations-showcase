<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Shared\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    private function __construct(
        private int $amountInCents,
        private string $currency = 'BRL'
    ) {
        if ($amountInCents < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }

    public static function fromCents(int $cents, string $currency = 'BRL'): self
    {
        return new self($cents, $currency);
    }

    public static function fromReais(float $reais, string $currency = 'BRL'): self
    {
        return new self((int) round($reais * 100), $currency);
    }

    public function amountInCents(): int
    {
        return $this->amountInCents;
    }

    public function amountInReais(): float
    {
        return $this->amountInCents / 100;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);
        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);
        return new self(max(0, $this->amountInCents - $other->amountInCents), $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self((int) round($this->amountInCents * $multiplier), $this->currency);
    }

    public function isGreaterThan(self $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->amountInCents > $other->amountInCents;
    }

    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents 
            && $this->currency === $other->currency;
    }

    public function formatted(): string
    {
        return sprintf('R$ %s', number_format($this->amountInReais(), 2, ',', '.'));
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot operate on different currencies');
        }
    }
}