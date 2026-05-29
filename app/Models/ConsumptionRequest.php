<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsumptionRequest extends Model
{
    use HasUuids, BranchScoped;

    protected $fillable = [
        'warehouse_id',
        'user_id',
        'requested_by',
        'date',
        'status',
        'notes',
        'number',
        'received_by_user_id',
        'received_at',
        'dispatched_by_user_id',
        'dispatched_at',
        'approved_by_user_id',
        'approved_at',
        'observed_by_user_id',
        'observed_at',
        'observation_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'received_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'approved_at' => 'datetime',
        'observed_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function dispatchedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by_user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function observedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'observed_by_user_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ConsumptionRequestDetail::class);
    }

    /**
     * Get the formatted sequence number (e.g., 0001)
     */
    public function getFormattedNumberAttribute(): string
    {
        return str_pad((string)($this->number ?? 0), 4, '0', STR_PAD_LEFT);
    }
}
