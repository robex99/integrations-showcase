<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Repositories;

use PaymentIntegrations\Domain\Subscription\Entities\Subscription;

interface SubscriptionRepositoryInterface
{
    public function findById(string $id): ?Subscription;
    
    public function findByUserId(int $userId): ?Subscription;
    
    public function save(Subscription $subscription): void;
    
    public function findDueForRenewal(\DateTimeImmutable $date): array;
}