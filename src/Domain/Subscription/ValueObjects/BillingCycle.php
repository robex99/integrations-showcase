<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class BillingCycle
{
    public function __construct(
        private readonly BillingPeriod $period,
        private readonly DateTimeImmutable $startDate,
        private readonly DateTimeImmutable $endDate
    ) {
        if ($startDate >= $endDate) {
            throw new InvalidArgumentException('Start date must be before end date');
        }
    }

    public static function create(BillingPeriod $period, DateTimeImmutable $startDate): self
    {
        $endDate = match ($period) {
            BillingPeriod::MONTHLY => $startDate->modify('+1 month'),
            BillingPeriod::QUARTERLY => $startDate->modify('+3 months'),
            BillingPeriod::SEMIANNUAL => $startDate->modify('+6 months'),
            BillingPeriod::YEARLY => $startDate->modify('+1 year'),
        };

        return new self($period, $startDate, $endDate);
    }

    public function period(): BillingPeriod
    {
        return $this->period;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function totalDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days;
    }

    public function remainingDays(DateTimeImmutable $currentDate): int
    {
        if ($currentDate >= $this->endDate) {
            return 0;
        }

        return (int) $currentDate->diff($this->endDate)->days;
    }

    public function usedDays(DateTimeImmutable $currentDate): int
    {
        return $this->totalDays() - $this->remainingDays($currentDate);
    }

    public function isActive(DateTimeImmutable $currentDate): bool
    {
        return $currentDate >= $this->startDate && $currentDate < $this->endDate;
    }

    public function nextCycle(): self
    {
        return self::create($this->period, $this->endDate);
    }
}
