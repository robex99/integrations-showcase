<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\PaymentGateways\MercadoPago;

use RuntimeException;

final class MercadoPagoHttpClient
{
    private const BASE_URL = 'https://api.mercadopago.com';

    public function __construct(
        private readonly string $accessToken,
        private readonly string $platformId
    ) {
    }

    public function post(string $endpoint, array $payload): object
    {
        return $this->request('POST', $endpoint, $payload);
    }

    public function get(string $endpoint): mixed
    {
        return $this->request('GET', $endpoint);
    }

    private function request(string $method, string $endpoint, array $payload = []): mixed
    {
        $url = self::BASE_URL . '/' . ltrim($endpoint, '/');
        $idempotencyKey = $this->generateIdempotencyKey();

        $headers = [
            'accept: application/json',
            'Authorization: Bearer ' . $this->accessToken,
            'content-type: application/json',
            'X-Idempotency-Key: ' . $idempotencyKey,
            'x-platform-id: ' . $this->platformId,
        ];

        $curl = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST' && !empty($payload)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            throw new RuntimeException("cURL Error: {$error}");
        }

        $decoded = json_decode($response);

        if ($httpCode >= 400) {
            $message = $decoded->message ?? $decoded->error ?? 'Unknown error';
            throw new RuntimeException("MercadoPago API Error [{$httpCode}]: {$message}");
        }

        return $decoded;
    }

    private function generateIdempotencyKey(): string
    {
        return md5($this->accessToken . date('Y-m-d H:i:s') . uniqid((string) rand(), true));
    }
}
