<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Invoicing\Contracts;

interface FiscalDocumentServiceInterface
{
    public function issueDocument(FiscalDocumentData $data): FiscalDocumentResult;
}