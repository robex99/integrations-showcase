<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\UseCases\Subscription\CancelSubscription;

use PaymentIntegrations\Domain\Subscription\Exceptions\SubscriptionNotFoundException;
use PaymentIntegrations\Domain\Subscription\Repositories\SubscriptionRepositoryInterface;
use PaymentIntegrations\Infrastructure\Notifications\Contracts\NotificationServiceInterface;

final class CancelSubscriptionUseCase
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly NotificationServiceInterface $notificationService
    ) {}

    public function execute(int $userId, string $reason): CancelSubscriptionResult
    {
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        if ($subscription === null) {
            throw SubscriptionNotFoundException::forUser($userId);
        }

        try {
            $subscription->cancel($reason);
            $this->subscriptionRepository->save($subscription);

            $this->notificationService->sendCancellationNotification([
                'user_id' => $subscription->userId(),
                'reason' => $reason
            ]);

            return CancelSubscriptionResult::success();

        } catch (\Exception $e) {
            return CancelSubscriptionResult::failure($e->getMessage());
        }
    }
}