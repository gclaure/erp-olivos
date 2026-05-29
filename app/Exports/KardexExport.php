<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\WithChunkReading;

class KardexExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading
{
    public function __construct(
        private $query
    ) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            ['KARDEX DE INVENTARIO'],
            [
                'Fecha Base',
                'Hora',
                'Producto',
                'Código',
                'Almacén',
                'Descripción',
                'Notas',
                'ENTRADA - C.',
                'ENTRADA - C.U.',
                'ENTRADA - V.T.',
                'SALIDA - C.',
                'SALIDA - C.U.',
                'SALIDA - V.T.',
                'SALDO - C.',
                'SALDO - C.P.',
                'SALDO - V.T.',
                'Usuario'
            ],
        ];
    }

    public function map($k): array
    {
        $isIngreso = in_array(strtoupper($k->type ?? ''), ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA']);
        $isSalida = in_array(strtoupper($k->type ?? ''), ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA']);

        $description = str_replace('_', ' ', strtoupper($k->type ?? '—'));
        
        if ($k->recordable) {
            $extra = [];
            if (isset($k->recordable->payment_type)) {
                $extra[] = "PAGO: " . $k->recordable->payment_type;
            }
            if (isset($k->recordable->delivery_mode)) {
                $extra[] = "ENTREGA: " . (is_object($k->recordable->delivery_mode) ? $k->recordable->delivery_mode->label() : $k->recordable->delivery_mode);
            }
            if (!empty($extra)) {
                $description .= " (" . implode(' | ', $extra) . ")";
            }
        }

        return [
            $k->created_at ? $k->created_at->format('Y-m-d') : '',
            $k->created_at ? $k->created_at->format('H:i') : '',
            $k->product?->name ?? '—',
            $k->product?->code ?? '—',
            $k->warehouse?->name ?? '—',
            $description,
            // Limpiamos el Log ID de las notas si existe para que no se vea en el Excel
            $k->notes ? preg_replace('/\. Log ID: [a-f0-9-]+/i', '.', $k->notes) : '',
            
            // ENTRADAS
            $isIngreso ? $k->quantity : '',
            $isIngreso ? $k->unit_cost : '',
            $isIngreso ? $k->total_cost : '',
            
            // SALIDAS
            $isSalida ? $k->quantity : '',
            $isSalida ? $k->unit_cost : '',
            $isSalida ? $k->total_cost : '',
            
            // SALDOS
            $k->balance_quantity ?? 0,
            $k->avg_cost ?? 0,
            $k->balance_total_cost ?? 0,
            
            $k->user?->name ?? 'Sistema',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:Q1');
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
