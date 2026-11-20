<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderComplementProviderEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderComplement extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'description',
        'provider_type',
    ];

    protected $casts = [
        'provider_type' => OrderComplementProviderEnum::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}

