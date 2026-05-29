<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Movement;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Kardex;
use App\Models\User;
use App\Notifications\InventoryDiscrepancyNotification;
use App\Services\KardexService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Exception;

class MovementService
{
    public function __construct(private KardexService $kardexService) {}

    /**
     * Record a manual inventory movement.
     * 
     * @param array $data [warehouse_id, type, date, reason, notes]
     * @param array $items [[product_id, quantity]]
     */
    public function recordMovement(array $data, array $items): Movement
    {
        $movement = DB::transaction(function () use ($data, $items) {
            $movement = Movement::create([
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => Auth::id(),
                'type' => $data['type'], // 'entrada' or 'salida'
                'date' => $data['date'] ?? now()->toDateString(),
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = (string) $item['quantity'];

                // Create Detail
                $movement->details()->create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);

                // Update Stock and Record in Kardex
                $this->processInventory($movement, $productId, $quantity);
            }

            return $movement;
        });

        // Notificar a administradores (Super Admins + usuarios de la sucursal)
        $users = User::where('is_super_admin', true)
            ->orWhere('branch_id', $movement->warehouse->branch_id)
            ->get();
        if ($users->isNotEmpty()) {
            Notification::send($users, new InventoryDiscrepancyNotification($movement->load(['warehouse.branch', 'user'])));
        }

        return $movement;
    }

    private function processInventory(Movement $movement, string $productId, string $quantity): void
    {
        $isEntry = $movement->type === 'entrada';
        $type = $isEntry ? 'ENTRADA' : 'SALIDA';

        // Fetch last average cost for the product in this warehouse
        $lastKardex = Kardex::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->where('warehouse_id', $movement->warehouse_id)
            ->latest('id')
            ->first();

        $avgCost = $lastKardex ? (string)$lastKardex->avg_cost : '0.0000';

        if ($isEntry) {
            $this->kardexService->record(
                type: $type,
                productId: $productId,
                warehouseId: $movement->warehouse_id,
                quantity: $quantity,
                unitCost: $avgCost,
                userId: Auth::id(),
                notes: "Entrada manual: {$movement->reason}",
                recordableType: Movement::class,
                recordableId: $movement->id
            );
        } else {
            $this->kardexService->record(
                type: $type,
                productId: $productId,
                warehouseId: $movement->warehouse_id,
                quantity: $quantity,
                unitCost: $avgCost,
                userId: Auth::id(),
                notes: "Salida manual: {$movement->reason}",
                recordableType: Movement::class,
                recordableId: $movement->id
            );
        }
    }
}
