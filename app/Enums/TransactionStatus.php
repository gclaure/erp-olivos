<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pendiente';
    case COMPLETED = 'completada';
    case CANCELLED = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::COMPLETED => 'Completada',
            self::CANCELLED => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::COMPLETED => 'positive',
            self::CANCELLED => 'negative',
        };
    }
}
