<?php

declare(strict_types=1);

namespace App\Enums;

enum KardexMovementType: string
{
    case PURCHASE = 'PURCHASE';
    case SALE = 'SALE';
    case TRANSFER_IN = 'TRANSFER_IN';
    case TRANSFER_OUT = 'TRANSFER_OUT';
    case ADJUSTMENT_IN = 'ADJUSTMENT_IN';
    case ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';
    case INITIAL_LAYER = 'INITIAL_LAYER';
    case RETURN = 'RETURN';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Compra',
            self::SALE => 'Venta',
            self::TRANSFER_IN => 'Transferencia (Entrada)',
            self::TRANSFER_OUT => 'Transferencia (Salida)',
            self::ADJUSTMENT_IN => 'Ajuste de Entrada',
            self::ADJUSTMENT_OUT => 'Ajuste de Salida',
            self::INITIAL_LAYER => 'Capa Inicial de Inventario',
            self::RETURN => 'Devolución',
        };
    }
}
