<?php

declare(strict_types=1);

use App\Enums\ConsumptionDetailFulfillmentEnum;
use App\Enums\ConsumptionModificationStatusEnum;
use App\Events\ConsumptionRequestModificationApplied;
use App\Events\ConsumptionRequestModificationRequested;
use App\Events\ConsumptionRequestModificationResolved;
use App\Events\ConsumptionRequestModificationReturnRecorded;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Models\ConsumptionRequestModification;
use App\Models\Kardex;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Role;
use App\Models\Stock;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ConsumptionRequestDispatchService;
use App\Services\ConsumptionRequestModificationService;
use App\Services\ConsumptionRequestService;
use App\Services\PurchaseOrderService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\PermissionRegistrar;

/**
 * Construye el escenario base (sucursal, almacén, unidades, productos, proveedor, roles y usuarios).
 *
 * @return array<string, mixed>
 */
function buildConsumptionScenario(): array
{
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $reqPerm = Permission::firstOrCreate(['name' => 'request-consumption-modification', 'guard_name' => 'web']);
    $appPerm = Permission::firstOrCreate(['name' => 'approve-consumption-modification', 'guard_name' => 'web']);
    $managePerm = Permission::firstOrCreate(['name' => 'manage-consumption', 'guard_name' => 'web']);

    $warehouseRole = Role::firstOrCreate(['name' => 'Almacén', 'guard_name' => 'web']);
    $warehouseRole->syncPermissions([$reqPerm, $managePerm]);

    $adminRole = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    $adminRole->syncPermissions([$reqPerm, $appPerm, $managePerm]);

    $consumerRole = Role::firstOrCreate(['name' => 'Consumidor', 'guard_name' => 'web']);

    $company = Company::create([
        'nit' => '1020304050',
        'name' => 'Los Olivos',
        'business_name' => 'Los Olivos SRL',
    ]);

    $branch = Branch::create([
        'company_id' => $company->id,
        'name' => 'Sucursal Central',
        'address' => 'Av. Principal 100',
        'is_active' => true,
        'is_main' => true,
    ]);

    $warehouse = Warehouse::create([
        'branch_id' => $branch->id,
        'name' => 'Almacén Central',
        'address' => 'Planta Baja',
        'is_active' => true,
    ]);

    $unitUnd = UnitOfMeasure::create([
        'name' => 'Unidad',
        'abbreviation' => 'UND',
        'is_active' => true,
    ]);

    $unitKg = UnitOfMeasure::create([
        'name' => 'Kilogramo',
        'abbreviation' => 'KG',
        'is_active' => true,
    ]);

    $butterSalted = Product::create([
        'unit_of_measure_id' => $unitUnd->id,
        'code' => 'MANT-SAL',
        'type' => 'materia_prima',
        'name' => 'Mantequilla con sal',
        'price' => '18.0000',
        'is_active' => true,
    ]);

    $butterUnsalted = Product::create([
        'unit_of_measure_id' => $unitUnd->id,
        'code' => 'MANT-SINSAL',
        'type' => 'materia_prima',
        'name' => 'Mantequilla sin sal',
        'price' => '21.5000',
        'is_active' => true,
    ]);

    $oil = Product::create([
        'unit_of_measure_id' => $unitUnd->id,
        'code' => 'ACEITE-01',
        'type' => 'materia_prima',
        'name' => 'Aceite vegetal',
        'price' => '15.0000',
        'is_active' => true,
    ]);

    $sugarKg = Product::create([
        'unit_of_measure_id' => $unitKg->id,
        'code' => 'AZUCAR-KG',
        'type' => 'materia_prima',
        'name' => 'Azúcar blanca',
        'price' => '8.0000',
        'is_active' => true,
    ]);

    $provider = Provider::create([
        'name' => 'Distribuidora Lácteos SRL',
        'document_number' => '1020304050',
        'is_active' => true,
    ]);

    $consumer = User::factory()->create([
        'branch_id' => $branch->id,
        'area' => 'Pastelería',
        'is_active' => true,
    ]);
    $consumer->assignRole($consumerRole);

    $warehouseUser = User::factory()->create([
        'branch_id' => $branch->id,
        'area' => 'Almacén',
        'is_active' => true,
    ]);
    $warehouseUser->assignRole($warehouseRole);

    $admin = User::factory()->create([
        'branch_id' => $branch->id,
        'is_active' => true,
    ]);
    $admin->assignRole($adminRole);

    $request = ConsumptionRequest::create([
        'number' => 1,
        'warehouse_id' => $warehouse->id,
        'user_id' => $consumer->id,
        'requested_by' => 'Pastelería',
        'date' => now()->toDateString(),
        'status' => 'aprobado',
        'approved_by_user_id' => $admin->id,
        'approved_at' => now(),
    ]);

    $butterDetail = ConsumptionRequestDetail::create([
        'consumption_request_id' => $request->id,
        'product_id' => $butterSalted->id,
        'quantity_requested' => '2.0000',
        'quantity_delivered' => '0.0000',
        'fulfillment_status' => ConsumptionDetailFulfillmentEnum::Normal,
    ]);

    $oilDetail = ConsumptionRequestDetail::create([
        'consumption_request_id' => $request->id,
        'product_id' => $oil->id,
        'quantity_requested' => '1.0000',
        'quantity_delivered' => '0.0000',
        'fulfillment_status' => ConsumptionDetailFulfillmentEnum::Normal,
    ]);

    return compact(
        'branch',
        'warehouse',
        'unitUnd',
        'unitKg',
        'butterSalted',
        'butterUnsalted',
        'oil',
        'sugarKg',
        'provider',
        'consumer',
        'warehouseUser',
        'admin',
        'request',
        'butterDetail',
        'oilDetail'
    );
}

