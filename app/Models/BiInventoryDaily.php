<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiInventoryDaily extends Model
{
    protected $table = 'bi_inventory_daily';

    protected $fillable = [
        'date',
        'warehouse_id',
        'product_id',
        'quantity',
        'avg_cost',
        'total_value',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:4',
        'avg_cost' => 'decimal:4',
        'total_value' => 'decimal:2',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
