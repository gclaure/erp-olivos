<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class KardexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rawType = strtoupper($this->type ?? '');
        $isIngreso = in_array($rawType, ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA', 'PURCHASE']);
        $isSalida = in_array($rawType, ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA']);

        $typeLabel = ucfirst(str_replace('_', ' ', strtolower($this->type ?? '—')));
        if ($rawType === 'PURCHASE') {
            $typeLabel = 'Compra';
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $typeLabel,
            'is_ingreso' => $isIngreso,
            'is_salida' => $isSalida,
            'quantity' => (float) $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'total_cost' => (float) $this->total_cost,
            'balance_quantity' => (float) $this->balance_quantity,
            'avg_cost' => (float) $this->avg_cost,
            'balance_total_cost' => (float) ($this->balance_total_cost ?? ($this->balance_quantity * $this->avg_cost)),
            'notes' => $this->notes,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'date_formatted' => $this->created_at ? $this->created_at->translatedFormat('l, d \d\e F \d\e Y') : null,
            'time_formatted' => $this->created_at ? $this->created_at->format('H:i') : null,
            
            // Relaciones
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'code' => $this->product?->code,
            ],
            'warehouse' => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ],
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'recordable' => $this->formatRecordable($this->recordable),
        ];
    }

    private function formatRecordable($recordable): ?array
    {
        if (!$recordable) {
            return null;
        }

        return [
            'payment_type' => $recordable->payment_type ?? null,
            'delivery_mode' => is_object($recordable->delivery_mode ?? null) 
                ? $recordable->delivery_mode->label() 
                : ($recordable->delivery_mode ?? null),
        ];
    }
}