it('ejecuta flujo completo preparado en almacén + producto sin stock + compra sin duplicados + ciclo pending_stock a normal', function (): void {
    $ctx = buildConsumptionScenario();

    // Hay stock de mantequilla con sal (5) y aceite (1), pero 0 de mantequilla sin sal
    Stock::create([
        'warehouse_id' => $ctx['warehouse']->id,
        'product_id' => $ctx['butterSalted']->id,
        'quantity' => '5.0000',
        'average_cost' => '18.0000',
    ]);
    Stock::create([
        'warehouse_id' => $ctx['warehouse']->id,
        'product_id' => $ctx['oil']->id,
        'quantity' => '1.0000',
        'average_cost' => '15.0000',
    ]);

    $dispatchService = app(ConsumptionRequestDispatchService::class);
    $modService = app(ConsumptionRequestModificationService::class);

    // 1. Almacén prepara inicialmente las 2 mantequillas con sal y 1 aceite
    $this->actingAs($ctx['warehouseUser']);
    $dispatchService->dispatch($ctx['request']);

    expect($ctx['request']->fresh()->status)->toBe('despachado')
        ->and((float) $ctx['butterDetail']->fresh()->quantity_delivered)->toBe(2.0);

    // 2. El solicitante detecta el error antes de salir; almacenero solicita modificación (2 preparadas en almacén, 0 entregadas)
    $mod = $modService->request(
        consumptionRequest: $ctx['request']->fresh(),
        detail: $ctx['butterDetail']->fresh(),
        toProductId: $ctx['butterUnsalted']->id,
        requestedQuantity: 3, // cambio de producto y de cantidad (2 -> 3)
        reason: 'Producto seleccionado incorrectamente por pastelería',
        requestedByUserId: $ctx['warehouseUser']->id,
        quantityPrepared: 2,
        quantityPhysicallyDelivered: 0
    );

    expect($mod->status)->toBe(ConsumptionModificationStatusEnum::Pending)
        ->and($ctx['butterDetail']->fresh()->product_id)->toBe($ctx['butterSalted']->id)
        ->and((float) $ctx['butterDetail']->fresh()->quantity_requested)->toBe(2.0);

    // 3. Administrador aprueba independientemente de que mantequilla sin sal tenga stock 0
    $resolved = $modService->approve(
        modification: $mod,
        resolvedByUserId: $ctx['admin']->id,
        resolutionNotes: 'Cambio autorizado'
    );

    $freshButter = $ctx['butterDetail']->fresh();
    $freshOil = $ctx['oilDetail']->fresh();
    $freshRequest = $ctx['request']->fresh();

    expect($resolved->status)->toBe(ConsumptionModificationStatusEnum::Applied)
        ->and($freshButter->product_id)->toBe($ctx['butterUnsalted']->id)
        ->and((float) $freshButter->quantity_requested)->toBe(3.0)
        ->and((float) $freshButter->quantity_delivered)->toBe(0.0)
        ->and($freshButter->fulfillment_status)->toBe(ConsumptionDetailFulfillmentEnum::Normal)
        // Aceite no afectado
        ->and((float) $freshOil->quantity_delivered)->toBe(1.0)
        // Como aceite tiene 1 entregada, la solicitud pasa a despachado_parcial
        ->and($freshRequest->status)->toBe('despachado_parcial')
        // Cero movimientos de Kardex
        ->and(Kardex::count())->toBe(0);

    // 4. Almacén despacha nuevamente: mantequilla sin sal (stock 0) queda en pending_stock y genera compra enlazada por 3
    $dispatchService->dispatch($freshRequest);

    $freshButter = $ctx['butterDetail']->fresh();
    expect($freshButter->fulfillment_status)->toBe(ConsumptionDetailFulfillmentEnum::PendingStock)
        ->and(PurchaseOrder::withoutGlobalScopes()->count())->toBe(1);

    $po = PurchaseOrder::withoutGlobalScopes()->with('details')->first();
    expect($po->consumption_request_id)->toBe($ctx['request']->id)
        ->and($po->details)->toHaveCount(1)
        ->and($po->details->first()->consumption_request_detail_id)->toBe($freshButter->id)
        ->and((float) $po->details->first()->quantity)->toBe(3.0);

    // 5. Un segundo despacho consecutivo sin cambio de faltante NO duplica la orden de compra
    $dispatchService->dispatch($ctx['request']->fresh());
    expect(PurchaseOrder::withoutGlobalScopes()->count())->toBe(1);

    // 6. Ingresa stock de mantequilla sin sal y almacén despacha: pending_stock vuelve a normal y solicitud a despachado
    Stock::create([
        'warehouse_id' => $ctx['warehouse']->id,
        'product_id' => $ctx['butterUnsalted']->id,
        'quantity' => '5.0000',
        'average_cost' => '21.5000',
    ]);

    $dispatchService->dispatch($ctx['request']->fresh());

    $freshButter = $ctx['butterDetail']->fresh();
    expect((float) $freshButter->quantity_delivered)->toBe(3.0)
        ->and($freshButter->fulfillment_status)->toBe(ConsumptionDetailFulfillmentEnum::Normal)
        ->and($ctx['request']->fresh()->status)->toBe('despachado')
        ->and(Kardex::count())->toBe(0);
});

