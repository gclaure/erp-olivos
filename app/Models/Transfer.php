<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Transfer extends Model
{
    use HasUuids, \App\Traits\BranchScoped;

    protected $fillable = [
        'number',
        'origin_branch_id',
        'destination_branch_id',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'user_id',
        'date',
        'status',
        'notes',
        'shipped_at',
        'received_at',
        'approved_by_id',
        'shipped_by_id',
        'received_by_id',
        'rejected_at',
        'rejected_by_id',
        'rejection_reason',
        'cancelled_at',
        'cancelled_by_id',
    ];

    protected $casts = [
        'status' => TransferStatus::class,
        'date' => 'date',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Custom scoping for transfers: show if either origin or destination branch matches.
     */
    public function scopeByBranch(\Illuminate\Database\Eloquent\Builder $builder, string $branchId): void
    {
        $builder->where(function ($q) use ($branchId) {
            $q->where('origin_branch_id', $branchId)
              ->orWhere('destination_branch_id', $branchId);
        });
    }

    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id')->withoutGlobalScopes();
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id')->withoutGlobalScopes();
    }

    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id')->withoutGlobalScopes();
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id')->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TransferHistory::class)->orderByDesc('created_at');
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransferDetail::class);
    }

    public function kardexes(): MorphMany
    {
        return $this->morphMany(Kardex::class, 'recordable');
    }
}
