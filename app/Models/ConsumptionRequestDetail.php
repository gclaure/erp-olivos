<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsumptionDetailFulfillmentEnum;
use App\Enums\ConsumptionModificationStatusEnum;
use DomainException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConsumptionRequestDetail extends Model
{
    use HasUuids;

    protected $attributes = [
        'fulfillment_status' => 'normal',
    ];

    protected $fillable = [
        'consumption_request_id',
        'product_id',
        'quantity_requested',
        'quantity_delivered',
        'quantity_received',
        'fulfillment_status',
        'observation',
        'receive_observation',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:4',
        'quantity_delivered' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'fulfillment_status' => ConsumptionDetailFulfillmentEnum::class,
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $detail): void {
            if ($detail->modifications()->exists() || $detail->purchaseOrderDetails()->exists()) {
                throw new DomainException('No se puede eliminar físicamente un detalle con modificaciones o líneas de compra asociadas.');
            }
        });
    }

    public function consumptionRequest(): BelongsTo
    {
        return $this->belongsTo(ConsumptionRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function modifications(): HasMany
    {
        return $this->hasMany(ConsumptionRequestModification::class, 'consumption_request_detail_id')
            ->orderByDesc('created_at');
    }

    public function openModification(): HasOne
    {
        return $this->hasOne(ConsumptionRequestModification::class, 'consumption_request_detail_id')
            ->whereIn('status', ConsumptionModificationStatusEnum::openValues());
    }

    public function purchaseOrderDetails(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'consumption_request_detail_id');
    }
}
