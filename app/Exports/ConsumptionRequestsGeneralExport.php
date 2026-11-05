<?php

declare(strict_types=1);

namespace App\Exports;

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

class ConsumptionRequestsGeneralExport implements FromCollection, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    private int $headerRow = 5;
    private int $lastRow = 6;

    public function __construct(
        private readonly ?string $search = null,
        private readonly ?string $status = null,
        private readonly ?string $area = null,
        private readonly ?string $startDate = null,
        private readonly ?string $endDate = null,
    ) {}

    public function title(): string
    {
        return 'Reporte General Consumos';
    }

    public function collection(): Collection
    {
        $query = ConsumptionRequestDetail::query()
            ->with([
                'consumptionRequest.warehouse',
                'consumptionRequest.user',
                'product.unitOfMeasure',
                'product.stocks',
            ])
            ->whereHas('consumptionRequest', function ($q): void {
                if ($this->search) {
                    $search = $this->search;
                    $q->where(function ($sub) use ($search): void {
                        $sub->where('requested_by', 'ilike', "%{$search}%")
                            ->orWhere('notes', 'ilike', "%{$search}%");
                        if (is_numeric($search)) {
                            $sub->orWhere('number', (int) $search);
                        }
                    });
                }

                if ($this->status) {
                    $q->where('status', $this->status);
                }

                if ($this->area) {
                    $q->where('requested_by', $this->area);
                }

                if ($this->startDate) {
                    $q->whereDate('date', '>=', $this->startDate);
                }

                if ($this->endDate) {
                    $q->whereDate('date', '<=', $this->endDate);
                }
            })
            ->orderByDesc('created_at');

        $details = $query->get();

        $totalRequests = $details->pluck('consumption_request_id')->unique()->count();
        $totalItems = $details->count();
        $totalRequested = 0.0;
        $totalDelivered = 0.0;
        $totalCost = 0.0;

        $dataRows = new Collection();

        foreach ($details as $detail) {
            $req = $detail->consumptionRequest;
            $product = $detail->product;
            $unitCost = $this->resolveUnitCost($detail);
            $qtyReq = (float) ($detail->quantity_requested ?? 0);
            $qtyDel = (float) ($detail->quantity_delivered ?? 0);

            $totalRequested += $qtyReq;
            $totalDelivered += $qtyDel;
            $totalCost += $unitCost;

            $dataRows->push([
                '#' . ($req?->formatted_number ?? 'S/N'),
                $this->formatSpanishDate($req?->date ?? $req?->created_at),
                $req?->created_at ? $req->created_at->format('H:i') . ' hrs' : '-',
                $req?->requested_by ?? 'Sin área',
                $req?->warehouse?->name ?? 'N/A',
                $req?->user?->name ?? 'N/A',
                $this->translateStatus((string) ($req?->status ?? '')),
                $product?->code ?? 'S/C',
                $product?->name ?? 'Producto eliminado',
                $product?->unitOfMeasure?->abbreviation ?? $product?->unitOfMeasure?->name ?? 'UND',
                round($qtyReq, 2),
                round($qtyDel, 2),
                round($unitCost, 2),
            ]);
        }

        $rows = new Collection();

        // Fila 1: Título Corporativo
        $rows->push([
            'LOS OLIVOS ERP — REPORTE GENERAL DE SOLICITUDES DE CONSUMO INTERNO',
            '', '', '', '', '', '', '', '', '', '', '', '',
        ]);

        // Fila 2: Metadatos de filtros y fecha de emisión
        $areaLabel = !empty($this->area) ? strtoupper($this->area) : 'TODAS LAS ÁREAS';
        $statusLabel = !empty($this->status) ? $this->translateStatus($this->status) : 'TODOS LOS ESTADOS';
        $emisionLabel = $this->formatSpanishDate(now()) . ' · ' . now()->format('H:i') . ' hrs';

        $rows->push([
            'Área Filtrada:',
            $areaLabel,
            '',
            'Estado:',
            $statusLabel,
            '',
            'Fecha de Emisión:',
            $emisionLabel,
            '', '', '', '', '',
        ]);

        // Fila 3: Resumen Ejecutivo (KPIs)
        $rows->push([
            'Total Solicitudes:',
            $totalRequests,
            '',
            'Ítems Registrados:',
            $totalItems,
            '',
            'Cant. Solicitada:',
            round($totalRequested, 2),
            'Cant. Entregada:',
            round($totalDelivered, 2),
            'Total (Bs.):',
            round($totalCost, 2),
            '',
        ]);

        // Fila 4: Espacio en blanco separador
        $rows->push(['', '', '', '', '', '', '', '', '', '', '', '', '']);

        // Fila 5: Encabezados de la tabla
        $this->headerRow = 5;
        $rows->push([
            'N° Solicitud',
            'Fecha',
            'Hora',
            'Área Solicitante',
            'Almacén Origen',
            'Solicitado Por',
            'Estado',
            'Código Producto',
            'Producto / Insumo',
            'U.M.',
            'Cant. Solicitada',
            'Cant. Entregada',
            'Costo Unitario (Bs.)',
        ]);

        // Filas de datos
        foreach ($dataRows as $row) {
            $rows->push($row);
        }

        // Fila final: Totales
        $rows->push([
            'TOTAL (BS.)',
            '', '', '', '', '', '', '', '', '',
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
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 13,
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
            $this->headerRow => [
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
                $headerRow = $this->headerRow;
                $lastRow = max($headerRow + 1, $this->lastRow);
                $firstDataRow = $headerRow + 1;
                $lastDataRow = max($firstDataRow, $lastRow - 1);

                // Combinar título principal y ajustar alturas de fila
                $sheet->mergeCells('A1:M1');
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(20);
                $sheet->getRowDimension($headerRow)->setRowHeight(24);
                $sheet->getRowDimension($lastRow)->setRowHeight(22);

                // Estilo de las filas de metadatos y KPIs (Filas 2 y 3)
                $sheet->getStyle('A2:M3')->applyFromArray([
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

                // Etiquetas en negrita Verde Bosque en filas 2 y 3
                foreach (['A2', 'D2', 'G2', 'A3', 'D3', 'G3', 'I3', 'K3'] as $cell) {
                    $sheet->getStyle($cell)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '44773C'],
                        ],
                    ]);
                }
                foreach (['B2', 'E2', 'H2', 'B3', 'E3', 'H3', 'J3', 'L3'] as $cell) {
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                }
                $sheet->getStyle('H3:L3')->getNumberFormat()->setFormatCode('#,##0.00');

                // Bordes de toda la tabla
                $sheet->getStyle("A{$headerRow}:M{$lastRow}")->applyFromArray([
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

                // Zebra striping y alineaciones en filas de datos
                for ($r = $firstDataRow; $r <= $lastDataRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(19);
                    if ($r % 2 === 0) {
                        $sheet->getStyle("A{$r}:M{$r}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FAF9F5'], // Marfil
                            ],
                        ]);
                    }
                }

                // Alineación de columnas de datos
                if ($lastDataRow >= $firstDataRow) {
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$firstDataRow}:C{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G{$firstDataRow}:H{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("J{$firstDataRow}:J{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("K{$firstDataRow}:M{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Destacar N° Solicitud y Área Solicitante en negrita
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getFont()->setBold(true);
                    $sheet->getStyle("D{$firstDataRow}:D{$lastDataRow}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '44773C'],
                        ],
                    ]);
                    $sheet->getStyle("M{$firstDataRow}:M{$lastDataRow}")->getFont()->setBold(true);
                }

                // Formato numérico contable para cantidades y costos
                $sheet->getStyle("K{$firstDataRow}:M{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                // Combinar celdas de etiqueta en fila TOTAL (BS.)
                $sheet->mergeCells("A{$lastRow}:J{$lastRow}");
                $sheet->getStyle("A{$lastRow}:J{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Estilo de fila TOTAL (BS.) con identidad Los Olivos
                $sheet->getStyle("A{$lastRow}:M{$lastRow}")->applyFromArray([
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

                // Activar autofiltro e inmovilizar encabezado de tabla
                $sheet->setAutoFilter("A{$headerRow}:M{$lastDataRow}");
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

        $warehouseId = $detail->consumptionRequest?->warehouse_id;
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
