<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case PAID = 'PAID';
    case PENDING = 'PENDING';
    case FAILED = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::PAID => 'Pagado',
            self::PENDING => 'Pendiente',
            self::FAILED => 'Fallido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PAID => 'emerald',
            self::PENDING => 'amber',
            self::FAILED => 'red',
        };
    }
}
