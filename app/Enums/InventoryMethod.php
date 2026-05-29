<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryMethod: string
{
    case WEIGHTED_AVERAGE = 'PROMEDIO_PONDERADO';
    case FIFO = 'PEPS';

    public function label(): string
    {
        return match ($this) {
            self::WEIGHTED_AVERAGE => 'Promedio Ponderado',
            self::FIFO => 'PEPS (Primeras Entradas, Primeras Salidas)',
        };
    }
}
