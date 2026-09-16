<?php

declare(strict_types=1);

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class MonthlyMovementsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading, WithEvents
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
            ['REPORTE MENSUAL DE ENTRADAS Y SALIDAS DE ALMACÉN'],
            [
                'Fecha',
                'Hora',
                'Tipo de Movimiento',
                'Categoría Operación',
                'Almacén',
                'Código',
                'Producto / Insumo',
                'Cant. Entrada',
                'Cant. Salida',
                'Costo Unit. (Bs)',
                'Total Costo (Bs)',
                'Saldo Cant.',
                'Usuario',
                'Notas / Referencia',
            ],
        ];
    }

    public function map($k): array
    {
        $isIngreso = in_array(strtoupper($k->type ?? ''), ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA', 'AJUSTE_POSITIVO', 'COMPRA']);
        $isSalida = in_array(strtoupper($k->type ?? ''), ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA', 'AJUSTE_NEGATIVO', 'CONSUMO', 'MERMA']);

        $typeLabel = str_replace('_', ' ', strtoupper($k->type ?? '—'));
        $categoryOp = $isIngreso ? 'ENTRADA' : ($isSalida ? 'SALIDA' : 'OTRO');

        return [
            $k->created_at ? $k->created_at->format('d/m/Y') : '—',
            $k->created_at ? $k->created_at->format('H:i') : '—',
            $typeLabel,
            $categoryOp,
            $k->warehouse?->name ?? '—',
            $k->product?->code ?? '—',
            $k->product?->name ?? 'Producto Eliminado',
            $isIngreso ? (float)$k->quantity : '',
            $isSalida ? (float)$k->quantity : '',
            (float)($k->unit_cost ?? 0),
            (float)($k->total_cost ?? 0),
            (float)($k->balance_quantity ?? 0),
            $k->user?->name ?? 'Sistema',
            $k->notes ? preg_replace('/\. Log ID: [a-f0-9-]+/i', '.', (string)$k->notes) : '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();
                $footerRow = $lastRow + 1;

                $event->sheet->setCellValue('A' . $footerRow, 'TOTALES DEL PERIODO');
                $event->sheet->mergeCells('A' . $footerRow . ':G' . $footerRow);

                $event->sheet->setCellValue('H' . $footerRow, $this->summaryData['total_entradas_qty'] ?? 0);
                $event->sheet->setCellValue('I' . $footerRow, $this->summaryData['total_salidas_qty'] ?? 0);
                $event->sheet->setCellValue('K' . $footerRow, $this->summaryData['total_cost'] ?? 0);

                $event->sheet->getStyle('A' . $footerRow . ':N' . $footerRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'F1F5F9']
                    ]
                ]);

                $event->sheet->getStyle('H' . $footerRow . ':K' . $footerRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:N1');
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => '1E40AF']],
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
