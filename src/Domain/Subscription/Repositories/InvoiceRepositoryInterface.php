<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Subscription\Repositories;

use PaymentIntegrations\Domain\Subscription\Entities\Invoice;

interface InvoiceRepositoryInterface
{
    public function nextIdentity(): string;

    public function save(Invoice $invoice): void;

    public function findById(string $id): ?Invoice;

    public function findByUserId(int $userId): array;
}
