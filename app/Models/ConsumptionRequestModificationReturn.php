<?php

declare(strict_types=1);

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumptionRequestModificationReturn extends Model
{
    use HasUuids;

    protected $fillable = [
        'consumption_request_modification_id',
        'returned_quantity',
        'returned_by_user_id',
        'returned_at',
        'return_notes',
    ];

    protected $casts = [
        'returned_quantity' => 'decimal:4',
        'returned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new DomainException('Los registros de devolución física son inmutables y no pueden editarse.');
        });

        static::deleting(function (): void {
            throw new DomainException('Los registros de devolución física son inmutables y no pueden eliminarse.');
        });
    }

    public function modification(): BelongsTo
    {
        return $this->belongsTo(ConsumptionRequestModification::class, 'consumption_request_modification_id');
    }

    public function returnedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by_user_id');
    }
}
