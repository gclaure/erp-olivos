<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiProductStats extends Model
{
    protected $table = 'bi_product_stats';

    protected $fillable = [
        'product_id',
        'total_revenue',
        'total_profit',
        'total_quantity_sold',
        'rotation_index',
        'abc_rank',
        'last_cost',
        'avg_cost',
        'last_sale_at',
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'total_quantity_sold' => 'decimal:4',
        'rotation_index' => 'decimal:2',
        'last_cost' => 'decimal:4',
        'avg_cost' => 'decimal:4',
        'last_sale_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
