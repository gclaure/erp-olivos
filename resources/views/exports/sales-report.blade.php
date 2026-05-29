<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas y Ganancias</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 8pt; line-height: 1.2; }
        .header { margin-bottom: 15px; border-bottom: 2px solid #334155; padding-bottom: 8px; }
        .header table { width: 100%; border-collapse: collapse; }
        .title { font-size: 16pt; font-weight: bold; color: #1e293b; margin: 0; }
        .company-name { font-size: 11pt; font-weight: bold; color: #475569; }
        .subtitle { font-size: 9pt; color: #64748b; margin-top: 3px; }
        
        .summary-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; margin-bottom: 15px; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-item { text-align: center; border-right: 1px solid #e2e8f0; }
        .summary-item:last-child { border-right: none; }
        .summary-label { font-size: 7pt; color: #64748b; text-transform: uppercase; margin-bottom: 1px; }
        .summary-value { font-size: 11pt; font-weight: bold; color: #0f172a; }
        .summary-profit { color: #16a34a; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: fixed; }
        .items-header { background-color: #334155; color: #ffffff; font-weight: bold; text-align: left; padding: 5px; font-size: 7.5pt; text-transform: uppercase; }
        .item-cell { padding: 4px 5px; border-bottom: 1px solid #e2e8f0; font-size: 7.5pt; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .profit-positive { color: #166534; font-weight: bold; }
        .profit-negative { color: #991b1b; font-weight: bold; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 7pt; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="width: 60%;">
                    <div class="company-name">GCLAURE INVENTORY SRL</div>
                    <div class="title">REPORTE DE VENTAS Y GANANCIAS</div>
                    <div class="subtitle">Periodo: {{ \Carbon\Carbon::parse($summary->date_from)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($summary->date_to)->format('d/m/Y') }}</div>
                </td>
                <td style="width: 40%; text-align: right;">
                    <div style="color: #64748b;">Usuario: {{ $summary->user }}</div>
                    <div style="color: #64748b;">Emisión: {{ now()->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="summary-item">
                    <div class="summary-label">Total Ítems Vendidos</div>
                    <div class="summary-value">{{ number_format($summary->total_items) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Ingreso Total (Ventas)</div>
                    <div class="summary-value">Bs {{ number_format($summary->total_sales, 2) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Utilidad Bruta Total</div>
                    <div class="summary-value summary-profit">Bs {{ number_format($summary->total_profit, 2) }}</div>
                </td>
                <td class="summary-item">
                    <div class="summary-label">Margen Promedio</div>
                    <div class="summary-value">
                        {{ $summary->total_sales> 0 ? number_format(($summary->total_profit / $summary->total_sales) * 100, 2) : 0 }}%
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @foreach(array_chunk($records, 35) as $chunk)
    <table class="items-table">
        <thead>
            <tr>
                <th class="items-header text-center" style="width: 55px;">FECHA</th>
                <th class="items-header" style="width: 45px;">NRO.</th>
                <th class="items-header">PRODUCTO</th>
                <th class="items-header text-center" style="width: 35px;">CANT</th>
                <th class="items-header text-right" style="width: 55px;">P.UNIT</th>
                <th class="items-header text-right" style="width: 55px;">C.UNIT</th>
                <th class="items-header text-right" style="width: 65px;">SUBTOTAL</th>
                <th class="items-header text-right" style="width: 65px;">GANANCIA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chunk as $s)
                <tr>
                    <td class="item-cell text-center">{{ \Carbon\Carbon::parse($s->date)->format('d/m/Y') }}</td>
                    <td class="item-cell font-bold">#{{ $s->number }}</td>
                    <td class="item-cell">
                        <div class="font-bold">{{ $s->product_name }}</div>
                        <div style="font-size: 6.5pt; color: #64748b;">{{ $s->product_code }} | {{ $s->client_name }}</div>
                    </td>
                    <td class="item-cell text-center">{{ number_format($s->quantity) }}</td>
                    <td class="item-cell text-right">Bs {{ number_format($s->price, 2) }}</td>
                    <td class="item-cell text-right">Bs {{ number_format($s->cost, 2) }}</td>
                    <td class="item-cell text-right font-bold">Bs {{ number_format($s->subtotal, 2) }}</td>
                    <td class="item-cell text-right {{ $s->profit>= 0 ? 'profit-positive' : 'profit-negative' }}">
                        Bs {{ number_format($s->profit, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        @if($loop->last)
        <tfoot>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="6" class="item-cell text-right" style="border: none; font-size: 9pt;">TOTALES DEL PERIODO</td>
                <td class="item-cell text-right" style="border: none; font-size: 9pt;">Bs {{ number_format($summary->total_sales, 2) }}</td>
                <td class="item-cell text-right summary-profit" style="border: none; font-size: 9pt;">Bs {{ number_format($summary->total_profit, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
    @if(!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
    @endforeach

    <div class="footer">
        Generado por GCLAURE INVENTORY SRL - Página 1
    </div>
</body>
</html>
