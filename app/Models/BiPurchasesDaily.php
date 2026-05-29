<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiPurchasesDaily extends Model
{
    protected $table = 'bi_purchases_daily';

    protected $fillable = [
        'date',
        'provider_id',
        'warehouse_id',
        'product_id',
        'total_quantity',
        'total_cost',
    ];

    protected $casts = [
        'date' => 'date',
        'total_quantity' => 'decimal:4',
        'total_cost' => 'decimal:2',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
