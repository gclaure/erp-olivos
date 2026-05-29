<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasUuids, \App\Traits\BranchScoped;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'inventory_value',
        'average_cost',
        'version',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'inventory_value' => 'decimal:2',
        'average_cost' => 'decimal:4',
        'version' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
