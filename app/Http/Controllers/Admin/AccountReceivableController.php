<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordPaymentRequest;
use App\Http\Resources\AccountReceivableResource;
use App\Models\AccountReceivable;
use App\Services\AccountReceivableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AccountReceivableController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $status = $request->get('status');

        $query = AccountReceivable::with(['client', 'sale'])
            ->whereHas('client', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('document_number', 'ilike', "%{$search}%");
            });

        if ($status) {
            if ($status === 'vencido') {
                $query->overdue();
            } else {
                $query->where('status', $status);
            }
        }

        $records = $query->orderBy('due_date', 'asc')->paginate(15)->withQueryString();

        return Inertia::render('Admin/AccountReceivable/Index', [
            'records' => AccountReceivableResource::collection($records),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function recordPayment(RecordPaymentRequest $request, AccountReceivable $accountReceivable, AccountReceivableService $service): RedirectResponse
    {
        $service->recordPayment($accountReceivable, array_merge($request->validated(), [
            'user_id' => Auth::id(),
        ]));

        return back()->with('success', 'Pago registrado correctamente.');
    }

    public function getHistory(AccountReceivable $accountReceivable): Response
    {
        $accountReceivable->load(['payments.user', 'sale', 'client']);
        
        return Inertia::render('Admin/AccountReceivable/Partials/HistoryModal', [
            'record' => new AccountReceivableResource($accountReceivable),
        ]);
    }
}
