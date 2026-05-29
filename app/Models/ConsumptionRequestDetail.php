<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumptionRequestDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'consumption_request_id',
        'product_id',
        'quantity_requested',
        'quantity_delivered',
        'quantity_received',
        'observation',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:4',
        'quantity_delivered' => 'decimal:4',
        'quantity_received' => 'decimal:4',
    ];

    public function consumptionRequest(): BelongsTo
    {
        return $this->belongsTo(ConsumptionRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
