<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Persistence\Repositories;

use PaymentIntegrations\Domain\Shared\ValueObjects\Money;
use PaymentIntegrations\Domain\Subscription\Entities\Invoice;
use PaymentIntegrations\Domain\Subscription\Entities\InvoiceStatus;
use PaymentIntegrations\Domain\Subscription\Repositories\InvoiceRepositoryInterface;
use PaymentIntegrations\Infrastructure\Persistence\Eloquent\Models\EloquentInvoice;

final class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function nextIdentity(): string
    {
        return uniqid('inv_', true);
    }

    public function save(Invoice $invoice): void
    {
        $data = [
            'user_id' => $invoice->userId(),
            'plan_id' => $invoice->planId(),
            'card_id' => $invoice->cardId(),
            'card_last_digits' => $invoice->cardLastDigits(),
            'card_brand' => $invoice->cardBrand(),
            'orders' => $invoice->ordersCount(),
            'amount' => $invoice->amount()->amountInCents(),
            'status' => $invoice->status()->value,
            'transaction_id' => $invoice->transactionId(),
            'last_status_reason' => $invoice->statusReason(),
            'updated_at' => $invoice->updatedAt()->format('Y-m-d H:i:s'),
        ];

        EloquentInvoice::updateOrCreate(
            ['id' => $invoice->id()],
            $data
        );
    }

    public function findById(string $id): ?Invoice
    {
        $eloquent = EloquentInvoice::find($id);
        
        if ($eloquent === null) {
            return null;
        }

        return $this->toDomain($eloquent);
    }

    public function findByUserId(int $userId): array
    {
        $eloquentInvoices = EloquentInvoice::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return array_map(
            fn($eloquent) => $this->toDomain($eloquent),
            $eloquentInvoices->all()
        );
    }

    private function toDomain(EloquentInvoice $eloquent): Invoice
    {
        return Invoice::reconstitute(
            id: (string) $eloquent->id,
            userId: $eloquent->user_id,
            planId: (string) $eloquent->plan_id,
            amount: Money::fromCents($eloquent->amount),
            status: InvoiceStatus::from($eloquent->status),
            transactionId: $eloquent->transaction_id,
            cardId: $eloquent->card_id,
            cardLastDigits: $eloquent->card_last_digits,
            cardBrand: $eloquent->card_brand,
            ordersCount: $eloquent->orders,
            statusReason: $eloquent->last_status_reason,
            createdAt: new \DateTimeImmutable($eloquent->created_at),
            updatedAt: new \DateTimeImmutable($eloquent->updated_at)
        );
    }
}