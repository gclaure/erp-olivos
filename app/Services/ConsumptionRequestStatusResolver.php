<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsumptionDetailFulfillmentEnum;
use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use Brick\Math\BigDecimal;
use Illuminate\Support\Collection;

class ConsumptionRequestStatusResolver
{
    private const PROTECTED_STATUSES = [
        'entregado',
        'cancelado',
        'observado',
        'pendiente',
    ];

    /**
     * Deriva el estado de despacho de una solicitud de consumo a partir de sus detalles.
     *
     * @param Collection<int, ConsumptionRequestDetail>|null $details
     */
    public function resolve(ConsumptionRequest $request, ?Collection $details = null): string
    {
        if (in_array($request->status, self::PROTECTED_STATUSES, true)) {
            return $request->status;
        }

        $items = $details ?? $request->details()->get();

        if ($items->isEmpty()) {
            return 'aprobado';
        }

        $allComplete = true;
        $totalDelivered = BigDecimal::zero();

        foreach ($items as $detail) {
            $requested = BigDecimal::of((string) ($detail->quantity_requested ?? '0'));
            $delivered = BigDecimal::of((string) ($detail->quantity_delivered ?? '0'));
            $totalDelivered = $totalDelivered->plus($delivered);

            $fulfillment = $detail->fulfillment_status instanceof ConsumptionDetailFulfillmentEnum
                ? $detail->fulfillment_status
                : ConsumptionDetailFulfillmentEnum::tryFrom((string) $detail->fulfillment_status);

            if ($fulfillment === ConsumptionDetailFulfillmentEnum::PendingReturn || $delivered->isLessThan($requested)) {
                $allComplete = false;
            }
        }

        if ($allComplete) {
            return 'despachado';
        }

        if ($totalDelivered->isGreaterThan(0)) {
            return 'despachado_parcial';
        }

        return 'aprobado';
    }
}
