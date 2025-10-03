<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use PaymentIntegrations\Domain\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_creates_money_from_cents(): void
    {
        $money = Money::fromCents(10000);

        $this->assertEquals(10000, $money->amountInCents());
        $this->assertEquals(100.0, $money->amountInReais());
    }

    public function test_creates_money_from_reais(): void
    {
        $money = Money::fromReais(100.50);

        $this->assertEquals(10050, $money->amountInCents());
        $this->assertEquals(100.50, $money->amountInReais());
    }

    public function test_throws_exception_for_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative');

        Money::fromCents(-100);
    }

    public function test_adds_money(): void
    {
        $money1 = Money::fromCents(10000);
        $money2 = Money::fromCents(5000);

        $result = $money1->add($money2);

        $this->assertEquals(15000, $result->amountInCents());
    }

    public function test_subtracts_money(): void
    {
        $money1 = Money::fromCents(10000);
        $money2 = Money::fromCents(3000);

        $result = $money1->subtract($money2);

        $this->assertEquals(7000, $result->amountInCents());
    }

    public function test_subtract_returns_zero_for_negative_results(): void
    {
        $money1 = Money::fromCents(5000);
        $money2 = Money::fromCents(10000);

        $result = $money1->subtract($money2);

        $this->assertEquals(0, $result->amountInCents());
    }

    public function test_multiplies_money(): void
    {
        $money = Money::fromCents(10000);

        $result = $money->multiply(2.5);

        $this->assertEquals(25000, $result->amountInCents());
    }

    public function test_compares_money(): void
    {
        $money1 = Money::fromCents(10000);
        $money2 = Money::fromCents(5000);

        $this->assertTrue($money1->isGreaterThan($money2));
        $this->assertFalse($money2->isGreaterThan($money1));
    }

    public function test_formats_money(): void
    {
        $money = Money::fromCents(123456);

        $this->assertEquals('R$ 1.234,56', $money->formatted());
    }

    public function test_money_equality(): void
    {
        $money1 = Money::fromCents(10000);
        $money2 = Money::fromCents(10000);
        $money3 = Money::fromCents(5000);

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
    }

    public function test_throws_exception_for_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot operate on different currencies');

        $money1 = Money::fromCents(100, 'BRL');
        $money2 = Money::fromCents(100, 'USD');

        $money1->add($money2);
    }
}