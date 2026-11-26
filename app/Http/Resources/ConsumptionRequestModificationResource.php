<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ConsumptionDetailFulfillmentEnum;
use App\Enums\ConsumptionModificationStatusEnum;
use App\Models\Product;
use App\Models\PurchaseOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsumptionRequestModificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusEnum = $this->status instanceof ConsumptionModificationStatusEnum
            ? $this->status
            : ConsumptionModificationStatusEnum::tryFrom((string) $this->status);

        $warehouseId = $this->relationLoaded('consumptionRequest') && $this->consumptionRequest
            ? $this->consumptionRequest->warehouse_id
            : null;

        $fromCost = $this->resolveProductCost($this->relationLoaded('fromProduct') ? $this->fromProduct : null, $warehouseId);
        $toCost = $this->resolveProductCost($this->relationLoaded('toProduct') ? $this->toProduct : null, $warehouseId);

        $totalReturned = 0.0;
        if ($this->relationLoaded('returns')) {
            $totalReturned = (float) $this->returns->sum(static fn ($r): float => (float) $r->returned_quantity);
        }

        $physDelivered = (float) $this->quantity_physically_delivered;
        $pendingReturn = max(0.0, round($physDelivered - $totalReturned, 4));

        $openPurchaseLines = [];
        if (
            $statusEnum === ConsumptionModificationStatusEnum::Pending
            && $this->relationLoaded('detail')
            && $this->detail
            && $this->detail->relationLoaded('purchaseOrderDetails')
        ) {
            foreach ($this->detail->purchaseOrderDetails as $poLine) {
                /** @var PurchaseOrderDetail $poLine */
                if ($poLine->isCancelled() || $poLine->product_id !== $this->from_product_id) {
                    continue;
                }
                $po = $poLine->relationLoaded('purchaseOrder') ? $poLine->purchaseOrder : null;
                if (!$po || !in_array($po->status, ['pendiente', 'aprobada'], true)) {
                    continue;
                }

                $otherActiveCount = 0;
                if ($po->relationLoaded('details')) {
                    $otherActiveCount = $po->details
                        ->filter(static fn (PurchaseOrderDetail $d): bool => !$d->isCancelled() && $d->id !== $poLine->id)
                        ->count();
                }

                $openPurchaseLines[] = [
                    'id' => $poLine->id,
                    'purchase_order_id' => $po->id,
                    'order_status' => $po->status,
                    'order_date' => $po->date?->format('Y-m-d'),
                    'provider_name' => $po->relationLoaded('provider') ? ($po->provider?->name ?? 'Proveedor') : 'Proveedor',
                    'quantity' => (float) $poLine->quantity,
                    'unit_price' => (float) $poLine->unit_price,
                    'subtotal' => (float) $poLine->subtotal,
                    'other_active_lines_count' => $otherActiveCount,
                ];
            }
        }

        $consumptionRequestData = null;
        if ($this->relationLoaded('consumptionRequest') && $this->consumptionRequest) {
            $cr = $this->consumptionRequest;
            $otherDetails = [];
            if ($cr->relationLoaded('details')) {
                foreach ($cr->details as $d) {
                    if ($d->id === $this->consumption_request_detail_id) {
                        continue;
                    }
                    $dStatus = $d->fulfillment_status instanceof ConsumptionDetailFulfillmentEnum
                        ? $d->fulfillment_status->value
                        : (string) $d->fulfillment_status;

                    $otherDetails[] = [
                        'id' => $d->id,
                        'product_name' => $d->relationLoaded('product') ? $d->product?->name : null,
                        'product_code' => $d->relationLoaded('product') ? $d->product?->code : null,
                        'unit_of_measure' => ($d->relationLoaded('product') && $d->product && $d->product->relationLoaded('unitOfMeasure'))
                            ? ($d->product->unitOfMeasure?->abbreviation ?? $d->product->unitOfMeasure?->name ?? 'UND')
                            : 'UND',
                        'quantity_requested' => (float) $d->quantity_requested,
                        'quantity_delivered' => (float) $d->quantity_delivered,
                        'fulfillment_status' => $dStatus,
                    ];
                }
            }

            $consumptionRequestData = [
                'id' => $cr->id,
                'number' => $cr->number,
                'formatted_number' => $cr->formatted_number,
                'requested_by' => $cr->requested_by,
                'status' => $cr->status,
                'date_formatted' => $cr->date ? $cr->date->translatedFormat('d M Y') : '—',
                'warehouse_id' => $cr->warehouse_id,
                'warehouse_name' => $cr->relationLoaded('warehouse') ? $cr->warehouse?->name : null,
                'user_name' => $cr->relationLoaded('user') ? $cr->user?->name : null,
                'other_details' => $otherDetails,
            ];
        }

        return [
            'id' => $this->id,
            'consumption_request_id' => $this->consumption_request_id,
            'consumption_request_detail_id' => $this->consumption_request_detail_id,
            'status' => $statusEnum?->value ?? (string) $this->status,
            'status_label' => $statusEnum?->label() ?? (string) $this->status,
            'is_open' => $statusEnum?->isOpen() ?? false,
            'from_product' => $this->relationLoaded('fromProduct') && $this->fromProduct ? [
                'id' => $this->fromProduct->id,
                'name' => $this->fromProduct->name,
                'code' => $this->fromProduct->code,
                'unit_of_measure_id' => $this->fromProduct->unit_of_measure_id,
                'unit_of_measure' => $this->fromProduct->relationLoaded('unitOfMeasure')
                    ? ($this->fromProduct->unitOfMeasure?->abbreviation ?? $this->fromProduct->unitOfMeasure?->name ?? 'UND')
                    : 'UND',
                'unit_cost' => $fromCost,
            ] : null,
            'to_product' => $this->relationLoaded('toProduct') && $this->toProduct ? [
                'id' => $this->toProduct->id,
                'name' => $this->toProduct->name,
                'code' => $this->toProduct->code,
                'is_active' => (bool) $this->toProduct->is_active,
                'unit_of_measure_id' => $this->toProduct->unit_of_measure_id,
                'unit_of_measure' => $this->toProduct->relationLoaded('unitOfMeasure')
                    ? ($this->toProduct->unitOfMeasure?->abbreviation ?? $this->toProduct->unitOfMeasure?->name ?? 'UND')
                    : 'UND',
                'unit_cost' => $toCost,
            ] : null,
            'cost_difference' => round($toCost - $fromCost, 4),
            'original_quantity' => (float) $this->original_quantity,
            'requested_quantity' => (float) $this->requested_quantity,
            'has_quantity_change' => abs((float) $this->requested_quantity - (float) $this->original_quantity) >= 0.0001,
            'quantity_dispatched_before_modification' => (float) $this->quantity_dispatched_before_modification,
            'quantity_prepared' => (float) $this->quantity_prepared,
            'quantity_physically_delivered' => $physDelivered,
            'total_returned_quantity' => $totalReturned,
            'pending_return_quantity' => $pendingReturn,
            'reason' => $this->reason,
            'resolution_notes' => $this->resolution_notes,
            'purchase_resolutions' => $this->purchase_resolutions ?? [],
            'open_purchase_lines' => $openPurchaseLines,
            'has_legacy_unlinked_orders_warning' => (bool) ($this->has_legacy_unlinked_orders_warning ?? false),
            'requested_by_user' => $this->relationLoaded('requestedByUser') && $this->requestedByUser ? [
                'id' => $this->requestedByUser->id,
                'name' => $this->requestedByUser->name,
            ] : null,
            'resolved_by_user' => $this->relationLoaded('resolvedByUser') && $this->resolvedByUser ? [
                'id' => $this->resolvedByUser->id,
                'name' => $this->resolvedByUser->name,
            ] : null,
            'cancelled_by_user' => $this->relationLoaded('cancelledByUser') && $this->cancelledByUser ? [
                'id' => $this->cancelledByUser->id,
                'name' => $this->cancelledByUser->name,
            ] : null,
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_formatted' => $this->created_at ? $this->created_at->translatedFormat('d M Y, H:i') : '—',
            'resolved_at' => $this->resolved_at?->format('Y-m-d H:i:s'),
            'resolved_at_formatted' => $this->resolved_at ? $this->resolved_at->translatedFormat('d M Y, H:i') : null,
            'applied_at' => $this->applied_at?->format('Y-m-d H:i:s'),
            'applied_at_formatted' => $this->applied_at ? $this->applied_at->translatedFormat('d M Y, H:i') : null,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancelled_at_formatted' => $this->cancelled_at ? $this->cancelled_at->translatedFormat('d M Y, H:i') : null,
            'returns' => ConsumptionRequestModificationReturnResource::collection($this->whenLoaded('returns')),
            'consumption_request' => $consumptionRequestData,
        ];
    }

    private function resolveProductCost(?Product $product, ?string $warehouseId): float
    {
        if (!$product) {
            return 0.0;
        }

        if ($product->relationLoaded('stocks')) {
            $stock = $warehouseId
                ? $product->stocks->where('warehouse_id', $warehouseId)->first()
                : $product->stocks->first();

            if ($stock && (float) ($stock->average_cost ?? 0) > 0) {
                return (float) $stock->average_cost;
            }
        }

        return (float) ($product->price ?? 0.0);
    }
}
