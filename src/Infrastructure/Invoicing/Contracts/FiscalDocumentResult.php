<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Invoicing\Contracts;

final readonly class FiscalDocumentResult
{
    private function __construct(
        public bool $success,
        public ?string $documentId,
        public ?string $documentUrl,
        public ?string $errorMessage
    ) {}

    public static function success(string $documentId, ?string $documentUrl = null): self
    {
        return new self(
            success: true,
            documentId: $documentId,
            documentUrl: $documentUrl,
            errorMessage: null
        );
    }

    public static function failure(string $errorMessage): self
    {
        return new self(
            success: false,
            documentId: null,
            documentUrl: null,
            errorMessage: $errorMessage
        );
    }
}