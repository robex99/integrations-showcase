<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Repositories;

use PaymentIntegrations\Domain\Subscription\Entities\Plan;

interface PlanRepositoryInterface
{
    public function findById(string $id): ?Plan;

    public function findAllActive(): array;
}
