<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Movimiento - {{ $movement->id }}</title>
    <style>
        @page {
            margin: 15mm;
            size: letter;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
        }
        .header {
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
            margin-bottom: 20px;
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
            width: 65%;
        }
        .movement-info {
            width: 35%;
            text-align: right;
        }
        .clear {
            clear: both;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10pt;
            color: #666;
            border-bottom: 1px solid #eee;
            margin-bottom: 8px;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border-bottom: 2px solid #dee2e6;
            font-size: 10pt;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9pt;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .font-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 4px; }
        .text-uppercase { text-transform: uppercase; }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-positive { background-color: #dcfce7; color: #166534; }
        .badge-negative { background-color: #fee2e2; color: #991b1b; }
        .logo-container {
            width: 80px;
            vertical-align: middle;
            padding-right: 15px;
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
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="company-header">
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

                    <table class="branding-table">
                        <tr>
                            @if($logoBase64)
                            <td style="width: 110px;">
                                <img src="{{ $logoBase64 }}" class="logo-img">
                            </td>
                            @endif
                            <td>
                                <div class="font-bold" style="font-size: 11pt; color: #444; line-height: 1.2;">{{ $company?->name ?? $movement->warehouse->branch->company->name }}</div>
                                <div class="font-bold" style="font-size: 13pt; margin-top: 2px; line-height: 1.2;">{{ $movement->warehouse->branch->name }}</div>
                                <div style="font-size: 8pt; color: #555; margin-top: 4px; line-height: 1.3;">
                                    {{ $movement->warehouse->branch->address }}<br>
                                    Teléfono: {{ $movement->warehouse->branch->phone ?? 'S/N' }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="movement-info">
                    <div class="font-bold" style="font-size: 11pt; color: #666;">ID: {{ substr($movement->id, 0, 8) }}</div>
                    <div style="margin-top: 5px;">Fecha: {{ $movement->date ? $movement->date->format('d/m/Y') : $movement->created_at->format('d/m/Y') }}</div>
                    <div>Hora: {{ $movement->created_at->format('H:i') }}</div>
                </td>
            </tr>
        </table>

        <div style="text-align: center; margin-top: 15px; margin-bottom: 5px;">
            <div style="font-size: 16pt; font-weight: bold; color: #4f46e5; letter-spacing: 1px; text-transform: uppercase;">
                COMPROBANTE DE MOVIMIENTO
            </div>
            <div class="badge {{ $movement->type === 'entrada' ? 'badge-positive' : 'badge-negative' }}" style="margin-top: 5px;">
                Ajuste de {{ ucfirst($movement->type) }}
            </div>
        </div>
    </div>

    <div class="section">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; border: none; padding: 0;">
                    <div class="section-title">DETALLES DEL ORIGEN</div>
                    <div class="font-bold" style="font-size: 11pt; margin-top: 5px;">Almacén: {{ $movement->warehouse->name }}</div>
                    <div style="font-size: 9pt; color: #555;">Responsable: {{ $movement->user->name }}</div>
                </td>
                <td style="width: 50%; border: none; padding: 0; text-align: right;">
                    <div class="section-title">MOTIVO / CONCEPTO</div>
                    <div class="font-bold" style="font-size: 10pt; margin-top: 5px;">{{ $movement->reason }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if($movement->notes)
    <div class="section" style="background-color: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #eee;">
        <div class="section-title" style="border: none;">OBSERVACIONES</div>
        <div style="font-size: 10pt; color: #555;">{{ $movement->notes }}</div>
    </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15%; text-align: center;">CANTIDAD</th>
                <th style="width: 20%; text-align: center;">UNIDAD</th>
                <th style="width: 50%;">PRODUCTO / DESCRIPCIÓN</th>
                <th style="width: 15%; text-align: center;">CÓDIGO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movement->details as $detail)
            <tr>
                <td class="text-center font-bold" style="font-size: 12pt;">
                    {{ $movement->type === 'entrada' ? '+' : '-' }} {{ number_format((float)$detail->quantity, 2) }}
                </td>
                <td class="text-center text-uppercase" style="font-size: 9pt; color: #666;">
                    {{ $detail->product->unitOfMeasure->name ?? 'Unidades' }}
                </td>
                <td>
                    <div class="font-bold text-uppercase" style="font-size: 11pt;">{{ $detail->product->name }}</div>
                </td>
                <td class="text-center font-mono" style="font-size: 9pt; color: #777;">
                    {{ $detail->product->code }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 50px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; text-align: center; border: none; padding: 0;">
                    <div style="width: 200px; border-top: 1px solid #333; margin: 0 auto; padding-top: 5px;">
                        <div class="font-bold" style="font-size: 10pt;">{{ $movement->user->name }}</div>
                        <div style="font-size: 8pt; color: #777;">PERSONAL RESPONSABLE</div>
                    </div>
                </td>
                <td style="width: 50%; text-align: center; border: none; padding: 0;">
                    <div style="width: 200px; border-top: 1px solid #333; margin: 0 auto; padding-top: 5px;">
                        <div class="font-bold" style="font-size: 10pt;">FIRMA / SELLO</div>
                        <div style="font-size: 8pt; color: #777;">CONTROL DE INVENTARIO</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Documento generado automáticamente por el Sistema de Inventario - {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>
