<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // 1. Validar que la plantilla no esté vacía (sólo cabeceras o nada)
        if ($rows->isEmpty()) {
            throw new \Exception('La plantilla está vacía. Debe agregar al menos un producto para importar.');
        }

        // 2. Validar que tenga las cabeceras requeridas revisando la primera fila
        $firstRow = $rows->first()->toArray();
        $requiredHeaders = ['codigo_producto', 'descripcion', 'unidad_de_medida', 'categoria'];
        $missingHeaders = array_diff($requiredHeaders, array_keys($firstRow));
        
        if (count($missingHeaders) > 0) {
            throw new \Exception('La plantilla no es válida o fue modificada. Faltan las siguientes columnas requeridas: ' . implode(', ', $missingHeaders));
        }

        // 3. Validar códigos duplicados dentro del archivo Excel
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

        // 4. Procesar las filas
        foreach ($rows as $row) {
            if (empty($row['codigo_producto']) || empty($row['descripcion'])) {
                continue;
            }

            $categoryName = $row['categoria'] ?? 'General';
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                [
                    'description' => null,
                    'is_active' => true,
                ]
            );

            $unitName = $row['unidad_de_medida'] ?? 'UND';
            $unit = UnitOfMeasure::firstOrCreate(
                ['name' => $unitName],
                [
                    'abbreviation' => strtoupper(substr($unitName, 0, 3)),
                    'is_active' => true,
                ]
            );

            $hasExpiration = false;
            $tieneVencimientoStr = trim(strtoupper((string)($row['tiene_vencimiento'] ?? 'NO')));
            if (in_array($tieneVencimientoStr, ['SI', 'SÍ', 'YES', '1', 'TRUE'])) {
                $hasExpiration = true;
            }

            $product = Product::updateOrCreate(
                ['code' => $row['codigo_producto']],
                [
                    'name' => $row['descripcion'],
                    'price' => 0.00,
                    'min_stock' => 5,
                    'is_active' => true,
                    'unit_of_measure_id' => $unit->id,
                    'has_expiration' => $hasExpiration,
                ]
            );

            $product->categories()->syncWithoutDetaching([$category->id]);
        }
    }
}
