<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class MovementPrintController extends Controller
{
    /**
     * Generate and stream the movement receipt PDF.
     */
    public function __invoke(string $id): Response
    {
        $movement = Movement::with([
            'warehouse.branch.company',
            'user',
            'details.product.unitOfMeasure'
        ])->findOrFail($id);

        $pdf = Pdf::loadView('admin.movements.receipt', [
            'movement' => $movement,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Movimiento-{$movement->id}.pdf");
    }
}
