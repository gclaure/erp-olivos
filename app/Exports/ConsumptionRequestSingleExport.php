<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConsumptionRequestSingleExport implements FromCollection, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    private int $tableHeaderRow = 6;
    private int $lastRow = 7;

    public function __construct(
        private readonly ConsumptionRequest $consumptionRequest,
    ) {}

    public function title(): string
    {
        return 'Solicitud #' . ($this->consumptionRequest->formatted_number ?? 'S-N');
    }

    public function collection(): Collection
    {
        $req = $this->consumptionRequest;
        $rows = new Collection();

        // Fila 1: Banner Corporativo
        $rows->push([
            'LOS OLIVOS ERP — REPORTE DE SOLICITUD DE CONSUMO INTERNO #' . ($req->formatted_number ?? 'S/N'),
            '', '', '', '', '',
        ]);

        // Fila 2: Datos Principales
        $rows->push([
            'Área Solicitante:',
            $req->requested_by ?? 'Sin área',
            'Almacén Origen:',
            $req->warehouse?->name ?? 'N/A',
            'Estado:',
            $this->translateStatus((string) ($req->status ?? '')),
        ]);

        // Fila 3: Solicitante, Fecha y Hora separadas
        $rows->push([
            'Solicitado Por:',
            $req->user?->name ?? 'N/A',
            'Fecha Solicitud:',
            $this->formatSpanishDate($req->date ?? $req->created_at),
            'Hora Registro:',
            $req->created_at ? $req->created_at->format('H:i') . ' hrs' : '-',
        ]);

        // Fila 4: Espacio en blanco separador
        $rows->push(['', '', '', '', '', '']);

        // Fila 5: Encabezados de la tabla de productos
        $this->tableHeaderRow = 5;
        $rows->push([
            'Código Producto',
            'Producto / Insumo',
            'U.M.',
            'Cant. Solicitada',
            'Cant. Entregada',
            'Costo Unitario (Bs.)',
        ]);

        $totalRequested = 0.0;
        $totalDelivered = 0.0;
        $totalCost = 0.0;

        foreach ($req->details as $detail) {
            $product = $detail->product;
            $unitCost = $this->resolveUnitCost($detail);
            $qtyReq = (float) ($detail->quantity_requested ?? 0);
            $qtyDel = (float) ($detail->quantity_delivered ?? 0);

            $totalRequested += $qtyReq;
            $totalDelivered += $qtyDel;
            $totalCost += $unitCost;

            $rows->push([
                $product?->code ?? 'S/C',
                $product?->name ?? 'Producto eliminado',
                $product?->unitOfMeasure?->abbreviation ?? $product?->unitOfMeasure?->name ?? 'UND',
                round($qtyReq, 2),
                round($qtyDel, 2),
                round($unitCost, 2),
            ]);
        }

        $rows->push([
            'TOTAL (BS.)',
            '',
            '',
            round($totalRequested, 2),
            round($totalDelivered, 2),
            round($totalCost, 2),
        ]);

        $this->lastRow = $rows->count();

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 13,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '44773C'], // Verde Bosque Los Olivos
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            $this->tableHeaderRow => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '44773C'], // Verde Bosque Los Olivos
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $headerRow = $this->tableHeaderRow;
                $lastRow = max($headerRow + 1, $this->lastRow);
                $firstDataRow = $headerRow + 1;
                $lastDataRow = max($firstDataRow, $lastRow - 1);

                $sheet->mergeCells('A1:F1');
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(20);
                $sheet->getRowDimension($headerRow)->setRowHeight(24);
                $sheet->getRowDimension($lastRow)->setRowHeight(22);

                // Bloque de metadatos (Filas 2 y 3)
                $sheet->getStyle('A2:F3')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FAF9F5'], // Marfil Los Olivos
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'A8C98A'], // Verde Salvia
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                foreach (['A2', 'C2', 'E2', 'A3', 'C3', 'E3'] as $cell) {
                    $sheet->getStyle($cell)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '44773C'],
                        ],
                    ]);
                }
                foreach (['B2', 'D2', 'F2', 'B3', 'D3', 'F3'] as $cell) {
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                }

                // Bordes de la tabla
                $sheet->getStyle("A{$headerRow}:F{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E9E9E6'], // Gris Piedra
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Zebra striping y alturas de fila
                for ($r = $firstDataRow; $r <= $lastDataRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(19);
                    if ($r % 2 === 0) {
                        $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FAF9F5'],
                            ],
                        ]);
                    }
                }

                if ($lastDataRow >= $firstDataRow) {
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getFont()->setBold(true);
                    $sheet->getStyle("C{$firstDataRow}:C{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$firstDataRow}:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("F{$firstDataRow}:F{$lastDataRow}")->getFont()->setBold(true);
                }

                $sheet->getStyle("D{$firstDataRow}:F{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                // Combinar celdas para TOTAL (BS.)
                $sheet->mergeCells("A{$lastRow}:C{$lastRow}");
                $sheet->getStyle("A{$lastRow}:C{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("A{$lastRow}:F{$lastRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => '44773C'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FAF9F5'],
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '44773C'],
                        ],
                        'bottom' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '73AC32'],
                        ],
                    ],
                ]);

                $sheet->setAutoFilter("A{$headerRow}:F{$lastDataRow}");
                $sheet->freezePane("A{$firstDataRow}");
            },
        ];
    }

    private function resolveUnitCost(ConsumptionRequestDetail $detail): float
    {
        $product = $detail->product;
        if (!$product) {
            return 0.0;
        }

        $price = (float) ($product->price ?? 0);
        if ($price > 0) {
            return $price;
        }

        $warehouseId = $this->consumptionRequest->warehouse_id;
        if ($warehouseId && $product->relationLoaded('stocks')) {
            $stock = $product->stocks->firstWhere('warehouse_id', $warehouseId);
            if ($stock && (float) $stock->average_cost > 0) {
                return (float) $stock->average_cost;
            }
        }

        return 0.0;
    }

    private function formatSpanishDate(mixed $dateVal): string
    {
        if (!$dateVal) {
            return '-';
        }

        $d = $dateVal instanceof \Carbon\CarbonInterface
            ? $dateVal
            : \Carbon\Carbon::parse($dateVal);

        $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return $days[(int) $d->format('w')] . ', ' . $d->format('d') . ' de ' . $months[(int) $d->format('n')] . ' de ' . $d->format('Y');
    }

    private function translateStatus(string $status): string
    {
        return match ($status) {
            'pendiente' => 'PENDIENTE',
            'aprobado' => 'APROBADO',
            'observado' => 'OBSERVADO',
            'despachado' => 'DESPACHADO',
            'parcial', 'despachado_parcial' => 'DESPACHO PARCIAL',
            'entregado' => 'ENTREGADO',
            'cancelado' => 'CANCELADO',
            default => strtoupper($status),
        };
    }
}