it('mantiene la solicitud en aprobado cuando el despacho encuentra stock 0 en todos los ítems', function (): void {
    $ctx = buildConsumptionScenario();
    $this->actingAs($ctx['warehouseUser']);

    $updated = app(ConsumptionRequestDispatchService::class)->dispatch($ctx['request']);

    expect($updated->status)->toBe('aprobado')
        ->and($ctx['butterDetail']->fresh()->fulfillment_status)->toBe(ConsumptionDetailFulfillmentEnum::PendingStock)
        ->and($ctx['oilDetail']->fresh()->fulfillment_status)->toBe(ConsumptionDetailFulfillmentEnum::PendingStock)
        ->and(PurchaseOrder::withoutGlobalScopes()->count())->toBe(1);
});

it('maneja entrega física mixta, devoluciones parciales bloqueantes, rechazo de exceso y aplicación al completar devolución', function (): void {
    $ctx = buildConsumptionScenario();
    $ctx['butterDetail']->update([
        'quantity_requested' => '3.0000',
        'quantity_delivered' => '3.0000',
    ]);
    $ctx['request']->update(['status' => 'despachado_parcial']);

    $modService = app(ConsumptionRequestModificationService::class);
    $dispatchService = app(ConsumptionRequestDispatchService::class);
    $consumptionService = app(ConsumptionRequestService::class);

    // Situación mixta: 1 preparada en almacén y 2 entregadas físicamente (1 + 2 = 3)
    $mod = $modService->request(
        consumptionRequest: $ctx['request'],
        detail: $ctx['butterDetail'],
        toProductId: $ctx['butterUnsalted']->id,
        requestedQuantity: 3,
        reason: 'Cambio solicitado tras entrega parcial en mesa',
        requestedByUserId: $ctx['warehouseUser']->id,
        quantityPrepared: 1,
        quantityPhysicallyDelivered: 2
    );

    // Mientras la modificación está abierta, recepcionar y cancelar la solicitud son rechazados
    expect(fn () => $consumptionService->receiveRequest($ctx['request']->fresh()))
        ->toThrow(DomainException::class)
        ->and(fn () => $consumptionService->cancelRequest($ctx['request']->fresh(), 'Intento cancelar'))
        ->toThrow(DomainException::class);

    // Admin aprueba: como hay 2 entregadas físicamente, queda aprobada y detalle bloqueado en pending_return
    $mod = $modService->approve($mod, $ctx['admin']->id);

    $detailAfterApprove = $ctx['butterDetail']->fresh();
    expect($mod->status)->toBe(ConsumptionModificationStatusEnum::Approved)
        ->and($detailAfterApprove->product_id)->toBe($ctx['butterSalted']->id)
        ->and($detailAfterApprove->fulfillment_status)->toBe(ConsumptionDetailFulfillmentEnum::PendingReturn)
        // La unidad que solo estaba preparada en almacén se canceló, quedando 2 entregadas físicamente
        ->and((float) $detailAfterApprove->quantity_delivered)->toBe(2.0);

    // Intentar despachar ese detalle antes de completar la devolución falla
    expect(fn () => $dispatchService->dispatch($ctx['request']->fresh(), [$detailAfterApprove->id => 1]))
        ->toThrow(DomainException::class);

    // Devolución parcial de 1 unidad: registra fila pero mantiene bloqueo y producto original
    $ret1 = $modService->confirmReturn($mod, 1, $ctx['warehouseUser']->id, 'Devuelve primera barra');
    expect((float) $ret1->returned_quantity)->toBe(1.0)
        ->and($mod->fresh()->status)->toBe(ConsumptionModificationStatusEnum::Approved)
        ->and($ctx['butterDetail']->fresh()->fulfillment_status)->toBe(ConsumptionDetailFulfillmentEnum::PendingReturn)
        ->and($ctx['butterDetail']->fresh()->product_id)->toBe($ctx['butterSalted']->id);

    // Intentar devolver 2 más (excede saldo pendiente de 1) es rechazado
    expect(fn () => $modService->confirmReturn($mod->fresh(), 2, $ctx['warehouseUser']->id))
        ->toThrow(DomainException::class);

    // Segunda devolución de 1 unidad completa las 2 entregadas: aplica sustitución y levanta bloqueo
    $modService->confirmReturn($mod->fresh(), 1, $ctx['warehouseUser']->id, 'Devuelve segunda barra');

    $finalDetail = $ctx['butterDetail']->fresh();
    expect($mod->fresh()->status)->toBe(ConsumptionModificationStatusEnum::Applied)
        ->and($finalDetail->product_id)->toBe($ctx['butterUnsalted']->id)
        ->and((float) $finalDetail->quantity_delivered)->toBe(0.0)
        ->and($finalDetail->fulfillment_status)->toBe(ConsumptionDetailFulfillmentEnum::Normal)
        ->and($ctx['request']->fresh()->status)->toBe('aprobado')
        ->and(Kardex::count())->toBe(0);
});

