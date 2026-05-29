<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAccountPayablePaymentRequest;
use App\Http\Resources\AccountPayableResource;
use App\Models\AccountPayable;
use App\Models\Provider;
use App\Services\AccountPayableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AccountPayableController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $provider_id = $request->get('provider_id');
        $status = $request->get('status');

        $query = AccountPayable::query()
            ->with(['provider', 'purchase'])
            ->when($search, function ($query, $search) {
                $query->whereHas('purchase', function ($q) use ($search) {
                    $q->where('purchase_number', 'ilike', "%{$search}%");
                });
            })
            ->when($provider_id, fn($q) => $q->where('provider_id', $provider_id))
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderBy('due_date', 'asc');

        $debts = $query->paginate(15)->withQueryString();
        
        $providers = Provider::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/AccountPayable/Index', [
            'records' => AccountPayableResource::collection($debts),
            'providers' => $providers,
            'filters' => [
                'search' => $search,
                'provider_id' => $provider_id,
                'status' => $status,
            ],
        ]);
    }

    public function recordPayment(SaveAccountPayablePaymentRequest $request, AccountPayable $accountPayable, AccountPayableService $service): RedirectResponse
    {
        if ((float)$request->amount > (float)$accountPayable->balance) {
            return back()->withErrors(['amount' => 'El monto no puede superar el saldo pendiente.']);
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('payments/receipts', 'public');
        }

        $service->recordPayment($accountPayable, array_merge($request->validated(), [
            'receipt_path' => $receiptPath,
            'user_id' => Auth::id(),
        ]));

        return back()->with('success', 'Pago registrado correctamente.');
    }

    public function getHistory(AccountPayable $accountPayable): Response
    {
        $accountPayable->load(['provider', 'purchase', 'payments.user']);
        
        return Inertia::render('Admin/AccountPayable/Partials/HistoryModal', [
            'record' => new AccountPayableResource($accountPayable),
        ]);
    }
}
