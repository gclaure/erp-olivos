<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proforma #{{ $quotation->formatted_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm 297mm;
        }
        body {
            font-family: 'Courier', 'monospace';
            font-size: 8pt;
            line-height: 1.1;
            margin: 0;
            padding: 2mm 4mm;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .header { margin-bottom: 10px; }
        .info { margin-bottom: 10px; font-size: 8pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 0; vertical-align: top; }
        .items th { border-bottom: 1px solid #000; font-size: 8pt; }
        .items td { font-size: 8pt; }
        .totals { margin-top: 10px; }
        .totals td { font-size: 9pt; }
        .footer { margin-top: 15px; font-size: 8pt; }
        .logo { max-width: 50mm; margin-bottom: 5px; }
        .watermark {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) rotate(-45deg);
            font-size: 25pt;
            color: rgba(220, 38, 38, 0.08);
            font-weight: bold;
            z-index: 1000;
            text-transform: uppercase;
            pointer-events: none;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="header text-center">
        @php
            $company = \App\Facades\CompanyFacade::getCompany();
        @endphp
        
        <span class="font-bold uppercase" style="font-size: 11pt;">{{ $company?->name ?? $quotation->branch->company->name }}</span><br>
        <span class="font-bold uppercase" style="font-size: 9pt;">{{ $quotation->branch->name }}</span><br>
        <div style="font-size: 7.5pt; margin-top: 3px;">
            {{ $quotation->branch->address }}<br>
            Teléfono: {{ $quotation->branch->phone ?? 'S/N' }}
        </div>
        
        <div class="divider"></div>
        <h2 style="margin: 5px 0; font-size: 11pt; text-transform: uppercase;">COTIZACIÓN / PROFORMA</h2>
        <div class="divider"></div>
    </div>

    <div style="position: relative;">
        @if($quotation->status === 'cancelada')
            <div class="watermark">CANCELADO</div>
        @endif

        <div class="info">
        <table style="width: 100%; font-size: 8pt;">
            <tr>
                <td class="font-bold">NRO. PROFORMA:</td>
                <td class="text-right">#{{ $quotation->formatted_number }}</td>
            </tr>
            <tr>
                <td>FECHA:</td>
                <td class="text-right">{{ $quotation->date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>VALIDEZ HASTA:</td>
                <td class="text-right">{{ $quotation->valid_until->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>VENDEDOR:</td>
                <td class="text-right">{{ $quotation->user->name }}</td>
            </tr>
        </table>
        
        <div class="divider"></div>
        
        <div style="margin: 5px 0;">
            <span style="font-size: 7pt; color: #444;">CLIENTE:</span><br>
            <span class="font-bold uppercase" style="font-size: 9pt;">{{ $quotation->client->name }}</span><br>
            <span style="font-size: 8pt;">NIT/CI: {{ $quotation->client->document_number ?? '0' }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <table class="items" style="width: 100%;">
        <thead>
            <tr>
                <th class="text-center" style="width: 12%; text-align: left;">CANT.</th>
                <th style="width: 63%; text-align: left;">DESCRIPCIÓN</th>
                <th class="text-right" style="width: 25%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->details as $detail)
            <tr style="border-top: 0.5px solid #eee;">
                <td class="text-center" style="text-align: left; padding-top: 4px;">{{ number_format((float)$detail->quantity, 0) }}</td>
                <td class="uppercase" style="padding-top: 4px;">
                    <span class="font-bold">{{ $detail->product->name }}</span><br>
                    <span style="font-size: 6.5pt; color: #555;">
                        {{ $detail->product->code }} | 
                        P.U: {{ number_format((float)$detail->unit_price, 2) }} 
                        @if($detail->discount> 0)
                        | DESC: {{ number_format((float)$detail->discount, 2) }}
                        @endif
                    </span>
                </td>
                <td class="text-right font-bold" style="padding-top: 4px;">{{ number_format((float)$detail->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="totals" style="margin-left: 15%;">
        <table style="width: 100%;">
            <tr>
                <td class="text-right" style="font-size: 8pt;">SUBTOTAL:</td>
                <td class="text-right font-bold">Bs {{ number_format((float)$quotation->subtotal, 2) }}</td>
            </tr>
            @if($quotation->global_discount> 0)
            <tr>
                <td class="text-right" style="font-size: 8pt;">DESCUENTO:</td>
                <td class="text-right font-bold">-Bs {{ number_format((float)$quotation->global_discount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td class="text-right font-bold" style="font-size: 9pt; padding-top: 5px;">TOTAL:</td>
                <td class="text-right font-bold" style="font-size: 11pt; padding-top: 5px;">Bs {{ number_format((float)$quotation->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="divider" style="margin-top: 15px;"></div>

    <div class="footer text-center" style="margin-top: 5px;">
        <div style="font-size: 7.5pt; font-style: italic; margin-bottom: 10px; padding: 0 5px;">
            @foreach($messages as $msg)
                {{ $msg }}<br>
            @endforeach
        </div>
        <div class="font-bold" style="font-size: 9pt;">Software de Inventario & POS</div>
    </div>

    <div class="divider"></div>

    <div class="footer text-center">
        @foreach($messages as $msg)
            {{ $msg }}<br>
        @endforeach
    </div>
</div>
</body>
</html>
