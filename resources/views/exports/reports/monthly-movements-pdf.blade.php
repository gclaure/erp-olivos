<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Entradas y Salidas Mensuales</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', sans-serif; color: #1e293b; font-size: 8pt; line-height: 1.25; }
        .header { margin-bottom: 12px; border-bottom: 2px solid #2563eb; padding-bottom: 8px; }
        .header table { width: 100%; border-collapse: collapse; }
        .title { font-size: 14pt; font-weight: bold; color: #1e40af; margin: 0; }
        .company-name { font-size: 11pt; font-weight: bold; color: #334155; }
        .subtitle { font-size: 8.5pt; color: #64748b; margin-top: 3px; }
        
        .summary-box { background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-item { text-align: center; border-right: 1px solid #bfdbfe; padding: 2px 8px; }
        .summary-item:last-child { border-right: none; }
        .summary-label { font-size: 7pt; color: #1e40af; text-transform: uppercase; margin-bottom: 2px; font-weight: bold; }
        .summary-value { font-size: 11pt; font-weight: bold; color: #1e3a8a; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; table-layout: fixed; }
        .items-header { background-color: #1e40af; color: #ffffff; font-weight: bold; text-align: left; padding: 5px 6px; font-size: 7pt; text-transform: uppercase; }
        .item-cell { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; font-size: 7.5pt; vertical-align: middle; word-wrap: break-word; }
        
        .badge { display: inline-block; padding: 2px 5px; font-size: 6.5pt; font-weight: bold; border-radius: 3px; text-transform: uppercase; }
        .badge-in { background-color: #dcfce7; color: #166534; }
        .badge-out { background-color: #fee2e2; color: #991b1b; }
        .badge-neutral { background-color: #f1f5f9; color: #475569; }

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
                    <div class="title">REPORTE DE ENTRADAS Y SALIDAS DE ALMACÉN</div>
                    <div class="subtitle">
                        Periodo: {{ $summary['date_from'] ? \Carbon\Carbon::parse($summary['date_from'])->format('d/m/Y') : 'Todos' }} 
                        al {{ $summary['date_to'] ? \Carbon\Carbon::parse($summary['date_to'])->format('d/m/Y') : 'Todos' }}
                        @if(!empty($summary['warehouse_name'])) | Almacén: {{ $summary['warehouse_name'] }} @endif
                        @if(!empty($summary['movement_type'])) | Tipo: {{ $summary['movement_type'] }} @endif
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
                    <div class="summary-label">Total Entradas (Cant.)</div>
                    <div class="summary-value" style="color: #166534;">+{{ number_format((float)($summary['total_entradas_qty'] ?? 0), 2) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Total Salidas (Cant.)</div>
                    <div class="summary-value" style="color: #991b1b;">-{{ number_format((float)($summary['total_salidas_qty'] ?? 0), 2) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Valor Entradas (Bs)</div>
                    <div class="summary-value">Bs {{ number_format((float)($summary['total_entradas_val'] ?? 0), 2) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Valor Salidas (Bs)</div>
                    <div class="summary-value">Bs {{ number_format((float)($summary['total_salidas_val'] ?? 0), 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    @foreach($records->chunk(30) as $chunk)
    <table class="items-table">
        <thead>
            <tr>
                <th class="items-header text-center" style="width: 55px;">FECHA</th>
                <th class="items-header" style="width: 75px;">TIPO</th>
                <th class="items-header">PRODUCTO / INSUMO</th>
                <th class="items-header" style="width: 75px;">ALMACÉN</th>
                <th class="items-header text-right" style="width: 50px;">ENTRADA</th>
                <th class="items-header text-right" style="width: 50px;">SALIDA</th>
                <th class="items-header text-right" style="width: 50px;">C.UNIT</th>
                <th class="items-header text-right" style="width: 55px;">SALDO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chunk as $k)
                @php
                    $isIngreso = in_array(strtoupper($k->type ?? ''), ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA', 'AJUSTE_POSITIVO', 'COMPRA']);
                    $isSalida = in_array(strtoupper($k->type ?? ''), ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA', 'AJUSTE_NEGATIVO', 'CONSUMO', 'MERMA']);
                @endphp
                <tr>
                    <td class="item-cell text-center">
                        {{ $k->created_at ? \Carbon\Carbon::parse($k->created_at)->format('d/m/Y') : '—' }}
                    </td>
                    <td class="item-cell">
                        @if($isIngreso)
                            <span class="badge badge-in">ENTRADA</span>
                        @elseif($isSalida)
                            <span class="badge badge-out">SALIDA</span>
                        @else
                            <span class="badge badge-neutral">{{ $k->type }}</span>
                        @endif
                        <div style="font-size: 6pt; color: #64748b; margin-top: 1px;">{{ str_replace('_', ' ', $k->type) }}</div>
                    </td>
                    <td class="item-cell">
                        <div class="font-bold">{{ $k->product?->name ?? 'Producto Eliminado' }}</div>
                        <div style="font-size: 6.5pt; color: #64748b;">{{ $k->product?->code ?? '—' }}</div>
                    </td>
                    <td class="item-cell">
                        {{ $k->warehouse?->name ?? '—' }}
                    </td>
                    <td class="item-cell text-right font-bold" style="color: #166534;">
                        {{ $isIngreso ? number_format((float)$k->quantity, 2) : '—' }}
                    </td>
                    <td class="item-cell text-right font-bold" style="color: #991b1b;">
                        {{ $isSalida ? number_format((float)$k->quantity, 2) : '—' }}
                    </td>
                    <td class="item-cell text-right">
                        Bs {{ number_format((float)($k->unit_cost ?? 0), 2) }}
                    </td>
                    <td class="item-cell text-right font-bold">
                        {{ number_format((float)($k->balance_quantity ?? 0), 2) }}
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
