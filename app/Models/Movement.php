<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Movement extends Model
{
    use HasUuids;

    protected $fillable = [
        'warehouse_id',
        'user_id',
        'type',
        'date',
        'reason',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

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
        return $this->hasMany(MovementDetail::class);
    }

    public function kardexes(): MorphMany
    {
        return $this->morphMany(Kardex::class, 'recordable');
    }
}
