<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtensionType;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'plan_id',
        'next_plan_id',
        'starts_at',
        'ends_at',
        'billing_anchor_day',
        'status',
        'auto_renew',
    ];

    public const PERIOD_MONTHLY = 'MONTHLY';
    public const PERIOD_YEARLY = 'YEARLY';

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'billing_anchor_day' => 'integer',
        'auto_renew' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function nextPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'next_plan_id');
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(SubscriptionExtension::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(SubscriptionCycle::class)->orderBy('period_start', 'desc');
    }

    public function currentCycle(): ?SubscriptionCycle
    {
        return $this->cycles()->first();
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function daysRemaining(): int
    {
        if ($this->ends_at === null) {
            return PHP_INT_MAX;
        }

        return max(0, (int) now()->diffInDays($this->ends_at, false));
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        $remaining = $this->daysRemaining();

        return $remaining > 0 && $remaining <= $days;
    }

    protected function activeMonths(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->starts_at || !$this->ends_at) {
                    return 0;
                }
                $days = $this->starts_at->diffInDays($this->ends_at);
                return (int) round($days / 30);
            }
        );
    }

    protected function giftMonths(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Returns gift days converted to months (assuming 30 days/month)
                $giftDays = $this->extensions->where('type', ExtensionType::GIFT)->sum('days_added');
                return (int) floor($giftDays / 30);
            }
        );
    }
}
