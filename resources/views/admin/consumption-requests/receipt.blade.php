<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Consumo Interno - #{{ $request->formatted_number }}</title>
    <style>
        /* ══════════════════════════════════════════════════
           PALETA DE IDENTIDAD LOS OLIVOS
           PRIMARY:    #73AC32  (Verde Olivo)
           SECONDARY:  #44773C  (Verde Bosque)
           ACCENT:     #A8C98A  (Verde Salvia)
           TEXT:       #050607  (Negro Olivo)
           BACKGROUND: #FAF9F5  (Marfil)
           SURFACE:    #E9E9E6  (Gris Piedra)
           ══════════════════════════════════════════════════ */
        @page {
            margin: 12mm 14mm 16mm 14mm;
            size: letter portrait;
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
        
        /* HEADER PRINCIPAL */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #44773C; /* SECONDARY: Verde Bosque */
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
            border: none;
        }
        .branding-left {
            width: 58%;
        }
        .doc-info-right {
            width: 42%;
            text-align: right;
        }
        .logo-container {
            vertical-align: middle;
            padding-right: 10px;
        }
        .logo-img {
            max-width: 85px;
            max-height: 65px;
            object-fit: contain;
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
            font-size: 12pt;
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
        .badge-pending { 
            background-color: #FAF9F5; 
            color: #B45309; 
            border-color: #FCD34D; 
        }
        .badge-approved { 
            background-color: #FAF9F5; 
            color: #44773C; 
            border-color: #A8C98A; 
        }
        .badge-observed { 
            background-color: #FAF9F5; 
            color: #C2410C; 
            border-color: #FED7AA; 
        }
        .badge-dispatched { 
            background-color: #E9E9E6; 
            color: #44773C; 
            border-color: #73AC32; 
        }
        .badge-delivered { 
            background-color: #FAF9F5; 
            color: #44773C; 
            border-color: #73AC32; 
        }
        .badge-cancelled { 
            background-color: #FEF2F2; 
            color: #B91C1C; 
            border-color: #FECACA; 
        }

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
            margin-bottom: 5px;
        }
        .data-row {
            font-size: 8pt;
            margin-bottom: 3px;
        }
        .data-label {
            font-weight: 700;
            color: #6B6B67;
            width: 110px;
            display: inline-block;
        }
        .data-val {
            font-weight: 600;
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
            margin-bottom: 12px;
            border: 1px solid #E9E9E6; /* SURFACE */
            border-radius: 4px;
        }
        .items-table th {
            background-color: #44773C; /* SECONDARY: Verde Bosque */
            color: #FFFFFF;
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 6px 5px;
            border: none;
        }
        .items-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #E9E9E6; /* SURFACE */
            font-size: 8pt;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) td {
            background-color: #FAF9F5; /* BACKGROUND: Marfil */
        }
        .items-table tr.highlight-diff td {
            background-color: #FFFBEB !important;
        }
        
        .code-cell {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.5pt;
            font-weight: 700;
            color: #44773C; /* SECONDARY */
            text-align: center;
        }
        .product-title {
            font-weight: 800;
            color: #050607; /* TEXT */
            text-transform: uppercase;
            font-size: 8pt;
        }
        .product-meta {
            font-size: 7pt;
            color: #6B6B67;
            margin-top: 1px;
        }
        .diff-tag {
            display: inline-block;
            background-color: #FEF3C7;
            color: #92400E;
            border: 1px solid #FCD34D;
            border-radius: 3px;
            padding: 1px 4px;
            font-size: 6.5pt;
            font-weight: 800;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .diff-obs {
            font-size: 7pt;
            color: #B45309;
            font-style: italic;
            margin-top: 1px;
            font-weight: 600;
        }
        .qty-num {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 800;
            font-size: 8.5pt;
            text-align: right;
        }
        .qty-requested { color: #050607; } /* TEXT */
        .qty-delivered { color: #73AC32; } /* PRIMARY: Verde Olivo */
        .qty-received { color: #44773C; } /* SECONDARY: Verde Bosque */
        .qty-diff { color: #B45309; font-weight: 900; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* NOTAS */
        .notes-box {
            background-color: #FAF9F5; /* BACKGROUND: Marfil */
            border: 1px solid #E9E9E6; /* SURFACE */
            border-left: 3.5px solid #73AC32; /* PRIMARY */
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 15px;
        }
        .notes-title {
            font-size: 7pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #44773C; /* SECONDARY */
            margin-bottom: 2px;
        }
        .notes-content {
            font-size: 7.5pt;
            color: #050607; /* TEXT */
        }

        /* SECCIÓN DE FIRMAS */
        .signatures-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
            margin-top: 20px;
            margin-left: -12px;
            margin-right: -12px;
            page-break-inside: avoid;
        }
        .sig-cell {
            width: 33.33%;
            vertical-align: top;
            text-align: center;
        }
        .sig-line {
            width: 85%;
            border-top: 1.5px solid #44773C; /* SECONDARY: Verde Bosque */
            margin: 35px auto 4px auto;
        }
        .sig-name {
            font-size: 8pt;
            font-weight: 800;
            color: #050607; /* TEXT */
            text-transform: uppercase;
        }
        .sig-role {
            font-size: 7pt;
            font-weight: 700;
            color: #44773C; /* SECONDARY */
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .sig-time {
            font-size: 6.5pt;
            color: #6B6B67;
            margin-top: 1px;
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
            border-top: 1px solid #E9E9E6; /* SURFACE */
            padding-top: 4px;
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

        $statusClass = 'badge-pending';
        $statusLabel = 'Pendiente de Aprobación';
        switch ($request->status) {
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
                $statusLabel = 'Despachado (En Tránsito)';
                break;
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
    @endphp

    <!-- ENCABEZADO CORPORATIVO -->
    <table class="header-table">
        <tr>
            <td class="branding-left">
                <table style="border-collapse: collapse;">
                    <tr>
                        @if($logoBase64)
                        <td class="logo-container">
                            <img src="{{ $logoBase64 }}" class="logo-img">
                        </td>
                        @endif
                        <td>
                            <div class="company-name">{{ $company?->name ?? $request->warehouse->branch->company->name }}</div>
                            <div class="branch-title">Sucursal: {{ $request->warehouse->branch->name }}</div>
                            <div class="company-subtext">
                                {{ $request->warehouse->branch->address }}<br>
                                @if($request->warehouse->branch->phone)
                                Tel: {{ $request->warehouse->branch->phone }} &bull;
                                @endif
                                Sistema ERP Los Olivos
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="doc-info-right">
                <div class="doc-title-badge">Solicitud de Consumo</div>
                <div class="doc-number">Nº {{ $request->formatted_number }}</div>
                <div class="meta-dates">
                    <strong>Fecha Emisión:</strong> {{ $request->created_at->format('d/m/Y') }} · {{ $request->created_at->format('H:i') }} hrs
                </div>
                <div>
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- BLOQUES DE METADATOS Y TRAZABILIDAD -->
    <table class="cards-table">
        <tr>
            <!-- DATOS DE LA SOLICITUD -->
            <td class="card-cell">
                <div class="card-header">Datos de la Solicitud</div>
                <div class="data-row">
                    <span class="data-label">Área Solicitante:</span>
                    <span class="data-val" style="color: #44773C; text-transform: uppercase;">{{ $request->requested_by ?? 'No especificada' }}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Almacén de Origen:</span>
                    <span class="data-val">{{ $request->warehouse->name }}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Creado por:</span>
                    <span class="data-val">{{ $request->user->name }}</span>
                </div>
            </td>

            <!-- TRAZABILIDAD DEL CICLO DE VIDA -->
            <td class="card-cell">
                <div class="card-header">Trazabilidad del Ciclo Operativo</div>
                <div class="data-row">
                    <span class="data-label">1. Aprobación:</span>
                    <span class="data-val">
                        @if($request->approvedByUser)
                            {{ $request->approvedByUser->name }} ({{ $request->approved_at ? $request->approved_at->format('d/m/Y H:i') : 'OK' }})
                        @elseif($request->status === 'observado')
                            <span style="color: #C2410C;">Observado por {{ $request->observedByUser?->name ?? 'Admin' }}</span>
                        @else
                            <span style="color: #6B6B67;">Pendiente de Aprobación</span>
                        @endif
                    </span>
                </div>
                <div class="data-row">
                    <span class="data-label">2. Despacho Almacén:</span>
                    <span class="data-val">
                        @if($request->dispatchedByUser)
                            {{ $request->dispatchedByUser->name }} ({{ $request->dispatched_at ? $request->dispatched_at->format('d/m/Y H:i') : 'OK' }})
                        @else
                            <span style="color: #6B6B67;">Pendiente de Despacho</span>
                        @endif
                    </span>
                </div>
                <div class="data-row">
                    <span class="data-label">3. Recepción Destino:</span>
                    <span class="data-val">
                        @if($request->receivedByUser)
                            {{ $request->receivedByUser->name }} ({{ $request->received_at ? $request->received_at->format('d/m/Y H:i') : 'OK' }})
                        @else
                            <span style="color: #6B6B67;">Pendiente de Recepción</span>
                        @endif
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- NOTAS / OBSERVACIONES GENERALES -->
    @if($request->notes || $request->observation_notes)
    <div class="notes-box">
        <div class="notes-title">Notas de la Solicitud / Observaciones Generales</div>
        <div class="notes-content">
            @if($request->notes)
                <div><strong>Notas:</strong> {{ $request->notes }}</div>
            @endif
            @if($request->observation_notes)
                <div style="margin-top: 2px; color: #B45309;"><strong>Nota de Aprobación/Revisión:</strong> {{ $request->observation_notes }}</div>
            @endif
        </div>
    </div>
    @endif

    <!-- TABLA DE DETALLE DE INSUMOS -->
    <div class="section-heading">Detalle de Insumos, Despacho y Recepción</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 14%; text-align: center;">CÓDIGO</th>
                <th style="width: 39%;">DESCRIPCIÓN DEL INSUMO</th>
                <th style="width: 14%; text-align: right;">SOLICITADO</th>
                <th style="width: 14%; text-align: right;">DESPACHADO</th>
                <th style="width: 14%; text-align: right;">RECIBIDO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($request->details as $index => $detail)
                @php
                    $reqQty = (float)$detail->quantity_requested;
                    $delivQty = (float)($detail->quantity_delivered ?? 0);
                    $recvQty = $detail->quantity_received !== null ? (float)$detail->quantity_received : null;
                    $unitName = $detail->product->unitOfMeasure->abbreviation ?? $detail->product->unitOfMeasure->name ?? 'UND';
                    
                    $hasDiff = ($recvQty !== null && abs($recvQty - $reqQty) >= 0.01);
                    $obsText = $detail->receive_observation ?? $detail->observation;
                @endphp
                <tr class="{{ $hasDiff ? 'highlight-diff' : '' }}">
                    <td class="text-center" style="font-weight: 700; color: #6B6B67;">
                        {{ $index + 1 }}
                    </td>
                    <td class="code-cell">
                        {{ $detail->product->code }}
                    </td>
                    <td>
                        <div class="product-title">{{ $detail->product->name }}</div>
                        <div class="product-meta">
                            Unidad: {{ $unitName }}
                            @if($detail->product->package_name && $detail->product->units_per_package > 1)
                                &bull; Empaque: {{ $detail->product->package_name }} ({{ $detail->product->units_per_package }} {{ $unitName }})
                            @endif
                            @if($detail->product->location)
                                &bull; Ubicación: {{ $detail->product->location }}
                            @endif
                        </div>
                        @if($hasDiff && $obsText)
                            <div class="diff-tag">Diferencia Reportada</div>
                            <div class="diff-obs">Motivo: "{{ $obsText }}"</div>
                        @elseif($obsText)
                            <div class="diff-obs">Nota: "{{ $obsText }}"</div>
                        @endif
                    </td>
                    <td class="qty-num qty-requested">
                        {{ number_format($reqQty, 2) }} <span style="font-size: 6.5pt; color: #6B6B67; font-weight: normal;">{{ $unitName }}</span>
                    </td>
                    <td class="qty-num qty-delivered">
                        @if($request->status === 'pendiente' || $request->status === 'aprobado' || $request->status === 'observado')
                            <span style="color: #6B6B67; font-weight: normal; font-size: 7.5pt;">0.00</span>
                        @else
                            {{ number_format($delivQty, 2) }} <span style="font-size: 6.5pt; color: #6B6B67; font-weight: normal;">{{ $unitName }}</span>
                        @endif
                    </td>
                    <td class="qty-num {{ $hasDiff ? 'qty-diff' : 'qty-received' }}">
                        @if($recvQty !== null)
                            {{ number_format($recvQty, 2) }} <span style="font-size: 6.5pt; color: #6B6B67; font-weight: normal;">{{ $unitName }}</span>
                            @if($hasDiff)
                                <span style="color: #B45309; font-size: 8pt;">⚠</span>
                            @endif
                        @elseif($request->status === 'entregado')
                            {{ number_format($delivQty, 2) }} <span style="font-size: 6.5pt; color: #6B6B67; font-weight: normal;">{{ $unitName }}</span>
                        @else
                            <span style="color: #6B6B67; font-weight: normal; font-size: 7.5pt;">Pendiente</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- SECCIÓN DE FIRMAS TRIPARTITAS -->
    <table class="signatures-table">
        <tr>
            <!-- 1. SOLICITANTE -->
            <td class="sig-cell">
                <div class="sig-line"></div>
                <div class="sig-name">{{ $request->user->name }}</div>
                <div class="sig-role">Solicitante ({{ $request->requested_by ?? 'Operaciones' }})</div>
                <div class="sig-time">Emisión: {{ $request->created_at->format('d/m/Y H:i') }}</div>
            </td>

            <!-- 2. DESPACHO ALMACÉN -->
            <td class="sig-cell">
                <div class="sig-line"></div>
                <div class="sig-name">{{ $request->dispatchedByUser?->name ?? 'Encargado Almacén' }}</div>
                <div class="sig-role">Despacho de Almacén</div>
                <div class="sig-time">
                    {{ $request->dispatched_at ? 'Despacho: ' . $request->dispatched_at->format('d/m/Y H:i') : 'Firma y Sello Almacén' }}
                </div>
            </td>

            <!-- 3. RECEPCIÓN CONFORME -->
            <td class="sig-cell">
                <div class="sig-line"></div>
                <div class="sig-name">{{ $request->receivedByUser?->name ?? ($request->user->name ?? 'Receptor Destino') }}</div>
                <div class="sig-role">Recepción Conforme Destino</div>
                <div class="sig-time">
                    {{ $request->received_at ? 'Recibido: ' . $request->received_at->format('d/m/Y H:i') : 'Firma y Sello Destino' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- PIE DE PÁGINA INSTITUCIONAL -->
    <div class="footer">
        Solicitud de Consumo Interno Nº {{ $request->formatted_number }} &bull; Generado: {{ date('d/m/Y H:i') }} &bull; Usuario de Impresión: {{ auth()->user()->name ?? 'Sistema' }} &bull; Documento Oficial de Control y Auditoría Interna &bull; Los Olivos ERP
    </div>
</body>
</html>
