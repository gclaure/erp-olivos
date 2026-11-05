<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte General de Solicitudes de Consumo Interno</title>
    <style>
        /* ══════════════════════════════════════════════════
           PALETA DE IDENTIDAD LOS OLIVOS (UX/UI & Branding)
           PRIMARY:    #73AC32  (Verde Olivo)
           SECONDARY:  #44773C  (Verde Bosque)
           ACCENT:     #A8C98A  (Verde Salvia)
           TEXT:       #050607  (Negro Olivo)
           BACKGROUND: #FAF9F5  (Marfil)
           SURFACE:    #E9E9E6  (Gris Piedra)
           MUTED:      #6B6B67  (Gris Texto Secundario)
           ══════════════════════════════════════════════════ */
        @page {
            margin: 10mm 12mm 12mm 12mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.3;
            color: #050607;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* ENCABEZADO CORPORATIVO 3 COLUMNAS */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #44773C;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
            border: none;
        }
        .branding-left {
            width: 36%;
            vertical-align: top;
        }
        .branding-center {
            width: 28%;
            text-align: center;
            vertical-align: middle;
            padding: 0 8px;
        }
        .doc-info-right {
            width: 36%;
            text-align: right;
            vertical-align: top;
        }
        .logo-img {
            max-width: 135px;
            max-height: 56px;
            display: inline-block;
        }
        .company-name {
            font-size: 11pt;
            font-weight: bold;
            color: #44773C;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .branch-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #050607;
            margin-top: 2px;
        }
        .company-subtext {
            font-size: 7.2pt;
            color: #6B6B67;
            margin-top: 2px;
        }
        .doc-title-badge {
            font-size: 11pt;
            font-weight: bold;
            color: #44773C;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .doc-subtitle {
            font-size: 8.5pt;
            font-weight: bold;
            color: #73AC32;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .meta-dates {
            font-size: 7.2pt;
            color: #6B6B67;
            margin-top: 3px;
        }

        /* ENCABEZADO COMPACTO PARA PÁGINAS DE CONTINUACIÓN */
        .continuation-header {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #44773C;
            margin-bottom: 8px;
            padding-bottom: 4px;
        }
        .continuation-header td {
            font-size: 7.5pt;
            color: #44773C;
            font-weight: bold;
            padding: 0 0 4px 0;
        }

        /* TARJETAS DE RESUMEN (KPIs) */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px 0;
            margin-bottom: 10px;
            margin-left: -5px;
            margin-right: -5px;
        }
        .kpi-card {
            width: 20%;
            background-color: #FAF9F5;
            border: 1px solid #E9E9E6;
            border-left: 3.5px solid #73AC32;
            padding: 6px 8px;
            vertical-align: top;
        }
        .kpi-card-highlight {
            width: 20%;
            background-color: #FAF9F5;
            border: 1px solid #A8C98A;
            border-left: 4px solid #44773C;
            padding: 6px 8px;
            vertical-align: top;
        }
        .kpi-label {
            font-size: 6.8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #44773C;
            display: block;
        }
        .kpi-value {
            font-size: 10.5pt;
            font-weight: bold;
            color: #050607;
            font-family: Courier, monospace;
            margin-top: 2px;
            display: block;
        }
        .kpi-value-primary {
            font-size: 10.5pt;
            font-weight: bold;
            color: #44773C;
            font-family: Courier, monospace;
            margin-top: 2px;
            display: block;
        }

        /* TABLA DE DATOS */
        .section-heading {
            font-size: 7.8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #44773C;
            margin-bottom: 4px;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #E9E9E6;
        }
        table.items-table th {
            background-color: #44773C;
            color: #FFFFFF;
            font-size: 6.8pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 4px;
            border: none;
            text-align: left;
        }
        table.items-table td {
            padding: 4px 4px;
            border-bottom: 1px solid #E9E9E6;
            font-size: 7.4pt;
            vertical-align: middle;
            color: #050607;
        }
        .row-even td {
            background-color: #FAF9F5;
        }
        .code-cell {
            font-family: Courier, monospace;
            font-size: 7.2pt;
            font-weight: bold;
            color: #44773C;
        }
        .area-pill {
            font-weight: bold;
            color: #44773C;
            text-transform: uppercase;
            font-size: 7.2pt;
        }
        .date-main {
            font-weight: bold;
            color: #050607;
            font-size: 7.1pt;
        }
        .time-sub {
            font-size: 6.7pt;
            color: #44773C;
            font-weight: bold;
        }
        .status-cell {
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }
        .st-pending { color: #B45309; }
        .st-approved { color: #44773C; }
        .st-observed { color: #C2410C; }
        .st-dispatched { color: #44773C; }
        .st-delivered { color: #73AC32; }
        .st-cancelled { color: #B91C1C; }

        .qty-num {
            font-family: Courier, monospace;
            font-weight: bold;
            font-size: 7.5pt;
            text-align: right;
        }
        .qty-delivered {
            color: #73AC32;
        }
        .cost-cell {
            font-family: Courier, monospace;
            font-weight: bold;
            font-size: 7.5pt;
            color: #050607;
            text-align: right;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .prod-cell { font-weight: bold; text-transform: uppercase; }
        .um-cell { text-align: center; color: #6B6B67; font-weight: bold; }

        /* FILA TOTALIZADORA */
        .total-row td {
            background-color: #FAF9F5;
            border-top: 2px solid #44773C;
            border-bottom: 2px solid #73AC32;
            font-weight: bold;
            font-size: 8pt;
            color: #44773C;
            padding: 6px 4px;
        }

        /* PIE DE PÁGINA INSTITUCIONAL */
        .footer {
            margin-top: 6px;
            text-align: center;
            font-size: 6.8pt;
            color: #6B6B67;
            border-top: 1px solid #E9E9E6;
            padding-top: 3px;
        }
    </style>
</head>
<body>
    @php
        $isFirstBatch = $isFirstBatch ?? true;
        $isLastBatch = $isLastBatch ?? true;
        $batchNumber = $batchNumber ?? 1;
        $totalBatches = $totalBatches ?? 1;

        $totalRequests = (int) ($summary['total_requests'] ?? 0);
        $totalItems = (int) ($summary['total_items'] ?? 0);
        $totalRequested = (float) ($summary['total_requested'] ?? 0);
        $totalDelivered = (float) ($summary['total_delivered'] ?? 0);
        $totalCost = (float) ($summary['total_cost'] ?? 0);

        $translateStatus = static function (string $status): string {
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
        };

        $statusClass = static function (string $status): string {
            return match ($status) {
                'pendiente' => 'st-pending',
                'aprobado' => 'st-approved',
                'observado' => 'st-observed',
                'despachado', 'parcial', 'despachado_parcial' => 'st-dispatched',
                'entregado' => 'st-delivered',
                'cancelado' => 'st-cancelled',
                default => 'st-pending',
            };
        };
    @endphp

    @if($isFirstBatch)
        <!-- ENCABEZADO CORPORATIVO LOS OLIVOS (PÁGINA INICIAL) -->
        <table class="header-table">
            <tr>
                <td class="branding-left">
                    <div class="company-name">{{ $company?->name ?? 'LOS OLIVOS ERP' }}</div>
                    <div class="branch-title">Auditoría y Control de Consumo Interno</div>
                    <div class="company-subtext">
                        Área Filtrada: <strong style="color: #44773C;">{{ !empty($filters['area']) ? strtoupper($filters['area']) : 'TODAS LAS ÁREAS' }}</strong>
                        &bull; Estado: <strong>{{ !empty($filters['status']) ? $translateStatus($filters['status']) : 'TODOS' }}</strong>
                    </div>
                </td>
                <td class="branding-center">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" class="logo-img" alt="Logotipo Corporativo">
                    @endif
                </td>
                <td class="doc-info-right">
                    <div class="doc-title-badge">Reporte General de Consumos</div>
                    <div class="doc-subtitle">{{ !empty($filters['area']) ? 'Área: ' . strtoupper($filters['area']) : 'Consolidado General por Ítems' }}</div>
                    <div class="meta-dates">
                        <div><strong>Fecha Emisión:</strong> {{ $generatedDateText ?? '' }}</div>
                        <div style="margin-top: 1px;"><strong>Hora Emisión:</strong> {{ $generatedTimeText ?? '' }}</div>
                        @if(!empty($periodText))
                            <div style="margin-top: 1px;"><strong>Periodo:</strong> {{ $periodText }}</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- TARJETAS DE RESUMEN EJECUTIVO -->
        <table class="kpi-table">
            <tr>
                <td class="kpi-card">
                    <span class="kpi-label">Total Solicitudes</span>
                    <span class="kpi-value">{{ $totalRequests }}</span>
                </td>
                <td class="kpi-card">
                    <span class="kpi-label">Ítems Registrados</span>
                    <span class="kpi-value">{{ $totalItems }}</span>
                </td>
                <td class="kpi-card">
                    <span class="kpi-label">Cant. Total Solicitada</span>
                    <span class="kpi-value">{{ number_format($totalRequested, 2) }}</span>
                </td>
                <td class="kpi-card">
                    <span class="kpi-label">Cant. Total Entregada</span>
                    <span class="kpi-value" style="color: #73AC32;">{{ number_format($totalDelivered, 2) }}</span>
                </td>
                <td class="kpi-card-highlight">
                    <span class="kpi-label">Total (Bs.)</span>
                    <span class="kpi-value-primary">Bs. {{ number_format($totalCost, 2) }}</span>
                </td>
            </tr>
        </table>

        <div class="section-heading">Detalle de Solicitudes e Insumos (Lote {{ $batchNumber }} de {{ $totalBatches }})</div>
    @else
        <!-- ENCABEZADO COMPACTO DE CONTINUACIÓN -->
        <table class="continuation-header">
            <tr>
                <td>{{ $company?->name ?? 'LOS OLIVOS ERP' }} &mdash; Reporte General de Solicitudes de Consumo Interno</td>
                <td class="text-right">Área: {{ !empty($filters['area']) ? strtoupper($filters['area']) : 'TODAS' }} &bull; Lote {{ $batchNumber }} de {{ $totalBatches }}</td>
            </tr>
        </table>
    @endif

    <table class="items-table">
        <thead>
            <tr><th style="width:7%;">N° Solicitud</th><th style="width:17%;">Fecha / Hora</th><th style="width:11%;">Área Solicitante</th><th style="width:13%;">Solicitado Por</th><th style="width:9%;" class="text-center">Estado</th><th style="width:7%;" class="text-center">Código</th><th style="width:18%;">Producto / Insumo</th><th style="width:4%;" class="text-center">U.M.</th><th style="width:4.5%;" class="text-right">Cant. Sol.</th><th style="width:4.5%;" class="text-right">Cant. Ent.</th><th style="width:5%;" class="text-right">Costo Unitario (Bs.)</th></tr>
        </thead>
        <tbody>
            @forelse($records as $idx => $item)
                <tr class="{{ $idx % 2 === 1 ? 'row-even' : '' }}"><td class="code-cell">#{{ $item->formatted_number }}</td><td><span class="date-main">{{ $item->date_formatted }}</span>@if(!empty($item->time_formatted))<br><span class="time-sub">{{ $item->time_formatted }}</span>@endif</td><td class="area-pill">{{ $item->requested_by }}</td><td>{{ $item->user_name }}</td><td class="status-cell {{ $statusClass($item->status) }}">{{ $translateStatus($item->status) }}</td><td class="code-cell text-center">{{ $item->product_code }}</td><td class="prod-cell">{{ $item->product_name }}</td><td class="um-cell">{{ $item->unit_name }}</td><td class="qty-num">{{ number_format($item->quantity_requested, 2) }}</td><td class="qty-num qty-delivered">{{ number_format($item->quantity_delivered, 2) }}</td><td class="cost-cell">{{ number_format($item->unit_cost, 2) }}</td></tr>
            @empty
                <tr><td colspan="11" class="text-center" style="padding: 14px; color: #6B6B67;">No se encontraron registros de solicitudes de consumo para los filtros seleccionados.</td></tr>
            @endforelse
            @if($isLastBatch && $totalItems > 0)
                <tr class="total-row"><td colspan="8" class="text-right">TOTAL (BS.)</td><td class="qty-num" style="color: #44773C;">{{ number_format($totalRequested, 2) }}</td><td class="qty-num" style="color: #73AC32;">{{ number_format($totalDelivered, 2) }}</td><td class="cost-cell" style="color: #44773C;">Bs. {{ number_format($totalCost, 2) }}</td></tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Reporte General de Solicitudes de Consumo Interno &bull; Generado: {{ $generatedDateText ?? '' }} {{ !empty($generatedTimeText) ? '· ' . $generatedTimeText : '' }} &bull; Usuario: {{ $generatedByUser ?? 'Administrador' }} &bull; Los Olivos ERP
    </div>
</body>
</html>
