<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderDeliveryModeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'number',
        'customer_id',
        'branch_id',
        'user_id',
        'registered_at',
        'delivery_date',
        'delivery_time',
        'delivery_mode',
        'delivery_address',
        'status',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancellation_reason',
        'subtotal',
        'discount',
        'total',
        'paid_amount',
        'balance_due',
        'payment_notes',
        'general_notes',
    ];

    protected $casts = [
        'number' => 'integer',
        'registered_at' => 'date',
        'delivery_date' => 'date',
        'delivery_mode' => OrderDeliveryModeEnum::class,
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected function formattedNumber(): Attribute
    {
        return Attribute::make(
            get: fn (): string => str_pad((string) ($this->number ?? 0), 5, '0', STR_PAD_LEFT),
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('sort_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OrderAttachment::class);
    }

    public function complements(): HasMany
    {
        return $this->hasMany(OrderComplement::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class)->orderBy('paid_at');
    }

    /**
     * Scope de búsqueda por número de reserva (#00001 o 1), nombre o celular del cliente.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $clean = trim($term);
        $numericSearch = ltrim(str_replace('#', '', $clean), '0');

        return $query->where(function (Builder $q) use ($clean, $numericSearch): void {
            if ($numericSearch !== '' && ctype_digit($numericSearch)) {
                $q->orWhere('number', (int) $numericSearch);
            }

            $q->orWhereHas('customer', function (Builder $customerQuery) use ($clean): void {
                $customerQuery->where('full_name', 'ilike', "%{$clean}%")
                    ->orWhere('phone', 'ilike', "%{$clean}%");
            });
        });
    }

    /**
     * Scope para filtrar por rango de fecha de registro.
     */
    public function scopeFilterByRegisteredDate(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from !== null && $from !== '') {
            $query->whereDate('registered_at', '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->whereDate('registered_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope para filtrar por rango de fecha programada de entrega.
     */
    /**
     * Scope para filtrar por rango de fecha programada de entrega.
     */
    public function scopeFilterByDeliveryDate(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from !== null && $from !== '') {
            $query->whereDate('delivery_date', '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->whereDate('delivery_date', '<=', $to);
        }

        return $query;
    }

    public function scopeFilterByStatus(Builder $query, ?string $status): Builder
    {
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        return $query;
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->where('status', 'registrado');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'anulado');
    }

    public function isCancelled(): bool
    {
        return $this->status === 'anulado';
    }

    public function isRegistered(): bool
    {
        return $this->status === 'registrado';
    }
}


