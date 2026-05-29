<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountPayable extends Model
{
    use HasUuids;

    protected $table = 'accounts_payable';

    protected $fillable = [
        'purchase_id',
        'provider_id',
        'total_amount',
        'paid_amount',
        'balance',
        'due_date',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:4',
        'paid_amount'  => 'decimal:4',
        'balance'      => 'decimal:4',
        'due_date'     => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AccountPayablePayment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }
}
