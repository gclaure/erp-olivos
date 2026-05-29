<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Kardex - {{ now()->format('d/m/Y') }}</title>
    <style>
        @page {
            margin: 10mm;
            size: letter landscape;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
        }
        .header {
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .company-header {
            width: 50%;
        }
        .document-info {
            width: 50%;
            text-align: right;
        }
        .logo-img {
            max-width: 100px;
            max-height: 100px;
            margin-bottom: 5px;
        }
        .branding-table {
            width: 100%;
            border-collapse: collapse;
        }
        .branding-table td {
            padding: 0;
            vertical-align: middle;
        }
        
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-header {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: left;
            padding: 5px;
            border: 1px solid #dee2e6;
            font-size: 8pt;
            text-transform: uppercase;
        }
        .item-cell {
            padding: 5px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .col-in { background-color: #ecfdf5; }
        .col-out { background-color: #fff1f2; }
        .col-bal { background-color: #f0f9ff; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #777;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    @php
        $company = \App\Facades\CompanyFacade::getCompany();
        $logoBase64 = null;
        $logoPath = $company?->logo_path 
            ? public_path('storage/' . $company->logo_path)
            : public_path('img/logo-inventory.jpg');

        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $extension = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoBase64 = 'data:image/' . $extension . ';base64,' . base64_encode($logoData);
        }
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="company-header">
                    <table class="branding-table">
                        <tr>
                            @if($logoBase64)
                            <td style="width: 110px;">
                                <img src="{{ $logoBase64 }}" class="logo-img">
                            </td>
                            @endif
                            <td>
                                <div class="font-bold" style="font-size: 11pt; color: #444; line-height: 1.2;">{{ $company?->name ?? 'SISTEMA DE INVENTARIO' }}</div>
                                <div style="font-size: 8pt; color: #555; margin-top: 4px; line-height: 1.3;">
                                    {{ $company?->address ?? '' }}<br>
                                    Teléfono: {{ $company?->phone ?? 'S/N' }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="document-info">
                    <div class="font-bold" style="font-size: 14pt; color: #1e293b;">REPORTE DE KARDEX</div>
                    <div style="margin-top: 5px;">Fecha Generación: {{ now()->format('d/m/Y H:i') }}</div>
                    <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">FÍSICO VALORADO</div>
                </td>
            </tr>
        </table>
    </div>

    @foreach(array_chunk($records, 100) as $chunk)
    <table class="items-table">
        <thead>
            <tr>
                <th rowspan="2" class="items-header text-center" style="width: 80px;">FECHA</th>
                <th rowspan="2" class="items-header">PRODUCTO</th>
                <th rowspan="2" class="items-header">DETALLE</th>
                <th colspan="3" class="items-header text-center col-in">ENTRADAS</th>
                <th colspan="3" class="items-header text-center col-out">SALIDAS</th>
                <th colspan="3" class="items-header text-center col-bal">SALDOS</th>
            </tr>
            <tr>
                <th class="items-header text-center col-in">CANT.</th>
                <th class="items-header text-right col-in">C.U.</th>
                <th class="items-header text-right col-in">TOTAL</th>
                <th class="items-header text-center col-out">CANT.</th>
                <th class="items-header text-right col-out">C.U.</th>
                <th class="items-header text-right col-out">TOTAL</th>
                <th class="items-header text-center col-bal">CANT.</th>
                <th class="items-header text-right col-bal">C.P.</th>
                <th class="items-header text-right col-bal">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chunk as $k)
                <tr>
                    <td class="item-cell text-center">{{ $k->created_at->format('d/m/Y') }}</td>
                    <td class="item-cell" style="font-size: 8pt;">
                        <div class="font-bold">{{ $k->product_name }}</div>
                        <div style="color: #666;">{{ $k->product_code }}</div>
                    </td>
                    <td class="item-cell text-uppercase" style="font-size: 7.5pt;">
                        <div class="font-bold">{{ ucfirst(str_replace('_', ' ', strtolower($k->type ?? ''))) }}</div>
                        @if(isset($k->payment_type))
                            <div style="font-size: 6.5pt; color: #666;">PAGO: {{ strtoupper($k->payment_type) }}</div>
                        @endif
                        @if(isset($k->delivery_mode_label))
                            <div style="font-size: 6.5pt; color: #4338ca;">ENTREGA: {{ strtoupper($k->delivery_mode_label) }}</div>
                        @endif
                    </td>
                    
                    @if(in_array(strtoupper($k->type ?? ''), ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA']))
                        <td class="item-cell text-center col-in">{{ number_format($k->quantity, 2) }}</td>
                        <td class="item-cell text-right col-in">{{ number_format($k->unit_cost, 2) }}</td>
                        <td class="item-cell text-right font-bold col-in">{{ number_format($k->total_cost, 2) }}</td>
                    @else
                        <td class="item-cell col-in"></td><td class="item-cell col-in"></td><td class="item-cell col-in"></td>
                    @endif
                    
                    @if(in_array(strtoupper($k->type ?? ''), ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA']))
                        <td class="item-cell text-center col-out">{{ number_format($k->quantity, 2) }}</td>
                        <td class="item-cell text-right col-out">{{ number_format($k->unit_cost, 2) }}</td>
                        <td class="item-cell text-right font-bold col-out">{{ number_format($k->total_cost, 2) }}</td>
                    @else
                        <td class="item-cell col-out"></td><td class="item-cell col-out"></td><td class="item-cell col-out"></td>
                    @endif
                    
                    <td class="item-cell text-center font-bold col-bal">{{ number_format($k->balance_quantity, 2) }}</td>
                    <td class="item-cell text-right col-bal">{{ number_format($k->avg_cost, 2) }}</td>
                    <td class="item-cell text-right font-bold col-bal">{{ number_format($k->balance_total_cost, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        
        @if($loop->last && count($records)> 0)
        <tfoot>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="3" class="text-right" style="padding: 8px;">TOTALES DEL PERIODO</td>
                <td class="text-center col-in" style="padding: 8px;">{{ number_format((float)$summary['total_entradas_qty'], 2) }}</td>
                <td class="col-in"></td>
                <td class="text-right col-in" style="padding: 8px;">Bs {{ number_format((float)$summary['total_entradas_val'], 2) }}</td>
                
                <td class="text-center col-out" style="padding: 8px;">{{ number_format((float)$summary['total_salidas_qty'], 2) }}</td>
                <td class="col-out"></td>
                <td class="text-right col-out" style="padding: 8px;">Bs {{ number_format((float)$summary['total_salidas_val'], 2) }}</td>
                
                <td class="text-center col-bal" style="padding: 8px;">{{ number_format((float)$summary['saldo_qty'], 2) }}</td>
                <td class="col-bal"></td>
                <td class="text-right col-bal" style="padding: 8px;">Bs {{ number_format((float)$summary['saldo_val'], 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
    
    @if(!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
    @endforeach

    @if(count($records) === 0)
    <table class="items-table">
        <tbody>
            <tr>
                <td class="item-cell text-center" style="padding: 20px;">No existen registros para los filtros seleccionados.</td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="footer">
        Generado por Sistema de Inventario - Página 1 de 1
    </div>
</body>
</html>
