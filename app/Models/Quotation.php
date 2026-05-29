<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasUuids, \App\Traits\BranchScoped;

    public string $branch_column = 'branch_id';

    protected $fillable = [
        'user_id',
        'branch_id',
        'date',
        'valid_until',
        'status',
        'notes',
        'total',
        'subtotal',
        'global_discount',
        'total_payment',
        'number',
        'year',
        'delivery_address',
        'delivery_contact_name',
        'delivery_contact_phone',
        'delivery_at',
        'delivery_observations',
        'delivery_zone',
        'delivery_reference',
        'delivery_cost',
        'delivery_time_slot',
        'delivery_map_url',
        'shipping_company',
        'shipping_origin',
        'shipping_destination',
    ];

    protected $casts = [
        'date' => 'date',
        'valid_until' => 'date',
        'total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'global_discount' => 'decimal:4',
        'total_payment' => 'decimal:2',
        'number' => 'integer',
        'year' => 'integer',
        'delivery_mode' => \App\Enums\DeliveryMode::class,
        'delivery_at' => 'datetime',
        'delivery_cost' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(QuotationDetail::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the formatted sequence number (e.g., 000001)
     */
    public function getFormattedNumberAttribute(): string
    {
        return str_pad((string)($this->number ?? 0), 6, '0', STR_PAD_LEFT);
    }
}
