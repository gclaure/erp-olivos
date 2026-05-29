<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountReceivable extends Model
{
    use HasUuids;

    protected $fillable = [
        'sale_id',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AccountReceivablePayment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'vencido')
                     ->orWhere(function($q) {
                         $q->where('status', '!=', 'pagado')
                           ->where('due_date', '<', now()->toDateString());
                     });
    }
}
