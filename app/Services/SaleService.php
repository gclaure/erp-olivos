<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Kardex;
use App\Models\Sale;
use App\Models\SaleSequence;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\KardexService;
use Illuminate\Support\Carbon;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class SaleService
{
    public function __construct(
        private KardexService $kardexService,
        private AccountReceivableService $accountReceivableService
    ) {}

    /**
     * @param array $data Data for Sale model
     * @param array $details Array of product details
     * @return Sale
     * @throws Exception
     */
    public function create(array $data, array $details): Sale
    {
        // 1. Verificar idempotencia (request_id) antes de iniciar cualquier proceso
        if (!empty($data['request_id'])) {
            $existingSale = Sale::where('request_id', $data['request_id'])->first();
            if ($existingSale) {
                return $existingSale;
            }
        }

        // 2. Ejecutar con política de reintentos para colisiones de numeración o deadlocks
        return DB::transaction(function () use ($data, $details) {
            $userId = (string) (Auth::id() ?? $data['user_id'] ?? '');
            $data['user_id'] = $userId;
            $date = $data['date'] ?? now()->toDateString();
            $data['date'] = $date;
            $warehouseId = (string) $data['warehouse_id'];

            if (empty($data['client_id'])) {
                $data['client_id'] = null;
            }

            // Determinar branch_id desde el almacén
            if (empty($data['branch_id'])) {
                $warehouse = Warehouse::find($warehouseId);
                if (!$warehouse) {
                    throw new Exception("Almacén no encontrado.");
                }
                $data['branch_id'] = $warehouse->branch_id;
            }

            // Variables acumuladoras (High Precision)
            $accumGross = BigDecimal::zero();
            $accumLineDiscount = BigDecimal::zero();
            $accumCost = BigDecimal::zero();
            $accumProfit = BigDecimal::zero();
            $accumQty = BigDecimal::zero();
            $itemsCount = count($details);

            // 1. Preparar datos de cabecera preliminares (Secuencial y Auditoría)
            $year = (int) date('Y', strtotime($date));
            $data['year'] = $year;
            $data['number'] = $this->getNextSaleNumber((string) $data['branch_id'], $year);
            $data['created_by'] = $userId;
            $data['cost_method'] = 'AVERAGE';

            // 2. Preparar líneas de venta (Cálculos de costo y margen)
            $saleLines = [];
            foreach ($details as $detail) {
                $productId = (string) $detail['product_id'];
                $orderQty = BigDecimal::of($detail['quantity']);
                $unitPrice = BigDecimal::of($detail['unit_price']);
                $lineDiscount = BigDecimal::of($detail['discount'] ?? 0);

                // OBTENER COSTO ACTUAL (Promedio Ponderado de Kardex)
                $lastKardex = Kardex::withoutGlobalScopes()
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->latest('id')
                    ->first();
                
                $unitCost = BigDecimal::of($lastKardex ? $lastKardex->avg_cost : '0.000000');

                // CÁLCULOS DE LÍNEA
                $lineGross = $orderQty->multipliedBy($unitPrice);
                $lineSubtotal = $lineGross->minus($lineDiscount);
                $lineCostTotal = $orderQty->multipliedBy($unitCost);
                $lineProfit = $lineSubtotal->minus($lineCostTotal);
                
                $marginPercent = BigDecimal::zero();
                if (!$lineSubtotal->isZero()) {
                    $marginPercent = $lineProfit->dividedBy($lineSubtotal, 4, RoundingMode::HALF_UP)->multipliedBy(100);
                }

                $saleLines[] = [
                    'product_id' => $productId,
                    'quantity'   => (string) $orderQty->toScale(4, RoundingMode::HALF_UP),
                    'unit_price' => (string) $unitPrice->toScale(4, RoundingMode::HALF_UP),
                    'unit_cost'  => (string) $unitCost->toScale(6, RoundingMode::HALF_UP),
                    'unit_profit' => (string) $lineProfit->dividedBy($orderQty, 6, RoundingMode::HALF_UP),
                    'profit_margin_percent' => (string) $marginPercent->toScale(4, RoundingMode::HALF_UP),
                    'discount_amount' => (string) $lineDiscount->toScale(2, RoundingMode::HALF_UP),
                    'subtotal'    => (string) $lineSubtotal->toScale(2, RoundingMode::HALF_UP),
                    'is_loss'     => $lineProfit->isNegative(),
                    'discount'    => (string) $lineDiscount->toScale(4, RoundingMode::HALF_UP),
                ];

                // Acumuladores
                $accumGross = $accumGross->plus($lineGross);
                $accumLineDiscount = $accumLineDiscount->plus($lineDiscount);
                $accumCost = $accumCost->plus($lineCostTotal);
                $accumProfit = $accumProfit->plus($lineProfit);
                $accumQty = $accumQty->plus($orderQty);
            }

            // 3. Totales Finales de Cabecera
            $deliveryCost = BigDecimal::of($data['delivery_cost'] ?? 0);
            $globalDiscount = BigDecimal::of($data['global_discount'] ?? 0);
            $totalDiscount = $accumLineDiscount->plus($globalDiscount);
            
            // Total neto incluye el costo de envío
            $totalNet = $accumGross->minus($totalDiscount)->plus($deliveryCost);
            $totalProfitFinal = $totalNet->minus($accumCost)->minus($deliveryCost); // Profit logic: cost of delivery is neutral or handled separately

            $data['total_gross'] = (string) $accumGross->toScale(2, RoundingMode::HALF_UP);
            $data['total_discount'] = (string) $totalDiscount->toScale(2, RoundingMode::HALF_UP);
            $data['subtotal'] = (string) $accumGross->minus($accumLineDiscount)->toScale(2, RoundingMode::HALF_UP);
            $data['total_cost'] = (string) $accumCost->toScale(6, RoundingMode::HALF_UP);
            $data['total_profit'] = (string) $totalProfitFinal->toScale(6, RoundingMode::HALF_UP);
            $data['total_quantity'] = (string) $accumQty->toScale(4, RoundingMode::HALF_UP);
            $data['items_count'] = $itemsCount;
            $data['is_loss'] = $totalProfitFinal->isNegative();
            $totalAmount = (string) $totalNet->toScale(2, RoundingMode::HALF_UP);
            $data['total'] = $totalAmount;

            // 4. VALIDAR LÍMITE DE CRÉDITO
            if (($data['payment_type'] ?? 'efectivo') === 'credito') {
                $downPaymentVal = $data['down_payment'] ?? 0;
                $downPayment = BigDecimal::of($downPaymentVal);
                
                $data['total_payment'] = (string) $downPayment->toScale(2, RoundingMode::HALF_UP);
                $remainingBalance = $totalNet->minus($downPayment);
                $data['balance'] = (string) $remainingBalance->toScale(2, RoundingMode::HALF_UP);

                // Sin validación de crédito ya que no hay clientes
            } else {
                $data['total_payment'] = $totalAmount;
                $data['balance'] = '0.00';
            }

            // 5. DETERMINAR ESTADO DE ENTREGA
            $deliveryModeInput = $data['delivery_mode'] ?? \App\Enums\DeliveryMode::DIRECT_SALE->value;
            // Solo se entrega ahora (descuenta stock) si es Venta Directa
            $shouldDeliverNow = $deliveryModeInput === \App\Enums\DeliveryMode::DIRECT_SALE->value;
            
            $data['is_delivered'] = $shouldDeliverNow;
            $data['delivered_at'] = $shouldDeliverNow ? now() : null;

            // 6. PERSISTIR VENTA
            $sale = Sale::create($data);

            // 7. CREAR CUENTA POR COBRAR
            if ($sale->payment_type === 'credito') {
                $this->accountReceivableService->createForSale($sale, $data['down_payment'] ?? null);
            }

            // 8. PERSISTIR DETALLES
            foreach ($saleLines as $line) {
                $sale->details()->create($line);
            }

            // 9. PROCESAR MOVIMIENTO FÍSICO SI CORRESPONDE
            if ($shouldDeliverNow) {
                $this->processInventoryMovement($sale);
            }

            $sale->load('details');
            event(new \App\Events\SaleCreated($sale));

            return $sale;
        }, 3);
    }

    /**
     * Realiza el descuento de stock y registra la salida en Kardex.
     */
    public function processInventoryMovement(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            foreach ($sale->details as $detail) {
                // Registrar en Kardex
                $this->kardexService->record(
                    type: 'SALIDA',
                    productId: (string) $detail->product_id,
                    warehouseId: (string) $sale->warehouse_id,
                    quantity: (string) $detail->quantity,
                    unitCost: (string) $detail->unit_cost,
                    userId: (string) ($sale->user_id ?? Auth::id()),
                    notes: $sale->notes ?? 'Venta N° ' . $sale->number,
                    recordableType: Sale::class,
                    recordableId: $sale->id,
                    pointOfSaleId: null
                );
            }
        });
    }

    /**
     * Completa la entrega física de una venta diferida.
     */
    public function deliver(Sale $sale, ?string $deliveredAt = null): bool
    {
        if ($sale->is_delivered) {
            throw new Exception("Esta venta ya ha sido entregada.");
        }

        if (!$sale->is_active) {
            throw new Exception("No se puede entregar una venta cancelada.");
        }

        return DB::transaction(function () use ($sale, $deliveredAt) {
            $this->processInventoryMovement($sale);

            $sale->is_delivered = true;
            $sale->delivered_at = $deliveredAt ? Carbon::parse($deliveredAt) : now();
            $sale->delivered_by = (string) Auth::id();
            return $sale->save();
        });
    }

    /**
     * Annul a sale and restore stock levels.
     */
    public function annul(Sale $sale, string $reason): bool
    {
        return DB::transaction(function () use ($sale, $reason) {
            // Solo restauramos stock si la venta fue efectivamente entregada (descontada)
            if ($sale->is_delivered) {
                foreach ($sale->details as $detail) {
                    // Find original Kardex record to get the cost
                    $originalKardex = Kardex::withoutGlobalScopes()
                        ->where('recordable_id', $sale->id)
                        ->where('product_id', $detail->product_id)
                        ->where('type', 'SALIDA')
                        ->first();

                    $unitCost = $originalKardex ? $originalKardex->unit_cost : '0.0000';

                    // Create new Kardex entry (ENTRADA) for reversion using the Service
                    $this->kardexService->record(
                        'ENTRADA',
                        (string) $detail->product_id,
                        (string) $sale->warehouse_id,
                        (string) $detail->quantity,
                        (string) $unitCost,
                        (string) (Auth::id() ?? $sale->user_id),
                        "Anulación venta N° {$sale->number} - motivo: {$reason}",
                        Sale::class,
                        $sale->id,
                        null
                    );
                }

                // Revert the sale from the BI metrics ONLY if it was delivered
                app(BIService::class)->revertDailySale($sale);
            }

            // Update sale status (logical annulment)
            $sale->is_active = false;
            $sale->status = 'cancelada';
            $sale->reason_cancel = $reason;
            $sale->cancelled_by = (string) Auth::id();
            $sale->cancelled_at = Carbon::now();
            
            return $sale->save();
        });
    }

    private function getNextSaleNumber(string $branchId, int $year): int
    {
        // Sin bloqueos. El índice único en la tabla `sales` manejará la colisión si ocurre.
        $lastNumber = Sale::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->where('year', $year)
            ->max('number') ?? 0;

        return (int)$lastNumber + 1;
    }
}
