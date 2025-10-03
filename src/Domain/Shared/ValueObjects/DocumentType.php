<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Shared\ValueObjects;

enum DocumentType: string
{
    case CPF = 'cpf';
    case CNPJ = 'cnpj';
}
