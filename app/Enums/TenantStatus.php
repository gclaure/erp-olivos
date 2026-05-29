<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatus: string
{
    case ACTIVE = 'ACTIVE';
    case TRIAL = 'TRIAL';
    case SUSPENDED = 'SUSPENDED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::TRIAL => 'Prueba',
            self::SUSPENDED => 'Suspendido',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'emerald',
            self::TRIAL => 'amber',
            self::SUSPENDED => 'red',
            self::CANCELLED => 'gray',
        };
    }
}
