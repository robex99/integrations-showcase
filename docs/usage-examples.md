# Usage Examples

## Setup and Configuration

### 1. Configure MercadoPago

config/payment_gateways.php
return [
    'mercadopago' => [
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'platform_id' => env('MERCADOPAGO_PLATFORM_ID'),
        'webhook_url' => env('MERCADOPAGO_WEBHOOK_URL'),
    ],
];

### 2. Configure Discord Notifications

config/notifications.php
return [
    'discord' => [
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),
    ],
];

## Use Case Examples

### Example 1: Creating a New Subscription

// Dependencies setup
$planRepository = new EloquentPlanRepository();
$subscriptionRepository = new EloquentSubscriptionRepository();
$invoiceRepository = new EloquentInvoiceRepository();
$paymentGateway = new MercadoPagoGateway($httpClient, $paymentMapper);
$notificationService = new DiscordNotificationService($webhookUrl);
$cardStorage = new EloquentCardStorageService();

// Create the use case
$createSubscription = new CreateSubscriptionUseCase(
    $planRepository,
    $subscriptionRepository,
    $invoiceRepository,
    $paymentGateway,
    $notificationService,
    $cardStorage
);

// Execute
$dto = new CreateSubscriptionDTO(
    userId: 123,
    planId: '1',
    cardToken: 'tok_abc123',
    cardNumber: '4111111111111111',
    cardholderName: 'John Doe',
    cpfCnpj: '62887357018',
    expiryMonth: 12,
    expiryYear: 2025,
    cvv: '123'
);

$result = $createSubscription->execute($dto);

if ($result->success) {
    echo "Subscription created: {$result->subscriptionId}";
} else {
    echo "Error: {$result->errorMessage}";
}

### Example 2: Renewing a Subscription (Scheduled Job)

// In a Laravel Command or Job
class RenewSubscriptionsCommand extends Command
{
    public function handle(
        RenewSubscriptionUseCase $renewSubscription,
        SubscriptionRepositoryInterface $subscriptionRepository
    ): void {
        $dueSubscriptions = $subscriptionRepository->findDueForRenewal(
            new DateTimeImmutable()
        );

        foreach ($dueSubscriptions as $subscription) {
            try {
                $result = $renewSubscription->execute($subscription->id());
                
                if ($result->success) {
                    $this->info("Renewed: {$subscription->id()}");
                } else {
                    $this->error("Failed: {$subscription->id()} - {$result->errorMessage}");
                }
            } catch (Exception $e) {
                $this->error("Error: {$e->getMessage()}");
            }
        }
    }
}

### Example 3: Changing Plan (Immediate Upgrade)

$changePlan = new ChangeSubscriptionPlanUseCase(
    $subscriptionRepository,
    $planRepository,
    $invoiceRepository,
    $paymentGateway,
    $notificationService,
    new PlanChangeEvaluator(),
    new ProrationCalculator(),
    $cardStorage
);

$dto = new ChangePlanDTO(
    userId: 123,
    newPlanId: '3'
);

$result = $changePlan->execute($dto);

if ($result->success) {
    if ($result->immediate) {
        echo "Plan changed immediately";
    } else {
        echo "Plan change scheduled: {$result->message}";
    }
}

### Example 4: Changing Credit Card

$changeCard = new ChangeCreditCardUseCase(
    $subscriptionRepository,
    $paymentGateway,
    $cardStorage
);

$dto = new ChangeCreditCardDTO(
    userId: 123,
    cardToken: 'tok_new_card',
    cardNumber: '5555555555554444',
    cardholderName: 'John Doe',
    cpfCnpj: '62887357018',
    expiryMonth: 6,
    expiryYear: 2026,
    cvv: '456'
);

$result = $changeCard->execute($dto);

if ($result->success) {
    echo "Card updated successfully";
}

### Example 5: Canceling a Subscription

$cancelSubscription = new CancelSubscriptionUseCase(
    $subscriptionRepository,
    $notificationService
);

$result = $cancelSubscription->execute(
    userId: 123,
    reason: 'Customer requested cancellation'
);

if ($result->success) {
    echo "Subscription cancelled";
}

## Domain Entity Usage

### Working with Money Value Object

use PaymentIntegrations\Domain\Shared\ValueObjects\Money;

// Create money
$price = Money::fromCents(10000); // R$ 100.00
$price = Money::fromReais(100.00);

// Operations
$doubled = $price->multiply(2);           // R$ 200.00
$discounted = $price->subtract($discount); // R$ 80.00
$total = $price->add($extra);             // R$ 120.00

// Comparisons
if ($price->isGreaterThan($minPrice)) {
    // ...
}

// Display
echo $price->formatted(); // "R$ 100,00"
echo $price->amountInCents(); // 10000
echo $price->amountInReais(); // 100.0

