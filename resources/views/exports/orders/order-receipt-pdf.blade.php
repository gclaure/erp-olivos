<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reserva #{{ $order->formatted_number }}</title>
    <style>
        @page {
            margin: 6mm 8mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* ─── CABECERA CORPORATIVA Y CONTROL ─── */
        .header-table {
            width: 100%;
            border-bottom: 2.5px solid #0f766e;
            padding-bottom: 7px;
            margin-bottom: 9px;
        }
        .logo-img {
            width: 140px;
            height: auto;
            max-height: 55px;
            display: inline-block;
        }
        .company-name {
            font-size: 11pt;
            font-weight: bold;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0;
            line-height: 1.2;
        }
        .company-sub {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.2;
        }
        .badge-reserva-wrap {
            margin-bottom: 3px;
        }
        .badge-reserva {
            display: inline-block;
            background-color: #0f766e;
            color: #ffffff;
            font-size: 10pt;
            font-weight: bold;
            padding: 3.5px 9px;
            border-radius: 4px;
            letter-spacing: 0.5px;
            vertical-align: middle;
        }
        .badge-cancelled {
            background-color: #dc2626 !important;
        }
        .status-pill {
            display: inline-block;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2.5px 7px;
            border-radius: 4px;
            margin-left: 4px;
            vertical-align: middle;
        }
        .status-pill-active {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .status-pill-cancelled {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .doc-subtitle {
            font-size: 7.5pt;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: bold;
            margin-top: 2px;
        }
        .meta-row {
            font-size: 7pt;
            color: #64748b;
            margin-top: 2px;
        }
        .meta-row strong {
            color: #334155;
        }

        /* ─── BANNER DE ANULACIÓN ─── */
        .cancelled-banner {
            background-color: #fef2f2;
            border: 1.5px solid #ef4444;
            border-radius: 5px;
            padding: 6px 10px;
            margin-bottom: 8px;
            color: #991b1b;
        }
        .cancelled-title {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .cancelled-detail {
            font-size: 7.5pt;
            color: #7f1d1d;
            line-height: 1.4;
        }

        /* ─── TARJETAS GEMELAS: CLIENTE Y ENTREGA ─── */
        .info-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 8px;
            margin-left: -6px;
            margin-right: -6px;
        }
        .info-cell {
            vertical-align: top;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3.5px solid #0f766e;
            border-radius: 5px;
            padding: 6px 9px;
        }
        .card-eyebrow {
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #0f766e;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 2px;
            margin-bottom: 4px;
        }
        .field-value {
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.25;
        }
        .field-subvalue {
            font-size: 7.5pt;
            color: #334155;
            margin-top: 2px;
            line-height: 1.3;
        }
        .sub-label {
            color: #64748b;
            font-weight: bold;
        }

        /* ─── SECCIÓN Y PRODUCTOS SOLICITADOS ─── */
        .section-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f766e;
            letter-spacing: 0.6px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
            margin-top: 8px;
            margin-bottom: 6px;
        }
        .item-card {
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 6px 9px;
            margin-bottom: 6px;
            background-color: #ffffff;
        }
        .item-headline {
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #f1f5f9;
        }
        .item-qty-badge {
            display: inline-block;
            background-color: #f0fdfa;
            color: #0f766e;
            border: 1px solid #ccfbf1;
            font-weight: bold;
            font-size: 8pt;
            padding: 1px 5px;
            border-radius: 3px;
            margin-right: 3px;
        }
        .item-portions {
            font-weight: normal;
            font-size: 8pt;
            color: #64748b;
        }

        .item-body-table {
            width: 100%;
            border-collapse: collapse;
        }
        .details-list {
            margin: 2px 0 4px 12px;
            padding: 0;
        }
        .details-list li {
            margin-bottom: 1.5px;
            font-size: 7.5pt;
            color: #1e293b;
            line-height: 1.3;
        }
        .details-list li strong {
            color: #0f172a;
        }

        .sub-box {
            background-color: #f8fafc;
            border-left: 3px solid #0f766e;
            border-radius: 0 4px 4px 0;
            padding: 3.5px 7px;
            margin-top: 3.5px;
        }
        .sub-box-obs {
            border-left-color: #64748b;
        }
        .sub-box-title {
            font-weight: bold;
            font-size: 6.5pt;
            text-transform: uppercase;
            color: #0f766e;
            letter-spacing: 0.4px;
            margin-bottom: 1px;
        }
        .sub-box-title-obs {
            color: #475569;
        }
        .sub-box-content {
            font-size: 7.5pt;
            color: #1e293b;
            line-height: 1.3;
        }

        /* ─── PANEL DERECHO DE REFERENCIA Y FOTOS ─── */
        .reference-panel {
            background-color: #fafaf9;
            border: 1px solid #e7e5e4;
            border-radius: 4px;
            padding: 5px;
            text-align: center;
        }
        .ref-badge {
            display: inline-block;
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
            max-width: 100%;
            word-wrap: break-word;
        }
        .photos-container {
            text-align: center;
        }
        .photo-frame {
            display: inline-block;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2px;
            margin: 1px;
        }
        .photo-thumb {
            width: 78px;
            height: 78px;
            object-fit: cover;
            display: block;
            border-radius: 2px;
        }

        /* ─── COMPLEMENTOS ─── */
        .complements-list {
            margin: 2px 0 4px 12px;
            padding: 0;
        }
        .complements-list li {
            margin-bottom: 2px;
            font-size: 7.5pt;
            line-height: 1.35;
        }
        .provider-tag {
            font-weight: bold;
            font-size: 6.5pt;
            padding: 1px 5px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .provider-negocio {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .provider-cliente {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        /* ─── NOTAS GENERALES ─── */
        .notes-box {
            margin-top: 4px;
            padding: 5px 8px;
            background-color: #f8fafc;
            border-left: 3px solid #0f766e;
            border-radius: 0 4px 4px 0;
            font-size: 7.5pt;
            color: #334155;
            line-height: 1.35;
        }

        /* ─── FOOTER ─── */
        .footer {
            margin-top: 10px;
            padding-top: 4px;
            border-top: 1px solid #e2e8f0;
            font-size: 6.5pt;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $deliveryModeLabel = $order->delivery_mode instanceof \App\Enums\OrderDeliveryModeEnum
            ? $order->delivery_mode->label()
            : (\App\Enums\OrderDeliveryModeEnum::tryFrom((string) $order->delivery_mode)?->label() ?? 'Recoger en tienda');

        $deliveryDateFormatted = $order->delivery_date
            ? ucfirst($order->delivery_date->locale('es')->translatedFormat('l j \d\e F, Y'))
            : '-';

        $registeredDateFormatted = $order->registered_at
            ? $order->registered_at->format('d/m/Y H:i')
            : '-';

        // Logotipo corporativo en base64 para renderizado seguro en DomPDF
        $logoPath = public_path('img/logo-light.png');
        $logoBase64 = null;
        if (is_file($logoPath) && is_readable($logoPath)) {
            $logoBytes = @file_get_contents($logoPath);
            if ($logoBytes !== false) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode($logoBytes);
            }
        }

        // Recolectar todos los complementos (de ítems y generales)
        $allComplements = collect();
        foreach ($order->items as $it) {
            foreach ($it->complements as $comp) {
                $allComplements->push($comp);
            }
        }
        foreach ($order->complements->whereNull('order_item_id') as $comp) {
            $allComplements->push($comp);
        }
    @endphp

    {{-- ══ CABECERA CORPORATIVA Y CONTROL ══ --}}
    <table class="header-table" cellspacing="0" cellpadding="0">
        <tr>
            {{-- Columna izquierda: Identidad corporativa y sucursal --}}
            <td style="width: 54%; vertical-align: middle;">
                <table cellspacing="0" cellpadding="0" style="width: 100%;">
                    <tr>
                        @if($logoBase64)
                        <td style="width: 145px; vertical-align: middle; padding-right: 10px;">
                            <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo" />
                        </td>
                        @endif
                        <td style="vertical-align: middle;">
                            <div class="company-name">{{ $company?->name ?? 'PASTELERÍA Y PANADERÍA LOS OLIVOS' }}</div>
                            <div class="company-sub">
                                Sucursal: {{ $order->branch?->name ?? 'Casa Matriz' }}
                                @if($order->branch?->phone)
                                    · Tel: {{ $order->branch->phone }}
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>

            {{-- Columna derecha: Ficha técnica / Control de Folio (Llena el espacio con propósito) --}}
            <td style="width: 46%; vertical-align: middle; text-align: right;">
                <div class="badge-reserva-wrap">
                    @if($order->is_cancelled)
                        <span class="badge-reserva badge-cancelled">ANULADO · #{{ $order->formatted_number }}</span>
                    @else
                        <span class="badge-reserva">Reserva #{{ $order->formatted_number }}</span>
                    @endif
                    <span class="status-pill {{ $order->is_cancelled ? 'status-pill-cancelled' : 'status-pill-active' }}">
                        {{ $order->is_cancelled ? 'Anulado' : 'Registrado' }}
                    </span>
                </div>
                <div class="doc-subtitle">Comanda Técnica de Producción</div>
                <div class="meta-row">
                    <strong>Emisión:</strong> {{ $registeredDateFormatted }}
                    @if($order->user)
                        · <strong>Por:</strong> {{ $order->user->name }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- ══ BANNER EN CASO DE ANULACIÓN ══ --}}
    @if($order->is_cancelled)
        <div class="cancelled-banner">
            <div class="cancelled-title">Aviso: Reserva Anulada</div>
            <div class="cancelled-detail">
                <strong>Motivo:</strong> {{ $order->cancellation_reason ?? 'Sin motivo especificado' }}<br>
                @if($order->cancelled_at)
                    <strong>Fecha de anulación:</strong> {{ $order->cancelled_at->format('d/m/Y H:i') }}
                    @if($order->cancelledBy)
                        · <strong>Por:</strong> {{ $order->cancelledBy->name }}
                    @endif
                @endif
            </div>
        </div>
    @endif

    {{-- ══ TARJETAS GEMELAS: CLIENTE Y ENTREGA ══ --}}
    <table class="info-grid" cellspacing="0" cellpadding="0">
        <tr>
            <td class="info-cell" style="width: 50%;">
                <div class="card-eyebrow">Datos del Cliente</div>
                <div class="field-value">{{ $order->customer?->full_name ?? '-' }}</div>
                <div class="field-subvalue">
                    <span class="sub-label">Teléfono / Celular:</span>
                    <strong>{{ $order->customer?->phone ?? '-' }}</strong>
                </div>
            </td>
            <td class="info-cell" style="width: 50%;">
                <div class="card-eyebrow">Logística de Entrega</div>
                <div class="field-value" style="color: #0f766e;">{{ $deliveryDateFormatted }}</div>
                <div class="field-subvalue">
                    <span class="sub-label">Modalidad:</span>
                    <strong>{{ $deliveryModeLabel }}</strong> · {{ $order->delivery_time }}
                </div>
                @if($order->delivery_address)
                    <div class="field-subvalue">
                        <span class="sub-label">Dirección:</span>
                        {{ $order->delivery_address }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ══ PRODUCTOS SOLICITADOS (LAYOUT EN 2 COLUMNAS) ══ --}}
    <div class="section-title">Productos solicitados</div>

    @foreach($order->items as $item)
        @php
            $qtyFormatted = rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.');
            $hasReference = !empty($item->reference_notes) || $item->attachments->isNotEmpty();
        @endphp
        <div class="item-card">
            <div class="item-headline">
                <span class="item-qty-badge">{{ $qtyFormatted }}</span>
                {{ $item->product_name }}
                @if($item->portions_or_size)
                    <span class="item-portions">({{ $item->portions_or_size }})</span>
                @endif
            </div>

            <table class="item-body-table" cellspacing="0" cellpadding="0">
                <tr>
                    {{-- Columna izquierda (68% si hay referencia, 100% si no): especificaciones y decoraciones --}}
                    <td style="width: {{ $hasReference ? '68%' : '100%' }}; vertical-align: top; padding-right: {{ $hasReference ? '8px' : '0' }};">
                        @if($item->details->isNotEmpty())
                            <ul class="details-list">
                                @foreach($item->details as $detail)
                                    <li><strong>{{ $detail->label }}:</strong> {{ $detail->value }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if($item->custom_description)
                            <div class="sub-box">
                                <div class="sub-box-title">Decoración / Descripción personalizada</div>
                                <div class="sub-box-content">{{ $item->custom_description }}</div>
                            </div>
                        @endif

                        @if($item->observations)
                            <div class="sub-box sub-box-obs">
                                <div class="sub-box-title sub-box-title-obs">Observaciones adicionales</div>
                                <div class="sub-box-content">{{ $item->observations }}</div>
                            </div>
                        @endif
                    </td>

                    {{-- Columna derecha (32%): panel enmarcado de referencia visual y fotografías --}}
                    @if($hasReference)
                        <td style="width: 32%; vertical-align: top;">
                            <div class="reference-panel">
                                @if($item->reference_notes)
                                    <div class="ref-badge">Ref: {{ $item->reference_notes }}</div>
                                @endif

                                @if($item->attachments->isNotEmpty())
                                    <div class="photos-container">
                                        @foreach($item->attachments as $att)
                                            @php
                                                $fullStoragePath = storage_path('app/public/' . ltrim($att->file_path, '/'));
                                                $base64Img = null;
                                                if (is_file($fullStoragePath) && is_readable($fullStoragePath)) {
                                                    $rawBytes = @file_get_contents($fullStoragePath);
                                                    if ($rawBytes !== false) {
                                                        $base64Img = 'data:image/webp;base64,' . base64_encode($rawBytes);
                                                    }
                                                }
                                            @endphp
                                            @if($base64Img)
                                                <div class="photo-frame">
                                                    <img src="{{ $base64Img }}" class="photo-thumb" alt="Referencia" />
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    @endforeach

    {{-- ══ COMPLEMENTOS ══ --}}
    @if($allComplements->isNotEmpty())
        <div class="section-title">Complementos</div>
        <ul class="complements-list">
            @foreach($allComplements as $comp)
                @php
                    $providerLabel = $comp->provider_type instanceof \App\Enums\OrderComplementProviderEnum
                        ? $comp->provider_type->label()
                        : (\App\Enums\OrderComplementProviderEnum::tryFrom((string) $comp->provider_type)?->label() ?? 'Cliente');
                    $isNegocio = strtolower($providerLabel) === 'negocio';
                @endphp
                <li>
                    {{ $comp->description }} &nbsp;
                    <span class="provider-tag {{ $isNegocio ? 'provider-negocio' : 'provider-cliente' }}">
                        {{ $providerLabel }}
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- ══ OBSERVACIONES GENERALES DE LA RESERVA ══ --}}
    @if($order->general_notes)
        <div class="section-title">Observaciones de la reserva</div>
        <div class="notes-box">
            {{ $order->general_notes }}
        </div>
    @endif

    {{-- ══ PIE DE PÁGINA ══ --}}
    <div class="footer">
        Comprobante Técnico de Reserva #{{ $order->formatted_number }} · Impreso el {{ $generatedAt }} · {{ $company?->name ?? 'Pastelería y Panadería Los Olivos' }}
    </div>
</body>
</html>
