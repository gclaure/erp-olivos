<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'transfer_id',
        'product_id',
        'quantity_requested',
        'quantity_sent',
        'quantity_received',
        'cost',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:4',
        'quantity_sent' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'cost' => 'decimal:4',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
