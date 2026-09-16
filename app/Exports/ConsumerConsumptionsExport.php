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

class ConsumerConsumptionsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading, WithEvents
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
            ['REPORTE DE CONSUMOS POR CONSUMIDOR / SOLICITANTE'],
            [
                'N° Solicitud',
                'Fecha',
                'Consumidor / Solicitante',
                'Almacén',
                'Estado',
                'Código Producto',
                'Producto / Insumo',
                'U.M.',
                'Cant. Solicitada',
                'Cant. Entregada',
                'Despachado Por',
                'Observaciones / Notas',
            ],
        ];
    }

    public function map($detail): array
    {
        $request = $detail->consumptionRequest;
        $statusLabels = [
            'pending' => 'Pendiente',
            'approved' => 'Aprobado',
            'dispatched' => 'Despachado',
            'received' => 'Recibido',
            'observed' => 'Observado',
            'cancelled' => 'Cancelado',
        ];

        $status = $statusLabels[$request->status ?? ''] ?? ($request->status ?? '—');

        return [
            $request ? ($request->formatted_number ? 'SOL-' . $request->formatted_number : 'SOL-' . $request->id) : '—',
            $request && $request->date ? Carbon::parse($request->date)->format('d/m/Y') : '—',
            $request->requested_by ?? $request->user?->name ?? '—',
            $request->warehouse?->name ?? '—',
            $status,
            $detail->product?->code ?? '—',
            $detail->product?->name ?? 'Producto Eliminado',
            $detail->product?->unitOfMeasure?->abbreviation ?? $detail->product?->unitOfMeasure?->name ?? 'UND',
            (float)$detail->quantity_requested,
            (float)($detail->quantity_delivered ?? $detail->quantity_requested),
            $request->dispatchedByUser?->name ?? '—',
            $detail->observation ?? $request->notes ?? '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();
                $footerRow = $lastRow + 1;

                $event->sheet->setCellValue('A' . $footerRow, 'TOTAL GENERAL');
                $event->sheet->mergeCells('A' . $footerRow . ':H' . $footerRow);

                $event->sheet->setCellValue('I' . $footerRow, $this->summaryData['total_requested'] ?? 0);
                $event->sheet->setCellValue('J' . $footerRow, $this->summaryData['total_delivered'] ?? 0);

                $event->sheet->getStyle('A' . $footerRow . ':L' . $footerRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'F1F5F9']
                    ]
                ]);

                $event->sheet->getStyle('I' . $footerRow . ':J' . $footerRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:L1');
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => '047857']],
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
