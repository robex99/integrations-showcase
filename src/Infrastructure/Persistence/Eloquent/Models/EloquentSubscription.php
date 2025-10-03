<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent Model - Apenas para persistência
 * Não contém lógica de negócio
 */
final class EloquentSubscription extends Model
{
    protected $table = 'admin_subscriptions';

    protected $fillable = [
        'user_id',
        'plan_id',
        'plan_price',
        'card_id',
        'status',
        'pay_status',
        'last_status',
        'type',
        'started_at',
        'due_day',
        'last_due',
        'next_due',
        'last_charge',
        'last_change',
        'customer_id',
        'first_code',
        'count_charge',
        'max_charge',
        'expire',
        'new_plan_id',
        'new_status',
        'cancel_reason',
    ];

    protected $casts = [
        'plan_price' => 'integer',
        'count_charge' => 'integer',
        'max_charge' => 'integer',
    ];
}
