<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Consumo #{{ $request->formatted_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            line-height: 1.15;
            margin: 0;
            padding: 3mm 4mm;
            color: #050607;
            background-color: #ffffff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        .divider-dashed {
            border-top: 0.5pt dashed #777;
            margin: 4pt 0;
        }
        .divider-solid {
            border-top: 1.2pt solid #44773C;
            margin: 2pt 0;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 4pt;
        }
        .logo-img {
            max-width: 130px;
            max-height: 55px;
            object-fit: contain;
            display: inline-block;
        }

        .header { margin-bottom: 4pt; }
        .company-title {
            font-size: 9.5pt;
            font-weight: 900;
            color: #44773C;
        }
        
        .title-block { margin: 4pt 0; }
        .title-text {
            font-size: 11pt;
            font-weight: 900;
            letter-spacing: 0.5pt;
            color: #44773C;
        }
        .status-badge {
            display: inline-block;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 1pt 4pt;
            border-radius: 3px;
            border: 0.8pt solid #44773C;
            margin-top: 2pt;
        }
        
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1.5pt 0; vertical-align: top; }
        
        .meta-table td {
            font-size: 7.5pt;
        }
        
        .items-header {
            border-bottom: 0.8pt solid #000;
            font-weight: bold;
            font-size: 7.5pt;
            color: #44773C;
        }
        .item-name {
            font-weight: bold;
            font-size: 8pt;
            display: block;
            margin-top: 2pt;
        }
        .item-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7pt;
            color: #6B6B67;
        }
        
        .notes-block {
            background-color: #FAF9F5;
            border: 0.5pt solid #E9E9E6;
            border-left: 2pt solid #44773C;
            padding: 3pt 4pt;
            font-size: 7pt;
            margin: 4pt 0;
        }

        .sig-box {
            margin-top: 8pt;
            text-align: center;
            page-break-inside: avoid;
        }
        .sig-line {
            width: 70%;
            border-top: 0.8pt solid #44773C;
            margin: 12pt auto 2pt auto;
        }
        .sig-label {
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .footer {
            margin-top: 6pt;
            font-size: 6.5pt;
            color: #6B6B67;
            text-align: center;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @php
        $company = \App\Facades\CompanyFacade::getCompany();
        $logoBase64 = null;
        $logoPath = public_path('img/logo-light.png');

        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $extension = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoBase64 = 'data:image/' . $extension . ';base64,' . base64_encode($logoData);
        }

        $statusLabel = match($request->status) {
            'pendiente' => 'PENDIENTE',
            'aprobado' => 'APROBADO',
            'observado' => 'OBSERVADO',
            'despachado' => 'DESPACHADO',
            'despachado_parcial' => 'DESPACHO PARCIAL',
            'entregado' => 'ENTREGADO / CONFORME',
            'cancelado' => 'CANCELADO',
            default => strtoupper($request->status)
        };
    @endphp

    @if($logoBase64)
    <div class="logo-container">
        <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
    </div>
    @endif

    <div class="header text-center">
        @if($company?->show_name ?? true)
        <div class="company-title uppercase">{{ $company?->name ?? 'LOS OLIVOS' }}</div>
        @endif
        <div class="uppercase" style="font-size: 7.5pt; font-weight: bold;">Sucursal: {{ $request->warehouse->branch->name }}</div>
        <div style="font-size: 7pt; color: #555;">{{ $request->warehouse->branch->address }}</div>
        @if($request->warehouse->branch->phone)
        <div style="font-size: 7pt; color: #555;">Tel: {{ $request->warehouse->branch->phone }}</div>
        @endif
    </div>

    <div class="title-block text-center">
        <div class="divider-solid"></div>
        <div class="title-text">SOLICITUD DE CONSUMO</div>
        <div><span class="status-badge">{{ $statusLabel }}</span></div>
        <div class="divider-solid"></div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="font-bold" style="width: 35%;">Nº SOLICITUD:</td>
            <td class="text-right font-bold" style="color: #44773C;">#{{ $request->formatted_number }}</td>
        </tr>
        <tr>
            <td class="font-bold">FECHA EMISIÓN:</td>
            <td class="text-right">{{ $request->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="font-bold">ÁREA SOLICITANTE:</td>
            <td class="text-right uppercase">{{ $request->requested_by ?? 'No especificada' }}</td>
        </tr>
        <tr>
            <td class="font-bold">ALMACÉN ORIGEN:</td>
            <td class="text-right uppercase">{{ $request->warehouse->name }}</td>
        </tr>
        <tr>
            <td class="font-bold">SOLICITANTE:</td>
            <td class="text-right uppercase">{{ $request->user->name }}</td>
        </tr>
    </table>

    @if($request->notes || $request->observation_notes)
    <div class="notes-block">
        @if($request->notes)
            <div><strong>Notas:</strong> {{ $request->notes }}</div>
        @endif
        @if($request->observation_notes)
            <div style="margin-top: 2pt; color: #B45309;"><strong>Obs:</strong> {{ $request->observation_notes }}</div>
        @endif
    </div>
    @endif

    <div class="divider-dashed"></div>

    <table>
        <tr class="items-header uppercase">
            <td style="width: 50%;">INSUMO</td>
            <td style="width: 16%; text-align: right;">SOL.</td>
            <td style="width: 17%; text-align: right;">DESP.</td>
            <td style="width: 17%; text-align: right;">REC.</td>
        </tr>
        @foreach($request->details as $index => $detail)
        <tr>
            <td colspan="4" style="padding-top: 3pt;">
                <span class="item-name uppercase">{{ $detail->product->name }}</span>
                <span class="item-code">[{{ $detail->product->code }}] · {{ $detail->product->unitOfMeasure?->symbol ?? 'UND' }}</span>
                
                <table style="width: 100%; margin-top: 1pt;">
                    <tr style="font-size: 7.5pt;">
                        <td style="width: 50%; color: #6B6B67;">Cantidades:</td>
                        <td style="width: 16%; text-align: right; font-weight: bold;">{{ number_format((float)$detail->quantity, 2) }}</td>
                        <td style="width: 17%; text-align: right; font-weight: bold; color: #44773C;">{{ $detail->dispatched_quantity !== null ? number_format((float)$detail->dispatched_quantity, 2) : '-' }}</td>
                        <td style="width: 17%; text-align: right; font-weight: bold; color: #15803D;">{{ $detail->received_quantity !== null ? number_format((float)$detail->received_quantity, 2) : '-' }}</td>
                    </tr>
                </table>

                @if($detail->has_discrepancy && $detail->observation)
                <div style="font-size: 6.5pt; color: #B45309; margin-top: 1pt;">
                    <strong>Obs:</strong> {{ $detail->observation }}
                </div>
                @endif
                <div style="border-bottom: 0.3pt dotted #ccc; margin-top: 2pt;"></div>
            </td>
        </tr>
        @endforeach
    </table>

    <div class="divider-solid"></div>

    <!-- FIRMAS DE CONFORMIDAD -->
    <div class="sig-box">
        <div class="sig-line"></div>
        <div class="sig-label">Solicitado por: {{ $request->user->name }}</div>
    </div>

    @if($request->dispatchedByUser)
    <div class="sig-box">
        <div class="sig-line"></div>
        <div class="sig-label">Despachado por: {{ $request->dispatchedByUser->name }}</div>
    </div>
    @endif

    <div class="sig-box">
        <div class="sig-line"></div>
        <div class="sig-label">Recibido por (Firma y Sello)</div>
    </div>

    <div class="footer">
        <div>Sistema ERP Los Olivos · Ticket de Control Interno</div>
        <div>Impreso: {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>
