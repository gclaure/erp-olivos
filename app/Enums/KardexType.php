<?php

declare(strict_types=1);

namespace App\Enums;

enum KardexType: string
{
    case PURCHASE = 'compra';
    case SALE = 'venta';
    case ENTRY = 'entrada';
    case EXIT = 'salida';
    case TRANSFER_IN = 'transferencia_entrada';
    case TRANSFER_OUT = 'transferencia_salida';
    case ANNULATION = 'anulacion';
    case LOSS = 'perdida';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Compra',
            self::SALE => 'Venta',
            self::ENTRY => 'Entrada',
            self::EXIT => 'Salida',
            self::TRANSFER_IN => 'Transferencia (Entrada)',
            self::TRANSFER_OUT => 'Transferencia (Salida)',
            self::ANNULATION => 'Anulación',
            self::LOSS => 'Pérdida',
        };
    }

    public function isEntry(): bool
    {
        return in_array($this, [self::PURCHASE, self::ENTRY, self::TRANSFER_IN]);
    }

    public function isExit(): bool
    {
        return in_array($this, [self::SALE, self::EXIT, self::TRANSFER_OUT, self::ANNULATION, self::LOSS]);
    }
}
