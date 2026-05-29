<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Models\Product;
use App\Enums\TransferStatus;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class TransferController extends Controller
{
    public function index(Request $request): Response
    {
        $statuses = collect(TransferStatus::cases())->map(fn ($status) => [
            'label' => $status->label(),
            'value' => $status->value,
        ]);

        $transfers = Transfer::query()
            ->with(['originWarehouse', 'destinationWarehouse', 'user', 'originBranch', 'destinationBranch'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('number', 'ilike', "%{$request->search}%")
                  ->orWhereHas('originWarehouse', fn($wq) => $wq->where('name', 'ilike', "%{$request->search}%"))
                  ->orWhereHas('destinationWarehouse', fn($wq) => $wq->where('name', 'ilike', "%{$request->search}%"));
            })
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->origin_warehouse_id, fn ($q) => $q->where('origin_warehouse_id', $request->origin_warehouse_id))
            ->when($request->destination_warehouse_id, fn ($q) => $q->where('destination_warehouse_id', $request->destination_warehouse_id))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(function ($transfer) {
                $transfer->can_approve = $this->canApprove($transfer);
                return $transfer;
            })
            ->withQueryString();

        $originWarehouses = Warehouse::with(['branch' => fn($q) => $q->withoutGlobalScopes()])
            ->orderBy('name')->get();

        $destinationWarehouses = Warehouse::withoutGlobalScope('branch_scoped')
            ->with(['branch' => fn($q) => $q->withoutGlobalScopes()])
            ->orderBy('name')->get();

        return Inertia::render('Admin/Transfers/Index', [
            'transfers' => $transfers,
            'originWarehouses' => $originWarehouses->map(fn($w) => [
                'id' => $w->id,
                'name' => ($w->branch?->name ?? 'S/S') . " - {$w->name}"
            ]),
            'destinationWarehouses' => $destinationWarehouses->map(fn($w) => [
                'id' => $w->id,
                'name' => ($w->branch?->name ?? 'S/S') . " - {$w->name}"
            ]),
            'statuses' => $statuses,
            'filters' => $request->only(['search', 'status', 'origin_warehouse_id', 'destination_warehouse_id']),
        ]);
    }

    public function create(): Response
    {
        $originWarehouses = Warehouse::with(['branch' => fn($q) => $q->withoutGlobalScopes()])
            ->orderBy('name')->get();

        $destinationWarehouses = Warehouse::withoutGlobalScope('branch_scoped')
            ->with(['branch' => fn($q) => $q->withoutGlobalScopes()])
            ->orderBy('name')->get();

        return Inertia::render('Admin/Transfers/Form', [
            'originWarehouses' => $originWarehouses->map(fn($w) => [
                'id' => $w->id,
                'name' => ($w->branch?->name ?? 'S/S') . " - {$w->name}"
            ]),
            'destinationWarehouses' => $destinationWarehouses->map(fn($w) => [
                'id' => $w->id,
                'name' => ($w->branch?->name ?? 'S/S') . " - {$w->name}"
            ]),
        ]);
    }

    public function edit(string $id): Response|RedirectResponse
    {
        $transfer = Transfer::with('details.product')->findOrFail($id);
        
        if ($transfer->status !== TransferStatus::DRAFT) {
            return redirect()->route('admin.transfers.index')
                ->with('error', 'Solo se pueden editar transferencias en estado Borrador.');
        }

        $items = $transfer->details->map(fn($detail) => [
            'id' => $detail->product_id,
            'name' => $detail->product->name,
            'code' => $detail->product->code,
            'quantity' => (float) $detail->quantity_sent,
            'stock' => $this->getStock($detail->product_id, (string)$transfer->origin_warehouse_id),
        ])->values()->toArray();

        $originWarehouses = Warehouse::with(['branch' => fn($q) => $q->withoutGlobalScopes()])
            ->orderBy('name')->get();

        $destinationWarehouses = Warehouse::withoutGlobalScope('branch_scoped')
            ->with(['branch' => fn($q) => $q->withoutGlobalScopes()])
            ->orderBy('name')->get();

        return Inertia::render('Admin/Transfers/Form', [
            'transfer' => $transfer,
            'items' => $items,
            'originWarehouses' => $originWarehouses->map(fn($w) => [
                'id' => $w->id,
                'name' => ($w->branch?->name ?? 'S/S') . " - {$w->name}"
            ]),
            'destinationWarehouses' => $destinationWarehouses->map(fn($w) => [
                'id' => $w->id,
                'name' => ($w->branch?->name ?? 'S/S') . " - {$w->name}"
            ]),
        ]);
    }

    public function show(string $id): Response
    {
        $transfer = Transfer::with([
            'originWarehouse', 
            'destinationWarehouse', 
            'originBranch', 
            'destinationBranch', 
            'details.product',
            'user',
            'approver',
            'shipper',
            'receiver',
            'rejecter',
            'canceller',
            'histories.user'
        ])->findOrFail($id);

        return Inertia::render('Admin/Transfers/Show', [
            'transfer' => $transfer,
            'canApprove' => $this->canApprove($transfer),
            'canDispatch' => $this->canDispatch($transfer),
            'canReceive' => $this->canReceive($transfer),
            'canCancel' => $this->canCancel($transfer),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'origin_warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id|different:origin_warehouse_id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            $service = app(TransferService::class);
            $data = [
                'origin_warehouse_id' => $request->origin_warehouse_id,
                'destination_warehouse_id' => $request->destination_warehouse_id,
                'notes' => $request->notes,
                'user_id' => (string) auth()->id(),
            ];

            $details = collect($request->items)->map(fn($item) => [
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
            ])->toArray();

            $transfer = $service->create($data, $details);

            if ($request->is_request) {
                $service->request($transfer);
                $msg = 'Transferencia solicitada correctamente.';
            } else {
                $msg = 'Borrador guardado correctamente.';
            }

            return redirect()->route('admin.transfers.index')->with('success', $msg);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $transfer = Transfer::findOrFail($id);

        if ($transfer->status !== TransferStatus::DRAFT) {
            return back()->with('error', 'Solo se pueden editar transferencias en estado Borrador.');
        }

        $request->validate([
            'origin_warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id|different:origin_warehouse_id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            DB::beginTransaction();

            $transfer->update([
                'origin_warehouse_id' => $request->origin_warehouse_id,
                'destination_warehouse_id' => $request->destination_warehouse_id,
                'notes' => $request->notes,
            ]);
            $transfer->details()->delete();

            foreach ($request->items as $item) {
                $transfer->details()->create([
                    'product_id' => $item['id'],
                    'quantity_sent' => $item['quantity'],
                    'quantity_received' => 0,
                ]);
            }

            if ($request->is_request) {
                app(TransferService::class)->request($transfer);
                $msg = 'Transferencia solicitada correctamente.';
            } else {
                $msg = 'Borrador guardado correctamente.';
            }

            DB::commit();
            return redirect()->route('admin.transfers.index')->with('success', $msg);
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function dispatch(Request $request, string $id): RedirectResponse
    {
        $transfer = Transfer::findOrFail($id);
        $request->validate(['shipped_quantities' => 'required|array']);

        try {
            app(TransferService::class)->dispatch($transfer, $request->shipped_quantities, (string) auth()->id());
            return back()->with('success', 'Transferencia despachada correctamente. Stock descontado del origen.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function receive(Request $request, string $id): RedirectResponse
    {
        $transfer = Transfer::findOrFail($id);
        $request->validate(['received_quantities' => 'required|array']);

        try {
            app(TransferService::class)->confirmReception($transfer, $request->received_quantities, (string) auth()->id());
            return back()->with('success', 'Transferencia recibida correctamente. Stock incrementado en el destino.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(string $id): RedirectResponse
    {
        $transfer = Transfer::findOrFail($id);
        try {
            app(TransferService::class)->approveTransfer($transfer, (string) auth()->id());
            return back()->with('success', 'Transferencia aprobada correctamente.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|min:5']);
        $transfer = Transfer::findOrFail($id);
        try {
            app(TransferService::class)->rejectTransfer($transfer, (string) auth()->id(), $request->reason);
            return back()->with('success', 'Transferencia rechazada.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(string $id): RedirectResponse
    {
        $transfer = Transfer::findOrFail($id);
        try {
            app(TransferService::class)->cancelTransfer($transfer, (string) auth()->id());
            return back()->with('success', 'Transferencia cancelada.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function setStatus(Request $request, string $id): RedirectResponse
    {
        $transfer = Transfer::findOrFail($id);
        $status = $request->status;

        try {
            // Este método ahora es legado o para estados simples. 
            // Las acciones principales tienen sus propios métodos arriba.
            $transfer->status = TransferStatus::from($status);
            $transfer->save();
            return back()->with('success', 'Estado actualizado a: ' . $transfer->status->label());
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function searchProducts(Request $request): \Illuminate\Http\JsonResponse
    {
        if (!$request->warehouse_id) return response()->json([]);

        $search = $request->search;

        $products = Product::query()
            ->withSum(['stocks as available_stock' => function($query) use ($request) {
                $query->where('warehouse_id', $request->warehouse_id);
            }], 'quantity')
            ->when($search, function($q) use ($search) {
                $q->where(function($sq) use ($search) {
                    $sq->where('name', 'ilike', "%{$search}%")
                      ->orWhere('code', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit($search ? 20 : 50)
            ->get();

        return response()->json($products);
    }

    private function getStock(string $productId, string $warehouseId): float
    {
        $stock = \App\Models\Stock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $stock ? (float) $stock->quantity : 0;
    }

    private function canApprove(Transfer $transfer): bool
    {
        if ($transfer->status !== TransferStatus::PENDING) return false;
        
        $user = auth()->user();
        if ($user?->is_super_admin || $user?->hasRole('Administrador')) return true;

        // Solo personal del destino puede aprobar
        return $user?->warehouse_id === $transfer->destination_warehouse_id 
            || $user?->branch_id === $transfer->destination_branch_id;
    }

    private function canDispatch(Transfer $transfer): bool
    {
        if ($transfer->status !== TransferStatus::APPROVED) return false;

        $user = auth()->user();
        if ($user?->is_super_admin || $user?->hasRole('Administrador')) return true;

        // Solo personal del origen puede despachar
        return $user?->warehouse_id === $transfer->origin_warehouse_id 
            || $user?->branch_id === $transfer->origin_branch_id;
    }

    private function canReceive(Transfer $transfer): bool
    {
        if ($transfer->status !== TransferStatus::IN_TRANSIT) return false;

        $user = auth()->user();
        if ($user?->is_super_admin || $user?->hasRole('Administrador')) return true;

        // Solo personal del destino puede recibir
        return $user?->warehouse_id === $transfer->destination_warehouse_id 
            || $user?->branch_id === $transfer->destination_branch_id;
    }

    private function canCancel(Transfer $transfer): bool
    {
        if (!in_array($transfer->status, [TransferStatus::DRAFT, TransferStatus::PENDING])) return false;

        $user = auth()->user();
        if ($user?->is_super_admin || $user?->hasRole('Administrador')) return true;

        // Solo el creador o personal del origen puede cancelar
        return $transfer->user_id === $user?->id 
            || $user?->warehouse_id === $transfer->origin_warehouse_id;
    }
}
