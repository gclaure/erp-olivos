<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SalePrintController extends Controller
{
    /**
     * Generate and stream the sale receipt PDF.
     */
    public function __invoke(Request $request, Sale $sale): Response
    {
        $format = $request->get('format', 'media');
        $view = $format === 'rollo' ? 'admin.sales.receipt-roll' : 'admin.sales.receipt';
        
        $sale->load(['client', 'user', 'warehouse.branch.company', 'details.product.unitOfMeasure']);

        $pdf = Pdf::loadView($view, [
            'sale' => $sale,
            'messages' => [
        'GRACIAS POR SU PREFERENCIA. SU APOYO CONTRIBUYE AL CRECIMIENTO DE NUESTRO NEGOCIO Y AL DESARROLLO DE NUESTRO PAÍS.',
        'GRACIAS POR ELEGIRNOS. SU CONFIANZA ES NUESTRO MAYOR IMPULSO PARA SEGUIR MEJORANDO CADA DÍA.',
        'MUCHAS GRACIAS POR SU VISITA. ES UN HONOR CONTAR CON CLIENTES COMO USTED. LE ESPERAMOS PRONTO.',
        'GRACIAS POR CONFIAR EN NOSOTROS. SEGUIREMOS TRABAJANDO PARA SUPERAR SUS EXPECTATIVAS.',
        'AGRADECEMOS SU PREFERENCIA. GRACIAS POR SER PARTE DE NUESTRA HISTORIA.',
        'GRACIAS POR SU COMPRA. SU SATISFACCIÓN ES NUESTRA PRIORIDAD.',
        'GRACIAS POR ELEGIRNOS. ESPERAMOS HABERLE BRINDADO UNA EXCELENTE EXPERIENCIA DE COMPRA.',
        'GRACIAS POR VISITARNOS. NOS COMPROMETEMOS A MANTENER LA CALIDAD QUE USTED MERECE.',
    ],
        ]);

        if ($format === 'rollo') {
            // Calculate dynamic height: base (headers/footers) + (items * rowHeight)
            // 80mm width = ~226pt. Printable area ~72mm = ~204pt.
            $baseHeight = 320; // increased for extra fields like title and seller
            $rowHeight = 35;   // estimated pixels/pts per item row
            $itemsCount = $sale->details->count();
            $dynamicHeight = $baseHeight + ($itemsCount * $rowHeight);
            
            $pdf->setPaper([0, 0, 226, $dynamicHeight], 'portrait');
        } elseif ($format === 'media') {
         $pdf->setPaper('letter', 'portrait');
        }

        return $pdf->stream("Venta-{$sale->id}.pdf");
    }
}
