<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

uses(TestCase::class)->in('Unit', 'Integration', 'Feature');

expect()->extend('toBeValidMoney', function () {
    return $this->toBeInstanceOf(\PaymentIntegrations\Domain\Shared\ValueObjects\Money::class);
});

expect()->extend('toBeValidDocument', function () {
    return $this->toBeInstanceOf(\PaymentIntegrations\Domain\Shared\ValueObjects\Document::class);
});