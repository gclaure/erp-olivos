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
use Carbon\Carbon;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading, WithEvents
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
            ['REPORTE DE VENTAS DETALLADO Y GANANCIAS'],
            [
                'Fecha',
                'N° Venta',
                'Cliente',
                'Producto',
                'Código',
                'Cant.',
                'P. Unit (Bs)',
                'C. Unit (Bs)',
                'Subtotal (Bs)',
                'Utilidad (Bs)',
                'Vendedor'
            ],
        ];
    }

    public function map($detail): array
    {
        $sale = $detail->sale;
        $profit = (float)($detail->subtotal - ($detail->quantity * $detail->unit_cost));
        
        return [
            $sale->date ? Carbon::parse($sale->date)->format('d/m/Y') : '—',
            $sale->formatted_number,
            $sale->client?->name ?? 'Venta Mostrador',
            $detail->product?->name ?? 'Producto Eliminado',
            $detail->product?->code ?? '—',
            (float)$detail->quantity,
            (float)$detail->unit_price,
            (float)$detail->unit_cost,
            (float)$detail->subtotal,
            $profit,
            $sale->user?->name ?? 'Sistema',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();
                $footerRow = $lastRow + 1;
                
                $event->sheet->setCellValue('A' . $footerRow, 'TOTALES DEL PERIODO');
                $event->sheet->mergeCells('A' . $footerRow . ':H' . $footerRow);
                
                $event->sheet->setCellValue('I' . $footerRow, $this->summaryData['total_sales'] ?? 0);
                $event->sheet->setCellValue('J' . $footerRow, $this->summaryData['total_profit'] ?? 0);
                
                // Style the footer
                $event->sheet->getStyle('A' . $footerRow . ':K' . $footerRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'F1F5F9']
                    ]
                ]);

                // Format numbers in footer
                $event->sheet->getStyle('I' . $footerRow . ':J' . $footerRow)
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
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ],
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