it('permite cancelación administrativa tras devolución parcial ajustando quantity_delivered', function (): void {
    $ctx = buildConsumptionScenario();
    $ctx['butterDetail']->update(['quantity_delivered' => '2.0000']);
    $ctx['request']->update(['status' => 'despachado_parcial']);

    $modService = app(ConsumptionRequestModificationService::class);

    $mod = $modService->request(
        consumptionRequest: $ctx['request'],
        detail: $ctx['butterDetail'],
        toProductId: $ctx['butterUnsalted']->id,
        requestedQuantity: 2,
        reason: 'Equivocación de insumo',
        requestedByUserId: $ctx['warehouseUser']->id,
        quantityPrepared: 0,
        quantityPhysicallyDelivered: 2
    );

    $mod = $modService->approve($mod, $ctx['admin']->id);
    $modService->confirmReturn($mod, 1, $ctx['warehouseUser']->id, 'Solo devolvió 1 unidad');

    // El solicitante ya usó la otra unidad; el admin cancela la modificación
    $cancelled = $modService->cancel($mod->fresh(), $ctx['admin']->id, 'La segunda barra ya fue utilizada en producción');

    $detail = $ctx['butterDetail']->fresh();
    expect($cancelled->status)->toBe(ConsumptionModificationStatusEnum::Cancelled)
        ->and($detail->product_id)->toBe($ctx['butterSalted']->id)
        ->and($detail->fulfillment_status)->toBe(ConsumptionDetailFulfillmentEnum::Normal)
        ->and((float) $detail->quantity_delivered)->toBe(1.0)
        ->and($ctx['request']->fresh()->status)->toBe('despachado_parcial');
});

