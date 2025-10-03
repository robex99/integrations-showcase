<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\PaymentGateways\Contracts;

final readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $transactionId,
        public string $status,
        public ?string $statusDetail = null,
        public ?string $errorMessage = null
    ) {
    }

    public static function success(string $transactionId): self
    {
        return new self(
            success: true,
            transactionId: $transactionId,
            status: 'approved',
            statusDetail: null,
            errorMessage: null
        );
    }

    public static function failure(string $status, string $statusDetail, ?string $transactionId = null): self
    {
        return new self(
            success: false,
            transactionId: $transactionId ?? '',
            status: $status,
            statusDetail: $statusDetail,
            errorMessage: self::translateError($statusDetail)
        );
    }

    private static function translateError(string $statusDetail): string
    {
        return match($statusDetail) {
            'cc_rejected_bad_filled_card_number' => 'Número do cartão inválido',
            'cc_rejected_bad_filled_date' => 'Data de validade incorreta',
            'cc_rejected_bad_filled_other' => 'Dados do cartão inválidos',
            'cc_rejected_bad_filled_security_code' => 'Código de segurança inválido',
            'cc_rejected_blacklist' => 'Cartão bloqueado pelo banco emissor',
            'cc_rejected_call_for_authorize' => 'O banco emissor requer autorização do titular',
            'cc_rejected_card_disabled' => 'Cartão desativado pelo banco emissor',
            'cc_rejected_duplicated_payment' => 'Pagamento duplicado',
            'cc_rejected_high_risk' => 'Pagamento recusado por suspeita de fraude',
            'cc_rejected_insufficient_amount' => 'Saldo insuficiente',
            'cc_rejected_invalid_installments' => 'Número de parcelas inválido',
            'cc_rejected_max_attempts' => 'Limite de tentativas excedido',
            'cc_rejected_other_reason' => 'Pagamento recusado pelo banco emissor',
            default => 'Ocorreu um erro ao processar o pagamento'
        };
    }
}
