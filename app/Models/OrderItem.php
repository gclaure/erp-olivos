<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'portions_or_size',
        'unit_price',
        'custom_description',
        'reference_notes',
        'observations',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderItemDetail::class)->orderBy('sort_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OrderAttachment::class);
    }

    public function complements(): HasMany
    {
        return $this->hasMany(OrderComplement::class);
    }
}

