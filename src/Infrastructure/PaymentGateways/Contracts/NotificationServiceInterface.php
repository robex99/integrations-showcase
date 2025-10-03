<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Notifications\Contracts;

interface NotificationServiceInterface
{
    public function sendNewSubscriptionNotification(array $data): void;
    
    public function sendRenewalNotification(array $data): void;
    
    public function sendPlanChangeNotification(array $data): void;
    
    public function sendCancellationNotification(array $data): void;
    
    public function sendFailureNotification(array $data): void;
}