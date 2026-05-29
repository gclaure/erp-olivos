<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Kardex;
use App\Models\Stock;

interface InventoryStrategy
{
    /**
     * Procesa un movimiento de entrada al inventario.
     */
    public function recordEntry(array $data, Stock $stock): Kardex;

    /**
     * Procesa un movimiento de salida del inventario.
     */
    public function recordExit(array $data, Stock $stock): Kardex;

    /**
     * Procesa la reversión de un movimiento.
     */
    public function reverse(Kardex $kardex, Stock $stock): void;
}
