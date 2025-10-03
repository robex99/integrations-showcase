<?php

/**
 * Example API Routes for Laravel Integration
 * 
 * This file demonstrates how to integrate the Clean Architecture
 * use cases with Laravel routing system.
 * 
 * In a real Laravel application, this would go in routes/api.php
 * 
 * Usage:
 * POST   /api/subscriptions              - Create new subscription
 * PUT    /api/subscriptions/plan         - Change plan
 * PUT    /api/subscriptions/card         - Update credit card
 * DELETE /api/subscriptions              - Cancel subscription
 * GET    /api/plans                      - List all active plans
 * GET    /api/plans/{id}                 - Get specific plan
 * GET    /api/users/{userId}/invoices    - Get user invoices
 * GET    /api/invoices/{id}              - Get specific invoice
 */

use PaymentIntegrations\Presentation\Http\Controllers\InvoiceController;
use PaymentIntegrations\Presentation\Http\Controllers\PlanController;
use PaymentIntegrations\Presentation\Http\Controllers\SubscriptionController;

// ============================================================================
// SUBSCRIPTION ROUTES
// ============================================================================

/**
 * Create a new subscription
 * 
 * @method POST
 * @route /api/subscriptions
 * 
 * Request Body:
 * {
 *   "user_id": 123,
 *   "plan_id": "1",
 *   "card_token": "tok_abc123",
 *   "card_number": "4111111111111111",
 *   "cardholder_name": "John Doe",
 *   "cpf_cnpj": "62887357018",
 *   "expiry_month": 12,
 *   "expiry_year": 2025,
 *   "cvv": "123"
 * }
 * 
 * Response (Success):
 * {
 *   "success": true,
 *   "data": {
 *     "subscription_id": "sub_abc123"
 *   },
 *   "message": "Subscription created successfully"
 * }
 * 
 * Response (Error):
 * {
 *   "success": false,
 *   "error": "Saldo insuficiente"
 * }
 */
Route::post('/subscriptions', [SubscriptionController::class, 'store'])
    ->name('subscriptions.store');

/**
 * Change subscription plan
 * 
 * @method PUT
 * @route /api/subscriptions/plan
 * 
 * Request Body:
 * {
 *   "user_id": 123,
 *   "new_plan_id": "3",
 *   "card_token": "tok_new_card",  // Optional - only if changing card too
 *   "card_number": "5555555555554444",
 *   "cardholder_name": "John Doe",
 *   "cpf_cnpj": "62887357018",
 *   "expiry_month": 6,
 *   "expiry_year": 2026,
 *   "cvv": "456"
 * }
 * 
 * Response (Immediate):
 * {
 *   "success": true,
 *   "data": {
 *     "immediate": true,
 *     "message": "Plan changed immediately"
 *   }
 * }
 * 
 * Response (Scheduled):
 * {
 *   "success": true,
 *   "data": {
 *     "immediate": false,
 *     "message": "Plan change scheduled for next billing cycle"
 *   }
 * }
 */
Route::put('/subscriptions/plan', [SubscriptionController::class, 'changePlan'])
    ->name('subscriptions.change-plan');

/**
 * Update credit card
 * 
 * @method PUT
 * @route /api/subscriptions/card
 * 
 * Request Body:
 * {
 *   "user_id": 123,
 *   "card_token": "tok_new_card",
 *   "card_number": "5555555555554444",
 *   "cardholder_name": "John Doe",
 *   "cpf_cnpj": "62887357018",
 *   "expiry_month": 6,
 *   "expiry_year": 2026,
 *   "cvv": "456"
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "Credit card updated successfully"
 * }
 */
Route::put('/subscriptions/card', [SubscriptionController::class, 'updateCard'])
    ->name('subscriptions.update-card');

/**
 * Cancel subscription
 * 
 * @method DELETE
 * @route /api/subscriptions
 * 
 * Request Body:
 * {
 *   "user_id": 123,
 *   "reason": "Customer requested cancellation"
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "Subscription cancelled successfully"
 * }
 */
Route::delete('/subscriptions', [SubscriptionController::class, 'cancel'])
    ->name('subscriptions.cancel');

// ============================================================================
// PLAN ROUTES
// ============================================================================

/**
 * List all active plans
 * 
 * @method GET
 * @route /api/plans
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": [
 *     {
 *       "id": "1",
 *       "name": "Basic Plan",
 *       "price": 10000,
 *       "price_formatted": "R$ 100,00",
 *       "orders_limit": 100,
 *       "billing_period": "monthly",
 *       "extra_order_charge": 50
 *     },
 *     ...
 *   ]
 * }
 */
Route::get('/plans', [PlanController::class, 'index'])
    ->name('plans.index');

/**
 * Get specific plan details
 * 
 * @method GET
 * @route /api/plans/{id}
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "id": "1",
 *     "name": "Basic Plan",
 *     "price": 10000,
 *     "price_formatted": "R$ 100,00",
 *     "orders_limit": 100,
 *     "billing_period": "monthly",
 *     "extra_order_charge": 50
 *   }
 * }
 */
Route::get('/plans/{id}', [PlanController::class, 'show'])
    ->name('plans.show');

// ============================================================================
// INVOICE ROUTES
// ============================================================================

/**
 * List user invoices
 * 
 * @method GET
 * @route /api/users/{userId}/invoices
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": [
 *     {
 *       "id": "inv_abc123",
 *       "amount": 10000,
 *       "amount_formatted": "R$ 100,00",
 *       "status": "approved",
 *       "transaction_id": "pay_xyz789",
 *       "card_last_digits": "1111",
 *       "card_brand": "Visa",
 *       "orders_count": 50,
 *       "status_reason": "Pagamento aprovado",
 *       "created_at": "2025-01-01 10:00:00"
 *     },
 *     ...
 *   ]
 * }
 */
Route::get('/users/{userId}/invoices', [InvoiceController::class, 'index'])
    ->name('invoices.user-index');

/**
 * Get specific invoice details
 * 
 * @method GET
 * @route /api/invoices/{id}
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "id": "inv_abc123",
 *     "user_id": 123,
 *     "plan_id": "1",
 *     "amount": 10000,
 *     "amount_formatted": "R$ 100,00",
 *     "status": "approved",
 *     "transaction_id": "pay_xyz789",
 *     "card_last_digits": "1111",
 *     "card_brand": "Visa",
 *     "orders_count": 50,
 *     "status_reason": "Pagamento aprovado",
 *     "created_at": "2025-01-01 10:00:00",
 *     "updated_at": "2025-01-01 10:00:05"
 *   }
 * }
 */
Route::get('/invoices/{id}', [InvoiceController::class, 'show'])
    ->name('invoices.show');

// ============================================================================
// USAGE IN LARAVEL
// ============================================================================

/**
 * To use these routes in a real Laravel application:
 * 
 * 1. Add to routes/api.php:
 *    require __DIR__ . '/../../examples/routes.php';
 * 
 * 2. Or copy the content to routes/api.php with prefix:
 *    Route::prefix('api/v1')->group(function () {
 *        // paste routes here
 *    });
 * 
 * 3. Make sure controllers are registered in service provider
 * 
 * 4. Configure dependency injection in AppServiceProvider:
 * 
 *    $this->app->bind(
 *        PaymentGatewayInterface::class,
 *        MercadoPagoGateway::class
 *    );
 * 
 *    $this->app->bind(
 *        SubscriptionRepositoryInterface::class,
 *        EloquentSubscriptionRepository::class
 *    );
 */