it('resuelve compras por línea sin cancelar otras líneas de la orden e ignora líneas canceladas al convertir a compra', function (): void {
    $ctx = buildConsumptionScenario();
    $poService = app(PurchaseOrderService::class);
    $modService = app(ConsumptionRequestModificationService::class);

    // Orden aprobada con 2 líneas: mantequilla con sal (ligada al detalle) y aceite (ligado al detalle de aceite)
    $po = $poService->create([
        'provider_id' => $ctx['provider']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'user_id' => $ctx['warehouseUser']->id,
        'consumption_request_id' => $ctx['request']->id,
        'date' => now()->toDateString(),
        'notes' => 'Compra combinada',
        'total' => 51.00,
    ], [
        [
            'product_id' => $ctx['butterSalted']->id,
            'consumption_request_detail_id' => $ctx['butterDetail']->id,
            'quantity' => 2,
            'unit_price' => 18.00,
            'subtotal' => 36.00,
        ],
        [
            'product_id' => $ctx['oil']->id,
            'consumption_request_detail_id' => $ctx['oilDetail']->id,
            'quantity' => 1,
            'unit_price' => 15.00,
            'subtotal' => 15.00,
        ],
    ]);
    $poService->changeStatus($po, 'aprobada');

    $butterPoLine = $po->details()->where('product_id', $ctx['butterSalted']->id)->firstOrFail();
    $oilPoLine = $po->details()->where('product_id', $ctx['oil']->id)->firstOrFail();

    $mod = $modService->request(
        consumptionRequest: $ctx['request'],
        detail: $ctx['butterDetail'],
        toProductId: $ctx['butterUnsalted']->id,
        requestedQuantity: 2,
        reason: 'Se requiere sin sal',
        requestedByUserId: $ctx['warehouseUser']->id
    );

    // Intentar aprobar sin resolver la línea abierta es rechazado
    expect(fn () => $modService->approve($mod, $ctx['admin']->id, 'Intento sin resolver', []))
        ->toThrow(DomainException::class);

    // Intentar mantener sin nota obligatoria es rechazado
    expect(fn () => $modService->approve($mod, $ctx['admin']->id, null, [
        ['purchase_order_detail_id' => $butterPoLine->id, 'decision' => 'mantener', 'notes' => ''],
    ]))->toThrow(DomainException::class);

    // Aprobar cancelando solo la línea de mantequilla con sal
    $modService->approve($mod, $ctx['admin']->id, 'Se cancela solo mantequilla', [
        [
            'purchase_order_detail_id' => $butterPoLine->id,
            'decision' => 'cancelar',
            'notes' => 'Proveedor aún no despachó mantequilla',
        ],
    ]);

    expect($butterPoLine->fresh()->isCancelled())->toBeTrue()
        ->and($oilPoLine->fresh()->isCancelled())->toBeFalse()
        ->and($po->fresh()->status)->toBe('aprobada')
        ->and((float) $po->fresh()->total)->toBe(15.0);

    // Convertir la orden en compra real solo ingresa el aceite (ignora mantequilla cancelada)
    $purchase = $poService->convertToPurchase(
        purchaseOrderId: $po->id,
        userId: $ctx['warehouseUser']->id,
        voucherType: 'factura',
        paymentType: 'efectivo'
    );

    expect($purchase->details)->toHaveCount(1)
        ->and($purchase->details->first()->product_id)->toBe($ctx['oil']->id)
        ->and((float) $purchase->total)->toBe(15.0)
        ->and($po->fresh()->status)->toBe('completada');
});

