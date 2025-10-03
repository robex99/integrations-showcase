<?php

declare(strict_types=1);

namespace PaymentIntegrations\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class EloquentPlan extends Model
{
    protected $table = 'admin_plans';

    protected $fillable = [
        'name',
        'price',
        'orders',
        'billing',
        'status',
    ];

    protected $casts = [
        'price' => 'integer',
        'orders' => 'integer',
    ];
}
