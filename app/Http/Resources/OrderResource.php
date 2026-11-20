<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\OrderComplementProviderEnum;
use App\Enums\OrderDeliveryModeEnum;
use App\Models\OrderAttachment;
use App\Models\OrderComplement;
use App\Models\OrderItem;
use App\Models\OrderItemDetail;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $deliveryMode = $this->delivery_mode instanceof OrderDeliveryModeEnum
            ? $this->delivery_mode
            : OrderDeliveryModeEnum::tryFrom((string) $this->delivery_mode) ?? OrderDeliveryModeEnum::Pickup;

        return [
            'id' => $this->id,
            'number' => (int) $this->number,
            'formatted_number' => $this->formatted_number,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn (): array => [
                'id' => $this->customer->id,
                'full_name' => $this->customer->full_name,
                'phone' => $this->customer->phone,
                'address' => $this->customer->address,
                'orders_count' => $this->customer->orders_count ?? null,
            ]),
            'user' => $this->whenLoaded('user', fn (): ?array => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null),
            'registered_at' => $this->registered_at?->format('Y-m-d'),
            'registered_at_formatted' => $this->registered_at?->locale('es')->translatedFormat('d/m/Y'),
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'delivery_date_formatted' => $this->delivery_date
                ? ucfirst($this->delivery_date->locale('es')->translatedFormat('l j \d\e F, Y'))
                : null,
            'delivery_date_short' => $this->delivery_date?->format('d/m/Y'),
            'delivery_time' => $this->delivery_time,
            'delivery_mode' => $deliveryMode->value,
            'delivery_mode_label' => $deliveryMode->label(),
            'delivery_address' => $this->delivery_address,
            'status' => $this->status ?? 'registrado',
            'is_cancelled' => ($this->status ?? 'registrado') === 'anulado',
            'cancelled_at' => $this->cancelled_at?->locale('es')->translatedFormat('d/m/Y H:i'),
            'cancelled_by' => $this->whenLoaded('cancelledBy', fn (): ?array => $this->cancelledBy ? [
                'id' => $this->cancelledBy->id,
                'name' => $this->cancelledBy->name,
            ] : null),
            'cancellation_reason' => $this->cancellation_reason,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'paid_amount' => (float) $this->paid_amount,
            'balance_due' => (float) $this->balance_due,
            'is_paid' => (float) $this->balance_due <= 0.0,
            'payment_notes' => $this->payment_notes,
            'general_notes' => $this->general_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'items_count' => $this->whenCounted('items'),

            'items' => $this->whenLoaded('items', fn () => $this->items->map(function (OrderItem $item): array {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => (float) $item->quantity,
                    'portions_or_size' => $item->portions_or_size,
                    'unit_price' => (float) $item->unit_price,
                    'custom_description' => $item->custom_description,
                    'reference_notes' => $item->reference_notes,
                    'observations' => $item->observations,
                    'details' => $item->relationLoaded('details')
                        ? $item->details->map(fn (OrderItemDetail $detail): array => [
                            'id' => $detail->id,
                            'label' => $detail->label,
                            'value' => $detail->value,
                        ])->values()->all()
                        : [],
                    'attachments' => $item->relationLoaded('attachments')
                        ? $item->attachments->map(fn (OrderAttachment $att): array => [
                            'id' => $att->id,
                            'file_path' => $att->file_path,
                            'file_url' => asset($att->file_url),
                            'original_name' => $att->original_name,
                            'notes' => $att->notes,
                        ])->values()->all()
                        : [],
                    'complements' => $item->relationLoaded('complements')
                        ? $item->complements->map(function (OrderComplement $comp): array {
                            $provider = $comp->provider_type instanceof OrderComplementProviderEnum
                                ? $comp->provider_type
                                : OrderComplementProviderEnum::tryFrom((string) $comp->provider_type) ?? OrderComplementProviderEnum::Client;

                            return [
                                'id' => $comp->id,
                                'description' => $comp->description,
                                'provider_type' => $provider->value,
                                'provider_label' => $provider->label(),
                            ];
                        })->values()->all()
                        : [],
                ];
            })->values()->all()),

            'complements' => $this->whenLoaded('complements', fn () => $this->complements->map(function (OrderComplement $comp): array {
                $provider = $comp->provider_type instanceof OrderComplementProviderEnum
                    ? $comp->provider_type
                    : OrderComplementProviderEnum::tryFrom((string) $comp->provider_type) ?? OrderComplementProviderEnum::Client;

                return [
                    'id' => $comp->id,
                    'order_item_id' => $comp->order_item_id,
                    'description' => $comp->description,
                    'provider_type' => $provider->value,
                    'provider_label' => $provider->label(),
                ];
            })->values()->all()),

            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn (OrderPayment $payment): array => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'paid_at' => $payment->paid_at?->format('Y-m-d H:i'),
                'paid_at_formatted' => $payment->paid_at?->locale('es')->translatedFormat('d/m/Y H:i'),
                'notes' => $payment->notes,
                'user_name' => $payment->relationLoaded('user') ? $payment->user?->name : null,
            ])->values()->all()),
        ];
    }
}

