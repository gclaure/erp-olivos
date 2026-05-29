<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Purchase;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\PurchaseReceivedNotification;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

readonly class PurchaseService
{
    public function __construct(
        private KardexService $kardexService,
        private AccountPayableService $accountPayableService
    ) {}

    private function generatePurchaseNumber(): string
    {
        $year = (int) date('Y');
        
        $sequence = \App\Models\PurchaseSequence::firstOrCreate(
            ['year' => $year],
            ['last_number' => 0]
        );

        // Atomic increment directly in DB
        $sequence->increment('last_number');
        $nextNumber = $sequence->fresh()->last_number;

        return "PO/{$year}/" . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param array $purchaseData Array con keys: provider_id, warehouse_id, user_id, date, notes, total
     * @param array $detailsData Array de arrays con keys: product_id, quantity, unit_price, subtotal
     * @return Purchase
     */
    public function createPurchase(array $purchaseData, array $detailsData, bool $notify = true): Purchase
    {
        $purchase = DB::transaction(function () use ($purchaseData, $detailsData) {
            $purchaseData['purchase_number'] = $this->generatePurchaseNumber();
            $purchase = Purchase::create($purchaseData);

            // logic for accounts payable
            if ($purchaseData['payment_type'] === 'credito') {
                $debt = $this->accountPayableService->createForPurchase($purchase);
                
                // Record down payment if exists
                if (!empty($purchaseData['down_payment']) && (float)$purchaseData['down_payment'] > 0) {
                    $this->accountPayableService->recordPayment($debt, [
                        'amount'         => $purchaseData['down_payment'],
                        'payment_date'   => $purchaseData['date'],
                        'payment_method' => 'EFECTIVO', // Default or from request
                        'reference'      => 'Adelanto inicial',
                        'notes'          => 'Pago inicial al registrar la compra.',
                        'receipt_path'   => $purchaseData['receipt_path'] ?? null,
                        'user_id'        => $purchaseData['user_id'],
                    ]);
                }
            }
            $warehouse = Warehouse::find($purchaseData['warehouse_id']);

            foreach ($detailsData as $detail) {
                $qty = BigDecimal::of($detail['quantity']);
                $unitPrice = BigDecimal::of($detail['unit_price']);
                // Calculamos el subtotal internamente para evitar errores de clave ausente
                $subtotal = $qty->multipliedBy($unitPrice);

                // 1. Guardar el detalle de la compra
                $purchase->details()->create([
                    'product_id'      => $detail['product_id'],
                    'quantity'        => (string) $qty->toScale(4, RoundingMode::HALF_UP),
                    'unit_price'      => (string) $unitPrice->toScale(4, RoundingMode::HALF_UP),
                    'subtotal'        => (string) $subtotal->toScale(2, RoundingMode::HALF_UP),
                    'expiration_date' => $detail['expiration_date'] ?? null,
                ]);

                // 2. Asegurar existencia de Stock preliminar (la estrategia de Kardex se encarga de actualizar cantidades y costos)
                Stock::firstOrCreate(
                    [
                        'product_id' => $detail['product_id'],
                        'warehouse_id' => $purchaseData['warehouse_id'],
                    ],
                    ['quantity' => '0.0000']
                );

                // 3. Registrar en Kardex
                $this->kardexService->record(
                    type: 'ENTRADA',
                    productId: (string) $detail['product_id'],
                    warehouseId: (string) $purchaseData['warehouse_id'],
                    quantity: (string) $qty->toScale(4, RoundingMode::HALF_UP),
                    unitCost: (string) $unitPrice->toScale(4, RoundingMode::HALF_UP),
                    userId: $purchaseData['user_id'],
                    notes: 'Ingreso por compra. Ref: ' . ($purchaseData['notes'] ?: 'Ninguna'),
                    recordableType: Purchase::class,
                    recordableId: $purchase->id,
                    expirationDate: $detail['expiration_date'] ?? null
                );

            }

            $purchase->load('details');
            event(new \App\Events\PurchaseCreated($purchase));

            return $purchase;
        }, 3);

        // 5. Notificar a administradores (Super Admins + usuarios de la sucursal)
        if ($notify) {
            $users = User::where('is_super_admin', true)
                ->orWhere('branch_id', $purchase->warehouse->branch_id)
                ->get();
            if ($users->isNotEmpty()) {
                try {
                    Notification::send($users, new PurchaseReceivedNotification($purchase->load(['warehouse.branch', 'provider'])));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("No se pudo enviar notificación de compra: " . $e->getMessage());
                }
            }
        }

        return $purchase;
    }
}
