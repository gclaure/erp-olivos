<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Solicitud de Consumo Interno - #{{ $consumptionRequest->formatted_number }}</title>
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
            margin: 12mm 14mm 16mm 14mm;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            color: #050607;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* ENCABEZADO CORPORATIVO 3 COLUMNAS */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #44773C; /* SECONDARY: Verde Bosque */
            padding-bottom: 8px;
            margin-bottom: 12px;
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
            max-width: 145px;
            max-height: 70px;
            object-fit: contain;
            display: inline-block;
        }
        .company-name {
            font-size: 11.5pt;
            font-weight: 900;
            color: #44773C; /* SECONDARY: Verde Bosque */
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .branch-title {
            font-size: 9.5pt;
            font-weight: 700;
            color: #050607; /* TEXT: Negro Olivo */
            margin-top: 1px;
        }
        .company-subtext {
            font-size: 7.5pt;
            color: #6B6B67;
            margin-top: 2px;
            line-height: 1.25;
        }
        .doc-title-badge {
            font-size: 11.5pt;
            font-weight: 900;
            color: #44773C; /* SECONDARY: Verde Bosque */
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-number {
            font-size: 11pt;
            font-weight: 900;
            color: #73AC32; /* PRIMARY: Verde Olivo */
            font-family: 'Courier New', Courier, monospace;
            margin-top: 2px;
        }
        .meta-dates {
            font-size: 7.5pt;
            color: #6B6B67;
            margin-top: 4px;
        }

        /* STATUS BADGE */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-top: 4px;
            border: 1px solid transparent;
        }
        .badge-pending { background-color: #FAF9F5; color: #B45309; border-color: #FCD34D; }
        .badge-approved { background-color: #FAF9F5; color: #44773C; border-color: #A8C98A; }
        .badge-observed { background-color: #FAF9F5; color: #C2410C; border-color: #FED7AA; }
        .badge-dispatched { background-color: #E9E9E6; color: #44773C; border-color: #73AC32; }
        .badge-delivered { background-color: #FAF9F5; color: #44773C; border-color: #73AC32; }
        .badge-cancelled { background-color: #FEF2F2; color: #B91C1C; border-color: #FECACA; }

        /* METADATA CARDS */
        .cards-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 12px;
            margin-left: -8px;
            margin-right: -8px;
        }
        .card-cell {
            width: 50%;
            vertical-align: top;
            background-color: #FAF9F5; /* BACKGROUND: Marfil */
            border: 1px solid #E9E9E6; /* SURFACE: Gris Piedra */
            border-left: 3.5px solid #73AC32; /* PRIMARY: Verde Olivo */
            border-radius: 6px;
            padding: 8px 10px;
        }
        .card-header {
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #44773C; /* SECONDARY: Verde Bosque */
            border-bottom: 1px solid #E9E9E6;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .card-info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .card-info-table td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 8pt;
            border: none;
        }
        .card-info-label {
            width: 34%;
            font-weight: 700;
            color: #6B6B67;
            padding-right: 6px;
            white-space: nowrap;
        }
        .card-info-val {
            width: 66%;
            font-weight: 700;
            color: #050607; /* TEXT: Negro Olivo */
        }

        /* TABLA DE PRODUCTOS */
        .section-heading {
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #44773C; /* SECONDARY: Verde Bosque */
            margin-bottom: 6px;
            padding-left: 2px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border: 1px solid #E9E9E6; /* SURFACE */
        }
        .items-table th {
            background-color: #44773C; /* SECONDARY: Verde Bosque */
            color: #FFFFFF;
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 6px 6px;
            border: none;
            text-align: left;
        }
        .items-table td {
            padding: 6px 6px;
            border-bottom: 1px solid #E9E9E6; /* SURFACE */
            font-size: 8pt;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) td {
            background-color: #FAF9F5; /* BACKGROUND: Marfil */
        }
        .code-cell {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.5pt;
            font-weight: 800;
            color: #44773C; /* SECONDARY */
            text-align: center;
        }
        .product-title {
            font-weight: 800;
            color: #050607; /* TEXT */
            text-transform: uppercase;
            font-size: 8pt;
        }
        .qty-num {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 800;
            font-size: 8.5pt;
            text-align: right;
            color: #050607;
        }
        .qty-delivered {
            color: #73AC32; /* PRIMARY: Verde Olivo */
        }
        .cost-cell {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 900;
            font-size: 8.5pt;
            color: #050607;
            text-align: right;
        }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }

        /* FILA TOTALIZADORA */
        .total-row td {
            background-color: #FAF9F5 !important;
            border-top: 2px solid #44773C !important;
            border-bottom: 2px solid #73AC32 !important;
            font-weight: 900;
            font-size: 9pt;
            color: #44773C;
            padding: 7px 6px;
        }

        /* PIE DE PÁGINA */
        .footer {
            position: fixed;
            bottom: -5mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7pt;
            color: #6B6B67;
            border-top: 1px solid #E9E9E6;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    @php
        $logoBase64 = null;
        $logoPath = public_path('img/logo-light.png');
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $extension = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoBase64 = 'data:image/' . $extension . ';base64,' . base64_encode($logoData);
        }

        $totalRequested = 0.0;
        $totalDelivered = 0.0;
        $totalCost = 0.0;

        $resolveUnitCost = function ($detail) use ($consumptionRequest): float {
            $product = $detail->product;
            if (!$product) {
                return 0.0;
            }
            $price = (float) ($product->price ?? 0);
            if ($price > 0) {
                return $price;
            }
            $warehouseId = $consumptionRequest->warehouse_id;
            if ($warehouseId && $product->relationLoaded('stocks')) {
                $stock = $product->stocks->firstWhere('warehouse_id', $warehouseId);
                if ($stock && (float) $stock->average_cost > 0) {
                    return (float) $stock->average_cost;
                }
            }
            return 0.0;
        };

        $statusClass = 'badge-pending';
        $statusLabel = 'Pendiente';
        switch ($consumptionRequest->status) {
            case 'pendiente':
                $statusClass = 'badge-pending';
                $statusLabel = 'Pendiente';
                break;
            case 'aprobado':
                $statusClass = 'badge-approved';
                $statusLabel = 'Aprobado';
                break;
            case 'observado':
                $statusClass = 'badge-observed';
                $statusLabel = 'Observado';
                break;
            case 'despachado':
                $statusClass = 'badge-dispatched';
                $statusLabel = 'Despachado';
                break;
            case 'parcial':
            case 'despachado_parcial':
                $statusClass = 'badge-dispatched';
                $statusLabel = 'Despacho Parcial';
                break;
            case 'entregado':
                $statusClass = 'badge-delivered';
                $statusLabel = 'Entregado / Conforme';
                break;
            case 'cancelado':
                $statusClass = 'badge-cancelled';
                $statusLabel = 'Cancelado';
                break;
        }

        $formatSpanishDate = function ($dateVal, bool $withTime = true, $timeSource = null): string {
            if (!$dateVal && !$timeSource) {
                return '-';
            }
            $d = ($dateVal ?? $timeSource) instanceof \Carbon\CarbonInterface
                ? ($dateVal ?? $timeSource)
                : \Carbon\Carbon::parse($dateVal ?? $timeSource);
            $t = $timeSource instanceof \Carbon\CarbonInterface
                ? $timeSource
                : ($timeSource ? \Carbon\Carbon::parse($timeSource) : $d);
            $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
            $months = [
                1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
                5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
                9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
            ];
            $text = $days[(int) $d->format('w')] . ', ' . $d->format('d') . ' de ' . $months[(int) $d->format('n')] . ' de ' . $d->format('Y');
            return $withTime ? $text . ' · ' . $t->format('H:i') . ' hrs' : $text;
        };

        foreach ($consumptionRequest->details as $d) {
            $totalRequested += (float) ($d->quantity_requested ?? 0);
            $totalDelivered += (float) ($d->quantity_delivered ?? 0);
            $totalCost += $resolveUnitCost($d);
        }
    @endphp

    <!-- ENCABEZADO CORPORATIVO LOS OLIVOS -->
    <table class="header-table">
        <tr>
            <td class="branding-left">
                @if($company?->show_name ?? true)
                    <div class="company-name">{{ $company?->name ?? ($consumptionRequest->warehouse?->branch?->company?->name ?? 'LOS OLIVOS ERP') }}</div>
                @endif
                <div class="branch-title">Sucursal: {{ $consumptionRequest->warehouse?->branch?->name ?? 'Central' }}</div>
                <div class="company-subtext">
                    @if($consumptionRequest->warehouse?->branch?->address)
                        {{ $consumptionRequest->warehouse->branch->address }}<br>
                    @endif
                    @if($consumptionRequest->warehouse?->branch?->phone)
                        Tel: {{ $consumptionRequest->warehouse->branch->phone }} &bull;
                    @endif
                    Sistema ERP Los Olivos
                </div>
            </td>
            <td class="branding-center">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo-img" alt="Logotipo Corporativo">
                @endif
            </td>
            <td class="doc-info-right">
                <div class="doc-title-badge">Reporte de Solicitud</div>
                <div class="doc-number">Nº {{ $consumptionRequest->formatted_number }}</div>
                <div class="meta-dates">
                    <div><strong>Fecha Emisión:</strong> {{ $formatSpanishDate($consumptionRequest->created_at ?? now(), false) }}</div>
                    <div style="margin-top: 1px;"><strong>Hora Emisión:</strong> {{ ($consumptionRequest->created_at ?? now())->format('H:i') }} hrs</div>
                </div>
                <div style="margin-top: 4px;">
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- BLOQUES DE METADATOS Y TRAZABILIDAD -->
    <table class="cards-table">
        <tr>
            <td class="card-cell">
                <div class="card-header">Datos de la Solicitud</div>
                <table class="card-info-table">
                    <tr>
                        <td class="card-info-label">Área Solicitante:</td>
                        <td class="card-info-val" style="color: #44773C; text-transform: uppercase;">{{ $consumptionRequest->requested_by ?? 'No especificada' }}</td>
                    </tr>
                    <tr>
                        <td class="card-info-label">Almacén de Origen:</td>
                        <td class="card-info-val">{{ $consumptionRequest->warehouse?->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="card-info-label">Solicitado por:</td>
                        <td class="card-info-val">{{ $consumptionRequest->user?->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="card-info-label">Estado:</td>
                        <td class="card-info-val">{{ $statusLabel }}</td>
                    </tr>
                </table>
            </td>
            <td class="card-cell">
                <div class="card-header">Trazabilidad y Resumen Financiero</div>
                <table class="card-info-table">
                    <tr>
                        <td class="card-info-label">Fecha Solicitud:</td>
                        <td class="card-info-val">{{ $formatSpanishDate($consumptionRequest->date ?? $consumptionRequest->created_at, false) }}</td>
                    </tr>
                    <tr>
                        <td class="card-info-label">Hora Registro:</td>
                        <td class="card-info-val" style="color: #44773C;">{{ $consumptionRequest->created_at ? $consumptionRequest->created_at->format('H:i') . ' hrs' : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="card-info-label">Total Ítems:</td>
                        <td class="card-info-val">{{ $consumptionRequest->details->count() }} producto(s)</td>
                    </tr>
                    <tr>
                        <td class="card-info-label">Total (Bs.):</td>
                        <td class="card-info-val" style="color: #44773C; font-family: 'Courier New', Courier, monospace; font-weight: 900;">Bs. {{ number_format($totalCost, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TABLA DE PRODUCTOS E IMPORTES -->
    <div class="section-heading">Detalle de Insumos y Costos Unitarios</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 14%;" class="text-center">Código Producto</th>
                <th style="width: 38%;">Producto / Insumo</th>
                <th style="width: 10%;" class="text-center">U.M.</th>
                <th style="width: 12%;" class="text-right">Cant. Solicitada</th>
                <th style="width: 12%;" class="text-right">Cant. Entregada</th>
                <th style="width: 14%;" class="text-right">Costo Unitario (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($consumptionRequest->details as $detail)
                @php
                    $product = $detail->product;
                    $unitCost = $resolveUnitCost($detail);
                    $unitName = $product?->unitOfMeasure?->abbreviation ?? $product?->unitOfMeasure?->name ?? 'UND';
                @endphp
                <tr>
                    <td class="code-cell">{{ $product?->code ?? 'S/C' }}</td>
                    <td>
                        <div class="product-title">{{ $product?->name ?? 'Producto eliminado' }}</div>
                    </td>
                    <td class="text-center" style="color: #6B6B67; font-weight: 700;">{{ $unitName }}</td>
                    <td class="qty-num">{{ number_format((float) ($detail->quantity_requested ?? 0), 2) }}</td>
                    <td class="qty-num qty-delivered">{{ number_format((float) ($detail->quantity_delivered ?? 0), 2) }}</td>
                    <td class="cost-cell">{{ number_format($unitCost, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 12px; color: #6B6B67;">
                        Esta solicitud no contiene ítems registrados.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($consumptionRequest->details->count() > 0)
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="text-right">TOTAL (BS.)</td>
                    <td class="qty-num" style="color: #44773C;">{{ number_format($totalRequested, 2) }}</td>
                    <td class="qty-num" style="color: #73AC32;">{{ number_format($totalDelivered, 2) }}</td>
                    <td class="cost-cell" style="color: #44773C;">Bs. {{ number_format($totalCost, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- PIE DE PÁGINA INSTITUCIONAL -->
    <div class="footer">
        Solicitud de Consumo Interno Nº {{ $consumptionRequest->formatted_number }} &bull; Generado: {{ $formatSpanishDate(now(), true) }} &bull; Usuario: {{ auth()->user()->name ?? 'Administrador' }} &bull; Documento Oficial de Control y Auditoría Interna &bull; Los Olivos ERP
    </div>
</body>
</html>
