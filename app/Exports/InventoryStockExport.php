<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class InventoryStockExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading, WithEvents
{
    public function __construct(
        private $query,
        private array $summaryData = []
    ) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            ['REPORTE DE EXISTENCIAS Y STOCK DE INVENTARIO'],
            [
                'Código',
                'Producto / Insumo',
                'Tipo',
                'Categoría',
                'Almacén',
                'U.M.',
                'Stock Actual',
                'Stock Mínimo',
                'Costo Promedio (Bs)',
                'Valor Inventario (Bs)',
                'Estado Stock',
            ],
        ];
    }

    public function map($stock): array
    {
        $product = $stock->product;
        $qty = (float)($stock->quantity ?? 0);
        $minStock = (float)($product?->min_stock ?? 0);
        $avgCost = (float)($stock->average_cost ?? 0);
        $totalVal = (float)($stock->inventory_value ?? ($qty * $avgCost));

        $status = 'NORMAL';
        if ($qty <= 0) {
            $status = 'SIN STOCK';
        } elseif ($qty <= $minStock) {
            $status = 'STOCK BAJO';
        }

        $categories = $product?->categories?->pluck('name')->join(', ') ?: '—';
        $typeLabel = $product?->type?->label() ?? ($product?->isSupply() ? 'Insumo' : 'Materia Prima');

        return [
            $product?->code ?? '—',
            $product?->name ?? 'Producto Eliminado',
            $typeLabel,
            $categories,
            $stock->warehouse?->name ?? '—',
            $product?->unitOfMeasure?->abbreviation ?? $product?->unitOfMeasure?->name ?? 'UND',
            $qty,
            $minStock,
            $avgCost,
            $totalVal,
            $status,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();
                $footerRow = $lastRow + 1;

                $event->sheet->setCellValue('A' . $footerRow, 'TOTALES GENERALES');
                $event->sheet->mergeCells('A' . $footerRow . ':F' . $footerRow);

                $event->sheet->setCellValue('G' . $footerRow, $this->summaryData['total_quantity'] ?? 0);
                $event->sheet->setCellValue('J' . $footerRow, $this->summaryData['total_value'] ?? 0);

                $event->sheet->getStyle('A' . $footerRow . ':K' . $footerRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'F1F5F9']
                    ]
                ]);

                $event->sheet->getStyle('G' . $footerRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                $event->sheet->getStyle('J' . $footerRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:K1');
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => '0F766E']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ],
            2 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'E2E8F0']
                ]
            ],
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
