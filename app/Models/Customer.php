<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'full_name',
        'phone',
        'address',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->orderByDesc('number');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $clean = trim($term);

        return $query->where(function (Builder $q) use ($clean): void {
            $q->where('full_name', 'ilike', "%{$clean}%")
                ->orWhere('phone', 'ilike', "%{$clean}%");
        });
    }
}

