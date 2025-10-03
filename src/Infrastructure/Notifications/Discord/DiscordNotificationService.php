<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Notifications\Discord;

use PaymentIntegrations\Infrastructure\Notifications\Contracts\NotificationServiceInterface;

final class DiscordNotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly string $webhookUrl
    ) {
    }

    public function sendNewSubscriptionNotification(array $data): void
    {
        $message = sprintf(
            "**Nova Assinatura!**\n" .
            "Usuário: #%d\n" .
            "Invoice ID: #%s\n" .
            "Plano: %s\n" .
            "Valor: %s",
            $data['user_id'],
            $data['invoice_id'],
            $data['plan_name'],
            $data['amount']
        );

        $this->send($message);
    }

    public function sendRenewalNotification(array $data): void
    {
        $message = sprintf(
            "**Renovação de Assinatura!**\n" .
            "Usuário: #%d\n" .
            "Invoice ID: #%s\n" .
            "Plano: %s\n" .
            "Valor: %s\n" .
            "Orders: %d",
            $data['user_id'],
            $data['invoice_id'],
            $data['plan_name'],
            $data['amount'],
            $data['orders_count'] ?? 0
        );

        $this->send($message);
    }

    public function sendPlanChangeNotification(array $data): void
    {
        $message = sprintf(
            "**Alteração de Plano!**\n" .
            "Usuário: #%d\n" .
            "Invoice ID: #%s\n" .
            "Plano Anterior: %s\n" .
            "Novo Plano: %s\n" .
            "Valor: %s",
            $data['user_id'],
            $data['invoice_id'],
            $data['old_plan_name'],
            $data['new_plan_name'],
            $data['amount']
        );

        $this->send($message);
    }

    public function sendCancellationNotification(array $data): void
    {
        $message = sprintf(
            "**Assinatura Cancelada**\n" .
            "Usuário: #%d\n" .
            "Motivo: %s",
            $data['user_id'],
            $data['reason']
        );

        $this->send($message);
    }

    public function sendFailureNotification(array $data): void
    {
        $message = sprintf(
            "**Falha na %s**\n" .
            "Usuário: #%d\n" .
            "Invoice ID: #%s\n" .
            "Motivo: %s",
            $data['action'],
            $data['user_id'],
            $data['invoice_id'],
            $data['reason']
        );

        $this->send($message);
    }

    private function send(string $message): void
    {
        $payload = json_encode(['content' => $message]);

        $ch = curl_init($this->webhookUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode >= 400) {
            error_log("Discord webhook error [{$httpCode}]: {$result}");
        }

        curl_close($ch);
    }
}
