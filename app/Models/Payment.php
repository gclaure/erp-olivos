<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'subscription_cycle_id',
        'amount',
        'currency',
        'paid_at',
        'method',
        'reference',
        'status',
        'notes',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(SubscriptionCycle::class, 'subscription_cycle_id');
    }

    protected $casts = [
        'amount' => 'string',
        'status' => PaymentStatus::class,
        'method' => PaymentMethod::class,
        'paid_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'tenant_id');
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::PAID;
    }
}
