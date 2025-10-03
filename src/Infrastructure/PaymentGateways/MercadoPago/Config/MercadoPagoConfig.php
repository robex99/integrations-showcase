<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\PaymentGateways\MercadoPago\Config;

final readonly class MercadoPagoConfig
{
    public function __construct(
        public string $publicKey,
        public string $accessToken,
        public string $platformId,
        public string $webhookUrl
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            publicKey: $config['public_key'] ?? '',
            accessToken: $config['access_token'] ?? '',
            platformId: $config['platform_id'] ?? '',
            webhookUrl: $config['webhook_url'] ?? ''
        );
    }
}