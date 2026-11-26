<?php

declare(strict_types=1);

use App\Enums\ConsumptionDetailFulfillmentEnum;
use App\Enums\ConsumptionModificationStatusEnum;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Models\ConsumptionRequestModification;
use App\Models\Product;
use App\Models\Stock;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\Warehouse;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Ejecuta dos procesos PHP concurrentes reales contra PostgreSQL (olivosdb_testing).
 *
 * @param array<string, mixed> $payloadA
 * @param array<string, mixed> $payloadB
 * @return array<int, array<string, mixed>>
 */
function runConcurrentWorkers(array $payloadA, array $payloadB): array
{
    $workerPath = base_path('tests/Support/concurrent_worker.php');

    $procA = new Process([PHP_BINARY, $workerPath, json_encode($payloadA, JSON_THROW_ON_ERROR)]);
    $procB = new Process([PHP_BINARY, $workerPath, json_encode($payloadB, JSON_THROW_ON_ERROR)]);

    $procA->start();
    $procB->start();

    $procA->wait();
    $procB->wait();

    return [
        json_decode(trim($procA->getOutput()), true, 512, JSON_THROW_ON_ERROR),
        json_decode(trim($procB->getOutput()), true, 512, JSON_THROW_ON_ERROR),
    ];
}

it('serializa solicitudes simultáneas, resoluciones concurrentes y aprobación vs despacho sin deadlocks en PostgreSQL', function (): void {
    $suffix = uniqid();
    $company = Company::create([
        'nit' => '99' . substr($suffix, -8),
        'name' => 'Concurrent Test ' . $suffix,
        'business_name' => 'Concurrent Test SRL',
    ]);
    $branch = Branch::create([
        'company_id' => $company->id,
        'name' => 'Sucursal Concurrencia',
        'address' => 'Calle 1',
        'is_active' => true,
        'is_main' => false,
    ]);
    $warehouse = Warehouse::create([
        'branch_id' => $branch->id,
        'name' => 'Almacén Concurrencia',
        'address' => 'Zona Sur',
        'is_active' => true,
    ]);
    $unit = UnitOfMeasure::create([
        'name' => 'Unidad ' . $suffix,
        'abbreviation' => 'U',
        'is_active' => true,
    ]);
    $prodA = Product::create([
        'unit_of_measure_id' => $unit->id,
        'code' => 'CA-' . $suffix,
        'type' => 'materia_prima',
        'name' => 'Mantequilla Salada Conc',
        'price' => '10.0000',
        'is_active' => true,
    ]);
    $prodB = Product::create([
        'unit_of_measure_id' => $unit->id,
        'code' => 'CB-' . $suffix,
        'type' => 'materia_prima',
        'name' => 'Mantequilla Sin Sal Conc',
        'price' => '12.0000',
        'is_active' => true,
    ]);
    $prodC = Product::create([
        'unit_of_measure_id' => $unit->id,
        'code' => 'CC-' . $suffix,
        'type' => 'materia_prima',
        'name' => 'Aceite Conc',
        'price' => '15.0000',
        'is_active' => true,
    ]);
    Stock::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $prodC->id,
        'quantity' => '10.0000',
        'average_cost' => '15.0000',
    ]);

    $warehouseUser = User::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
    $adminA = User::factory()->create(['branch_id' => $branch->id, 'is_super_admin' => true, 'is_active' => true]);
    $adminB = User::factory()->create(['branch_id' => $branch->id, 'is_super_admin' => true, 'is_active' => true]);

    $request = ConsumptionRequest::create([
        'number' => random_int(10000, 99999),
        'warehouse_id' => $warehouse->id,
        'user_id' => $warehouseUser->id,
        'requested_by' => 'Cocina',
        'date' => now()->toDateString(),
        'status' => 'aprobado',
        'approved_by_user_id' => $adminA->id,
        'approved_at' => now(),
    ]);

    $detailButter = ConsumptionRequestDetail::create([
        'consumption_request_id' => $request->id,
        'product_id' => $prodA->id,
        'quantity_requested' => '2.0000',
        'quantity_delivered' => '0.0000',
        'fulfillment_status' => ConsumptionDetailFulfillmentEnum::Normal,
    ]);

    $detailOil = ConsumptionRequestDetail::create([
        'consumption_request_id' => $request->id,
        'product_id' => $prodC->id,
        'quantity_requested' => '2.0000',
        'quantity_delivered' => '0.0000',
        'fulfillment_status' => ConsumptionDetailFulfillmentEnum::Normal,
    ]);

    try {
        // 1. Dos solicitudes simultáneas sobre el mismo detalle -> solo 1 prospera, 0 deadlocks
        [$res1, $res2] = runConcurrentWorkers(
            [
                'mode' => 'request',
                'request_id' => $request->id,
                'detail_id' => $detailButter->id,
                'to_product_id' => $prodB->id,
                'qty' => 2,
                'reason' => 'Solicitud simultánea A',
                'user_id' => $warehouseUser->id,
            ],
            [
                'mode' => 'request',
                'request_id' => $request->id,
                'detail_id' => $detailButter->id,
                'to_product_id' => $prodB->id,
                'qty' => 3,
                'reason' => 'Solicitud simultánea B',
                'user_id' => $warehouseUser->id,
            ]
        );

        $successCount = ($res1['ok'] ? 1 : 0) + ($res2['ok'] ? 1 : 0);
        expect($successCount)->toBe(1)
            ->and(ConsumptionRequestModification::where('consumption_request_detail_id', $detailButter->id)->open()->count())->toBe(1);

        $openMod = ConsumptionRequestModification::where('consumption_request_detail_id', $detailButter->id)->open()->firstOrFail();

        // 2. Dos administradores resolviendo simultáneamente (uno aprueba, otro rechaza) -> solo 1 prospera
        [$admRes1, $admRes2] = runConcurrentWorkers(
            [
                'mode' => 'approve',
                'modification_id' => $openMod->id,
                'user_id' => $adminA->id,
            ],
            [
                'mode' => 'reject',
                'modification_id' => $openMod->id,
                'user_id' => $adminB->id,
            ]
        );

        $admSuccessCount = ($admRes1['ok'] ? 1 : 0) + ($admRes2['ok'] ? 1 : 0);
        expect($admSuccessCount)->toBe(1)
            ->and(in_array($openMod->fresh()->status, [
                ConsumptionModificationStatusEnum::Applied,
                ConsumptionModificationStatusEnum::Rejected,
            ], true))->toBeTrue();

        // 3. Aprobación concurrente con Despacho sobre la misma solicitud -> ambos usan el mismo orden de bloqueo sin deadlock
        $modOil = app(\App\Services\ConsumptionRequestModificationService::class)->request(
            consumptionRequest: $request->fresh(),
            detail: $detailOil->fresh(),
            toProductId: $prodB->id,
            requestedQuantity: 2,
            reason: 'Cambio concurrente con despacho',
            requestedByUserId: $warehouseUser->id
        );

        [$concApprove, $concDispatch] = runConcurrentWorkers(
            [
                'mode' => 'approve',
                'modification_id' => $modOil->id,
                'user_id' => $adminA->id,
            ],
            [
                'mode' => 'dispatch',
                'request_id' => $request->id,
                'user_id' => $warehouseUser->id,
            ]
        );

        expect($concApprove['ok'])->toBeTrue()
            ->and($concDispatch['ok'])->toBeTrue()
            ->and($modOil->fresh()->status)->toBe(ConsumptionModificationStatusEnum::Applied);
    } finally {
        // Limpiar triggers temporalmente en olivosdb_testing solo para borrar datos de este test aislado
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE consumption_request_modifications DISABLE TRIGGER trg_prevent_immutable_crm_changes');
        \Illuminate\Support\Facades\DB::table('consumption_request_modifications')->where('consumption_request_id', $request->id)->delete();
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE consumption_request_modifications ENABLE TRIGGER trg_prevent_immutable_crm_changes');
        \Illuminate\Support\Facades\DB::table('purchase_order_details')->whereIn('consumption_request_detail_id', [$detailButter->id, $detailOil->id])->delete();
        \Illuminate\Support\Facades\DB::table('purchase_orders')->where('consumption_request_id', $request->id)->delete();
        \Illuminate\Support\Facades\DB::table('consumption_request_details')->where('consumption_request_id', $request->id)->delete();
        \Illuminate\Support\Facades\DB::table('consumption_requests')->where('id', $request->id)->delete();
    }
});
