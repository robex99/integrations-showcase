<?php

declare(strict_types=1);

namespace PaymentIntegrations\Presentation\Http\Controllers;

use PaymentIntegrations\Domain\Subscription\Repositories\InvoiceRepositoryInterface;

final class InvoiceController
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function index(int $userId): array
    {
        $invoices = $this->invoiceRepository->findByUserId($userId);

        return [
            'success' => true,
            'data' => array_map(function ($invoice) {
                return [
                    'id' => $invoice->id(),
                    'amount' => $invoice->amount()->amountInCents(),
                    'amount_formatted' => $invoice->amount()->formatted(),
                    'status' => $invoice->status()->value,
                    'transaction_id' => $invoice->transactionId(),
                    'card_last_digits' => $invoice->cardLastDigits(),
                    'card_brand' => $invoice->cardBrand(),
                    'orders_count' => $invoice->ordersCount(),
                    'status_reason' => $invoice->statusReason(),
                    'created_at' => $invoice->createdAt()->format('Y-m-d H:i:s'),
                ];
            }, $invoices)
        ];
    }

    public function show(string $id): array
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            return [
                'success' => false,
                'error' => 'Invoice not found'
            ];
        }

        return [
            'success' => true,
            'data' => [
                'id' => $invoice->id(),
                'user_id' => $invoice->userId(),
                'plan_id' => $invoice->planId(),
                'amount' => $invoice->amount()->amountInCents(),
                'amount_formatted' => $invoice->amount()->formatted(),
                'status' => $invoice->status()->value,
                'transaction_id' => $invoice->transactionId(),
                'card_last_digits' => $invoice->cardLastDigits(),
                'card_brand' => $invoice->cardBrand(),
                'orders_count' => $invoice->ordersCount(),
                'status_reason' => $invoice->statusReason(),
                'created_at' => $invoice->createdAt()->format('Y-m-d H:i:s'),
                'updated_at' => $invoice->updatedAt()->format('Y-m-d H:i:s'),
            ]
        ];
    }
}