<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Provider;
use App\Models\ConsumptionRequest;
use App\Models\PurchaseOrder;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\Kardex;
use App\Models\UnitOfMeasure;
use App\Models\Branch;
use App\Services\ConsumptionRequestService;
use App\Services\PurchaseOrderService;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Enums\TenantStatus;
use Exception;

class TestBusinessFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-business-flow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Valida de punta a punta el flujo de solicitudes de consumo y compras en dos fases';

    /**
     * Execute the console command.
     */
    public function handle(
        ConsumptionRequestService $consumptionService,
        PurchaseOrderService $purchaseOrderService
    ): int {
        $this->info("==========================================================================");
        $this->info(" Iniciando Validación Integral del Flujo de Consumos y Compras ");
        $this->info("==========================================================================");

        try {
            DB::transaction(function () use ($consumptionService, $purchaseOrderService) {
                // 1. Obtener o crear Compañía (Tenant)
                $company = Company::first();
                if (!$company) {
                    $this->info("Creando Compañía (Tenant) temporal...");
                    $company = Company::create([
                        'nit' => '1234567890',
                        'name' => 'Olivos Test SRL',
                        'business_name' => 'Olivos Test SRL',
                        'phone' => '77777777',
                        'email' => 'test@olivos.com',
                        'status' => TenantStatus::ACTIVE,
                        'slug' => 'olivos-test',
                        'inventory_method' => \App\Enums\InventoryMethod::WEIGHTED_AVERAGE,
                    ]);
                }
                
                $this->info("Compañía Activa: {$company->name} (ID: {$company->id})");

                // 2. Obtener o crear Sucursal (Branch)
                $branch = Branch::where('company_id', $company->id)->first();
                if (!$branch) {
                    $this->info("Creando Sucursal temporal...");
                    $branch = Branch::create([
                        'company_id' => $company->id,
                        'name' => 'Sucursal Principal Test',
                        'address' => 'Av. Busch #123',
                        'is_main' => true,
                        'is_active' => true,
                    ]);
                }
                Session::put('active_branch_id', $branch->id);

                // 3. Obtener o crear Almacén (Warehouse)
                $warehouse = Warehouse::where('branch_id', $branch->id)->first();
                if (!$warehouse) {
                    $this->info("Creando Almacén temporal...");
                    $warehouse = Warehouse::create([
                        'branch_id' => $branch->id,
                        'name' => 'Almacén Cocina Central',
                        'address' => 'Av. Busch #123',
                        'is_active' => true,
                    ]);
                }
                $this->info("Almacén: {$warehouse->name} (ID: {$warehouse->id})");

                // 4. Obtener o crear Proveedor (Provider)
                $provider = Provider::where('is_active', true)->first();
                if (!$provider) {
                    $this->info("Creando Proveedor temporal...");
                    $provider = Provider::create([
                        'nit' => '88888888',
                        'document_number' => '88888888',
                        'name' => 'Distribuidora Alimentos SRL',
                        'is_active' => true,
                        'phone' => '4444444',
                        'email' => 'ventas@alimentos.com',
                        'address' => 'Z. Central',
                    ]);
                }

                // 5. Obtener o crear Unidad de Medida (UnitOfMeasure)
                $unit = UnitOfMeasure::first();
                if (!$unit) {
                    $this->info("Creando Unidad de Medida temporal...");
                    $unit = UnitOfMeasure::create([
                        'name' => 'Kilogramo',
                        'abbreviation' => 'kg',
                        'is_active' => true,
                    ]);
                }

                // 6. Obtener o crear Producto de prueba y asegurar stock inicial en CERO
                $product = Product::where('name', 'Harina 3 Ceros Test')->first();
                if (!$product) {
                    $this->info("Creando Producto temporal...");
                    $product = Product::create([
                        'code' => 'PROD-HAR-001',
                        'name' => 'Harina 3 Ceros Test',
                        'price' => '10.5000',
                        'min_stock' => '5.0000',
                        'is_active' => true,
                        'unit_of_measure_id' => $unit->id,
                        'units_per_package' => '1.0000',
                    ]);
                }

                // Forzar Stock en 0.00 en este almacén
                $stock = Stock::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                    ],
                    [
                        'quantity' => '0.0000',
                        'inventory_value' => '0.0000',
                        'average_cost' => '10.5000',
                    ]
                );
                $this->info("Producto: {$product->name} (Stock Inicial: {$stock->quantity})");

                // 7. Obtener o crear Usuarios (Admin y Cocina)
                $adminUser = User::where('email', 'admin@olivos.com')->first();
                if (!$adminUser) {
                    $adminUser = User::first() ?? User::create([
                        'name' => 'Administrador Olivos',
                        'email' => 'admin@olivos.com',
                        'password' => bcrypt('admin123'),
                    ]);
                }
                
                $cocinaUser = User::where('email', 'cocina@olivos.com')->first();
                if (!$cocinaUser) {
                    $cocinaUser = User::create([
                        'name' => 'Cocinero Jefe',
                        'email' => 'cocina@olivos.com',
                        'password' => bcrypt('cocina123'),
                    ]);
                }

                // Autenticar al usuario Cocina
                Auth::login($cocinaUser);
                $this->info("Autenticado como: {$cocinaUser->name} (Rol: Cocina)");

                // 8. Crear una Solicitud de Consumo de Cocina
                $this->info("-> Paso 1: Creando Solicitud de Consumo de Cocina...");
                $requestData = [
                    'warehouse_id' => $warehouse->id,
                    'requested_by' => 'Cocina',
                    'date' => now()->toDateString(),
                    'notes' => 'Necesitamos harina de forma urgente para panadería',
                ];
                $items = [
                    [
                        'id' => $product->id,
                        'quantity' => 10.0,
                    ]
                ];

                $consumptionRequest = $consumptionService->createRequest($requestData, $items);
                $this->info("✓ Solicitud de Consumo Creada. ID: {$consumptionRequest->id}, Estado: '{$consumptionRequest->status}'");
                if ($consumptionRequest->status !== 'pendiente') {
                    throw new Exception("Error: La solicitud de consumo debería estar en estado 'pendiente'.");
                }

                // Simular aprobación del administrador antes de despachar
                $this->info("-> Paso 1.5: Aprobando Solicitud de Consumo como Administrador...");
                $consumptionRequest = $consumptionService->approveRequest($consumptionRequest, (string)$adminUser->id);
                $this->info("✓ Solicitud de Consumo Aprobada. Estado: '{$consumptionRequest->status}'");
                if ($consumptionRequest->status !== 'aprobado') {
                    throw new Exception("Error: La solicitud de consumo debería estar en estado 'aprobado'.");
                }

                // 9. Simular despacho por parte del encargado de almacén
                $this->info("-> Paso 2: Despachando insumos desde Almacén (No hay stock físico)...");
                $updatedRequest = $consumptionService->dispatchRequest($consumptionRequest);
                $this->info("✓ Insumos despachados. Nuevo Estado: '{$updatedRequest->status}'");

                if ($updatedRequest->status !== 'despachado_parcial') {
                    throw new Exception("Error: La solicitud debería haber pasado a estado 'despachado_parcial' debido a la falta de stock.");
                }

                // 10. Validar la autogeneración de la Solicitud de Compra (PurchaseOrder)
                $purchaseOrder = PurchaseOrder::where('warehouse_id', $warehouse->id)
                    ->where('status', 'pendiente')
                    ->latest()
                    ->first();

                if (!$purchaseOrder) {
                    throw new Exception("Error: No se creó la Solicitud de Compra de forma automática.");
                }
                $this->info("✓ Solicitud de Compra Autogenerada de forma Correcta.");
                $this->info("  Orden de Compra ID: {$purchaseOrder->id}");
                $this->info("  Estado Inicial: '{$purchaseOrder->status}'");
                $this->info("  Total Estimado: Bs. {$purchaseOrder->total}");
                $this->info("  Nota: {$purchaseOrder->notes}");

                // 11. Paso 3: Aprobación Administrativa por parte del Admin
                $this->info("-> Paso 3: Aprobación Administrativa de la Orden por parte del Admin...");
                Auth::login($adminUser);
                $purchaseOrderService->changeStatus($purchaseOrder, 'aprobada');
                $purchaseOrder->refresh();

                $this->info("✓ Orden Aprobada por Administración. Estado: '{$purchaseOrder->status}'");
                if ($purchaseOrder->status !== 'aprobada') {
                    throw new Exception("Error: El estado de la orden de compra debería ser 'aprobada'.");
                }

                // 12. Paso 4: Conversión a Compra Real con Comprobante físico
                $this->info("-> Paso 4: Encargado de Almacén registra Compra Real con comprobante físico...");
                Auth::login($cocinaUser); // Rol operativo

                $purchase = $purchaseOrderService->convertToPurchase(
                    purchaseOrderId: $purchaseOrder->id,
                    userId: $cocinaUser->id,
                    voucherType: 'factura',
                    paymentType: 'efectivo',
                    dueDate: null,
                    downPayment: '0.0000',
                    receiptPath: null
                );

                $purchaseOrder->refresh();
                $this->info("✓ Conversión Completa. Estado de la Orden de Compra: '{$purchaseOrder->status}'");
                if ($purchaseOrder->status !== 'completada') {
                    throw new Exception("Error: El estado final de la orden de compra debería ser 'completada'.");
                }

                $this->info("  Compra Física Creada ID: {$purchase->id}");
                $this->info("  Número de Compra: {$purchase->purchase_number}");
                $this->info("  Tipo de Comprobante: {$purchase->voucher_type}");
                $this->info("  Método de Pago: {$purchase->payment_type}");

                // 13. Validar Incremento de Stock y Kardex
                $updatedStock = Stock::where('warehouse_id', $warehouse->id)
                    ->where('product_id', $product->id)
                    ->first();

                $this->info("✓ Verificación de Inventarios:");
                $this->info("  Stock en Almacén Actualizado: " . (float) $updatedStock->quantity . " unidades (Esperado: 10.0)");
                if ((float) $updatedStock->quantity !== 10.0) {
                    throw new Exception("Error: El stock físico del producto no se actualizó correctamente (actual: {$updatedStock->quantity}).");
                }

                $kardexEntry = Kardex::where('product_id', $product->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->latest()
                    ->first();

                $this->info("  Último Movimiento Kardex Registrado: ID: {$kardexEntry->id}");
                $this->info("  Tipo Movimiento: '{$kardexEntry->movement_type->value}'");
                $this->info("  Cantidad Ingresada: {$kardexEntry->quantity}");
                $this->info("  Costo Unitario: Bs. {$kardexEntry->unit_cost}");
                $this->info("  Notas Kardex: {$kardexEntry->notes}");

                if ($kardexEntry->movement_type->value !== 'PURCHASE') {
                    throw new Exception("Error: El tipo de movimiento de Kardex debería ser 'PURCHASE'.");
                }

                // 14. Despachar el saldo restante desde el Almacén ahora que hay stock
                $this->info("-> Paso 5: Despachando saldo pendiente desde Almacén ahora que hay stock...");
                $consumptionRequest->refresh();
                $updatedRequest2 = $consumptionService->dispatchRequest($consumptionRequest);
                $this->info("✓ Insumos despachados completamente. Nuevo Estado: '{$updatedRequest2->status}'");
                if ($updatedRequest2->status !== 'despachado') {
                    throw new Exception("Error: La solicitud debería haber pasado a estado 'despachado'.");
                }

                // 15. Simular la Recepción física con cantidad diferente SIN observaciones (debe fallar)
                $this->info("-> Paso 6: Probando recepción con discrepancia de cantidad SIN observación obligatoria...");
                $detail = $updatedRequest2->details->first();
                
                try {
                    // Queremos recibir 8.0 en lugar de 10.0 (hay diferencia) pero sin observación
                    $consumptionService->receiveRequest($updatedRequest2, [
                        $detail->id => 8.0
                    ], []);
                    throw new Exception("Error: La validación falló, permitió recibir una cantidad diferente sin observación.");
                } catch (Exception $e) {
                    if (str_contains($e->getMessage(), 'Se requiere una observación')) {
                        $this->info("✓ Correcto: El sistema bloqueó la recepción con discrepancia de cantidad sin justificación.");
                    } else {
                        throw $e; // Si es otro error, propagamos
                    }
                }

                // 16. Simular la Recepción física con cantidad diferente CON observaciones (debe tener éxito)
                $this->info("-> Paso 7: Confirmando recepción física con discrepancia de cantidad e ingresando observación...");
                
                // Recibiremos 8.0 en lugar de 10.0, justificando con una observación
                $comment = 'Se recibió una bolsa dañada por humedad de transporte, solo se aceptaron 8kg.';
                $finalRequest = $consumptionService->receiveRequest($updatedRequest2, [
                    $detail->id => 8.0
                ], [
                    $detail->id => $comment
                ]);

                $this->info("✓ Recepción confirmada exitosamente. Nuevo Estado: '{$finalRequest->status}'");
                if ($finalRequest->status !== 'entregado') {
                    throw new Exception("Error: El estado final de la solicitud debería ser 'entregado'.");
                }

                // Cargar detalle guardado para verificar que la observación y cantidad recibida persistan
                $finalDetail = $finalRequest->details()->first();
                $this->info("  Cantidad Solicitada: {$finalDetail->quantity_requested}");
                $this->info("  Cantidad Recibida Guardada: {$finalDetail->quantity_received}");
                $this->info("  Observación Guardada: '{$finalDetail->observation}'");

                if (abs((float)$finalDetail->quantity_received - 8.0) >= 0.01) {
                    throw new Exception("Error: La cantidad recibida guardada no coincide.");
                }

                if ($finalDetail->observation !== $comment) {
                    throw new Exception("Error: La observación guardada no coincide.");
                }

                $this->info("✓ Verificación de discrepancia y persistencia completada con éxito.");

                $this->info("==========================================================================");
                $this->info(" ✓ ¡VALIDACIÓN E2E EXITOSA! TODO EL FLUJO OPERA DE FORMA CORRECTA Y ESTABLE ");
                $this->info("==========================================================================");

                // HACER ROLLBACK: Dejar la base de datos intacta sin registros de prueba
                $this->info("Haciendo Rollback de la transacción para mantener la base de datos limpia...");
                throw new Exception("ROLLBACK_EXITOSO");
            });
        } catch (Exception $e) {
            if ($e->getMessage() === "ROLLBACK_EXITOSO") {
                $this->info("✓ Base de datos restaurada correctamente. Ningún registro basura fue guardado.");
                return 0;
            } else {
                $this->error("🚨 ERROR DURANTE LA VALIDACIÓN DEL FLUJO: " . $e->getMessage());
                $this->error("Línea: " . $e->getLine() . " en " . $e->getFile());
                return 1;
            }
        }

        return 0;
    }
}

