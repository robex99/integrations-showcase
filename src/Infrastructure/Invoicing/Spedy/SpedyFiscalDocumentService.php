<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Invoicing\Spedy;

use PaymentIntegrations\Infrastructure\Invoicing\Contracts\FiscalDocumentData;
use PaymentIntegrations\Infrastructure\Invoicing\Contracts\FiscalDocumentResult;
use PaymentIntegrations\Infrastructure\Invoicing\Contracts\FiscalDocumentServiceInterface;

final class SpedyFiscalDocumentService implements FiscalDocumentServiceInterface
{
    private const API_URL = 'https://api.spedy.com.br/v1/orders';

    public function __construct(
        private readonly string $apiKey,
        private readonly bool $autoIssue = true
    ) {}

    public function issueDocument(FiscalDocumentData $data): FiscalDocumentResult
    {
        try {
            $payload = $this->buildPayload($data);
            
            $response = $this->makeRequest($payload);

            if ($response['success']) {
                return FiscalDocumentResult::success(
                    documentId: $response['data']['id'] ?? 'unknown',
                    documentUrl: $response['data']['url'] ?? null
                );
            }

            return FiscalDocumentResult::failure(
                $response['error'] ?? 'Unknown error from Spedy'
            );

        } catch (\Exception $e) {
            return FiscalDocumentResult::failure($e->getMessage());
        }
    }

    private function buildPayload(FiscalDocumentData $data): array
    {
        $amount = $data->amountInCents / 100;

        return [
            'transactionId' => $data->transactionId,
            'customer' => [
                'name' => $data->customerName,
                'email' => $data->customerEmail,
                'federalTaxNumber' => preg_replace('/[^0-9]/', '', $data->customerDocument),
                'address' => [
                    'street' => $data->customerStreet ?? 'N/A',
                    'district' => $data->customerDistrict ?? 'N/A',
                    'postalCode' => preg_replace('/[^0-9]/', '', $data->customerPostalCode ?? '00000000'),
                    'number' => $data->customerNumber ?? 's/n',
                    'city' => [
                        'name' => $data->customerCity ?? 'N/A',
                        'state' => $data->customerState ?? 'SP'
                    ],
                    'country' => [
                        'name' => 'Brasil'
                    ]
                ]
            ],
            'amount' => $amount,
            'date' => date('c'),
            'warrantyDate' => date('c', strtotime('+7 days')),
            'sendEmailToCustomer' => $data->sendEmailToCustomer,
            'status' => 'approved',
            'autoIssueMode' => $this->autoIssue ? 'immediately' : 'manual',
            'paymentMethod' => 'creditCard',
            'profileType' => 'producer',
            'items' => [
                [
                    'description' => $data->itemDescription,
                    'quantity' => 1.0,
                    'price' => $amount,
                    'amount' => $amount,
                    'product' => [
                        'name' => $data->itemDescription,
                        'code' => $data->itemCode,
                        'price' => $amount
                    ]
                ]
            ]
        ];
    }

    private function makeRequest(array $payload): array
    {
        $ch = curl_init(self::API_URL);
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Api-Key: ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            return [
                'success' => false,
                'error' => "HTTP Error {$httpCode}: {$response}"
            ];
        }

        $decoded = json_decode($response, true);

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => $decoded,
            'error' => null
        ];
    }
}