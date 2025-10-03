<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\PaymentGateways\Contracts;

interface PaymentGatewayInterface
{
    public function createCustomer(CustomerData $customerData): CustomerResult;
    
    public function createCard(string $customerId, CardData $cardData): CardResult;
    
    public function processPayment(PaymentData $paymentData): PaymentResult;
    
    public function getCustomerCards(string $customerId): array;
}