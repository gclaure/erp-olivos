<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Stock y Existencias de Insumos</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', sans-serif; color: #1e293b; font-size: 8pt; line-height: 1.25; }
        .header { margin-bottom: 12px; border-bottom: 2px solid #0f766e; padding-bottom: 8px; }
        .header table { width: 100%; border-collapse: collapse; }
        .title { font-size: 14pt; font-weight: bold; color: #0f766e; margin: 0; }
        .company-name { font-size: 11pt; font-weight: bold; color: #334155; }
        .subtitle { font-size: 8.5pt; color: #64748b; margin-top: 3px; }
        
        .summary-box { background-color: #f0fdfa; border: 1px solid #99f6e4; border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-item { text-align: center; border-right: 1px solid #99f6e4; padding: 2px 8px; }
        .summary-item:last-child { border-right: none; }
        .summary-label { font-size: 7pt; color: #0f766e; text-transform: uppercase; margin-bottom: 2px; font-weight: bold; }
        .summary-value { font-size: 11pt; font-weight: bold; color: #115e59; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; table-layout: fixed; }
        .items-header { background-color: #0f766e; color: #ffffff; font-weight: bold; text-align: left; padding: 5px 6px; font-size: 7pt; text-transform: uppercase; }
        .item-cell { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; font-size: 7.5pt; vertical-align: middle; word-wrap: break-word; }
        
        .badge { display: inline-block; padding: 2px 5px; font-size: 6.5pt; font-weight: bold; border-radius: 3px; text-transform: uppercase; }
        .badge-normal { background-color: #dcfce7; color: #166534; }
        .badge-low { background-color: #fef3c7; color: #92400e; }
        .badge-zero { background-color: #fee2e2; color: #991b1b; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 7pt; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="width: 65%;">
                    <div class="company-name">{{ $company->name ?? config('app.name', 'ERP INVENTARIO') }}</div>
                    <div class="title">REPORTE DE EXISTENCIAS Y STOCK DE INSUMOS</div>
                    <div class="subtitle">
                        Filtro de Existencias: <strong>{{ ($summary['only_with_stock'] ?? false) ? 'Solo productos con stock mayor a cero (Stock > 0)' : 'Todos los productos (incluyendo stock en cero)' }}</strong>
                        @if(!empty($summary['warehouse_name'])) | Almacén: {{ $summary['warehouse_name'] }} @endif
                        @if(!empty($summary['category_name'])) | Categoría: {{ $summary['category_name'] }} @endif
                    </div>
                </td>
                <td style="width: 35%; text-align: right;">
                    <div style="color: #64748b;">Generado por: {{ auth()->user()->name ?? 'Sistema' }}</div>
                    <div style="color: #64748b;">Fecha Emisión: {{ now()->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="summary-item">
                    <div class="summary-label">Total Productos</div>
                    <div class="summary-value">{{ number_format($summary['total_items'] ?? 0) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Stock Total (Unidades)</div>
                    <div class="summary-value">{{ number_format((float)($summary['total_quantity'] ?? 0), 2) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Valorización Total</div>
                    <div class="summary-value">Bs {{ number_format((float)($summary['total_value'] ?? 0), 2) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Ítems con Stock Bajo / Agotado</div>
                    <div class="summary-value" style="color: #b91c1c;">{{ number_format($summary['low_stock_count'] ?? 0) }}</div>
                </td>
            </tr>
        </table>
    </div>

    @foreach($records->chunk(30) as $chunk)
    <table class="items-table">
        <thead>
            <tr>
                <th class="items-header" style="width: 60px;">CÓDIGO</th>
                <th class="items-header">PRODUCTO / INSUMO</th>
                <th class="items-header" style="width: 80px;">ALMACÉN</th>
                <th class="items-header text-center" style="width: 35px;">U.M.</th>
                <th class="items-header text-right" style="width: 50px;">STOCK</th>
                <th class="items-header text-right" style="width: 45px;">MÍNIMO</th>
                <th class="items-header text-right" style="width: 50px;">C.PROM</th>
                <th class="items-header text-right" style="width: 55px;">VALOR TOTAL</th>
                <th class="items-header text-center" style="width: 65px;">ESTADO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chunk as $s)
                @php
                    $qty = (float)($s->quantity ?? 0);
                    $minStock = (float)($s->product?->min_stock ?? 0);
                    $avgCost = (float)($s->average_cost ?? 0);
                    $totalVal = (float)($s->inventory_value ?? ($qty * $avgCost));
                    
                    if ($qty <= 0) {
                        $badgeClass = 'badge-zero';
                        $statusText = 'SIN STOCK';
                    } elseif ($qty <= $minStock) {
                        $badgeClass = 'badge-low';
                        $statusText = 'BAJO';
                    } else {
                        $badgeClass = 'badge-normal';
                        $statusText = 'NORMAL';
                    }
                @endphp
                <tr>
                    <td class="item-cell font-bold">
                        {{ $s->product?->code ?? '—' }}
                    </td>
                    <td class="item-cell">
                        <div class="font-bold">{{ $s->product?->name ?? 'Producto Eliminado' }}</div>
                        <div style="font-size: 6.5pt; color: #64748b;">
                            {{ $s->product?->categories?->pluck('name')->join(', ') ?: 'Sin Categoría' }}
                        </div>
                    </td>
                    <td class="item-cell">
                        {{ $s->warehouse?->name ?? '—' }}
                    </td>
                    <td class="item-cell text-center">
                        {{ $s->product?->unitOfMeasure?->abbreviation ?? $s->product?->unitOfMeasure?->name ?? 'UND' }}
                    </td>
                    <td class="item-cell text-right font-bold" style="{{ $qty <= 0 ? 'color: #991b1b;' : ($qty <= $minStock ? 'color: #92400e;' : 'color: #0f766e;') }}">
                        {{ number_format($qty, 2) }}
                    </td>
                    <td class="item-cell text-right">
                        {{ number_format($minStock, 2) }}
                    </td>
                    <td class="item-cell text-right">
                        Bs {{ number_format($avgCost, 2) }}
                    </td>
                    <td class="item-cell text-right font-bold">
                        Bs {{ number_format($totalVal, 2) }}
                    </td>
                    <td class="item-cell text-center">
                        <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach

    <div class="footer">
        Documento generado automáticamente por el Sistema de Inventarios - Página <span class="page-number"></span>
    </div>
</body>
</html>
