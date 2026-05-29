<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Kardex extends Model
{
    use HasUuids, \App\Traits\BranchScoped;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'point_of_sale_id',
        'user_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'balance_quantity',
        'balance_total_cost',
        'avg_cost',
        'recordable_id',
        'recordable_type',
        'notes',
        'operation_uuid',
        'source_operation_uuid',
        'movement_type',
        'reference_type',
        'reference_id',
        'movement_date',
        'accounting_date',
        'processed_at',
        'queued_at',
        'balance_after',
        'value_after',
        'remaining_quantity',
        'is_fifo_layer',
        'expiration_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'balance_quantity' => 'decimal:4',
        'balance_total_cost' => 'decimal:2',
        'avg_cost' => 'decimal:4',
        'movement_type' => \App\Enums\KardexMovementType::class,
        'movement_date' => 'datetime',
        'accounting_date' => 'datetime',
        'processed_at' => 'datetime',
        'queued_at' => 'datetime',
        'balance_after' => 'decimal:4',
        'value_after' => 'decimal:2',
        'remaining_quantity' => 'decimal:4',
        'is_fifo_layer' => 'boolean',
        'expiration_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recordable(): MorphTo
    {
        return $this->morphTo();
    }

    public function fifoConsumptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KardexFifoConsumption::class, 'output_kardex_id');
    }

    public function fifoEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KardexFifoConsumption::class, 'input_kardex_id');
    }

    /**
     * Regla de Inmutabilidad ERP: El Kardex es un libro mayor append-only.
     */
    protected static function booted(): void
    {
        static::updating(function ($model) {
            // Permitir actualizaciones solo si son campos técnicos específicos o si se está revirtiendo?
            // User feedback: "Kardex nunca se actualiza. Solo: reversión, compensación, append-only."
            if (!$model->isDirty('status')) { // Example: if we added status later
                throw new \RuntimeException('El registro de Kardex es inmutable y no puede ser modificado.');
            }
        });

        static::deleting(function ($model) {
            throw new \RuntimeException('El registro de Kardex es inmutable y no puede ser eliminado.');
        });
    }
}
