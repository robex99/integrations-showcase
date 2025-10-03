<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\PaymentGateways\Contracts;

final readonly class PaymentData
{
    public function __construct(
        public string $customerId,
        public string $cardToken,
        public int $amountInCents,
        public string $description,
        public string $externalReference,
        public bool $isRecurring,
        public ?string $subscriptionId = null,
        public ?int $sequenceNumber = null,
        public ?string $firstPaymentId = null
    ) {}
}