<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPayablePayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'account_payable_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'receipt_path',
        'user_id',
    ];

    protected $casts = [
        'amount'       => 'decimal:4',
        'payment_date' => 'date',
    ];

    public function accountPayable(): BelongsTo
    {
        return $this->belongsTo(AccountPayable::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
