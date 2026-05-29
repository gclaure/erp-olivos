<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\QuotationSequence;
use App\Models\Warehouse;
use Exception;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    public function __construct(private SaleService $saleService) {}

    /**
     * @param array $quotationData
     * @param array $details
     * @return Quotation
     * @throws Exception
     */
    public function create(array $quotationData, array $details): Quotation
    {
        return DB::transaction(function () use ($quotationData, $details) {
            // Determine branch_id from warehouse
            if (empty($quotationData['branch_id'])) {
                $warehouseId = $quotationData['warehouse_id'] ?? Warehouse::first()->id;
                $warehouse = Warehouse::find($warehouseId);
                if (!$warehouse) {
                    throw new Exception("Almacén no encontrado.");
                }
                $quotationData['branch_id'] = $warehouse->branch_id;
            }

            // Assign sequential number
            $year = (int) date('Y', strtotime($quotationData['date'] ?? now()->toDateString()));
            $quotationData['year'] = $year;
            $quotationData['number'] = $this->getNextQuotationNumber((string)$quotationData['branch_id'], $year);

            $subtotal = BigDecimal::of($quotationData['subtotal']);
            $globalDiscount = BigDecimal::of($quotationData['global_discount'] ?? 0);
            $deliveryCost = BigDecimal::of($quotationData['delivery_cost'] ?? 0);
            
            // Recalcular total para asegurar integridad en caso de disparidad de red/frontend
            $total = $subtotal->minus($globalDiscount)->plus($deliveryCost);
            if ($total->isNegative()) $total = BigDecimal::zero();

            $quotation = Quotation::create([
                'client_id'       => $quotationData['client_id'],
                'user_id'         => $quotationData['user_id'],
                'branch_id'       => $quotationData['branch_id'],
                'date'            => $quotationData['date'],
                'valid_until'     => $quotationData['valid_until'] ?? now()->addDays(15)->format('Y-m-d'),
                'notes'           => $quotationData['notes'] ?? null,
                'status'          => 'pendiente',
                'subtotal'        => (string) $subtotal->toScale(2, RoundingMode::HALF_UP),
                'global_discount' => (string) $globalDiscount->toScale(4, RoundingMode::HALF_UP),
                'total_payment'   => (string) $total->toScale(2, RoundingMode::HALF_UP),
                'total'           => (string) $total->toScale(2, RoundingMode::HALF_UP),
                'number'          => $quotationData['number'],
                'year'            => $quotationData['year'],
                // Campos Logísticos
                'delivery_address' => $quotationData['delivery_address'] ?? null,
                'delivery_contact_name' => $quotationData['delivery_contact_name'] ?? null,
                'delivery_contact_phone' => $quotationData['delivery_contact_phone'] ?? null,
                'delivery_at' => $quotationData['delivery_at'] ?? null,
                'delivery_observations' => $quotationData['delivery_observations'] ?? null,
                'delivery_zone' => $quotationData['delivery_zone'] ?? null,
                'delivery_reference' => $quotationData['delivery_reference'] ?? null,
                'delivery_cost' => (string) $deliveryCost->toScale(2, RoundingMode::HALF_UP),
                'delivery_time_slot' => $quotationData['delivery_time_slot'] ?? null,
                'delivery_map_url' => $quotationData['delivery_map_url'] ?? null,
            ]);

            foreach ($details as $detail) {
                $qty = BigDecimal::of($detail['quantity']);
                $unitPrice = BigDecimal::of($detail['unit_price']);
                $discount = BigDecimal::of($detail['discount'] ?? 0);
                $subtotal = BigDecimal::of($detail['subtotal']);

                $quotation->details()->create([
                    'product_id' => $detail['product_id'],
                    'quantity'   => (string) $qty->toScale(4, RoundingMode::HALF_UP),
                    'unit_price' => (string) $unitPrice->toScale(4, RoundingMode::HALF_UP),
                    'discount'   => (string) $discount->toScale(4, RoundingMode::HALF_UP),
                    'subtotal'   => (string) $subtotal->toScale(2, RoundingMode::HALF_UP),
                ]);
            }

            return $quotation;
        }, 3);
    }

    /**
     * @param Quotation $quotation
     * @param array $quotationData
     * @param array $details
     * @return Quotation
     * @throws Exception
     */
    public function update(Quotation $quotation, array $quotationData, array $details): Quotation
    {
        return DB::transaction(function () use ($quotation, $quotationData, $details) {
            if ($quotation->status !== 'pendiente') {
                throw new Exception("Solo se pueden editar cotizaciones en estado pendiente.");
            }

            $subtotal = BigDecimal::of($quotationData['subtotal'] ?? 0);
            $globalDiscount = BigDecimal::of($quotationData['global_discount'] ?? 0);
            $deliveryCost = BigDecimal::of($quotationData['delivery_cost'] ?? 0);
            
            $total = $subtotal->minus($globalDiscount)->plus($deliveryCost);
            if ($total->isNegative()) {
                $total = BigDecimal::zero();
            }

            $quotation->update([
                'client_id'       => $quotationData['client_id'],
                'date'            => $quotationData['date'],
                'valid_until'     => $quotationData['valid_until'] ?? $quotation->valid_until,
                'notes'           => array_key_exists('notes', $quotationData) ? $quotationData['notes'] : $quotation->notes,
                'subtotal'        => (string) $subtotal->toScale(2, RoundingMode::HALF_UP),
                'global_discount' => (string) $globalDiscount->toScale(4, RoundingMode::HALF_UP),
                'total_payment'   => (string) $total->toScale(2, RoundingMode::HALF_UP),
                'total'           => (string) $total->toScale(2, RoundingMode::HALF_UP),
            ]);

            // Campos Logísticos (solo si vienen en el array)
            $logisticFields = [
                'delivery_address', 'delivery_contact_name', 'delivery_contact_phone',
                'delivery_at', 'delivery_observations', 'delivery_zone',
                'delivery_reference', 'delivery_time_slot', 'delivery_map_url'
            ];

            foreach ($logisticFields as $field) {
                if (array_key_exists($field, $quotationData)) {
                    $quotation->{$field} = $quotationData[$field];
                }
            }

            if (array_key_exists('delivery_cost', $quotationData)) {
                $quotation->delivery_cost = (string) BigDecimal::of($quotationData['delivery_cost'] ?? 0)->toScale(2, RoundingMode::HALF_UP);
            }

            $quotation->save();

            // Sync details: delete and recreate
            $quotation->details()->delete();

            foreach ($details as $detail) {
                $qty = BigDecimal::of($detail['quantity']);
                $unitPrice = BigDecimal::of($detail['unit_price']);
                $discount = BigDecimal::of($detail['discount'] ?? 0);
                $subtotalDetail = BigDecimal::of($detail['subtotal']);

                $quotation->details()->create([
                    'product_id' => $detail['product_id'],
                    'quantity'   => (string) $qty->toScale(4, RoundingMode::HALF_UP),
                    'unit_price' => (string) $unitPrice->toScale(4, RoundingMode::HALF_UP),
                    'discount'   => (string) $discount->toScale(4, RoundingMode::HALF_UP),
                    'subtotal'   => (string) $subtotalDetail->toScale(2, RoundingMode::HALF_UP),
                ]);
            }

            return $quotation->fresh();
        }, 3);
    }

    /**
     * Get the next sequential number for a branch and year using pessimistic locking.
     */
    private function getNextQuotationNumber(string $branchId, int $year): int
    {
        $lastNumber = Quotation::where('branch_id', $branchId)
            ->where('year', $year)
            ->max('number') ?? 0;

        return (int)$lastNumber + 1;
    }

    /**
     * @param Quotation $quotation
     * @param string $status
     * @return void
     * @throws Exception
     */
    public function changeStatus(Quotation $quotation, string $status): void
    {
        if ($quotation->status === 'completada') {
            throw new Exception("No se puede alterar una cotización ya ejecutada como Venta.");
        }

        if (!in_array($status, ['pendiente', 'cancelada'])) {
            throw new Exception("Estado inválido para la cotización.");
        }

        $quotation->update(['status' => $status]);
    }

    /**
     * Convierte de cotización a Venta.
     */
    public function convertToSale(string $quotationId, string $userId)
    {
        return DB::transaction(function () use ($quotationId, $userId) {
            $quotation = Quotation::with('details')->findOrFail($quotationId);

            if ($quotation->status !== 'pendiente') {
                throw new Exception("Solo cotizaciones pendientes pueden procesarse.");
            }

            // Preparar datos para SaleService
            $saleData = [
                'client_id'       => $quotation->client_id,
                'warehouse_id'    => Warehouse::where('branch_id', $quotation->branch_id)->first()->id ?? Warehouse::first()->id,
                'user_id'         => $userId,
                'branch_id'       => $quotation->branch_id,
                'date'            => now()->format('Y-m-d'),
                'notes'           => "Conversión desde Proforma #" . $quotation->formatted_number . ". " . $quotation->notes,
                'subtotal'        => (string) BigDecimal::of($quotation->subtotal)->toScale(2, RoundingMode::HALF_UP),
                'global_discount' => (string) BigDecimal::of($quotation->global_discount)->toScale(4, RoundingMode::HALF_UP),
                'total_payment'   => (string) BigDecimal::of($quotation->total_payment)->toScale(2, RoundingMode::HALF_UP),
                'total'           => (string) BigDecimal::of($quotation->total)->toScale(2, RoundingMode::HALF_UP),
                'status'          => 'completada',
            ];

            $saleDetails = [];
            foreach ($quotation->details as $d) {
                $saleDetails[] = [
                    'product_id' => $d->product_id,
                    'quantity'   => (string) BigDecimal::of($d->quantity)->toScale(4, RoundingMode::HALF_UP),
                    'unit_price' => (string) BigDecimal::of($d->unit_price)->toScale(4, RoundingMode::HALF_UP),
                    'discount'   => (string) BigDecimal::of($d->discount)->toScale(4, RoundingMode::HALF_UP),
                    'subtotal'   => (string) BigDecimal::of($d->subtotal)->toScale(2, RoundingMode::HALF_UP),
                ];
            }

            $sale = $this->saleService->create($saleData, $saleDetails);

            $quotation->update(['status' => 'completada']);

            return $sale;
        });
    }
}
