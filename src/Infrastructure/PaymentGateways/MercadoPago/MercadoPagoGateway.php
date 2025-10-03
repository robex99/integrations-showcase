<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\PaymentGateways\MercadoPago;

use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CardData;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CardResult;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CustomerData;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CustomerResult;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentData;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentGatewayInterface;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentResult;
use PaymentIntegrations\Infrastructure\PaymentGateways\MercadoPago\Mappers\PaymentRequestMapper;

final class MercadoPagoGateway implements PaymentGatewayInterface
{
    private ?object $lastResponse = null;

    public function __construct(
        private readonly MercadoPagoHttpClient $httpClient,
        private readonly PaymentRequestMapper $paymentMapper
    ) {
    }

    public function createCustomer(CustomerData $customerData): CustomerResult
    {
        $payload = [
            'email' => $customerData->email,
            'first_name' => $customerData->firstName,
            'last_name' => $customerData->lastName,
            'identification' => [
                'type' => $customerData->documentType,
                'number' => $customerData->documentNumber,
            ],
            'phone' => [
                'area_code' => $customerData->phoneAreaCode,
                'number' => $customerData->phoneNumber,
            ],
        ];

        $response = $this->httpClient->post('/v1/customers', $payload);

        return new CustomerResult(
            customerId: $response->id,
            email: $response->email
        );
    }

    public function createCard(string $customerId, CardData $cardData): CardResult
    {
        $existingCards = $this->getCustomerCards($customerId);

        $existingCard = $this->findMatchingCard($existingCards, $cardData);
        if ($existingCard !== null) {
            return $existingCard;
        }

        $tokenResponse = $this->httpClient->post('/v1/card_tokens', [
            'card_id' => $cardData->token
        ]);

        $payload = [
            'token' => $tokenResponse->id,
        ];

        $response = $this->httpClient->post("/v1/customers/{$customerId}/cards", $payload);

        return new CardResult(
            id: $response->id,
            brand: $response->payment_method->name ?? 'unknown',
            lastFourDigits: $response->last_four_digits,
            firstSixDigits: $response->first_six_digits,
            expirationMonth: (int) $response->expiration_month,
            expirationYear: (int) $response->expiration_year,
            issuerId: $response->issuer->id ?? null
        );
    }

    public function processPayment(PaymentData $paymentData): PaymentResult
    {
        $payload = $this->paymentMapper->map($paymentData);

        $cardTokenResponse = $this->httpClient->post('/v1/card_tokens', [
            'card_id' => $paymentData->cardToken
        ]);

        $payload['token'] = $cardTokenResponse->id;

        $response = $this->httpClient->post('/v1/payments', $payload);
        $this->lastResponse = $response;

        if ($response->status === 'approved') {
            return PaymentResult::success($response->id);
        }

        return PaymentResult::failure(
            $response->status ?? 'failed',
            $response->status_detail ?? 'unknown_error',
            $response->id ?? null
        );
    }

    public function getCustomerCards(string $customerId): array
    {
        $response = $this->httpClient->get("/v1/customers/{$customerId}/cards");

        if (empty($response) || !is_array($response)) {
            return [];
        }

        return array_map(function ($card) {
            return new CardResult(
                id: $card['id'] ?? $card->id,
                brand: $card['payment_method']['name'] ?? $card->payment_method->name ?? 'unknown',
                lastFourDigits: $card['last_four_digits'] ?? $card->last_four_digits,
                firstSixDigits: $card['first_six_digits'] ?? $card->first_six_digits,
                expirationMonth: (int) ($card['expiration_month'] ?? $card->expiration_month),
                expirationYear: (int) ($card['expiration_year'] ?? $card->expiration_year),
                issuerId: $card['issuer']['id'] ?? $card->issuer->id ?? null
            );
        }, is_array($response) ? $response : [$response]);
    }

    public function getLastResponse(): ?object
    {
        return $this->lastResponse;
    }

    private function findMatchingCard(array $existingCards, CardData $cardData): ?CardResult
    {
        $lastFour = substr($cardData->cardNumber, -4);
        $firstSix = substr($cardData->cardNumber, 0, 6);

        foreach ($existingCards as $card) {
            if (
                $card->lastFourDigits === $lastFour &&
                $card->firstSixDigits === $firstSix &&
                $card->expirationMonth === $cardData->expirationMonth &&
                $card->expirationYear === $cardData->expirationYear
            ) {
                return $card;
            }
        }

        return null;
    }
}
