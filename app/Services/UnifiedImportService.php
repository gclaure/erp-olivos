<?php

declare(strict_types=1);

namespace App\Services;

use App\Imports\UnifiedProductImport;
use App\Models\Category;
use App\Models\ImportLog;
use App\Models\ImportLogDetail;
use App\Models\Product;
use App\Models\Provider;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\ProductService;
use App\Services\PurchaseService;
use Exception;

readonly class UnifiedImportService
{
    public function __construct(
        private PurchaseService $purchaseService,
        private ProductService $productService
    ) {}

    /**
     * Preview and validate the import file, checking for idempotency and business rules.
     */
    public function preview(string $filePath, string $userId): array
    {
        $import = new UnifiedProductImport();
        $sheets = Excel::toArray($import, $filePath, 'public');
        
        $rawRows = $sheets[0] ?? []; // Extract the first sheet's rows
        
        if (empty($rawRows)) {
            throw new Exception('El archivo está vacío o no tiene el formato correcto.');
        }

        // Limit to 1000 rows
        if (count($rawRows) > 1000) {
            throw new Exception('El archivo supera el límite de 1000 filas. Por favor, fragmente el documento para evitar problemas de memoria.');
        }

        return $this->processValidation($rawRows);
    }

    private function processValidation(array $rawRows): array
    {
        $errors = [];
        $validGrouped = [];
        $indexMap = []; // To keep track of original rows for error reporting

        $requiredHeaders = ['codigo_producto', 'descripcion', 'unidad_de_medida', 'categoria', 'fecha_compra', 'cantidad', 'costo_unitario', 'tiene_vencimiento', 'unidades_por_empaque', 'nombre_empaque'];

        // 2. Normalize and Group
        $rowIndex = 2; // +1 for 0-index, +1 for header
        foreach ($rawRows as $row) {
            $excelRow = $rowIndex++;
            
            // Check empty row
            if (empty($row['descripcion'])) {
                continue;
            }

            // Normalization
            $descOriginal = (string) $row['descripcion'];
            $cleanDesc = $this->normalizeString($descOriginal);
            $cleanCode = $this->normalizeString((string)($row['codigo_producto'] ?? ''));
            $cleanCat = mb_strtoupper($this->normalizeString((string)($row['categoria'] ?? 'GENERAL')));
            $cleanUom = mb_strtoupper($this->normalizeString((string)($row['unidad_de_medida'] ?? 'UND')));

            $date = $this->parseDate($row['fecha_compra'] ?? null);
            $qty = (float)($row['cantidad'] ?? 0);
            $cost = (float)($row['costo_unitario'] ?? 0);
            $hasExpiration = strtoupper(trim((string)($row['tiene_vencimiento'] ?? 'NO'))) === 'SI';
            $unitsPerPackage = (float)($row['unidades_por_empaque'] ?? 1);
            $packageName = (string)($row['nombre_empaque'] ?? '');

            // Row-level validations
            $rowErrors = [];
            if ($qty <= 0) $rowErrors[] = "Cantidad debe ser estrictamente mayor a 0.";
            if (!$date) {
                $rowErrors[] = "Fecha de compra inválida.";
            } elseif ($date->isFuture()) {
                $rowErrors[] = "La fecha de compra no puede estar en el futuro respecto a la zona horaria del servidor.";
            }

            if (count($rowErrors) > 0) {
                $errors[] = [
                    'row' => $excelRow,
                    'descripcion' => $descOriginal,
                    'messages' => $rowErrors
                ];
                continue;
            }

            // Grouping key
            $groupKey = $cleanCode !== '' ? 'code_' . $cleanCode : 'desc_' . mb_strtolower($cleanDesc);

            if (!isset($validGrouped[$groupKey])) {
                $validGrouped[$groupKey] = [
                    'original_row' => $excelRow,
                    'codigo_producto' => $cleanCode,
                    'descripcion' => $descOriginal,
                    'clean_desc' => $cleanDesc,
                    'categoria' => $cleanCat,
                    'unidad_de_medida' => $cleanUom,
                    'fecha_compra' => $date->format('Y-m-d'),
                    'cantidad' => $qty,
                    'costo_unitario' => $cost,
                    'tiene_vencimiento' => $hasExpiration,
                    'units_per_package' => $unitsPerPackage,
                    'package_name' => $packageName,
                ];
            } else {
                // Cross-check for inconsistencies in duplicates
                $existing = $validGrouped[$groupKey];
                $inconsistencies = [];
                if ((float)$existing['costo_unitario'] !== $cost) $inconsistencies[] = "Costo unitario difiere en filas duplicadas del mismo producto.";
                
                if (count($inconsistencies) > 0) {
                    $errors[] = [
                        'row' => $excelRow . ' y ' . $existing['original_row'],
                        'descripcion' => $descOriginal,
                        'messages' => $inconsistencies
                    ];
                    // Remove from valid to prevent bad insert
                    unset($validGrouped[$groupKey]);
                } else {
                    // Accumulate quantity
                    $validGrouped[$groupKey]['cantidad'] += $qty;
                }
            }
        }

        return [
            'valid_rows' => array_values($validGrouped),
            'errors' => $errors,
            'success_count' => count($validGrouped),
            'error_count' => count($errors),
        ];
    }

    /**
     * Extremely strict string normalization: remove accents, special chars, standardizing to single spaces.
     */
    private function normalizeString(string $value): string
    {
        $value = Str::ascii($value);
        // Replace everything that is not a letter, number, or space with a space
        $value = preg_replace('/[^A-Za-z0-9\s]/', ' ', $value);
        return trim(Str::squish($value));
    }

    private function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;

        try {
            $tz = config('app.timezone');
            if (is_numeric($value)) {
                $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return Carbon::instance($dateObj)->setTimezone($tz);
            }
            return Carbon::parse($value, $tz);
        } catch (Exception $e) {
            return null;
        }
    }

    public function initializeLog(string $fileName, int $totalRows, string $userId): ImportLog
    {
        $log = ImportLog::create([
            'user_id' => $userId,
            'file_name' => $fileName,
            'file_hash' => 'skipped', // No longer using hash for blocking
            'total_rows' => $totalRows,
            'status' => 'processing'
        ]);

        return $log;
    }

    /**
     * Process a single row of the import.
     */
    public function processRow(array $data, string $importLogId, bool $overridePrices): void
    {
        DB::transaction(function () use ($data, $importLogId, $overridePrices) {
            // 1. Resolve Category & UoM
            $category = Category::firstOrCreate(['name' => $data['categoria']], ['is_active' => true]);
            $uom = UnitOfMeasure::firstOrCreate(['name' => $data['unidad_de_medida']], [
                'abbreviation' => substr($data['unidad_de_medida'], 0, 3), 
                'is_active' => true
            ]);

            // 2. Resolve Product (with Locking)
            // 1. VERIFICAR SI EL PRODUCTO YA EXISTE (POR CÓDIGO O POR NOMBRE)
            $product = null;
            
            // Primero intentamos por código si está disponible
            if (!empty($data['codigo_producto'])) {
                $product = Product::where('code', $data['codigo_producto'])->first();
            }
            
            // Si no se encontró por código (o no tiene), intentamos por nombre exacto
            if (!$product) {
                $product = Product::where(DB::raw('LOWER(name)'), mb_strtolower($data['clean_desc']))->first();
            }

            if ($product) {
                // 2. SI EXISTE: PROCEDER A ACTUALIZAR
                $updateData = [
                    'name' => $data['descripcion'],
                    'unit_of_measure_id' => $uom->id,
                    'has_expiration' => $data['tiene_vencimiento'],
                ];

                $updateData['units_per_package'] = $data['units_per_package'] ?? 1;
                $updateData['package_name'] = $data['package_name'] ?? null;

                $product->update($updateData);
                $data['was_created'] = false;
            } else {
                // 3. SI NO EXISTE: PROCEDER A CREAR
                $code = !empty($data['codigo_producto']) ? $data['codigo_producto'] : $this->productService->generateSku($data['descripcion'], [$category->id]);
                
                $product = Product::create([
                    'code' => $code,
                    'name' => $data['descripcion'],
                    'unit_of_measure_id' => $uom->id,
                    'price' => 0, // Al no haber precio_venta en plantilla, iniciamos en 0
                    'has_expiration' => $data['tiene_vencimiento'],
                    'units_per_package' => $data['units_per_package'] ?? 1,
                    'package_name' => $data['package_name'] ?? null,
                    'is_active' => true,
                    'min_stock' => 5,
                ]);
                $data['was_created'] = true;
            }

            // Sincronizar categoría (siempre, ya sea nuevo o actualizado)
            $product->categories()->syncWithoutDetaching([$category->id]);

            // Store calculated subtotal in row_data for later aggregation
            $qty = BigDecimal::of($data['cantidad']);
            $cost = BigDecimal::of($data['costo_unitario']);
            $subtotal = $qty->multipliedBy($cost);
            
            $data['calculated_product_id'] = $product->id;
            $data['calculated_subtotal'] = (string) $subtotal->toScale(2, RoundingMode::HALF_UP);

            ImportLogDetail::create([
                'import_log_id' => $importLogId,
                'row_number' => $data['original_row'],
                'status' => 'success',
                'row_data' => $data
            ]);
        });
    }

    /**
     * Complete the import by consolidating all successful rows into a single Purchase.
     */
    public function completeImport(string $importLogId, string $warehouseId, string $providerId, string $userId): array
    {
        $log = ImportLog::findOrFail($importLogId);
        $details = ImportLogDetail::where('import_log_id', $importLogId)->where('status', 'success')->get();

        if ($details->isEmpty()) {
            $log->update(['status' => 'failed', 'metrics' => ['error' => 'No se procesaron filas exitosas.']]);
            throw new Exception("No hay filas válidas para finalizar la importación.");
        }

        return DB::transaction(function () use ($log, $details, $warehouseId, $providerId, $userId) {
            $purchaseDetails = [];
            $totalPurchase = BigDecimal::zero();
            $metrics = [
                'created_count' => 0,
                'updated_count' => 0,
                'total_stock_added' => 0,
                'purchase_id' => null
            ];

            foreach ($details as $detail) {
                $data = $detail->row_data;
                $subtotal = BigDecimal::of($data['calculated_subtotal']);
                $totalPurchase = $totalPurchase->plus($subtotal);
                $metrics['total_stock_added'] += (float) $data['cantidad'];

                if ($data['was_created'] ?? false) {
                    $metrics['created_count']++;
                } else {
                    $metrics['updated_count']++;
                }

                $purchaseDetails[] = [
                    'product_id' => $data['calculated_product_id'],
                    'quantity' => $data['cantidad'],
                    'unit_price' => $data['costo_unitario'],
                    'subtotal' => (string) $subtotal,
                ];
            }

            $purchaseData = [
                'provider_id' => $providerId,
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'date' => Carbon::now(config('app.timezone'))->format('Y-m-d'),
                'notes' => 'Ingreso Automático por Importación Masiva (Asíncrona).',
                'total' => (string) $totalPurchase->toScale(2, RoundingMode::HALF_UP),
                'payment_type' => 'contado',
                'status' => 'completada',
                'voucher_type' => 'sin_factura',
            ];

            \App\Observers\StockObserver::$muteNotifications = true;
            $purchase = $this->purchaseService->createPurchase($purchaseData, $purchaseDetails, false);
            \App\Observers\StockObserver::$muteNotifications = false;
            $metrics['purchase_id'] = $purchase->id;

            $log->update([
                'status' => 'completed',
                'success_rows' => $details->count(),
                'metrics' => $metrics
            ]);

            return $metrics;
        });
    }

    public function executeImport(string $fileName, array $validData, string $warehouseId, string $providerId, string $userId, bool $overridePrices): array
    {
        $log = ImportLog::create([
            'user_id' => $userId,
            'file_name' => $fileName,
            'file_hash' => 'skipped',
            'total_rows' => count($validData),
            'status' => 'processing'
        ]);

        $metrics = [
            'created' => 0,
            'updated' => 0,
            'total_stock_added' => 0,
            'purchase_id' => null
        ];

        DB::beginTransaction();

        try {
            $purchaseDetails = [];
            $totalPurchase = BigDecimal::zero();

            foreach ($validData as $data) {
                // 1. Resolve Category & UoM
                $category = Category::firstOrCreate(['name' => $data['categoria']], ['is_active' => true]);
                $uom = UnitOfMeasure::firstOrCreate(['name' => $data['unidad_de_medida']], ['abbreviation' => substr($data['unidad_de_medida'], 0, 3), 'is_active' => true]);

                // 2. Resolve Product (with Locking)
                $productQuery = Product::query();
                if (!empty($data['codigo_producto'])) {
                    $productQuery->where('code', $data['codigo_producto']);
                } else {
                    // Find by normalized description
                    $productQuery->where(DB::raw('LOWER(name)'), mb_strtolower($data['clean_desc']));
                }

                $product = $productQuery->lockForUpdate()->first();

                $isNew = false;
                if (!$product) {
                    $isNew = true;
                    // Auto-generate code if empty
                    $code = !empty($data['codigo_producto']) ? $data['codigo_producto'] : 'PRD-' . strtoupper(Str::random(6));
                    
                    $product = Product::create([
                        'code' => $code,
                        'name' => $data['descripcion'], // Keep original for display
                        'unit_of_measure_id' => $uom->id,
                        'price' => 0,
                        'has_expiration' => $data['tiene_vencimiento'],
                        'is_active' => true,
                        'min_stock' => 5,
                    ]);
                    $metrics['created']++;
                } else {
                    $metrics['updated']++;
                    $product->update(['has_expiration' => $data['tiene_vencimiento']]);
                }

                $product->categories()->syncWithoutDetaching([$category->id]);

                // 3. Prepare Purchase Detail
                $qty = BigDecimal::of($data['cantidad']);
                $cost = BigDecimal::of($data['costo_unitario']);
                $subtotal = $qty->multipliedBy($cost);

                $totalPurchase = $totalPurchase->plus($subtotal);
                $metrics['total_stock_added'] += $data['cantidad'];

                $purchaseDetails[] = [
                    'product_id' => $product->id,
                    'quantity' => $data['cantidad'],
                    'unit_price' => $data['costo_unitario'],
                    'subtotal' => (string) $subtotal->toScale(2, RoundingMode::HALF_UP),
                ];
                
                ImportLogDetail::create([
                    'import_log_id' => $log->id,
                    'row_number' => $data['original_row'],
                    'status' => 'success',
                    'row_data' => $data
                ]);
            }

            // 4. Create the consolidated Purchase for the entire batch
            $purchaseData = [
                'provider_id' => $providerId,
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'date' => Carbon::now(config('app.timezone'))->format('Y-m-d'), // Use today for the operational purchase
                'notes' => 'Ingreso Automático por Importación Unificada.',
                'total' => (string) $totalPurchase->toScale(2, RoundingMode::HALF_UP),
                'payment_type' => 'contado', // assume fully paid or just a stock intake
                'status' => 'completada',
                'voucher_type' => 'sin_factura',
            ];

            \App\Observers\StockObserver::$muteNotifications = true;
            $purchase = $this->purchaseService->createPurchase($purchaseData, $purchaseDetails, false);
            \App\Observers\StockObserver::$muteNotifications = false;
            $metrics['purchase_id'] = $purchase->id;

            $log->update([
                'status' => 'completed',
                'success_rows' => count($validData),
                'metrics' => $metrics
            ]);

            DB::commit();

            return $metrics;

        } catch (Exception $e) {
            DB::rollBack();
            $log->update([
                'status' => 'failed',
                'metrics' => ['error' => $e->getMessage()]
            ]);
            throw $e;
        }
    }
}
