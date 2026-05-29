<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Product;
use App\Models\Provider;
use App\Services\PurchaseService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class PurchaseImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
        private readonly string $warehouseId,
        private readonly string $userId
    ) {}

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            throw new \Exception('La plantilla está vacía. Debe agregar al menos una compra.');
        }

        $firstRow = $rows->first()->toArray();
        $requiredHeaders = ['codigo_producto', 'fecha_compra', 'cantidad', 'costo_unitario', 'utilidad'];
        $missingHeaders = array_diff($requiredHeaders, array_keys($firstRow));
        
        if (count($missingHeaders) > 0) {
            throw new \Exception('La plantilla no es válida. Faltan las siguientes columnas: ' . implode(', ', $missingHeaders));
        }

        // Validar códigos duplicados dentro del archivo Excel
        $seenCodes = [];
        foreach ($rows as $index => $row) {
            $code = trim((string)($row['codigo_producto'] ?? ''));
            if ($code === '') {
                continue;
            }

            // El índice de Collection comienza en 0, la fila 1 es la cabecera en Excel,
            // por tanto la fila de datos real en Excel es $index + 2.
            $excelRow = $index + 2;

            if (isset($seenCodes[$code])) {
                throw new \Exception(
                    sprintf(
                        'Se encontraron códigos de producto duplicados en el archivo Excel. El código "%s" aparece en las filas %d y %d.',
                        $code,
                        $seenCodes[$code],
                        $excelRow
                    )
                );
            }
            $seenCodes[$code] = $excelRow;
        }

        $genericProvider = Provider::where('name', 'PROVEEDOR GENERAL')->first();
        if (!$genericProvider) {
            $genericProvider = Provider::firstOrCreate(
                ['name' => 'PROVEEDOR GENERAL'], [ 'is_active' => true ]
            );
        }

        // Agrupar filas por fecha_compra
        $groupedByDate = $rows->groupBy(function ($row) {
            try {
                if (is_numeric($row['fecha_compra'])) {
                    return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['fecha_compra'])->format('Y-m-d');
                }
                return Carbon::parse($row['fecha_compra'])->format('Y-m-d');
            } catch (\Exception $e) {
                return date('Y-m-d');
            }
        });

        $errors = [];

        DB::transaction(function () use ($groupedByDate, $genericProvider, &$errors) {
            foreach ($groupedByDate as $date => $purchaseRows) {
                
                $details = [];
                $totalPurchase = BigDecimal::zero();

                foreach ($purchaseRows as $index => $row) {
                    $rowNum = $index + 2;

                    if (empty($row['codigo_producto'])) {
                        continue;
                    }

                    $product = Product::where('code', $row['codigo_producto'])->first();
                    if (!$product) {
                        $errors[] = "Fila {$rowNum}: El producto '{$row['codigo_producto']}' no existe. Fila omitida.";
                        continue;
                    }

                    // Validación estricta de números
                    if (!is_numeric($row['cantidad']) || (float)$row['cantidad'] <= 0) {
                        $errors[] = "Fila {$rowNum}: La cantidad debe ser un número válido mayor a 0. Fila omitida.";
                        continue;
                    }

                    if (!is_numeric($row['costo_unitario']) || (float)$row['costo_unitario'] <= 0) {
                        $errors[] = "Fila {$rowNum}: El costo unitario debe ser un número válido mayor a 0. Fila omitida.";
                        continue;
                    }

                    if (isset($row['utilidad']) && !is_numeric($row['utilidad'])) {
                         $errors[] = "Fila {$rowNum}: La utilidad debe ser un número (porcentaje). Fila omitida.";
                         continue;
                    }

                    $qty = BigDecimal::of($row['cantidad'])->toScale(4, RoundingMode::HALF_UP);
                    $unitPrice = BigDecimal::of($row['costo_unitario'])->toScale(4, RoundingMode::HALF_UP);
                    $utilityPercent = BigDecimal::of(isset($row['utilidad']) && is_numeric($row['utilidad']) ? $row['utilidad'] : 0);

                    $subtotal = $qty->multipliedBy($unitPrice);
                    $totalPurchase = $totalPurchase->plus($subtotal);

                    // Cálculo matemático: Ganancia = Costo * (Utilidad / 100) -> Nuevo Precio = Costo + Ganancia
                    // Asegurar que Utilidad sea porcentaje o decimal (e.g. 23 significa 23%, 0.23 será el factor).
                    $profitFactor = $utilityPercent->dividedBy(100, 4, RoundingMode::HALF_UP);
                    
                    $newSalePrice = $unitPrice->plus($unitPrice->multipliedBy($profitFactor));

                    // Actualizar el precio de venta en el catálogo
                    $product->price = (string) $newSalePrice->toScale(2, RoundingMode::HALF_UP);
                    $product->save();

                    $details[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'quantity' => (float)(string) $qty->toScale(4, RoundingMode::HALF_UP),
                        'unit_price' => (float)(string) $unitPrice->toScale(4, RoundingMode::HALF_UP),
                        'subtotal' => (float)(string) $subtotal->toScale(2, RoundingMode::HALF_UP),
                        'expiration_date' => isset($row['fecha_vencimiento']) ? (is_numeric($row['fecha_vencimiento']) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['fecha_vencimiento'])->format('Y-m-d') : Carbon::parse($row['fecha_vencimiento'])->format('Y-m-d')) : null,
                    ];
                }

                if (count($details) > 0) {
                    $purchaseData = [
                        'provider_id' => $genericProvider->id,
                        'warehouse_id' => $this->warehouseId,
                        'user_id' => $this->userId,
                        'date' => $date,
                        'notes' => 'Importación masiva.',
                        'total' => (float)(string) $totalPurchase->toScale(2, RoundingMode::HALF_UP),
                        'status' => 'completada',
                        'payment_type' => 'contado',
                        'voucher_type' => 'sin_factura',
                    ];

                    $this->purchaseService->createPurchase($purchaseData, $details);
                }
            }
        });

        if (!empty($errors)) {
            $errorSummary = implode('<br>', $errors);
            throw new \Exception("Importación parcialmente completada con las siguientes observaciones:<br>" . $errorSummary);
        }
    }
}
