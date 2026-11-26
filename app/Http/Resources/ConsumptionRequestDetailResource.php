<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ConsumptionDetailFulfillmentEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ConsumptionRequestDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $warehouseId = $this->relationLoaded('consumptionRequest') && $this->consumptionRequest
            ? $this->consumptionRequest->warehouse_id
            : null;
        $stock = 0.0;
        $unitCost = 0.0;

        if ($this->product) {
            $stockRecord = null;
            if ($this->product->relationLoaded('stocks')) {
                $stockRecord = $warehouseId
                    ? $this->product->stocks->where('warehouse_id', $warehouseId)->first()
                    : $this->product->stocks->first();
            }
            $stock = (float) ($stockRecord?->quantity ?? 0.0);
            $unitCost = (float) (($stockRecord && (float) ($stockRecord->average_cost ?? 0) > 0)
                ? $stockRecord->average_cost
                : ($this->product->price ?? 0.0));
        }

        $imageUrl = null;
        $categories = [];
        $images = [];
        if ($this->product) {
            $imageUrl = $this->product->image_path
                ? (str_starts_with($this->product->image_path, 'http') ? $this->product->image_path : Storage::url($this->product->image_path))
                : (isset($this->product->drive_links[0]) ? $this->product->drive_links[0] : null);

            if ($this->product->relationLoaded('categories')) {
                $categories = $this->product->categories->pluck('name')->toArray();
            }

            $driveLinks = is_array($this->product->drive_links)
                ? $this->product->drive_links
                : (json_decode($this->product->drive_links ?? '[]', true) ?? []);
            if ($imageUrl) {
                $images[] = $imageUrl;
            }
            foreach ($driveLinks as $link) {
                if ($link && !in_array($link, $images, true)) {
                    $images[] = $link;
                }
            }
        }

        $fulfillmentEnum = $this->fulfillment_status instanceof ConsumptionDetailFulfillmentEnum
            ? $this->fulfillment_status
            : (ConsumptionDetailFulfillmentEnum::tryFrom((string) $this->fulfillment_status) ?? ConsumptionDetailFulfillmentEnum::Normal);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product?->name,
            'product_code' => $this->product?->code,
            'product_image_url' => $imageUrl,
            'product_images' => $images,
            'product_description' => $this->product?->description,
            'product_location' => $this->product?->location,
            'product_brand' => $this->product?->brand,
            'product_categories' => $categories,
            'product_package_name' => $this->product?->package_name,
            'product_units_per_package' => (float) ($this->product?->units_per_package ?? 1),
            'product_has_expiration' => (bool) $this->product?->has_expiration,
            'unit_of_measure_id' => $this->product?->unit_of_measure_id,
            'unit_of_measure' => $this->product?->unitOfMeasure?->abbreviation ?? $this->product?->unitOfMeasure?->name ?? 'U',
            'unit_cost' => $unitCost,
            'quantity_requested' => (float) $this->quantity_requested,
            'quantity_delivered' => (float) $this->quantity_delivered,
            'quantity_received' => $this->quantity_received !== null ? (float) $this->quantity_received : null,
            'fulfillment_status' => $fulfillmentEnum->value,
            'fulfillment_status_label' => $fulfillmentEnum->label(),
            'observation' => $this->observation,
            'receive_observation' => $this->receive_observation,
            'stock_available' => $stock,
            'product_type' => $this->product?->type?->value ?? 'materia_prima',
            'is_inventoriable' => $this->product ? $this->product->isInventoriable() : true,
            'open_modification' => $this->whenLoaded('openModification', function () {
                return $this->openModification
                    ? (new ConsumptionRequestModificationResource($this->openModification))->toArray(request())
                    : null;
            }),
            'modifications' => $this->whenLoaded('modifications', function () {
                return ConsumptionRequestModificationResource::collection($this->modifications)->resolve();
            }, []),
        ];
    }
}
