<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\InventoryStrategy;
use App\Enums\InventoryMethod;
use App\Enums\KardexMovementType;
use App\Models\Company;
use App\Models\Kardex;
use App\Models\Stock;
use App\Strategies\FifoStrategy;
use App\Strategies\WeightedAverageStrategy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class KardexService
{
    /**
     * Registra un movimiento en el Kardex y actualiza el stock.
     */
    public function record(
        string|KardexMovementType $type,
        string $productId,
        string $warehouseId,
        string|float $quantity,
        string|float $unitCost,
        $userId,
        string $notes,
        string $recordableType,
        $recordableId,
        ?string $pointOfSaleId = null,
        ?string $movementDate = null,
        ?string $accountingDate = null,
        ?string $operationUuid = null,
        ?string $sourceOperationUuid = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $expirationDate = null
    ): Kardex {
        $company = $this->getCompany();
        $strategy = $this->resolveStrategy($company->inventory_method);

        // Convertir string a Enum con soporte de compatibilidad legacy
        if (!($type instanceof KardexMovementType)) {
            $typeStr = strtoupper((string) $type);
            $mappedType = match ($typeStr) {
                'ENTRADA' => match ($recordableType) {
                    \App\Models\Purchase::class => KardexMovementType::PURCHASE,
                    \App\Models\Sale::class => KardexMovementType::RETURN, // Reversión de venta (Devolución)
                    \App\Models\Movement::class => KardexMovementType::ADJUSTMENT_IN, // Ajuste de entrada manual
                    default => KardexMovementType::ADJUSTMENT_IN,
                },
                'SALIDA' => match ($recordableType) {
                    \App\Models\Sale::class => KardexMovementType::SALE,
                    \App\Models\Purchase::class => KardexMovementType::RETURN, // Devolución al proveedor / Cancelación
                    \App\Models\Movement::class => KardexMovementType::ADJUSTMENT_OUT, // Ajuste de salida manual
                    default => KardexMovementType::ADJUSTMENT_OUT,
                },
                'TRANSFERENCIA_ENTRADA' => KardexMovementType::TRANSFER_IN,
                'TRANSFERENCIA_SALIDA' => KardexMovementType::TRANSFER_OUT,
                default => null,
            };

            $movementType = $mappedType ?? KardexMovementType::from($typeStr);
        } else {
            $movementType = $type;
        }

        // Preparar datos base
        $data = [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'user_id' => $userId,
            'type' => $movementType->value, // Mantener para compatibilidad
            'movement_type' => $movementType,
            'quantity' => (string) $quantity,
            'unit_cost' => (string) $unitCost,
            'recordable_type' => $recordableType,
            'recordable_id' => $recordableId,
            'notes' => $notes,
            'movement_date' => $movementDate ? \Illuminate\Support\Carbon::parse($movementDate) : now(),
            'accounting_date' => $accountingDate ? \Illuminate\Support\Carbon::parse($accountingDate) : now(),
            'expiration_date' => $expirationDate ? \Illuminate\Support\Carbon::parse($expirationDate)->toDateString() : null,
            'operation_uuid' => $operationUuid ?? (string) Str::uuid(),
            'source_operation_uuid' => $sourceOperationUuid,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'processed_at' => now(),
        ];

        // Validaciones de Integridad ERP
        $this->validateMovement($company, $data);

        // Obtener o crear el Stock Agregado (Materialized View)
        $stock = Stock::firstOrCreate(
            [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ],
            [
                'quantity' => '0',
                'inventory_value' => '0',
                'average_cost' => '0',
                'version' => 1,
            ]
        );

        // Marcar que la empresa ya tiene movimientos
        if (!$company->has_inventory_movements) {
            $company->update(['has_inventory_movements' => true]);
        }

        // Ejecutar estrategia
        if ($this->isIngreso($movementType)) {
            return $strategy->recordEntry($data, $stock);
        } else {
            return $strategy->recordExit($data, $stock);
        }
    }

    /**
     * Revierte un movimiento de Kardex.
     */
    public function reverse(string $kardexId): void
    {
        $kardex = Kardex::findOrFail($kardexId);
        $company = $this->getCompany();
        $strategy = $this->resolveStrategy($company->inventory_method);

        $stock = Stock::withoutGlobalScopes()
            ->where('product_id', $kardex->product_id)
            ->where('warehouse_id', $kardex->warehouse_id)
            ->firstOrFail();

        $strategy->reverse($kardex, $stock);
    }

    protected function resolveStrategy(InventoryMethod $method): InventoryStrategy
    {
        return match ($method) {
            InventoryMethod::WEIGHTED_AVERAGE => new WeightedAverageStrategy(),
            InventoryMethod::FIFO => new FifoStrategy(),
        };
    }

    protected function getCompany(): Company
    {
        return \App\Models\Company::first();
    }

    protected function isIngreso(KardexMovementType $type): bool
    {
        return in_array($type, [
            KardexMovementType::PURCHASE,
            KardexMovementType::ADJUSTMENT_IN,
            KardexMovementType::TRANSFER_IN,
            KardexMovementType::RETURN,
            KardexMovementType::INITIAL_LAYER,
        ]);
    }

    protected function validateMovement(Company $company, array $data): void
    {
        // 1. Validar Cierre Contable
        if ($company->inventories_closed_until && $data['accounting_date']->isBefore($company->inventories_closed_until)) {
            throw new Exception("No se pueden registrar movimientos en periodos cerrados (hasta {$company->inventories_closed_until->format('d/m/Y')}).");
        }

        // 2. Validar Política Anti-Retroactiva (opcional según configuración, pero recomendado)
        $lastMovement = Kardex::withoutGlobalScopes()
            ->where('product_id', $data['product_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->latest('movement_date')
            ->first();

        if ($lastMovement && $data['movement_date']->isBefore($lastMovement->movement_date)) {
            throw new Exception("Inconsistencia FIFO: No se permiten movimientos con fecha anterior al último movimiento registrado para este producto.");
        }
    }
}
