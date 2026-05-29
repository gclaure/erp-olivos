<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #{{ $sale->formatted_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5pt;
            line-height: 1.1;
            margin: 0;
            padding: 3mm 4mm;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        .divider-dashed {
            border-top: 0.5pt dashed #444;
            margin: 4pt 0;
        }
        .divider-solid {
            border-top: 1.2pt solid #000;
            margin: 1.5pt 0;
        }
        
        .header { margin-bottom: 5pt; }
        .title-block { margin: 5pt 0; }
        .title-text {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 1.5pt;
            padding: 1pt 0;
        }
        
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1.5pt 0; vertical-align: top; }
        
        .items-header {
            border-bottom: 0.8pt solid #000;
            font-weight: bold;
            font-size: 8pt;
        }
        .item-name {
            font-weight: bold;
            font-size: 8.5pt;
            display: block;
            margin-top: 2pt;
        }
        .item-details {
            font-size: 7.5pt;
            color: #333;
        }
        
        .totals-section {
            margin-top: 5pt;
        }
        .total-pay {
            font-size: 10.5pt;
            font-weight: bold;
            border-top: 0.8pt solid #000;
            padding-top: 3pt;
        }
        
        .footer {
            margin-top: 8pt;
            font-size: 8pt;
        }
        .amount-words {
            font-size: 7.5pt;
            margin-top: 4pt;
        }
        .watermark {
            position: absolute;
            top: 100pt;
            left: 50%;
            transform: translateX(-50%) rotate(-45deg);
            font-size: 35pt;
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
    @endphp

    <div class="header text-center">
        <div class="font-bold uppercase" style="font-size: 9pt;">{{ $company?->name ?? 'EMPRESA' }}</div>
        <div class="uppercase" style="font-size: 7.5pt;">{{ $sale->warehouse->branch->name }}</div>
        <div style="font-size: 7.5pt;">{{ $sale->warehouse->branch->address }}</div>
        <div style="font-size: 7.5pt;">Tel: {{ $sale->warehouse->branch->phone ?? 'S/N' }}</div>
    </div>

    <div class="title-block text-center">
        <div class="divider-solid"></div>
        <div class="title-text uppercase">NOTA DE VENTA</div>
        <div class="divider-solid"></div>
    </div>

    <table style="font-size: 8pt;">
        <tr>
            <td class="font-bold">N° NOTA:</td>
            <td class="text-right font-bold">{{ $sale->formatted_number }}</td>
        </tr>
        <tr>
            <td class="font-bold">FECHA:</td>
            <td class="text-right">{{ $sale->date->format('d/m/Y') }} {{ $sale->created_at->format('H:i') }}</td>
        </tr>
    </table>

    <div class="divider-dashed"></div>

    <table style="font-size: 8pt;">
        <tr>
            <td class="font-bold" style="width: 25%;">CLIENTE:</td>
            <td class="text-right uppercase">{{ $sale->client->name }}</td>
        </tr>
        <tr>
            <td class="font-bold">NIT/CI:</td>
            <td class="text-right">{{ $sale->client->document_number ?? '0' }}</td>
        </tr>
    </table>

    <div class="divider-dashed"></div>

    <table>
        <tr class="items-header uppercase">
            <td style="width: 75%;">DESCRIPCIÓN</td>
            <td style="width: 25%; text-align: right;">TOTAL</td>
        </tr>
        @foreach($sale->details as $detail)
        <tr>
            <td colspan="2">
                <span class="item-name uppercase">{{ $detail->product->name }}</span>
                <table style="width: 100%;">
                    <tr>
                        <td class="item-details">
                            {{ number_format((float)$detail->quantity, 1) }} {{ $detail->product->unitOfMeasure->abbreviation ?? 'UNI' }} x {{ number_format((float)$detail->unit_price, 2) }}
                        </td>
                        <td class="text-right font-bold" style="font-size: 9pt;">
                            {{ number_format((float)$detail->subtotal, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @endforeach
    </table>

    <div class="totals-section">
        <table style="font-size: 8.5pt;">
            <tr>
                <td class="text-right" style="width: 70%;">SUBTOTAL Bs:</td>
                <td class="text-right" style="width: 30%;">{{ number_format((float)$sale->subtotal, 2) }}</td>
            </tr>
            @if($sale->global_discount> 0)
            <tr>
                <td class="text-right">DESC. Bs:</td>
                <td class="text-right">-{{ number_format((float)$sale->global_discount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-pay">
                <td class="text-right uppercase">TOTAL Bs:</td>
                <td class="text-right">{{ number_format((float)$sale->total_payment, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="amount-words">
        @php
            $integerPart = floor($sale->total_payment);
            $decimalPart = round(($sale->total_payment - $integerPart) * 100);
            $words = app(\App\Services\NumberToWordsService::class)->convert($integerPart);
        @endphp
        <span class="font-bold uppercase">SON:</span> <i>{{ mb_strtolower($words) }} {{ str_pad((string)$decimalPart, 2, '0', STR_PAD_LEFT) }}/100 bolivianos.</i>
    </div>

    <div class="divider-dashed"></div>

    <div class="footer text-center">
        <div class="font-bold">ESTE DOCUMENTO NO TIENE VALOR FISCAL</div>
        <div style="margin-top: 2pt; font-size: 7.5pt;">REVISE SU MERCADERÍA, NO SE ACEPTAN DEVOLUCIONES</div>
        <div class="font-bold uppercase" style="margin-top: 6pt; font-size: 9pt;">¡GRACIAS POR SU COMPRA!</div>
        <div style="font-size: 6.5pt; color: #666; margin-top: 2pt;">Generado por Sistema Inventory</div>
    </div>

</body>
</html>
