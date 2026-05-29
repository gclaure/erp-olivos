<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'unit_profit',
        'profit_margin_percent',
        'discount',
        'discount_amount',
        'tax_amount',
        'tax_percent',
        'subtotal',
        'is_loss',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'unit_cost' => 'decimal:6',
        'unit_profit' => 'decimal:6',
        'profit_margin_percent' => 'decimal:4',
        'discount' => 'decimal:4',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'is_loss' => 'boolean',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
