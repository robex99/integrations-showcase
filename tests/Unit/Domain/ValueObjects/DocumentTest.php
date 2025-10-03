<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use PaymentIntegrations\Domain\Shared\ValueObjects\Document;
use PaymentIntegrations\Domain\Shared\ValueObjects\DocumentType;
use PHPUnit\Framework\TestCase;

final class DocumentTest extends TestCase
{
    public function test_creates_cpf_from_string(): void
    {
        $document = Document::fromString('62887357018');

        $this->assertTrue($document->isCPF());
        $this->assertFalse($document->isCNPJ());
        $this->assertEquals('62887357018', $document->number());
        $this->assertEquals(DocumentType::CPF, $document->type());
    }

    public function test_creates_cpf_from_formatted_string(): void
    {
        $document = Document::fromString('628.873.570-18');

        $this->assertTrue($document->isCPF());
        $this->assertEquals('62887357018', $document->number());
    }

    public function test_creates_cnpj_from_string(): void
    {
        $document = Document::fromString('12345678000190');

        $this->assertTrue($document->isCNPJ());
        $this->assertFalse($document->isCPF());
        $this->assertEquals(DocumentType::CNPJ, $document->type());
    }

    public function test_formats_cpf(): void
    {
        $document = Document::fromString('62887357018');

        $this->assertEquals('628.873.570-18', $document->formatted());
    }

    public function test_formats_cnpj(): void
    {
        $document = Document::fromString('12345678000190');

        $this->assertEquals('12.345.678/0001-90', $document->formatted());
    }

    public function test_throws_exception_for_invalid_length(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid document length');

        Document::fromString('123456');
    }

    public function test_converts_to_string(): void
    {
        $document = Document::fromString('62887357018');

        $this->assertEquals('62887357018', (string) $document);
    }
}