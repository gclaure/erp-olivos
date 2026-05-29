<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Exports\SalesExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportController extends Controller
{
    public function downloadPdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $query = Sale::query()
            ->with(['client', 'user', 'warehouse'])
            ->whereDate('date', '>=', $request->date_from)
            ->whereDate('date', '<=', $request->date_to)
            ->where('status', '!=', 'cancelada');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $query->orderBy('date', 'asc')->orderBy('number', 'asc');

        // Límite de seguridad para PDF
        $totalRows = \App\Models\SaleDetail::whereIn('sale_id', $query->pluck('id'))->count();
        if ($totalRows > 1000) {
            return back()->with('error', "El reporte de ventas en PDF está limitado a 1,000 registros. Por favor, selecciona un rango de fechas más pequeño o usa la exportación a Excel (Total registros encontrados: $totalRows).");
        }

        // Fetch in chunks to build a detailed data array including products and profit
        $allRecords = [];
        $query->with(['details.product'])->chunk(100, function ($batch) use (&$allRecords) {
            foreach ($batch as $sale) {
                foreach ($sale->details as $detail) {
                    $allRecords[] = (object) [
                        'date' => $sale->date,
                        'number' => $sale->formatted_number,
                        'client_name' => $sale->client?->name ?? 'Venta Mostrador',
                        'product_name' => $detail->product?->name ?? 'Producto Eliminado',
                        'product_code' => $detail->product?->code ?? '—',
                        'quantity' => (float)$detail->quantity,
                        'price' => (float)$detail->unit_price,
                        'cost' => (float)$detail->unit_cost,
                        'subtotal' => (float)$detail->subtotal,
                        'profit' => (float)($detail->subtotal - ($detail->quantity * $detail->unit_cost)),
                        'user_name' => $sale->user?->name ?? 'Sistema',
                    ];
                }
            }
        });

        $summary = [
            'total_sales' => array_sum(array_column($allRecords, 'subtotal')),
            'total_profit' => array_sum(array_column($allRecords, 'profit')),
            'total_items' => array_sum(array_column($allRecords, 'quantity')),
            'count' => count($allRecords),
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'user' => $request->filled('user_id') ? User::find($request->user_id)?->name : 'TODOS',
        ];

        $pdf = Pdf::loadView('exports.sales-report', [
            'records' => $allRecords,
            'summary' => (object)$summary
        ])->setPaper('a4', 'landscape');

        $filename = 'Reporte_Ventas_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadExcel(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        // Consulta optimizada para Excel
        $detailsQuery = \App\Models\SaleDetail::query()
            ->select('sale_details.*') // Evitar colisión de IDs con el join
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->with(['product', 'sale.client', 'sale.user'])
            ->whereDate('sales.date', '>=', $request->date_from)
            ->whereDate('sales.date', '<=', $request->date_to)
            ->where('sales.status', '!=', 'cancelada');

        if ($request->filled('user_id')) {
            $detailsQuery->where('sales.user_id', $request->user_id);
        }

        // Límite de seguridad para estabilidad
        $totalCount = $detailsQuery->count();
        if ($totalCount > 3000) {
            return back()->with('error', "La exportación a Excel está limitada a 3,000 registros por descarga. (Encontrados: $totalCount)");
        }

        $detailsQuery->orderBy('sales.date', 'asc')->orderBy('sales.number', 'asc');

        $summaryData = [
            'total_sales' => (float)$detailsQuery->sum('sale_details.subtotal'),
            'total_profit' => (float)$detailsQuery->sum(\Illuminate\Support\Facades\DB::raw('sale_details.subtotal - (sale_details.quantity * sale_details.unit_cost)'))
        ];

        $filename = 'Reporte_Ganancias_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new SalesExport($detailsQuery, $summaryData), $filename);
    }
}
