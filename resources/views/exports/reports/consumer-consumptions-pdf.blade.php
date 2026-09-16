<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Consumos por Consumidor</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', sans-serif; color: #1e293b; font-size: 8pt; line-height: 1.25; }
        .header { margin-bottom: 12px; border-bottom: 2px solid #059669; padding-bottom: 8px; }
        .header table { width: 100%; border-collapse: collapse; }
        .title { font-size: 14pt; font-weight: bold; color: #065f46; margin: 0; }
        .company-name { font-size: 11pt; font-weight: bold; color: #334155; }
        .subtitle { font-size: 8.5pt; color: #64748b; margin-top: 3px; }
        
        .summary-box { background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-item { text-align: center; border-right: 1px solid #bbf7d0; padding: 2px 8px; }
        .summary-item:last-child { border-right: none; }
        .summary-label { font-size: 7pt; color: #166534; text-transform: uppercase; margin-bottom: 2px; font-weight: bold; }
        .summary-value { font-size: 11pt; font-weight: bold; color: #065f46; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; table-layout: fixed; }
        .items-header { background-color: #065f46; color: #ffffff; font-weight: bold; text-align: left; padding: 5px 6px; font-size: 7pt; text-transform: uppercase; }
        .item-cell { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; font-size: 7.5pt; vertical-align: middle; word-wrap: break-word; }
        
        .badge { display: inline-block; padding: 2px 5px; font-size: 6.5pt; font-weight: bold; border-radius: 3px; text-transform: uppercase; }
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-approved { background-color: #dbeafe; color: #1e40af; }
        .badge-dispatched { background-color: #e0e7ff; color: #3730a3; }
        .badge-received { background-color: #dcfce7; color: #166534; }
        .badge-observed { background-color: #ffedd5; color: #9a3412; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }

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
                    <div class="title">REPORTE DE CONSUMOS POR CONSUMIDOR</div>
                    <div class="subtitle">
                        Periodo: {{ $summary['date_from'] ? \Carbon\Carbon::parse($summary['date_from'])->format('d/m/Y') : 'Todos' }} 
                        al {{ $summary['date_to'] ? \Carbon\Carbon::parse($summary['date_to'])->format('d/m/Y') : 'Todos' }}
                        @if(!empty($summary['consumer_name'])) | Consumidor: {{ $summary['consumer_name'] }} @endif
                        @if(!empty($summary['warehouse_name'])) | Almacén: {{ $summary['warehouse_name'] }} @endif
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
                    <div class="summary-label">Total Solicitudes</div>
                    <div class="summary-value">{{ number_format($summary['total_requests'] ?? 0) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Ítems Registrados</div>
                    <div class="summary-value">{{ number_format($summary['total_items'] ?? 0) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Cant. Total Solicitada</div>
                    <div class="summary-value">{{ number_format((float)($summary['total_requested'] ?? 0), 2) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Cant. Total Entregada</div>
                    <div class="summary-value">{{ number_format((float)($summary['total_delivered'] ?? 0), 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    @foreach($records->chunk(30) as $chunk)
    <table class="items-table">
        <thead>
            <tr>
                <th class="items-header" style="width: 48px;">N° SOL.</th>
                <th class="items-header text-center" style="width: 55px;">FECHA</th>
                <th class="items-header" style="width: 100px;">CONSUMIDOR</th>
                <th class="items-header">PRODUCTO / INSUMO</th>
                <th class="items-header text-center" style="width: 40px;">U.M.</th>
                <th class="items-header text-right" style="width: 45px;">SOLIC.</th>
                <th class="items-header text-right" style="width: 45px;">ENTREG.</th>
                <th class="items-header text-center" style="width: 55px;">ESTADO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chunk as $item)
                <tr>
                    <td class="item-cell font-bold">
                        SOL-{{ $item->consumptionRequest?->formatted_number ?? $item->consumptionRequest?->number ?? '—' }}
                    </td>
                    <td class="item-cell text-center">
                        {{ $item->consumptionRequest?->date ? \Carbon\Carbon::parse($item->consumptionRequest->date)->format('d/m/Y') : '—' }}
                    </td>
                    <td class="item-cell">
                        <div class="font-bold">{{ $item->consumptionRequest?->requested_by ?? $item->consumptionRequest?->user?->name ?? '—' }}</div>
                        <div style="font-size: 6.5pt; color: #64748b;">{{ $item->consumptionRequest?->warehouse?->name ?? '' }}</div>
                    </td>
                    <td class="item-cell">
                        <div class="font-bold">{{ $item->product?->name ?? 'Producto Eliminado' }}</div>
                        <div style="font-size: 6.5pt; color: #64748b;">{{ $item->product?->code ?? '—' }}</div>
                    </td>
                    <td class="item-cell text-center">
                        {{ $item->product?->unitOfMeasure?->abbreviation ?? $item->product?->unitOfMeasure?->name ?? 'UND' }}
                    </td>
                    <td class="item-cell text-right font-bold">
                        {{ number_format((float)$item->quantity_requested, 2) }}
                    </td>
                    <td class="item-cell text-right font-bold" style="color: #065f46;">
                        {{ number_format((float)($item->quantity_delivered ?? $item->quantity_requested), 2) }}
                    </td>
                    <td class="item-cell text-center">
                        @php
                            $st = $item->consumptionRequest?->status ?? 'pending';
                            $badges = [
                                'pending' => ['label' => 'Pendiente', 'class' => 'badge-pending'],
                                'approved' => ['label' => 'Aprobado', 'class' => 'badge-approved'],
                                'dispatched' => ['label' => 'Despachado', 'class' => 'badge-dispatched'],
                                'received' => ['label' => 'Recibido', 'class' => 'badge-received'],
                                'observed' => ['label' => 'Observado', 'class' => 'badge-observed'],
                                'cancelled' => ['label' => 'Cancelado', 'class' => 'badge-cancelled'],
                            ];
                            $currBadge = $badges[$st] ?? ['label' => strtoupper($st), 'class' => 'badge-pending'];
                        @endphp
                        <span class="badge {{ $currBadge['class'] }}">{{ $currBadge['label'] }}</span>
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
