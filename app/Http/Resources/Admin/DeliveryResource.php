<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class DeliveryResource extends JsonResource
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
            'formatted_number' => str_pad((string)($this->number ?? 0), 6, '0', STR_PAD_LEFT),
            'date' => $this->date,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'formatted_created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'client' => [
                'id' => $this->client->id ?? null,
                'name' => $this->client->name ?? 'Consumidor Final',
                'document_number' => $this->client->document_number ?? null,
                'phone' => $this->client->phone ?? null,
            ],
            'warehouse' => [
                'id' => $this->warehouse->id ?? null,
                'name' => $this->warehouse->name ?? '—',
            ],
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? '—',
            ],
            'delivery_mode' => [
                'value' => $this->delivery_mode->value,
                'label' => $this->delivery_mode->label(),
                'icon' => $this->delivery_mode->icon(),
            ],
            'delivery_address' => $this->delivery_address,
            'delivery_reference' => $this->delivery_reference,
            'delivery_zone' => $this->delivery_zone,
            'delivery_at' => $this->delivery_at ? $this->delivery_at->format('Y-m-d H:i:s') : null,
            'formatted_delivery_at' => $this->delivery_at ? $this->delivery_at->format('d/m/Y H:i') : null,
            'delivery_observations' => $this->delivery_observations,
            'delivery_cost' => (float)$this->delivery_cost,
            'delivery_time_slot' => $this->delivery_time_slot,
            'delivery_map_url' => $this->delivery_map_url,
            'delivery_contact_name' => $this->delivery_contact_name,
            'delivery_contact_phone' => $this->delivery_contact_phone,
            'shipping_company' => $this->shipping_company,
            'shipping_origin' => $this->shipping_origin,
            'shipping_destination' => $this->shipping_destination,
            'is_delivered' => $this->is_delivered,
            'delivered_at' => $this->delivered_at ? $this->delivered_at->format('Y-m-d H:i:s') : null,
            'formatted_delivered_at' => $this->delivered_at ? $this->delivered_at->format('d/m/Y H:i') : null,
            'delivered_by_name' => $this->deliveredBy->name ?? 'Sistema',
            'is_active' => $this->is_active,
            'cancelled_at' => $this->cancelled_at ? $this->cancelled_at->format('Y-m-d H:i:s') : null,
            'formatted_cancelled_at' => $this->cancelled_at ? $this->cancelled_at->format('d/m/Y H:i') : null,
            'cancelled_by_name' => $this->cancelledBy->name ?? 'Sistema',
            'reason_cancel' => $this->reason_cancel,
            'total_quantity' => (float)$this->total_quantity,
            'total' => (float)$this->total,
            'subtotal' => (float)$this->subtotal,
            'global_discount' => (float)$this->global_discount,
            'details' => $this->whenLoaded('details', function() {
                return $this->details->map(fn($detail) => [
                    'id' => $detail->id,
                    'product_name' => $detail->product->name ?? '—',
                    'quantity' => (float)$detail->quantity,
                    'unit_price' => (float)$detail->unit_price,
                    'subtotal' => (float)$detail->subtotal,
                    'discount_amount' => (float)$detail->discount_amount,
                ]);
            }),
        ];
    }
}
