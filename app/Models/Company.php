<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasUuids;

    protected $fillable = [
        'nit',
        'name',
        'business_name',
        'phone',
        'phone_secondary',
        'email',
        'logo_path',
        'status',
        'owner_user_id',
        'trial_ends_at',
        'vendedor_id',
        'receipt_type',
        'show_name',
        'slug',
        'inventory_method',
        'has_inventory_movements',
        'inventories_closed_until',
    ];

    protected $casts = [
        'status' => TenantStatus::class,
        'inventory_method' => \App\Enums\InventoryMethod::class,
        'trial_ends_at' => 'datetime',
        'show_name' => 'boolean',
        'has_inventory_movements' => 'boolean',
        'inventories_closed_until' => 'date',
    ];

    // ── Relaciones ──────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Get the asesor associated with this company.
     */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Asesor::class, 'vendedor_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }



    // ── Helpers de Estado ───────────────────────

    public function isActive(): bool
    {
        return $this->status === TenantStatus::ACTIVE;
    }

    public function isTrial(): bool
    {
        return $this->status === TenantStatus::TRIAL;
    }

    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::SUSPENDED;
    }

    public function isCancelled(): bool
    {
        return $this->status === TenantStatus::CANCELLED;
    }

    public function isOperational(): bool
    {
        return $this->isActive() || $this->isTrial();
    }

    public function currentPlan(): ?Plan
    {
        return $this->activeSubscription?->plan;
    }

    /**
     * Get the logo URL, falling back to the default logo if not set.
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->logo_path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path);
        }

        return asset('img/logo-inventory.png');
    }

    // NO SE DEBE AGREGAR NINGUN VALOR AL SLUG AUTOMATICAMENTE
    // REGLA DE ORO: ESTO LO DEBE HACER EL CLIENTE DESDE LA CONFIGURACION
    protected static function booted(): void
    {
        // El slug se deja vacío por defecto para que el cliente lo establezca manualmente
    }
}
