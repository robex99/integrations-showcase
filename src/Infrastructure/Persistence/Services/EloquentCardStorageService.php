<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Persistence\Services;

use PaymentIntegrations\Application\UseCases\Subscription\CreateSubscription\CardStorageService;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CardResult;

final class EloquentCardStorageService implements CardStorageService
{
    public function store(int $userId, CardResult $cardResult, string $customerId): string
    {
        // Simplified implementation - in production would use AdminCard model
        $cardData = [
            'user_id' => $userId,
            'gateway' => 'mercadopago',
            'status' => 'active',
            'additional_data' => json_encode([
                'brand' => $cardResult->brand,
                'last_digits' => $cardResult->lastFourDigits,
                'first_digits' => $cardResult->firstSixDigits,
                'hash' => $cardResult->id,
                'type' => 'credit_card',
                'issuer_id' => $cardResult->issuerId,
                'customer_id' => $customerId,
            ]),
        ];

        // In real implementation: return AdminCard::create($cardData)->id;
        // For showcase, return a mock ID
        return $cardResult->id;
    }
}