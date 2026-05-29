<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KardexFifoConsumption extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'input_kardex_id',
        'output_kardex_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'tenant_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inputKardex(): BelongsTo
    {
        return $this->belongsTo(Kardex::class, 'input_kardex_id');
    }

    public function outputKardex(): BelongsTo
    {
        return $this->belongsTo(Kardex::class, 'output_kardex_id');
    }
}
