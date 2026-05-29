<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Consumo Interno - #{{ $request->formatted_number }}</title>
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
            border-bottom: 2px solid #4f46e5;
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
        .request-info {
            width: 35%;
            text-align: right;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10pt;
            color: #4f46e5;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 8px;
            padding-bottom: 3px;
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
            font-size: 9pt;
            text-transform: uppercase;
            color: #4f46e5;
        }
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
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
            padding: 6px 15px;
            border-radius: 5px;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 8px;
            border: 1.5px solid;
            letter-spacing: 0.5px;
        }
        .badge-pending { 
            background-color: #fffbeb; 
            color: #b45309; 
            border-color: #d97706; 
        }
        .badge-approved { 
            background-color: #f0fdf4; 
            color: #15803d; 
            border-color: #16a34a; 
        }
        .badge-dispatched { 
            background-color: #faf5ff; 
            color: #6d28d9; 
            border-color: #7c3aed; 
        }
        .badge-delivered { 
            background-color: #ecfdf5; 
            color: #047857; 
            border-color: #059669; 
        }
        .badge-observed { 
            background-color: #fff7ed; 
            color: #c2410c; 
            border-color: #ea580c; 
        }
        .badge-cancelled { 
            background-color: #fef2f2; 
            color: #b91c1c; 
            border-color: #dc2626; 
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
                                <div class="font-bold" style="font-size: 11pt; color: #444; line-height: 1.2;">{{ $company?->name ?? $request->warehouse->branch->company->name }}</div>
                                <div class="font-bold" style="font-size: 13pt; margin-top: 2px; line-height: 1.2;">{{ $request->warehouse->branch->name }}</div>
                                <div style="font-size: 8pt; color: #555; margin-top: 4px; line-height: 1.3;">
                                    {{ $request->warehouse->branch->address }}<br>
                                    Teléfono: {{ $request->warehouse->branch->phone ?? 'S/N' }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="request-info">
                    <div class="font-bold" style="font-size: 11pt; color: #4f46e5;">Nº {{ $request->formatted_number }}</div>
                    <div style="margin-top: 5px;">Fecha: {{ $request->created_at->format('d/m/Y') }}</div>
                    <div>Hora: {{ $request->created_at->format('H:i') }}</div>
                </td>
            </tr>
        </table>

        <div style="text-align: center; margin-top: 15px; margin-bottom: 5px;">
            <div style="font-size: 16pt; font-weight: bold; color: #4f46e5; letter-spacing: 1px; text-transform: uppercase;">
                SOLICITUD DE CONSUMO INTERNO
            </div>
            
            @php
                $statusClass = 'badge-pending';
                $statusLabel = 'Pendiente';
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
                    case 'despachado_parcial':
                        $statusClass = 'badge-dispatched';
                        $statusLabel = 'Despachado';
                        break;
                    case 'entregado':
                        $statusClass = 'badge-delivered';
                        $statusLabel = 'Entregado';
                        break;
                    case 'cancelado':
                        $statusClass = 'badge-cancelled';
                        $statusLabel = 'Cancelado';
                        break;
                }
            @endphp
            <div class="badge {{ $statusClass }}" style="margin-top: 5px;">
                {{ $statusLabel }}
            </div>
        </div>
    </div>

    <div class="section">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; border: none; padding: 0; vertical-align: top;">
                    <div class="section-title">DATOS DE LA SOLICITUD</div>
                    <div style="font-size: 10pt; margin-top: 5px;">
                        <span class="font-bold">Área Solicitante:</span> {{ $request->requested_by }}
                    </div>
                </td>
                <td style="width: 50%; border: none; padding: 0; vertical-align: top; text-align: right;">
                    <div class="section-title">RESPONSABLE</div>
                    <div style="font-size: 10pt; margin-top: 5px;">
                        <span class="font-bold">Registrado por:</span> {{ $request->user->name }}<br>
                        <span class="font-bold">Fecha Registro:</span> {{ $request->created_at->format('d/m/Y H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if($request->notes)
    <div class="section" style="background-color: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #eee;">
        <div class="section-title" style="border: none; margin-bottom: 4px; padding-bottom: 0;">NOTAS / OBSERVACIONES GENERALES</div>
        <div style="font-size: 9.5pt; color: #555;">{{ $request->notes }}</div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">DETALLE DE INSUMOS SOLICITADOS</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 15%; text-align: center;">CÓDIGO</th>
                    <th style="width: 40%;">DESCRIPCIÓN DEL PRODUCTO</th>
                    <th style="width: 18%; text-align: center;">ALMACÉN ORIGEN</th>
                    <th style="width: 13%; text-align: center;">SOLICITADO</th>
                    <th style="width: 14%; text-align: center;">UNIDAD</th>
                </tr>
            </thead>
            <tbody>
                @foreach($request->details as $detail)
                <tr>
                    <td class="text-center font-mono" style="font-size: 9pt; color: #666;">
                        {{ $detail->product->code }}
                    </td>
                    <td>
                        <div class="font-bold text-uppercase" style="font-size: 10pt;">{{ $detail->product->name }}</div>
                    </td>
                    <td class="text-center text-uppercase font-bold" style="font-size: 8.5pt; color: #555;">
                        {{ $request->warehouse->name }}
                    </td>
                    <td class="text-center font-bold" style="font-size: 11pt;">
                        {{ number_format((float)$detail->quantity_requested, 2) }}
                    </td>
                    <td class="text-center text-uppercase" style="font-size: 9pt; color: #666;">
                        {{ $detail->product->unitOfMeasure->abbreviation ?? $detail->product->unitOfMeasure->name ?? 'U' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 50px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; text-align: center; border: none; padding: 0;">
                    <div style="width: 200px; border-top: 1px solid #333; margin: 0 auto; padding-top: 5px;">
                        <div class="font-bold" style="font-size: 10pt;">{{ $request->user->name }}</div>
                        <div style="font-size: 8pt; color: #777;">SOLICITANTE</div>
                    </div>
                </td>
                <td style="width: 50%; text-align: center; border: none; padding: 0;">
                    <div style="width: 200px; border-top: 1px solid #333; margin: 0 auto; padding-top: 5px;">
                        <div class="font-bold" style="font-size: 10pt;">FIRMA / SELLO</div>
                        <div style="font-size: 8pt; color: #777; text-transform: uppercase;">ENCARGADO ALMACÉN</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Solicitud de Consumo Interno Nº {{ $request->formatted_number }} | Generado: {{ date('d/m/Y H:i') }} | Usuario: {{ auth()->user()->name ?? 'Sistema' }}
    </div>
</body>
</html>
