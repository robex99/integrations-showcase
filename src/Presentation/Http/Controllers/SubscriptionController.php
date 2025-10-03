<?php

declare(strict_types=1);

namespace PaymentIntegrations\Presentation\Http\Controllers;

use PaymentIntegrations\Application\DTOs\ChangeCreditCardDTO;
use PaymentIntegrations\Application\DTOs\ChangePlanDTO;
use PaymentIntegrations\Application\DTOs\CreateSubscriptionDTO;
use PaymentIntegrations\Application\UseCases\Subscription\CancelSubscription\CancelSubscriptionUseCase;
use PaymentIntegrations\Application\UseCases\Subscription\ChangeCreditCard\ChangeCreditCardUseCase;
use PaymentIntegrations\Application\UseCases\Subscription\ChangeSubscriptionPlan\ChangeSubscriptionPlanUseCase;
use PaymentIntegrations\Application\UseCases\Subscription\CreateSubscription\CreateSubscriptionUseCase;

final class SubscriptionController
{
    public function __construct(
        private readonly CreateSubscriptionUseCase $createSubscription,
        private readonly ChangeSubscriptionPlanUseCase $changePlan,
        private readonly ChangeCreditCardUseCase $changeCard,
        private readonly CancelSubscriptionUseCase $cancelSubscription
    ) {}

    public function store(array $data): array
    {
        $dto = new CreateSubscriptionDTO(
            userId: $data['user_id'],
            planId: $data['plan_id'],
            cardToken: $data['card_token'],
            cardNumber: $data['card_number'],
            cardholderName: $data['cardholder_name'],
            cpfCnpj: $data['cpf_cnpj'],
            expiryMonth: (int) $data['expiry_month'],
            expiryYear: (int) $data['expiry_year'],
            cvv: $data['cvv']
        );

        $result = $this->createSubscription->execute($dto);

        if ($result->success) {
            return [
                'success' => true,
                'data' => [
                    'subscription_id' => $result->subscriptionId
                ],
                'message' => 'Subscription created successfully'
            ];
        }

        return [
            'success' => false,
            'error' => $result->errorMessage
        ];
    }

    public function changePlan(array $data): array
    {
        $dto = new ChangePlanDTO(
            userId: $data['user_id'],
            newPlanId: $data['new_plan_id'],
            cardToken: $data['card_token'] ?? null,
            cardNumber: $data['card_number'] ?? null,
            cardholderName: $data['cardholder_name'] ?? null,
            cpfCnpj: $data['cpf_cnpj'] ?? null,
            expiryMonth: isset($data['expiry_month']) ? (int) $data['expiry_month'] : null,
            expiryYear: isset($data['expiry_year']) ? (int) $data['expiry_year'] : null,
            cvv: $data['cvv'] ?? null
        );

        $result = $this->changePlan->execute($dto);

        if ($result->success) {
            return [
                'success' => true,
                'data' => [
                    'immediate' => $result->immediate,
                    'message' => $result->message
                ]
            ];
        }

        return [
            'success' => false,
            'error' => $result->message
        ];
    }

    public function updateCard(array $data): array
    {
        $dto = new ChangeCreditCardDTO(
            userId: $data['user_id'],
            cardToken: $data['card_token'],
            cardNumber: $data['card_number'],
            cardholderName: $data['cardholder_name'],
            cpfCnpj: $data['cpf_cnpj'],
            expiryMonth: (int) $data['expiry_month'],
            expiryYear: (int) $data['expiry_year'],
            cvv: $data['cvv']
        );

        $result = $this->changeCard->execute($dto);

        if ($result->success) {
            return [
                'success' => true,
                'message' => 'Credit card updated successfully'
            ];
        }

        return [
            'success' => false,
            'error' => $result->errorMessage
        ];
    }

    public function cancel(array $data): array
    {
        $result = $this->cancelSubscription->execute(
            userId: $data['user_id'],
            reason: $data['reason'] ?? 'Customer requested cancellation'
        );

        if ($result->success) {
            return [
                'success' => true,
                'message' => 'Subscription cancelled successfully'
            ];
        }

        return [
            'success' => false,
            'error' => $result->errorMessage
        ];
    }
}