it('pasa la orden de compra a cancelada al cancelar su única línea activa y genera nueva compra si se mantuvo la del producto original', function (): void {
    $ctx = buildConsumptionScenario();
    $poService = app(PurchaseOrderService::class);
    $modService = app(ConsumptionRequestModificationService::class);
    $dispatchService = app(ConsumptionRequestDispatchService::class);

    // Caso 1: única línea cancelada -> orden cancelada
    $singlePo = $poService->create([
        'provider_id' => $ctx['provider']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'user_id' => $ctx['warehouseUser']->id,
        'consumption_request_id' => $ctx['request']->id,
        'date' => now()->toDateString(),
        'total' => 36.00,
    ], [[
        'product_id' => $ctx['butterSalted']->id,
        'consumption_request_detail_id' => $ctx['butterDetail']->id,
        'quantity' => 2,
        'unit_price' => 18.00,
        'subtotal' => 36.00,
    ]]);
    $singleLine = $singlePo->details()->firstOrFail();
    $poService->cancelDetailLine($singleLine, 'Cancelación única línea');

    expect($singlePo->fresh()->status)->toBe('cancelada');

    // Caso 2: línea mantenida del producto original no bloquea compra del nuevo producto
    $keptPo = $poService->create([
        'provider_id' => $ctx['provider']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'user_id' => $ctx['warehouseUser']->id,
        'consumption_request_id' => $ctx['request']->id,
        'date' => now()->toDateString(),
        'total' => 36.00,
    ], [[
        'product_id' => $ctx['butterSalted']->id,
        'consumption_request_detail_id' => $ctx['butterDetail']->id,
        'quantity' => 2,
        'unit_price' => 18.00,
        'subtotal' => 36.00,
    ]]);
    $keptLine = $keptPo->details()->firstOrFail();

    $mod = $modService->request(
        consumptionRequest: $ctx['request'],
        detail: $ctx['butterDetail'],
        toProductId: $ctx['butterUnsalted']->id,
        requestedQuantity: 2,
        reason: 'Cambio a sin sal',
        requestedByUserId: $ctx['warehouseUser']->id
    );

    $modService->approve($mod, $ctx['admin']->id, 'Mantener para stock general', [[
        'purchase_order_detail_id' => $keptLine->id,
        'decision' => 'mantener',
        'notes' => 'Se usará en panadería mañana',
    ]]);

    expect($keptLine->fresh()->product_id)->toBe($ctx['butterSalted']->id)
        ->and($keptLine->fresh()->isCancelled())->toBeFalse();

    // Despachar ahora genera compra para mantequilla sin sal porque la línea mantenida es de mantequilla con sal
    $this->actingAs($ctx['warehouseUser']);
    $dispatchService->dispatch($ctx['request']->fresh());

    $unsaltedPoLine = PurchaseOrderDetail::query()
        ->where('consumption_request_detail_id', $ctx['butterDetail']->id)
        ->where('product_id', $ctx['butterUnsalted']->id)
        ->whereNull('cancelled_at')
        ->first();

    expect($unsaltedPoLine)->not->toBeNull()
        ->and((float) $unsaltedPoLine->quantity)->toBe(2.0);
});

