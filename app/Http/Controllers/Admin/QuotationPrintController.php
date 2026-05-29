<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QuotationPrintController extends Controller
{
    /**
     * Generate and stream the quotation (proforma) PDF.
     */
    public function __invoke(Request $request, Quotation $quotation): Response
    {
        $format = $request->get('format', 'media');
        $view = $format === 'rollo' ? 'admin.quotations.quotation-roll' : 'admin.quotations.quotation';
        
        $quotation->load(['client', 'user', 'branch.company', 'details.product.unitOfMeasure']);

        $pdf = Pdf::loadView($view, [
            'quotation' => $quotation,
            'messages' => [
                'ESTA PROFORMA TIENE UNA VALIDEZ DE 15 DÍAS A PARTIR DE LA FECHA DE EMISIÓN.',
                'LOS PRECIOS ESTÁN SUJETOS A CAMBIOS SIN PREVIO AVISO SEGÚN DISPONIBILIDAD DE STOCK.',
                'GRACIAS POR SU INTERÉS EN NUESTROS PRODUCTOS. ESTAREMOS ATENTOS A SU CONFIRMACIÓN.',
            ],
        ]);

        if ($format === 'rollo') {
            $baseHeight = 320;
            $rowHeight = 35;
            $itemsCount = $quotation->details->count();
            $dynamicHeight = $baseHeight + ($itemsCount * $rowHeight);
            
            $pdf->setPaper([0, 0, 226, $dynamicHeight], 'portrait');
        } elseif ($format === 'media') {
            $pdf->setPaper([0, 0, 396, 612], 'portrait');
        } else {
            $pdf->setPaper('letter', 'portrait');
        }

        return $pdf->stream("Proforma-{$quotation->formatted_number}.pdf");
    }
}
