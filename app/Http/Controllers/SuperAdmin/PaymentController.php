<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\PaymentResource;
use App\Models\Company;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = Payment::with('tenant')
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('tenant', fn ($t) => $t->where('name', 'ilike', '%' . $request->search . '%'));
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Payments/Index', [
            'payments' => PaymentResource::collection($payments),
            'filters' => $request->only(['search', 'status']),
            'tenants' => Company::select('id', 'name')->orderBy('name')->get(),
            'statusOptions' => PaymentStatus::cases(),
            'methodOptions' => PaymentMethod::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:companies,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:CASH,TRANSFER,QR,CARD,OTHER',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'status' => 'required|in:PAID,PENDING,FAILED',
        ]);

        Payment::create([
            'tenant_id' => $validated['tenant_id'],
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
            'paid_at' => $validated['status'] === 'PAID' ? now() : null,
        ]);

        return back()->with('success', 'Pago registrado correctamente.');
    }

    public function markAsPaid(Payment $payment): RedirectResponse
    {
        $payment->update([
            'status' => PaymentStatus::PAID->value,
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Pago marcado como pagado.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return back()->with('success', 'Pago eliminado correctamente.');
    }
}