it('garantiza unicidad de modificación abierta, inmutabilidad en PostgreSQL, integridad histórica, permisos HTTP y eventos solo tras commit', function (): void {
    $ctx = buildConsumptionScenario();
    $modService = app(ConsumptionRequestModificationService::class);

    // 1. Validación de distinta unidad de medida rechazada
    expect(fn () => $modService->request(
        consumptionRequest: $ctx['request'],
        detail: $ctx['butterDetail'],
        toProductId: $ctx['sugarKg']->id,
        requestedQuantity: 2,
        reason: 'Unidad distinta',
        requestedByUserId: $ctx['warehouseUser']->id
    ))->toThrow(DomainException::class);

    // 2. Si la transacción hace rollback, NO se despacha el evento ShouldDispatchAfterCommit
    Event::fake([
        ConsumptionRequestModificationRequested::class,
        ConsumptionRequestModificationResolved::class,
        ConsumptionRequestModificationReturnRecorded::class,
        ConsumptionRequestModificationApplied::class,
    ]);

    try {
        DB::transaction(function () use ($modService, $ctx): void {
            $modService->request(
                consumptionRequest: $ctx['request'],
                detail: $ctx['butterDetail'],
                toProductId: $ctx['butterUnsalted']->id,
                requestedQuantity: 2,
                reason: 'Prueba rollback',
                requestedByUserId: $ctx['warehouseUser']->id
            );
            throw new RuntimeException('Fallo simulado antes de commit');
        });
    } catch (RuntimeException) {
        // esperado
    }

    Event::assertNotDispatched(ConsumptionRequestModificationRequested::class);
    expect(ConsumptionRequestModification::count())->toBe(0);

    // 3. Al confirmar transacción, sí se despacha el evento y una segunda abierta en el mismo detalle es rechazada
    $mod = $modService->request(
        consumptionRequest: $ctx['request'],
        detail: $ctx['butterDetail'],
        toProductId: $ctx['butterUnsalted']->id,
        requestedQuantity: 2,
        reason: 'Solicitud válida',
        requestedByUserId: $ctx['warehouseUser']->id
    );

    Event::assertDispatched(ConsumptionRequestModificationRequested::class);

    // Segunda solicitud abierta sobre el mismo detalle -> rechazada
    expect(fn () => $modService->request(
        consumptionRequest: $ctx['request'],
        detail: $ctx['butterDetail'],
        toProductId: $ctx['butterUnsalted']->id,
        requestedQuantity: 4,
        reason: 'Duplicada',
        requestedByUserId: $ctx['warehouseUser']->id
    ))->toThrow(DomainException::class);

    // Pero sobre OTRO detalle de la misma solicitud -> permitida
    $otherMod = $modService->request(
        consumptionRequest: $ctx['request'],
        detail: $ctx['oilDetail'],
        toProductId: $ctx['butterUnsalted']->id,
        requestedQuantity: 1,
        reason: 'Cambio en otro detalle',
        requestedByUserId: $ctx['warehouseUser']->id
    );
    expect($otherMod->status)->toBe(ConsumptionModificationStatusEnum::Pending);

    // 4. Permiso HTTP: almacenero no puede aprobar ni rechazar (403)
    $this->actingAs($ctx['warehouseUser'])
        ->post(route('admin.consumption-requests.modifications.approve', [
            'consumption_request' => $ctx['request']->id,
            'modification' => $mod->id,
        ]))
        ->assertForbidden();

    // 5. Doble resolución: la primera aplica, la segunda es rechazada porque ya no está pendiente
    $applied = $modService->approve($mod, $ctx['admin']->id, 'Aprobación primaria');
    expect($applied->status)->toBe(ConsumptionModificationStatusEnum::Applied);

    expect(fn () => $modService->reject($mod->fresh(), $ctx['admin']->id, 'Intento tardío'))
        ->toThrow(DomainException::class);

    // 6. Inmutabilidad estricta en modelo Y en trigger PostgreSQL directo (con savepoint para no abortar la transacción de test en Postgres)
    expect(fn () => $applied->update(['reason' => 'Alteración prohibida']))
        ->toThrow(DomainException::class);

    expect(fn () => DB::transaction(fn () => DB::table('consumption_request_modifications')
        ->where('id', $applied->id)
        ->update(['status' => 'pendiente'])))
        ->toThrow(QueryException::class);

    expect(fn () => DB::transaction(fn () => DB::table('consumption_request_modifications')
        ->where('id', $applied->id)
        ->delete()))
        ->toThrow(QueryException::class);

    // 7. Integridad histórica: impedir eliminar físicamente un detalle con modificaciones o compras asociadas (modelo y FK restrictOnDelete)
    expect(fn () => $ctx['butterDetail']->fresh()->delete())
        ->toThrow(DomainException::class);

    expect(fn () => DB::transaction(fn () => DB::table('consumption_request_details')
        ->where('id', $ctx['butterDetail']->id)
        ->delete()))
        ->toThrow(QueryException::class);
});

