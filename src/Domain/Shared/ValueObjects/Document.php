<?php

declare(strict_types=1);

namespace PaymentIntegrations\Domain\Shared\ValueObjects;

use InvalidArgumentException;

final readonly class Document
{
    private const CPF_LENGTH = 11;
    private const CNPJ_LENGTH = 14;

    private function __construct(
        private string $number,
        private DocumentType $type
    ) {
        $cleanNumber = preg_replace('/[^0-9]/', '', $number);

        if ($type === DocumentType::CPF && strlen($cleanNumber) !== self::CPF_LENGTH) {
            throw new InvalidArgumentException('Invalid CPF length');
        }

        if ($type === DocumentType::CNPJ && strlen($cleanNumber) !== self::CNPJ_LENGTH) {
            throw new InvalidArgumentException('Invalid CNPJ length');
        }
    }

    public static function fromString(string $document): self
    {
        $clean = preg_replace('/[^0-9]/', '', $document);
        $length = strlen($clean);

        $type = match ($length) {
            self::CPF_LENGTH => DocumentType::CPF,
            self::CNPJ_LENGTH => DocumentType::CNPJ,
            default => throw new InvalidArgumentException('Invalid document length')
        };

        return new self($clean, $type);
    }

    public function number(): string
    {
        return $this->number;
    }

    public function type(): DocumentType
    {
        return $this->type;
    }

    public function isCPF(): bool
    {
        return $this->type === DocumentType::CPF;
    }

    public function isCNPJ(): bool
    {
        return $this->type === DocumentType::CNPJ;
    }

    public function formatted(): string
    {
        if ($this->isCPF()) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->number);
        }

        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $this->number);
    }

    public function __toString(): string
    {
        return $this->number;
    }
}