### Working with Document Value Object

use PaymentIntegrations\Domain\Shared\ValueObjects\Document;

// Create and validate
$document = Document::fromString('628.873.570-18');
$document = Document::fromString('62887357018');

// CNPJ example
$cnpj = Document::fromString('12.345.678/0001-90');

// Use
if ($document->isCPF()) {
    echo "Individual";
}

echo $document->formatted(); // "628.873.570-18"
echo $document->number();    // "62887357018"
echo $document->type();      // DocumentType::CPF

### Working with Subscription Entity

use PaymentIntegrations\Domain\Subscription\Entities\Subscription;

// Create new subscription
$subscription = Subscription::create(
    id: 'sub_123',
    userId: 456,
    plan: $plan,
    cardId: 'card_789',
    customerId: 'cus_abc',
    startDate: new DateTimeImmutable()
);

// Business operations
$subscription->recordSuccessfulPayment('pay_xyz', new DateTimeImmutable());
$subscription->recordFailedPayment(new DateTimeImmutable());
$subscription->renewCycle(new DateTimeImmutable());
$subscription->schedulePlanChange('plan_2');
$subscription->cancel('Customer requested');

// Queries
if ($subscription->isActive()) {
    // ...
}

if ($subscription->needsRenewal(new DateTimeImmutable())) {
    // ...
}

if ($subscription->hasReachedMaxRetries()) {
    $subscription->end();
}

### Working with Plan Entity

use PaymentIntegrations\Domain\Subscription\Entities\Plan;

// Calculate total with extra orders
$totalAmount = $plan->calculateTotalAmount($ordersCount);

if ($plan->hasExtraOrders($ordersCount)) {
    $extraOrders = $ordersCount - $plan->ordersLimit();
    $extraCharge = $plan->extraOrderCharge()->multiply($extraOrders);
}

### Proration Calculation

use PaymentIntegrations\Domain\Subscription\Services\ProrationCalculator;

$calculator = new ProrationCalculator();

$proratedAmount = $calculator->calculateProrationForPlanChange(
    subscription: $subscription,
    currentPlan: $currentPlan,
    newPlan: $newPlan,
    changeDate: new DateTimeImmutable()
);

echo $proratedAmount->formatted(); // "R$ 45,50"

## Error Handling

### Domain Exceptions

use PaymentIntegrations\Domain\Subscription\Exceptions\SubscriptionNotFoundException;
use PaymentIntegrations\Domain\Subscription\Exceptions\PlanNotFoundException;
use PaymentIntegrations\Domain\Subscription\Exceptions\InvalidPlanChangeException;

try {
    $subscription = $repository->findById('sub_123');
    if ($subscription === null) {
        throw SubscriptionNotFoundException::withId('sub_123');
    }
} catch (SubscriptionNotFoundException $e) {
    // Handle not found
    echo $e->getMessage(); // "Subscription with ID 'sub_123' not found"
}

try {
    $subscription->schedulePlanChange('new_plan');
} catch (InvalidPlanChangeException $e) {
    // Handle invalid change
    echo $e->getMessage(); // "Plan cannot be changed before 15 days since last change"
}

## Testing Your Integration

### Manual Testing Script

// test-subscription.php
require 'vendor/autoload.php';

// Setup dependencies
$config = MercadoPagoConfig::fromArray(require 'config/payment_gateways.php');
$httpClient = new MercadoPagoHttpClient($config->accessToken, $config->platformId);
$paymentMapper = new PaymentRequestMapper();
$gateway = new MercadoPagoGateway($httpClient, $paymentMapper);

// Test customer creation
$customerData = new CustomerData(
    email: 'test@example.com',
    firstName: 'Test',
    lastName: 'User',
    documentType: 'cpf',
    documentNumber: '62887357018',
    phoneAreaCode: '11',
    phoneNumber: '999999999'
);

$customerResult = $gateway->createCustomer($customerData);
echo "Customer created: {$customerResult->customerId}\n";

// Test card creation
$cardData = new CardData(
    token: 'card_token_here',
    cardNumber: '4111111111111111',
    cardholderName: 'Test User',
    expirationMonth: 12,
    expirationYear: 2025,
    securityCode: '123'
);

$cardResult = $gateway->createCard($customerResult->customerId, $cardData);
echo "Card created: {$cardResult->id}\n";

// Test payment
$paymentData = new PaymentData(
    customerId: $customerResult->customerId,
    cardToken: $cardResult->id,
    amountInCents: 10000,
    description: 'Test Payment',
    externalReference: 'test_123',
    isRecurring: false
);

$paymentResult = $gateway->processPayment($paymentData);
echo $paymentResult->success ? "Payment successful!" : "Payment failed: {$paymentResult->errorMessage}\n";