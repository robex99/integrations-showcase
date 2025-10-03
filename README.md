# Payment Integrations Showcase

> Clean Architecture implementation of payment gateway integrations with a focus on subscription management.

**DISCLAIMER**
This code was originally written by me for a full-scale project and migrated here for demonstration purposes. The migration and documentation insights were generated with the assistance of AI (Claude 4.5 Sonnet) and reviewed by myself.
---

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-11.x-red)](https://laravel.com/)
[![Clean Architecture](https://img.shields.io/badge/architecture-clean-green)](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)

## Overview

This project demonstrates a production-ready implementation of payment gateway integrations following Clean Architecture principles, SOLID design patterns, and Domain-Driven Design (DDD).

### Key Features

- Clean Architecture: Clear separation between Domain, Application, Infrastructure, and Presentation layers
- SOLID Principles: Every class has a single responsibility and follows dependency inversion
- DDD: Rich domain models with value objects and aggregates
- Strategy Pattern: Easy to add new payment gateways
- Testable: 95%+ code coverage with unit and integration tests
- Production Ready: Error handling, logging, and monitoring included

### Supported Features

- Subscription creation and management
- Credit card management
- Plan upgrades/downgrades with proration
- Multi-channel notifications (Email, Discord, Webhooks)
- Invoice generation and fiscal document integration
- Usage-based billing

## Architecture
┌─────────────────────────────────────────────────────────────┐
│ Presentation Layer │
│ (Controllers, Requests, API) │
└────────────────────────┬────────────────────────────────────┘
│
┌────────────────────────▼────────────────────────────────────┐
│ Application Layer │
│ (Use Cases, DTOs) │
└────────────────────────┬────────────────────────────────────┘
│
┌────────────────────────▼────────────────────────────────────┐
│ Domain Layer │
│ (Entities, Value Objects, Domain Services) │
└────────────────────────┬────────────────────────────────────┘
│
┌────────────────────────▼────────────────────────────────────┐
│ Infrastructure Layer │
│ (Payment Gateways, Repositories, Notifications) │
└─────────────────────────────────────────────────────────────┘
## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- PostgreSQL or MySQL

### Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Running Tests

```bash
composer test
composer test:coverage
```

## Documentation

- [Architecture Decision Records](docs/architecture.md)
- [Payment Gateway Integration Guide](docs/payment-gateways.md)

## Code Quality

```bash
composer analyse        # PHPStan Level 9
composer format        # PHP CS Fixer
composer test:coverage # 95%+ coverage
```

## Project Structure

See [ARCHITECTURE.md](docs/architecture.md) for detailed explanation.

## License

MIT License - see LICENSE file for details