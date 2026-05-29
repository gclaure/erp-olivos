<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\QuotationResource;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class QuotationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $quotations = Quotation::query()
            ->with(['client', 'user'])
            ->when($search, fn ($q) => $q->whereHas('client', fn ($cq) => $cq->where('name', 'ilike', "%{$search}%")))
            ->orderByDesc('date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Quotations/Index', [
            'quotations' => QuotationResource::collection($quotations),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Cancel the specified quotation.
     */
    public function cancel(Quotation $quotation, QuotationService $service): RedirectResponse
    {
        try {
            if ($quotation->status === 'cancelada') {
                return back()->with('error', 'La cotización ya se encuentra cancelada.');
            }

            $service->changeStatus($quotation, 'cancelada');
            
            return back()->with('success', 'Cotización cancelada.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Convert the specified quotation to a sale.
     */
    public function convertToSale(string $id, QuotationService $service): RedirectResponse
    {
        try {
            $service->convertToSale($id, (string) Auth::id());
            
            return back()->with('success', 'Cotización ejecutada como Venta.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
