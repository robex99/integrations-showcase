<?php

declare(strict_types=1);

namespace PaymentIntegrations\Presentation\Http\Controllers;

use PaymentIntegrations\Domain\Subscription\Repositories\PlanRepositoryInterface;

final class PlanController
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository
    ) {}

    public function index(): array
    {
        $plans = $this->planRepository->findAllActive();

        return [
            'success' => true,
            'data' => array_map(function ($plan) {
                return [
                    'id' => $plan->id(),
                    'name' => $plan->name(),
                    'price' => $plan->price()->amountInCents(),
                    'price_formatted' => $plan->price()->formatted(),
                    'orders_limit' => $plan->ordersLimit(),
                    'billing_period' => $plan->billingPeriod()->value,
                    'extra_order_charge' => $plan->extraOrderCharge()->amountInCents(),
                ];
            }, $plans)
        ];
    }

    public function show(string $id): array
    {
        $plan = $this->planRepository->findById($id);

        if (!$plan) {
            return [
                'success' => false,
                'error' => 'Plan not found'
            ];
        }

        return [
            'success' => true,
            'data' => [
                'id' => $plan->id(),
                'name' => $plan->name(),
                'price' => $plan->price()->amountInCents(),
                'price_formatted' => $plan->price()->formatted(),
                'orders_limit' => $plan->ordersLimit(),
                'billing_period' => $plan->billingPeriod()->value,
                'extra_order_charge' => $plan->extraOrderCharge()->amountInCents(),
            ]
        ];
    }
}