<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'ACTIVE';
    case EXPIRED = 'EXPIRED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activa',
            self::EXPIRED => 'Expirada',
            self::CANCELLED => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'emerald',
            self::EXPIRED => 'red',
            self::CANCELLED => 'gray',
        };
    }
}
