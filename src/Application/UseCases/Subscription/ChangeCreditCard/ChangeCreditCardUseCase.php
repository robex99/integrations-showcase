<?php

declare(strict_types=1);

namespace PaymentIntegrations\Application\UseCases\Subscription\ChangeCreditCard;

use PaymentIntegrations\Application\DTOs\ChangeCreditCardDTO;
use PaymentIntegrations\Application\UseCases\Subscription\CreateSubscription\CardStorageService;
use PaymentIntegrations\Domain\Subscription\Exceptions\SubscriptionNotFoundException;
use PaymentIntegrations\Domain\Subscription\Repositories\SubscriptionRepositoryInterface;
use PaymentIntegrations\Domain\Subscription\ValueObjects\SubscriptionStatus;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\CardData;
use PaymentIntegrations\Infrastructure\PaymentGateways\Contracts\PaymentGatewayInterface;

final class ChangeCreditCardUseCase
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly CardStorageService $cardStorageService
    ) {
    }

    public function execute(ChangeCreditCardDTO $dto): ChangeCreditCardResult
    {
        $subscription = $this->subscriptionRepository->findByUserId($dto->userId);
        if ($subscription === null) {
            throw SubscriptionNotFoundException::forUser($dto->userId);
        }

        try {
            $cardData = new CardData(
                token: $dto->cardToken,
                cardNumber: $dto->cardNumber,
                cardholderName: $dto->cardholderName,
                expirationMonth: $dto->expiryMonth,
                expirationYear: $dto->expiryYear,
                securityCode: $dto->cvv
            );

            $cardResult = $this->paymentGateway->createCard($subscription->customerId(), $cardData);

            $storedCardId = $this->cardStorageService->store(
                userId: $dto->userId,
                cardResult: $cardResult,
                customerId: $subscription->customerId()
            );

            $subscription->changeCard($storedCardId);
            $this->subscriptionRepository->save($subscription);

            return ChangeCreditCardResult::success();

        } catch (\Exception $e) {
            return ChangeCreditCardResult::failure($e->getMessage());
        }
    }
}
