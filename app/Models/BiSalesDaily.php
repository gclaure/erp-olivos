<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiSalesDaily extends Model
{
    protected $table = 'bi_sales_daily';

    protected $fillable = [
        'date',
        'branch_id',
        'warehouse_id',
        'product_id',
        'total_quantity',
        'total_revenue',
        'total_cost',
        'total_profit',
    ];

    protected $casts = [
        'date' => 'date',
        'total_quantity' => 'decimal:4',
        'total_revenue' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_profit' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
