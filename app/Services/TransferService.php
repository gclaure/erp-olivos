<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Kardex;
use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Models\TransferSequence;
use App\Models\TransferHistory;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\User;
use App\Enums\TransferStatus;
use App\Notifications\TransferDiscrepancyNotification;
use App\Services\KardexService;
use Illuminate\Support\Carbon;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Exception;

class TransferService
{
    public function __construct(private KardexService $kardexService) {}

    /**
     * Create a transfer in DRAFT status.
     */
    public function create(array $data, array $details): Transfer
    {
        return DB::transaction(function () use ($data, $details) {
            $originWarehouse = Warehouse::findOrFail($data['origin_warehouse_id']);
            $destWarehouse = Warehouse::findOrFail($data['destination_warehouse_id']);

            if ($originWarehouse->id === $destWarehouse->id) {
                throw new Exception("El almacén de origen y destino no pueden ser el mismo.");
            }

            $transfer = Transfer::create([
                'origin_branch_id' => $originWarehouse->branch_id,
                'destination_branch_id' => $destWarehouse->branch_id,
                'origin_warehouse_id' => $originWarehouse->id,
                'destination_warehouse_id' => $destWarehouse->id,
                'user_id' => $data['user_id'] ?? Auth::id(),
                'date' => now()->toDateString(),
                'status' => TransferStatus::DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($details as $detail) {
                $qty = BigDecimal::of($detail['quantity']);
                $transfer->details()->create([
                    'product_id' => $detail['product_id'],
                    'quantity_requested' => (string) $qty->toScale(4, RoundingMode::HALF_UP),
                    'quantity_sent' => (string) $qty->toScale(4, RoundingMode::HALF_UP),
                    'quantity_received' => '0.0000',
                    'cost' => '0.0000',
                ]);
            }

            $this->recordHistory($transfer, 'solicitud', "Borrador de transferencia creado.");

            return $transfer;
        });
    }

    /**
     * Request transfer (Move to PENDING and assign Number).
     */
    public function request(Transfer $transfer): void
    {
        if ($transfer->status !== TransferStatus::DRAFT) {
            throw new Exception("Solo se pueden solicitar transferencias en estado Borrador.");
        }

        DB::transaction(function () use ($transfer) {
            $year = (int) date('Y');
            $transfer->number = $this->getNextTransferNumber($transfer->origin_branch_id, $year);
            $transfer->status = TransferStatus::PENDING;
            $transfer->save();

            $this->recordHistory($transfer, 'solicitud', "Solicitud de transferencia generada.");
        });
    }

    /**
     * Approve transfer (Move to APPROVED).
     * Professional Rule: Destination warehouse staff or Admin must approve.
     */
    public function approveTransfer(Transfer $transfer, string $userId): void
    {
        if ($transfer->status !== TransferStatus::PENDING) {
            throw new Exception("Solo se pueden aprobar transferencias en estado Pendiente.");
        }

        DB::transaction(function () use ($transfer, $userId) {
            $user = User::findOrFail($userId);
            
            // Validation: Only staff from destination warehouse/branch or Admin can approve
            // We use warehouse_id if available, otherwise branch_id as fallback
            $isDestinationStaff = $user->warehouse_id === $transfer->destination_warehouse_id 
                || $user->branch_id === $transfer->destination_branch_id;
            
            if (!$isDestinationStaff && !$user->is_super_admin && !$user->hasRole('Administrador')) {
                throw new Exception("Solo el personal del almacén destino o administradores pueden aprobar esta transferencia.");
            }

            $transfer->status = TransferStatus::APPROVED;
            $transfer->approved_by_id = $userId;
            $transfer->save();

            $this->recordHistory($transfer, 'aprobacion', "Transferencia aprobada por el destino.");
        });
    }

    /**
     * Reject transfer (Move to REJECTED).
     */
    public function rejectTransfer(Transfer $transfer, string $userId, string $reason): void
    {
        if ($transfer->status !== TransferStatus::PENDING) {
            throw new Exception("Solo se pueden rechazar transferencias en estado Pendiente.");
        }

        DB::transaction(function () use ($transfer, $userId, $reason) {
            $transfer->status = TransferStatus::REJECTED;
            $transfer->rejected_at = now();
            $transfer->rejected_by_id = $userId;
            $transfer->rejection_reason = $reason;
            $transfer->save();

            $this->recordHistory($transfer, 'rechazo', "Transferencia rechazada. Motivo: {$reason}");
        });
    }

    /**
     * Cancel transfer (Move to CANCELLED).
     * Rule: Only the origin can cancel if it's still pending.
     */
    public function cancelTransfer(Transfer $transfer, string $userId): void
    {
        if ($transfer->status !== TransferStatus::PENDING && $transfer->status !== TransferStatus::DRAFT) {
            throw new Exception("No se puede cancelar una transferencia que ya ha sido procesada.");
        }

        DB::transaction(function () use ($transfer, $userId) {
            $transfer->status = TransferStatus::CANCELLED;
            $transfer->cancelled_at = now();
            $transfer->cancelled_by_id = $userId;
            $transfer->save();

            $this->recordHistory($transfer, 'cancelacion', "Transferencia cancelada por el origen.");
        });
    }

    /**
     * Dispatch/Ship transfer (Move to IN_TRANSIT and deduct stock from origin).
     */
    public function dispatch(Transfer $transfer, array $quantities, string $userId): void
    {
        if ($transfer->status !== TransferStatus::APPROVED && $transfer->status !== TransferStatus::PICKING) {
            throw new Exception("Estado no válido para realizar el despacho. Debe estar Aprobada.");
        }

        DB::transaction(function () use ($transfer, $quantities, $userId) {
            foreach ($transfer->details as $detail) {
                $shippedQty = BigDecimal::of($quantities[$detail->id] ?? $detail->quantity_sent);
                
                // Update quantity_sent with the actual amount dispatched
                $detail->update(['quantity_sent' => (string) $shippedQty->toScale(4, RoundingMode::HALF_UP)]);

                // Fetch current cost for the product in the origin warehouse
                $lastKardex = Kardex::withoutGlobalScopes()
                    ->where('product_id', $detail->product_id)
                    ->where('warehouse_id', $transfer->origin_warehouse_id)
                    ->latest('id')
                    ->first();
                
                $cost = $lastKardex ? $lastKardex->avg_cost : '0.0000';
                $detail->update(['cost' => $cost]);

                // Record Kardex TRANSFER_OUT (SALIDA)
                $this->kardexService->record(
                    type: 'TRANSFERENCIA_SALIDA',
                    productId: (string) $detail->product_id,
                    warehouseId: (string) $transfer->origin_warehouse_id,
                    quantity: (string) $shippedQty,
                    unitCost: (string) $cost,
                    userId: $userId,
                    notes: "Transferencia #{$transfer->number} despachada hacia " . $transfer->destinationWarehouse->name,
                    recordableType: Transfer::class,
                    recordableId: $transfer->id
                );
            }

            $transfer->status = TransferStatus::IN_TRANSIT;
            $transfer->shipped_at = now();
            $transfer->shipped_by_id = $userId;
            $transfer->save();

            $this->recordHistory($transfer, 'despacho', "Productos despachados. Salida de Kardex registrada.");
        }, 3);
    }

    /**
     * Confirm Reception (Move to COMPLETED and increase stock in destination).
     */
    public function confirmReception(Transfer $transfer, array $quantities, string $userId): void
    {
        if ($transfer->status !== TransferStatus::IN_TRANSIT) {
            throw new Exception("Solo se pueden recibir transferencias que estén En Tránsito.");
        }

        DB::transaction(function () use ($transfer, $quantities, $userId) {
            $hasDiscrepancy = false;

            foreach ($transfer->details as $detail) {
                $receivedQty = BigDecimal::of($quantities[$detail->id] ?? $detail->quantity_sent);
                $detail->update(['quantity_received' => (string) $receivedQty->toScale(4, RoundingMode::HALF_UP)]);

                if (!$receivedQty->isEqualTo(BigDecimal::of($detail->quantity_sent))) {
                    $hasDiscrepancy = true;
                }

                // Record Kardex TRANSFER_IN (ENTRADA)
                $this->kardexService->record(
                    type: 'TRANSFERENCIA_ENTRADA',
                    productId: (string) $detail->product_id,
                    warehouseId: (string) $transfer->destination_warehouse_id,
                    quantity: (string) $receivedQty,
                    unitCost: (string) $detail->cost,
                    userId: $userId,
                    notes: "Transferencia #{$transfer->number} recibida desde " . $transfer->originWarehouse->name,
                    recordableType: Transfer::class,
                    recordableId: $transfer->id
                );
            }

            $transfer->status = TransferStatus::COMPLETED;
            $transfer->received_at = now();
            $transfer->received_by_id = $userId;
            $transfer->save();

            $this->recordHistory($transfer, 'recepcion', "Recepción confirmada. Entrada de Kardex registrada.");

            // Notify if discrepancy exists
            if ($hasDiscrepancy) {
                $users = User::where('is_super_admin', true)
                    ->orWhere('branch_id', $transfer->destination_branch_id)
                    ->get();
                if ($users->isNotEmpty()) {
                    Notification::send($users, new TransferDiscrepancyNotification($transfer->load(['originWarehouse', 'destinationWarehouse.branch'])));
                }
            }
        }, 3);
    }

    private function recordHistory(Transfer $transfer, string $action, string $notes): void
    {
        $snapshot = $transfer->details->map(fn($d) => [
            'product_id' => $d->product_id,
            'quantity_sent' => $d->quantity_sent,
            'quantity_received' => $d->quantity_received,
        ])->toArray();

        TransferHistory::create([
            'transfer_id' => $transfer->id,
            'user_id' => Auth::id(),
            'warehouse_id' => Auth::user()?->warehouse_id ?? $transfer->origin_warehouse_id,
            'action' => $action,
            'previous_status' => $transfer->getOriginal('status'),
            'new_status' => $transfer->status,
            'metadata' => [
                'items' => $snapshot,
                'notes' => $notes,
            ],
            'ip_address' => request()->ip(),
        ]);
    }

    private function getNextTransferNumber(string $branchId, int $year): string
    {
        $sequence = TransferSequence::firstOrCreate(
            ['branch_id' => $branchId, 'year' => $year],
            ['last_number' => 0]
        );

        $sequence->increment('last_number');
        $nextNumber = $sequence->fresh()->last_number;

        $branch = \App\Models\Branch::find($branchId);
        $prefix = strtoupper(substr($branch->name, 0, 3));

        return "TRF-{$prefix}-{$year}-" . str_pad((string)$nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
