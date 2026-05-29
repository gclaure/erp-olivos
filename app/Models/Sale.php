<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|Sale whereYear(string $column, mixed $value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sale whereMonth(string $column, mixed $value)
 */
class Sale extends Model
{
    use HasUuids, \App\Traits\BranchScoped;

    public string $branch_column = 'branch_id';

    protected $fillable = [
        'client_id',
        'warehouse_id',
        'user_id',
        'branch_id',
        'date',
        'status',
        'notes',
        'total',
        'subtotal',
        'total_gross',
        'total_discount',
        'total_tax',
        'total_cost',
        'total_profit',
        'items_count',
        'total_quantity',
        'is_loss',
        'cost_method',
        'currency_code',
        'exchange_rate',
        'global_discount',
        'total_payment',
        'number',
        'year',
        'request_id',
        'is_active',
        'reason_cancel',
        'created_by',
        'updated_by',
        'cancelled_by',
        'cancelled_at',
        'delivered_by',
        'payment_type',
        'credit_days',
        'due_date',
        'balance',
    ];

    protected $casts = [
        'date' => 'date',
        'total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_gross' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_cost' => 'decimal:6',
        'total_profit' => 'decimal:6',
        'total_quantity' => 'decimal:4',
        'is_loss' => 'boolean',
        'exchange_rate' => 'decimal:4',
        'global_discount' => 'decimal:4',
        'total_payment' => 'decimal:2',
        'number' => 'integer',
        'year' => 'integer',
        'is_active' => 'boolean',
        'cancelled_at' => 'datetime',
        'balance' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }



    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function kardexes(): MorphMany
    {
        return $this->morphMany(Kardex::class, 'recordable');
    }

    /**
     * Get the formatted sequence number (e.g., 000001)
     */
    public function getFormattedNumberAttribute(): string
    {
        return str_pad((string)($this->number ?? 0), 6, '0', STR_PAD_LEFT);
    }

    public function accountReceivable(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AccountReceivable::class);
    }


}
