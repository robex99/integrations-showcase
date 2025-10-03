# Architecture Documentation

## Overview

This project implements a Clean Architecture approach with clear separation of concerns across four main layers:

Domain (Business Logic) → Application (Use Cases) → Infrastructure (Technical Details) → Presentation (UI/API)

## Layers

### 1. Domain Layer

Location: src/Domain/

Purpose: Contains pure business logic with zero external dependencies.

Components:
- Entities: Core business objects with identity (Subscription, Plan, Invoice)
- Value Objects: Immutable objects without identity (Money, Email, BillingCycle)
- Domain Services: Business logic spanning multiple entities (ProrationCalculator)
- Repository Interfaces: Contracts for data persistence
- Exceptions: Domain-specific errors

Key Principles:
- No framework dependencies
- No infrastructure concerns
- Pure PHP with business rules only
- Testable in isolation

Example:
Value Object - Immutable and self-validating
$money = Money::fromCents(10000); // R$ 100.00
$doubled = $money->multiply(2);   // R$ 200.00

Entity - With business rules
$subscription->recordFailedPayment($now);
if ($subscription->hasReachedMaxRetries()) {
    $subscription->end();
}

### 2. Application Layer

Location: src/Application/

Purpose: Orchestrates domain objects to fulfill use cases.

Components:
- Use Cases: Application-specific business rules
- DTOs: Data transfer between layers
- Service Interfaces: Contracts for infrastructure

Key Principles:
- Depends only on Domain
- Framework-agnostic
- Transaction boundaries
- Input validation

Example:
$result = $createSubscriptionUseCase->execute(
    new CreateSubscriptionDTO(
        userId: 1,
        planId: '1',
        cardToken: 'tok_xxx',
    )
);

if ($result->success) {
    // Handle success
}

### 3. Infrastructure Layer

Location: src/Infrastructure/

Purpose: Implements technical details and external integrations.

Components:
- Payment Gateways: MercadoPago, Stripe, etc.
- Repositories: Eloquent implementations
- Notifications: Discord, Email, SMS
- Persistence: Database models

Key Principles:
- Implements Domain interfaces
- Framework-specific code here
- External API integrations
- Database operations

Example:
Adapter pattern - wraps MercadoPago API
class MercadoPagoGateway implements PaymentGatewayInterface
{
    public function processPayment(PaymentData $data): PaymentResult
    {
        $response = $this->httpClient->post('/v1/payments', $payload);
        return $response->status === 'approved'
            ? PaymentResult::success($response->id)
            : PaymentResult::failure($response->status, $response->status_detail);
    }
}

### 4. Presentation Layer

Location: src/Presentation/

Purpose: HTTP controllers, API endpoints, CLI commands.

Components:
- Controllers: Handle HTTP requests
- Requests: Validation and sanitization
- Resources: Response formatting

## Design Patterns Used

### 1. Repository Pattern
Abstracts data persistence, allowing domain to be database-agnostic.

interface SubscriptionRepositoryInterface
{
    public function findById(string $id): ?Subscription;
    public function save(Subscription $subscription): void;
}

### 2. Strategy Pattern
Payment gateways are interchangeable through interface.

interface PaymentGatewayInterface
{
    public function processPayment(PaymentData $data): PaymentResult;
}

### 3. Factory Pattern
Creating complex domain objects.

$subscription = Subscription::create(
    id: $id,
    userId: $userId,
    plan: $plan,
    cardId: $cardId,
    customerId: $customerId,
    startDate: $now
);

### 4. Value Object Pattern
Immutable objects encapsulating validation.

$email = Email::fromString('user@example.com');
$document = Document::fromString('12345678901');

## Data Flow

### Creating a Subscription

1. HTTP Request
2. Controller validates input
3. Creates DTO
4. Calls Use Case
5. Use Case orchestrates:
   - Load Plan from Repository
   - Create Invoice (Domain Entity)
   - Call Payment Gateway
   - Create Subscription (Domain Entity)
   - Save via Repository
   - Send Notification
6. Returns Result
7. Controller formats Response

### Renewing a Subscription

1. Scheduled Job
2. Finds subscriptions due for renewal
3. For each subscription:
   - Load Plan
   - Calculate usage-based charges
   - Create Invoice
   - Process Payment
   - Update Subscription cycle
   - Send Notifications

## Testing Strategy

### Unit Tests (Domain Layer)
Test business logic in isolation.

public function test_subscription_records_failed_payment()
{
    $subscription = SubscriptionFactory::create();
    $subscription->recordFailedPayment(new DateTimeImmutable());
    
    $this->assertEquals(SubscriptionStatus::PAST_DUE, $subscription->status());
    $this->assertEquals(1, $subscription->retryCount());
}

### Integration Tests (Infrastructure)
Test external integrations with mocks.

public function test_mercadopago_gateway_processes_payment()
{
    $gateway = new MercadoPagoGateway($mockedHttpClient, $mapper);
    $result = $gateway->processPayment($paymentData);
    
    $this->assertTrue($result->success);
}

### Feature Tests (End-to-End)
Test complete flows.

public function test_user_can_create_subscription()
{
    $response = $this->post('/api/subscriptions', $validData);
    
    $response->assertSuccessful();
    $this->assertDatabaseHas('admin_subscriptions', ['user_id' => 1]);
}

## Benefits of This Architecture

### 1. Testability
- Domain logic tested without database
- Use cases tested with repository mocks
- Independent of frameworks

### 2. Flexibility
- Easy to swap payment gateways
- Database-agnostic domain
- Can add new notification channels without changing core

### 3. Maintainability
- Clear separation of concerns
- Easy to locate and fix bugs
- New developers understand structure quickly

### 4. Scalability
- Domain layer is performance-optimized
- Infrastructure can be replaced
- Can extract microservices easily

## SOLID Principles Applied

### Single Responsibility
Each class has one reason to change.

### Open/Closed
Open for extension (new payment gateways) closed for modification (domain stays same).

### Liskov Substitution
Any PaymentGatewayInterface implementation can be used.

### Interface Segregation
Small, focused interfaces (NotificationServiceInterface).

### Dependency Inversion
High-level modules (Use Cases) depend on abstractions (Repositories), not concrete implementations.