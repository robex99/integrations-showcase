<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\PaymentGateways\MercadoPago\Mappers;

use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentData;

final class PaymentRequestMapper
{
    public function map(PaymentData $data): array
    {
        $payload = [
            'external_reference' => $data->externalReference,
            'description' => $data->description,
            'statement_descriptor' => 'OnProfit',
            'transaction_amount' => round($data->amountInCents / 100, 2),
            'capture' => true,
            'installments' => 1,
            'binary_mode' => true,
            'payer' => [
                'id' => $data->customerId,
            ],
        ];

        if ($data->isRecurring) {
            $payload['point_of_interaction'] = [
                'type' => 'SUBSCRIPTIONS',
                'transaction_data' => [
                    'first_time_use' => $data->subscriptionId === null,
                    'subscription_id' => $data->subscriptionId ?? $data->externalReference,
                    'subscription_sequence' => [
                        'number' => $data->sequenceNumber ?? 1,
                    ],
                    'invoice_period' => [
                        'period' => 1,
                        'type' => 'monthly',
                    ],
                    'billing_date' => date('Y-m-d'),
                ],
            ];

            if ($data->firstPaymentId !== null) {
                $payload['point_of_interaction']['transaction_data']['payment_reference'] = [
                    'id' => $data->firstPaymentId,
                ];
            }
        }

        return $payload;
    }
}
