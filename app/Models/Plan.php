<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasUuids;

    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function ($builder) {
            $builder->orderBy('price', 'asc');
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'price',
        'duration_days',
        'billing_period',
        'is_active',
        'features',
    ];

    public const PERIOD_MONTHLY = 'MONTHLY';
    public const PERIOD_YEARLY = 'YEARLY';

    protected $casts = [
        'price' => 'string',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getFeature(string $key, mixed $default = null): mixed
    {
        return data_get($this->features, $key, $default);
    }

    public function getMaxUsers(): int
    {
        return (int) $this->getFeature('max_users', 0);
    }

    public function getMaxBranches(): int
    {
        return (int) $this->getFeature('max_branches', 0);
    }

    public function getMaxProducts(): int
    {
        return (int) $this->getFeature('max_products', 0);
    }

    public function getMaxWarehouses(): int
    {
        return (int) $this->getFeature('max_warehouses', 0);
    }

    public function getMaxPointsOfSale(): int
    {
        return (int) $this->getFeature('max_pos', 0);
    }

    public function isUnlimited(string $feature): bool
    {
        $value = $this->getFeature($feature, 0);

        return $value === -1 || $value === null;
    }
}
