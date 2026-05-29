<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kardex;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KardexExportController extends Controller
{
    public function downloadPdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $query = Kardex::query()->with(['product', 'warehouse', 'user', 'recordable']);

        // Apply filters from request
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('type')) {
            $type = $request->type;
            if ($type === 'ENTRADA') {
                $query->whereIn('type', ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA']);
            } elseif ($type === 'SALIDA') {
                $query->whereIn('type', ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA']);
            } else {
                $query->where('type', $type);
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Aplicar límite de seguridad para PDF (Evitar caídas del servidor)
        $totalCount = $query->count();
        if ($totalCount > 1000) {
            return back()->with('error', "La exportación a PDF está limitada a 1,000 registros para garantizar la estabilidad del servidor. Por favor, usa filtros para reducir los resultados o utiliza la exportación a Excel para grandes volúmenes (Total encontrado: $totalCount).");
        }

        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy('created_at', $sortOrder)->orderBy('id', $sortOrder);

        // Fetch in chunks of 100 to build a lightweight data array for the view
        $allRecords = [];
        $query->chunk(100, function ($batch) use (&$allRecords) {
            foreach ($batch as $record) {
                // We keep only the needed data as a simple object to minimize memory
                $allRecords[] = (object) [
                    'created_at' => $record->created_at,
                    'product_name' => $record->product?->name,
                    'product_code' => $record->product?->code,
                    'type' => $record->type,
                    'quantity' => (float)$record->quantity,
                    'unit_cost' => (float)$record->unit_cost,
                    'total_cost' => (float)$record->total_cost,
                    'balance_quantity' => (float)$record->balance_quantity,
                    'avg_cost' => (float)$record->avg_cost,
                    'balance_total_cost' => (float)$record->balance_total_cost,
                    'user_name' => $record->user?->name,
                    'payment_type' => $record->recordable?->payment_type,
                    'delivery_mode_label' => $record->recordable && isset($record->recordable->delivery_mode) 
                        ? (is_object($record->recordable->delivery_mode) ? $record->recordable->delivery_mode->label() : $record->recordable->delivery_mode)
                        : null,
                ];
            }
        });

        // Summary stats (calculating once outside chunks for efficiency)
        $summary = $this->calculateSummary($request);

        $pdf = Pdf::loadView('exports.kardex', [
            'records' => $allRecords,
            'summary' => $summary
        ])->setPaper('a4', 'landscape');

        $filename = 'Kardex_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadExcel(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $query = Kardex::query()->with(['product', 'warehouse', 'user']);

        // Apply filters from request
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('type')) {
            $type = $request->type;
            if ($type === 'ENTRADA') {
                $query->whereIn('type', ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA']);
            } elseif ($type === 'SALIDA') {
                $query->whereIn('type', ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA']);
            } else {
                $query->where('type', $type);
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy('created_at', $sortOrder)->orderBy('id', $sortOrder);

        $filename = 'Kardex_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\KardexExport($query),
            $filename
        );
    }

    private function calculateSummary(Request $request): array
    {
        $query = Kardex::query();

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('type')) {
            $type = $request->type;
            if ($type === 'ENTRADA') {
                $query->whereIn('type', ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA']);
            } elseif ($type === 'SALIDA') {
                $query->whereIn('type', ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA']);
            } else {
                $query->where('type', $type);
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $entradas = (clone $query)->whereIn('type', ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA']);
        $salidas  = (clone $query)->whereIn('type', ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA']);
        
        $lastRecord = (clone $query)->orderByDesc('created_at')->orderByDesc('id')->first();

        return [
            'total_entradas_qty' => $entradas->sum('quantity'),
            'total_entradas_val' => $entradas->sum('total_cost'),
            'total_salidas_qty'  => $salidas->sum('quantity'),
            'total_salidas_val'  => $salidas->sum('total_cost'),
            'saldo_qty'          => $lastRecord ? $lastRecord->balance_quantity : 0,
            'saldo_val'          => $lastRecord ? $lastRecord->balance_total_cost : 0,
        ];
    }
}
