<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Invoicing\Contracts;

final readonly class FiscalDocumentData
{
    public function __construct(
        public string $transactionId,
        public string $customerName,
        public string $customerEmail,
        public string $customerDocument,
        public ?string $customerStreet,
        public ?string $customerDistrict,
        public ?string $customerPostalCode,
        public ?string $customerNumber,
        public ?string $customerCity,
        public ?string $customerState,
        public int $amountInCents,
        public string $itemDescription,
        public string $itemCode,
        public bool $sendEmailToCustomer = true
    ) {}
}