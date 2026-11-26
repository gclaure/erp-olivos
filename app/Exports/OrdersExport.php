<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\OrderDeliveryModeEnum;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading, WithEvents
{
    /**
     * @param Builder<Order> $query
     * @param array{total_orders: int, total_amount: float, total_paid: float, total_balance: float} $summaryData
     */
    public function __construct(
        private readonly Builder $query,
        private readonly array $summaryData = []
    ) {}

    /**
     * @return Builder<Order>
     */
    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function headings(): array
    {
        return [
            ['REPORTE DE PEDIDOS Y RESERVAS DE CLIENTES'],
            [
                'Folio',
                'Fecha Registro',
                'Registrado Por',
                'Cliente',
                'Teléfono',
                'Fecha Entrega',
                'Hora Entrega',
                'Modalidad',
                'Dirección Entrega',
                'Productos Solicitados',
                'Subtotal (Bs)',
                'Descuento (Bs)',
                'Total (Bs)',
                'A Cuenta (Bs)',
                'Saldo (Bs)',
                'Estado',
            ],
        ];
    }

    /**
     * @param Order $order
     * @return array<int, mixed>
     */
    public function map($order): array
    {
        $deliveryMode = $order->delivery_mode instanceof OrderDeliveryModeEnum
            ? $order->delivery_mode
            : OrderDeliveryModeEnum::tryFrom((string) $order->delivery_mode) ?? OrderDeliveryModeEnum::Pickup;

        $itemsSummary = $order->items
            ->map(function (OrderItem $it): string {
                $qty = (float) $it->quantity;
                $size = $it->portions_or_size ? " ({$it->portions_or_size})" : '';
                return "{$qty} {$it->product_name}{$size}";
            })
            ->join(', ');

        return [
            $order->formatted_number,
            $order->registered_at ? $order->registered_at->format('d/m/Y') : '—',
            $order->user?->name ?? 'Sistema',
            $order->customer?->full_name ?? 'Sin cliente',
            $order->customer?->phone ?? '—',
            $order->delivery_date ? $order->delivery_date->format('d/m/Y') : '—',
            $order->delivery_time ?? '—',
            $deliveryMode->label(),
            $order->delivery_address ?: '—',
            $itemsSummary ?: 'Sin productos',
            (float) $order->subtotal,
            (float) $order->discount,
            (float) $order->total,
            (float) $order->paid_amount,
            (float) $order->balance_due,
            $order->isCancelled() ? 'ANULADO' : 'REGISTRADO',
        ];
    }

    /**
     * @return array<class-string, \Closure(AfterSheet): void>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $lastRow = $event->sheet->getHighestRow();
                $footerRow = $lastRow + 1;

                // Fila de Totales
                $event->sheet->setCellValue('A' . $footerRow, 'TOTALES');
                $event->sheet->mergeCells('A' . $footerRow . ':L' . $footerRow);

                $event->sheet->setCellValue('M' . $footerRow, $this->summaryData['total_amount'] ?? 0);
                $event->sheet->setCellValue('N' . $footerRow, $this->summaryData['total_paid'] ?? 0);
                $event->sheet->setCellValue('O' . $footerRow, $this->summaryData['total_balance'] ?? 0);

                // Estilos para la fila del footer (Paleta Los Olivos: Crema #F1EDE2 y texto Olivo Oscuro #142307)
                $event->sheet->getStyle('A' . $footerRow . ':P' . $footerRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF142307']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF1EDE2'], // Crema institucional
                    ],
                ]);

                // Formatear columnas de moneda en el cuerpo y el pie (K, L, M, N, O)
                if ($lastRow >= 3) {
                    $event->sheet->getStyle('K3:O' . $footerRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                // Centrar columnas específicas (Folio, Fechas, Horas, Modalidad, Estado)
                $event->sheet->getStyle('A3:A' . $lastRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $event->sheet->getStyle('B3:B' . $lastRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $event->sheet->getStyle('F3:G' . $lastRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $event->sheet->getStyle('P3:P' . $lastRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:P1');

        return [
            // Fila 1: Título Principal — Verde Olivo Oficial (#73AC32) con texto blanco
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF73AC32'], // Verde Olivo Oficial Los Olivos
                ],
            ],
            // Fila 2: Encabezados de Columna — Fondo Crema (#F1EDE2) con texto Olivo Oscuro (#223C12)
            2 => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF223C12']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF1EDE2'], // Crema Los Olivos
                ],
            ],
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
