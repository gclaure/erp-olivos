<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsumptionRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'formatted_number' => $this->formatted_number,
            'requested_by' => $this->requested_by,
            'date' => $this->date?->format('Y-m-d'),
            'date_formatted' => $this->date ? $this->date->translatedFormat('l, d \d\e F \d\e Y') : '—',
            'status' => $this->status, // pendiente, entregado, parcial, compras_generado, cancelado
            'notes' => $this->notes,
            'warehouse' => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ],
            'warehouse_name' => $this->warehouse?->name,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'user_name' => $this->user?->name,
            'received_by_user' => $this->relationLoaded('receivedByUser') && $this->receivedByUser ? [
                'id' => $this->receivedByUser->id,
                'name' => $this->receivedByUser->name,
            ] : null,
            'received_at' => $this->received_at?->format('Y-m-d H:i:s'),
            'received_at_formatted' => $this->received_at ? $this->received_at->translatedFormat('d \d\e F, Y \a \l\a\s H:i') : null,
            'dispatched_by_user' => $this->relationLoaded('dispatchedByUser') && $this->dispatchedByUser ? [
                'id' => $this->dispatchedByUser->id,
                'name' => $this->dispatchedByUser->name,
            ] : null,
            'dispatched_at' => $this->dispatched_at?->format('Y-m-d H:i:s'),
            'dispatched_at_formatted' => $this->dispatched_at ? $this->dispatched_at->translatedFormat('d \d\e F, Y \a \l\a\s H:i') : null,
            'approved_by_user' => $this->relationLoaded('approvedByUser') && $this->approvedByUser ? [
                'id' => $this->approvedByUser->id,
                'name' => $this->approvedByUser->name,
            ] : null,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'approved_at_formatted' => $this->approved_at ? $this->approved_at->translatedFormat('d \d\e F, Y \a \l\a\s H:i') : null,
            'observed_by_user' => $this->relationLoaded('observedByUser') && $this->observedByUser ? [
                'id' => $this->observedByUser->id,
                'name' => $this->observedByUser->name,
            ] : null,
            'observed_at' => $this->observed_at?->format('Y-m-d H:i:s'),
            'observed_at_formatted' => $this->observed_at ? $this->observed_at->translatedFormat('d \d\e F, Y \a \l\a\s H:i') : null,
            'observation_notes' => $this->observation_notes,
            'cancelled_by_user' => $this->relationLoaded('cancelledByUser') && $this->cancelledByUser ? [
                'id' => $this->cancelledByUser->id,
                'name' => $this->cancelledByUser->name,
            ] : null,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancelled_at_formatted' => $this->cancelled_at ? $this->cancelled_at->translatedFormat('d \d\e F, Y \a \l\a\s H:i') : null,
            'cancellation_notes' => $this->cancellation_notes,
            'details' => ConsumptionRequestDetailResource::collection($this->whenLoaded('details')),
            'has_missing_stock' => $this->whenLoaded('details', function () {
                foreach ($this->details as $detail) {
                    $stock = (float) ($detail->product?->stocks->where('warehouse_id', $this->warehouse_id)->first()?->quantity ?? 0);
                    $pending = (float) $detail->quantity_requested - (float) $detail->quantity_delivered;
                    if ($pending > $stock) {
                        return true;
                    }
                }
                return false;
            }, false),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_time' => $this->created_at?->format('H:i'),
        ];
    }
}
