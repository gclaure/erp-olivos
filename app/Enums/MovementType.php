<?php

declare(strict_types=1);

namespace App\Enums;

enum MovementType: string
{
    case ENTRY = 'entrada';
    case EXIT = 'salida';

    public function label(): string
    {
        return match ($this) {
            self::ENTRY => 'Entrada',
            self::EXIT => 'Salida',
        };
    }
}
