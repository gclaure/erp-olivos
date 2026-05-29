<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Venta - {{ $sale->formatted_number }}</title>
    <style>
        @page {
            margin: 8mm 10mm;
            size: letter portrait;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5pt;
            line-height: 1.15;
            color: #1f2937;
            margin: 0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        /* Title Block */
        .main-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            color: #1e293b;
            letter-spacing: 4pt;
            margin-bottom: 12pt;
            border-bottom: 1pt solid #e5e7eb;
            padding-bottom: 4pt;
        }

        /* Header Info Layout */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8pt;
        }
        .logo-container {
            width: 80pt;
            vertical-align: top;
        }
        .logo-img {
            max-width: 75pt;
            max-height: 55pt;
            display: block;
        }
        .company-data {
            vertical-align: top;
            padding-left: 10pt;
        }
        .company-name {
            font-size: 13pt;
            font-weight: 900;
            color: #000;
            line-height: 1;
        }
        .branch-info {
            font-size: 7.5pt;
            font-weight: bold;
            color: #4b5563;
            margin-top: 2pt;
        }
        .contact-info {
            font-size: 7.5pt;
            color: #6b7280;
            margin-top: 1pt;
        }

        /* Meta Data (Right Side) */
        .meta-container {
            width: 160pt;
            text-align: right;
            vertical-align: top;
        }
        .meta-row {
            margin-bottom: 2pt;
            font-size: 8.5pt;
        }
        .meta-label {
            color: #6b7280;
            font-weight: bold;
            margin-right: 2pt;
        }
        .meta-value {
            color: #000;
            font-weight: 900;
        }

        /* Client Section (The Gray Bar) */
        .client-bar {
            width: 100%;
            background-color: #f3f4f6;
            border-left: 3pt solid #1e293b;
            margin: 10pt 0;
            border-collapse: collapse;
        }
        .client-bar td {
            padding: 5pt 8pt;
            vertical-align: middle;
        }
        .client-label {
            color: #4b5563;
            font-weight: bold;
            font-size: 8pt;
            width: 1%;
            white-space: nowrap;
        }
        .client-value {
            font-weight: bold;
            font-size: 9pt;
            color: #000;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5pt;
        }
        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 5pt 6pt;
            text-align: left;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 4pt 6pt;
            border-bottom: 0.5pt solid #f3f4f6;
            font-size: 8.5pt;
            vertical-align: middle;
        }
        .code-cell { width: 12%; color: #6b7280; font-size: 7.5pt; }
        .desc-cell { width: 45%; }
        .um-cell { width: 8%; text-align: center; }
        .qty-cell { width: 8%; text-align: center; font-weight: bold; }
        .price-cell { width: 12%; text-align: right; }
        .disc-cell { width: 5%; text-align: right; color: #991b1b; }
        .sub-cell { width: 10%; text-align: right; font-weight: 900; }

        /* Totals Area */
        .summary-container {
            width: 100%;
            margin-top: 12pt;
        }
        .amount-in-words {
            font-style: italic;
            color: #4b5563;
            font-size: 8.5pt;
            padding-top: 5pt;
        }
        .totals-table {
            float: right;
            width: 180pt;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 3pt 5pt;
            font-size: 9pt;
        }
        .total-pay-row {
            border-top: 1.5pt solid #1e293b;
            padding-top: 6pt !important;
        }
        .total-pay-label {
            font-size: 11pt;
            font-weight: 900;
            color: #000;
        }
        .total-pay-value {
            font-size: 12pt;
            font-weight: 900;
            color: #000;
        }

        /* Footer Info */
        .footer-warning {
            color: #dc2626;
            font-weight: bold;
            font-size: 8pt;
            text-align: center;
            margin-top: 10pt;
        }
        .footer-thanks {
            color: #6b7280;
            font-size: 7.5pt;
            text-align: center;
            margin-top: 4pt;
        }
        .footer-system {
            color: #94a3b8;
            font-size: 6pt;
            text-align: center;
            margin-top: 2pt;
        }
        .clear { clear: both; }
        .watermark {
            position: absolute;
            top: 70pt;
            left: 50%;
            transform: translateX(-50%) rotate(-45deg);
            font-size: 55pt;
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
    @if(!$sale->is_active || $sale->status === 'anulada')
        <div class="watermark">ANULADO</div>
    @endif
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

    <div class="main-title uppercase">NOTA DE VENTA</div>

    <table class="header-table">
        <tr>
            <td class="logo-container">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo-img">
                @endif
            </td>
            <td class="company-data">
                <div class="company-name uppercase">{{ $company?->name ?? 'EMPRESA' }}</div>
                <div class="branch-info uppercase">
                    CASA MATRIZ | {{ $sale->warehouse->branch->name }}
                </div>
                <div class="contact-info uppercase">
                    {{ $sale->warehouse->branch->address }}
                </div>
                <div class="contact-info">
                    Teléfono: {{ $sale->warehouse->branch->phone ?? 'S/N' }}
                </div>
            </td>
            <td class="meta-container">
                <table style="width: 110pt; border-collapse: collapse; margin-left: auto;">
                    <tr>
                        <td class="meta-label uppercase" style="text-align: left; padding-bottom: 2pt;">N° NOTA:</td>
                        <td class="meta-value text-right" style="padding-bottom: 2pt;">{{ $sale->formatted_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label uppercase" style="text-align: left;">FECHA:</td>
                        <td class="meta-value text-right">{{ $sale->date->format('d/m/Y') }} {{ $sale->created_at->format('H:i') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="client-bar">
        <tr>
            <td class="client-label uppercase">CLIENTE:</td>
            <td class="client-value uppercase">{{ $sale->client->name }}</td>
            <td class="client-label uppercase text-right" style="width: 10%;">DOC:</td>
            <td class="client-value text-right" style="width: 20%;">{{ $sale->client->document_number ?? 'S/N' }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="code-cell">CÓDIGO</th>
                <th class="desc-cell">DESCRIPCIÓN</th>
                <th class="um-cell">U.M.</th>
                <th class="qty-cell">CANT.</th>
                <th class="price-cell">P. UNIT.</th>
                <th class="disc-cell">DESC.</th>
                <th class="sub-cell">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->details as $detail)
            <tr>
                <td class="code-cell">{{ $detail->product->code }}</td>
                <td class="desc-cell uppercase font-bold">{{ $detail->product->name }}</td>
                <td class="um-cell">{{ $detail->product->unitOfMeasure->abbreviation ?? 'UNI' }}</td>
                <td class="qty-cell">{{ number_format((float)$detail->quantity, 1) }}</td>
                <td class="price-cell">Bs {{ number_format((float)$detail->unit_price, 2) }}</td>
                <td class="disc-cell">{{ number_format((float)$detail->discount, 2) }}</td>
                <td class="sub-cell">Bs {{ number_format((float)$detail->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-container">
        <div style="float: left; width: 55%;" class="amount-in-words">
            @php
                $integerPart = floor($sale->total_payment);
                $decimalPart = round(($sale->total_payment - $integerPart) * 100);
                $words = app(\App\Services\NumberToWordsService::class)->convert($integerPart);
            @endphp
            Son: <span>{{ mb_strtolower($words) }} {{ str_pad((string)$decimalPart, 2, '0', STR_PAD_LEFT) }}/100 bolivianos.</span>
        </div>

        <table class="totals-table">
            <tr>
                <td class="text-right font-bold" style="color: #6b7280; font-size: 8pt;">Subtotal Bs</td>
                <td class="text-right font-bold" style="width: 70pt;">{{ number_format((float)$sale->subtotal, 2) }}</td>
            </tr>
            @if($sale->global_discount> 0)
            <tr>
                <td class="text-right font-bold" style="color: #b91c1c; font-size: 8pt;">Descuento Adic. Bs</td>
                <td class="text-right font-bold" style="color: #b91c1c;">-{{ number_format((float)$sale->global_discount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-pay-row">
                <td class="text-right total-pay-label uppercase">TOTAL A PAGAR Bs</td>
                <td class="text-right total-pay-value">{{ number_format((float)$sale->total_payment, 2) }}</td>
            </tr>
        </table>
        <div class="clear"></div>
    </div>

    <div class="footer-warning uppercase">
        ESTE DOCUMENTO NO TIENE VALOR FISCAL
    </div>
    <div class="footer-thanks uppercase">
        {{ $messages[array_rand($messages)] }}
    </div>
    <div class="footer-system">
        Generado por Sistema Inventory | {{ date('d/m/Y H:i:s') }}
    </div>

</body>
</html>
