<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsumptionModificationStatusEnum;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsumptionRequestModification extends Model
{
    use HasUuids;

    protected $fillable = [
        'consumption_request_id',
        'consumption_request_detail_id',
        'from_product_id',
        'to_product_id',
        'original_quantity',
        'requested_quantity',
        'quantity_dispatched_before_modification',
        'quantity_prepared',
        'quantity_physically_delivered',
        'reason',
        'status',
        'requested_by_user_id',
        'resolved_by_user_id',
        'resolved_at',
        'resolution_notes',
        'purchase_resolutions',
        'cancelled_by_user_id',
        'cancelled_at',
        'cancellation_reason',
        'applied_at',
    ];

    protected $casts = [
        'original_quantity' => 'decimal:4',
        'requested_quantity' => 'decimal:4',
        'quantity_dispatched_before_modification' => 'decimal:4',
        'quantity_prepared' => 'decimal:4',
        'quantity_physically_delivered' => 'decimal:4',
        'status' => ConsumptionModificationStatusEnum::class,
        'resolved_at' => 'datetime',
        'purchase_resolutions' => 'array',
        'cancelled_at' => 'datetime',
        'applied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $modification): void {
            $originalStatus = $modification->getRawOriginal('status');
            if (is_string($originalStatus) && in_array($originalStatus, ConsumptionModificationStatusEnum::terminalValues(), true)) {
                throw new DomainException("Una modificación en estado final ({$originalStatus}) es inmutable y no puede modificarse.");
            }
        });

        static::deleting(function (): void {
            throw new DomainException('El historial de modificaciones de consumo es inmutable y no puede eliminarse.');
        });
    }

    public function consumptionRequest(): BelongsTo
    {
        return $this->belongsTo(ConsumptionRequest::class);
    }

    public function detail(): BelongsTo
    {
        return $this->belongsTo(ConsumptionRequestDetail::class, 'consumption_request_detail_id');
    }

    public function fromProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'from_product_id');
    }

    public function toProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'to_product_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ConsumptionRequestModificationReturn::class, 'consumption_request_modification_id')
            ->orderBy('returned_at')
            ->orderBy('created_at');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ConsumptionModificationStatusEnum::openValues());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ConsumptionModificationStatusEnum::Pending->value);
    }
}
