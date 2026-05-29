<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseTemplateExport implements WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    use Exportable;

    public function headings(): array
    {
        return [
            'codigo_producto',
            'fecha_compra',
            'cantidad',
            'costo_unitario',
            'utilidad',
            'fecha_vencimiento'
        ];
    }

    public function title(): string
    {
        return 'Plantilla Compras';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 15,
            'C' => 15,
            'D' => 20,
            'E' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => [
                'font' => ['bold' => true],
            ],
        ];
    }
}
