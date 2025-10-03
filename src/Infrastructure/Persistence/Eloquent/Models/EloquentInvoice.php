<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class EloquentInvoice extends Model
{
    protected $table = 'admin_invoices';

    protected $fillable = [
        'user_id',
        'plan_id',
        'card_id',
        'card_last_digits',
        'card_brand',
        'orders',
        'amount',
        'status',
        'transaction_id',
        'last_status_reason',
    ];

    protected $casts = [
        'amount' => 'integer',
        'orders' => 'integer',
    ];
}
