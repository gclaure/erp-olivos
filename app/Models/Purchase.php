<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    use HasUuids, \App\Traits\BranchScoped;

    protected $fillable = [
        'purchase_number',
        'provider_id',
        'warehouse_id',
        'user_id',
        'date',
        'due_date',
        'voucher_type',
        'total',
        'down_payment',
        'status',
        'notes',
        'payment_type',
        'receipt_path',
    ];

    protected $casts = [
        'total'        => 'decimal:4',
        'down_payment' => 'decimal:4',
        'date'         => 'date',
        'due_date'     => 'date',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
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
        return $this->hasMany(PurchaseDetail::class);
    }

    public function kardexes(): MorphMany
    {
        return $this->morphMany(Kardex::class, 'recordable');
    }

    public function accountPayable(): HasOne
    {
        return $this->hasOne(AccountPayable::class);
    }
}
