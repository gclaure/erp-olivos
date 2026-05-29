<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountReceivablePayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'account_receivable_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'amount'       => 'decimal:4',
        'payment_date' => 'date',
    ];

    public function accountReceivable(): BelongsTo
    {
        return $this->belongsTo(AccountReceivable::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
