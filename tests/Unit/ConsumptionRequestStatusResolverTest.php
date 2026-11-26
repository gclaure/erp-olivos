<?php

declare(strict_types=1);

use App\Enums\ConsumptionDetailFulfillmentEnum;
use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Services\ConsumptionRequestStatusResolver;

it('resuelve despachado cuando todos los detalles están completos y sin devolución pendiente', function (): void {
    $resolver = new ConsumptionRequestStatusResolver();
    $request = new ConsumptionRequest(['status' => 'aprobado']);

    $details = collect([
        new ConsumptionRequestDetail([
            'quantity_requested' => '2.0000',
            'quantity_delivered' => '2.0000',
            'fulfillment_status' => ConsumptionDetailFulfillmentEnum::Normal,
        ]),
        new ConsumptionRequestDetail([
            'quantity_requested' => '1.5000',
            'quantity_delivered' => '1.5000',
            'fulfillment_status' => ConsumptionDetailFulfillmentEnum::Normal,
        ]),
    ]);

    expect($resolver->resolve($request, $details))->toBe('despachado');
});

it('resuelve despachado_parcial en escenario mixto aceite/azúcar/mantequilla con al menos una unidad entregada', function (): void {
    $resolver = new ConsumptionRequestStatusResolver();
    $request = new ConsumptionRequest(['status' => 'aprobado']);

    $details = collect([
        // Aceite vegetal completo
        new ConsumptionRequestDetail([
            'quantity_requested' => '1.0000',
            'quantity_delivered' => '1.0000',
            'fulfillment_status' => ConsumptionDetailFulfillmentEnum::Normal,
        ]),
        // Azúcar sin existencias
        new ConsumptionRequestDetail([
            'quantity_requested' => '3.0000',
            'quantity_delivered' => '0.0000',
            'fulfillment_status' => ConsumptionDetailFulfillmentEnum::PendingStock,
        ]),
        // Mantequilla modificada con despacho 0
        new ConsumptionRequestDetail([
            'quantity_requested' => '2.0000',
            'quantity_delivered' => '0.0000',
            'fulfillment_status' => ConsumptionDetailFulfillmentEnum::Normal,
        ]),
    ]);

    expect($resolver->resolve($request, $details))->toBe('despachado_parcial');
});

it('resuelve aprobado cuando ninguna unidad ha sido despachada incluso con ítems en pending_stock', function (): void {
    $resolver = new ConsumptionRequestStatusResolver();
    $request = new ConsumptionRequest(['status' => 'despachado_parcial']);

    $details = collect([
        new ConsumptionRequestDetail([
            'quantity_requested' => '2.0000',
            'quantity_delivered' => '0.0000',
            'fulfillment_status' => ConsumptionDetailFulfillmentEnum::PendingStock,
        ]),
        new ConsumptionRequestDetail([
            'quantity_requested' => '4.0000',
            'quantity_delivered' => '0.0000',
            'fulfillment_status' => ConsumptionDetailFulfillmentEnum::PendingStock,
        ]),
    ]);

    expect($resolver->resolve($request, $details))->toBe('aprobado');
});

it('no resuelve despachado si un detalle está bloqueado en pending_return', function (): void {
    $resolver = new ConsumptionRequestStatusResolver();
    $request = new ConsumptionRequest(['status' => 'despachado']);

    $details = collect([
        new ConsumptionRequestDetail([
            'quantity_requested' => '1.0000',
            'quantity_delivered' => '1.0000',
            'fulfillment_status' => ConsumptionDetailFulfillmentEnum::Normal,
        ]),
        new ConsumptionRequestDetail([
            'quantity_requested' => '2.0000',
            'quantity_delivered' => '2.0000',
            'fulfillment_status' => ConsumptionDetailFulfillmentEnum::PendingReturn,
        ]),
    ]);

    expect($resolver->resolve($request, $details))->toBe('despachado_parcial');
});

it('conserva estados protegidos como entregado, cancelado, observado y pendiente', function (): void {
    $resolver = new ConsumptionRequestStatusResolver();
    $details = collect([
        new ConsumptionRequestDetail([
            'quantity_requested' => '2.0000',
            'quantity_delivered' => '0.0000',
            'fulfillment_status' => ConsumptionDetailFulfillmentEnum::Normal,
        ]),
    ]);

    foreach (['entregado', 'cancelado', 'observado', 'pendiente'] as $status) {
        $request = new ConsumptionRequest(['status' => $status]);
        expect($resolver->resolve($request, $details))->toBe($status);
    }
});