it('opera correctamente los endpoints HTTP de bandeja administrativa, solicitud, aprobación y devolución', function (): void {
    $ctx = buildConsumptionScenario();
    $ctx['butterDetail']->update(['quantity_delivered' => '2.0000']);
    $ctx['request']->update(['status' => 'despachado_parcial']);

    // 1. Almacenero envía solicitud de modificación por HTTP
    $this->actingAs($ctx['warehouseUser'])
        ->post(route('admin.consumption-requests.modifications.store', [
            'consumption_request' => $ctx['request']->id,
            'detail' => $ctx['butterDetail']->id,
        ]), [
            'to_product_id' => $ctx['butterUnsalted']->id,
            'requested_quantity' => 2,
            'quantity_prepared' => 1,
            'quantity_physically_delivered' => 1,
            'reason' => 'Solicitado por HTTP desde vista Show',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $mod = ConsumptionRequestModification::firstOrFail();

    // 2. Administrador consulta la bandeja de modificaciones
    $this->actingAs($ctx['admin'])
        ->get(route('admin.consumption-modifications.index'))
        ->assertOk();

    // 3. Administrador aprueba por HTTP
    $this->actingAs($ctx['admin'])
        ->post(route('admin.consumption-requests.modifications.approve', [
            'consumption_request' => $ctx['request']->id,
            'modification' => $mod->id,
        ]), [
            'resolution_notes' => 'Autorizado desde bandeja',
            'purchase_resolutions' => [],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($mod->fresh()->status)->toBe(ConsumptionModificationStatusEnum::Approved);

    // 4. Almacenero registra devolución física por HTTP completando la unidad entregada
    $this->actingAs($ctx['warehouseUser'])
        ->post(route('admin.consumption-requests.modifications.returns.store', [
            'consumption_request' => $ctx['request']->id,
            'modification' => $mod->id,
        ]), [
            'returned_quantity' => 1,
            'return_notes' => 'Unidad recibida conforme en almacén',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($mod->fresh()->status)->toBe(ConsumptionModificationStatusEnum::Applied)
        ->and($ctx['butterDetail']->fresh()->product_id)->toBe($ctx['butterUnsalted']->id);
});
