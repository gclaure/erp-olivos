<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\KardexController;
use App\Http\Controllers\Admin\MovementController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\UnitOfMeasureController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\AccountReceivableController;
use App\Http\Controllers\Admin\AccountPayableController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\ConsumptionRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

// Notificaciones
Route::get('notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
Route::post('notifications/{notification}/read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('notifications.read');
Route::post('notifications/read-all', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
Route::delete('notifications/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('notifications.destroy');
Route::delete('notifications-all', [\App\Http\Controllers\Admin\NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');

// Selección de Sucursal
Route::post('/branches/switch', [\App\Http\Controllers\Admin\BranchSelectionController::class, 'switch'])->name('branches.switch');

// Sedes
Route::get('branches', [\App\Http\Controllers\Admin\BranchController::class, 'index'])->name('branches.index');
Route::post('branches', [\App\Http\Controllers\Admin\BranchController::class, 'store'])->name('branches.store');
Route::put('branches/{branch}', [\App\Http\Controllers\Admin\BranchController::class, 'update'])->name('branches.update');
Route::delete('branches/{branch}', [\App\Http\Controllers\Admin\BranchController::class, 'destroy'])->name('branches.destroy');




// Inventario
Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
Route::get('products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');
Route::post('products', [\App\Http\Controllers\Admin\ProductController::class, 'store'])->name('products.store');
Route::put('products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'update'])->name('products.update');
Route::delete('products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('products.destroy');
Route::get('products/template', [\App\Http\Controllers\Admin\ProductController::class, 'downloadTemplate'])->name('products.template');
Route::post('products/import', [\App\Http\Controllers\Admin\ProductController::class, 'import'])->name('products.import');
Route::post('products/import-chunk', [\App\Http\Controllers\Admin\ProductController::class, 'importChunk'])->name('products.import-chunk');
Route::post('products/import-finalize', [\App\Http\Controllers\Admin\ProductController::class, 'importFinalize'])->name('products.import-finalize');
Route::post('products/import-preview', [\App\Http\Controllers\Admin\ProductController::class, 'importPreview'])->name('products.import-preview');
Route::get('products/generate-sku', [\App\Http\Controllers\Admin\ProductController::class, 'generateSku'])->name('products.generate-sku');
Route::post('products/upload-image', [\App\Http\Controllers\Admin\ProductController::class, 'uploadImage'])->name('products.upload-image');
Route::post('products/process-image-url', [\App\Http\Controllers\Admin\ProductController::class, 'processImageUrl'])->name('products.process-image-url');

// Almacenes
Route::resource('warehouses', \App\Http\Controllers\Admin\WarehouseController::class)
    ->only(['index', 'store', 'update', 'destroy']);
Route::get('unit-of-measures', [UnitOfMeasureController::class, 'index'])->name('unit-of-measures.index');
Route::post('unit-of-measures', [UnitOfMeasureController::class, 'store'])->name('unit-of-measures.store');
Route::put('unit-of-measures/{unit_of_measure}', [UnitOfMeasureController::class, 'update'])->name('unit-of-measures.update');
Route::delete('unit-of-measures/{unit_of_measure}', [UnitOfMeasureController::class, 'destroy'])->name('unit-of-measures.destroy');

// Comercial
Route::get('providers', [ProviderController::class, 'index'])->name('providers.index');
Route::post('providers', [ProviderController::class, 'store'])->name('providers.store');
Route::put('providers/{provider}', [ProviderController::class, 'update'])->name('providers.update');
Route::delete('providers/{provider}', [ProviderController::class, 'destroy'])->name('providers.destroy');

// Compras
Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');
Route::get('purchases/template', [PurchaseController::class, 'downloadTemplate'])->name('purchases.template');
Route::post('purchases/import', [PurchaseController::class, 'import'])->name('purchases.import');
Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
Route::get('purchases/{purchase}/details', [PurchaseController::class, 'details'])->name('purchases.details');
Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
Route::get('accounts-payable', [AccountPayableController::class, 'index'])->name('accounts-payable.index');
Route::post('accounts-payable/{account_payable}/payment', [AccountPayableController::class, 'recordPayment'])->name('accounts-payable.payment');
Route::get('accounts-payable/{account_payable}/history', [AccountPayableController::class, 'getHistory'])->name('accounts-payable.history');

// Solicitudes de Compra
Route::group(['prefix' => 'purchase-orders', 'as' => 'purchase-orders.'], function() {
    Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
    Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
    Route::get('/{purchase_order}', [PurchaseOrderController::class, 'show'])->name('show');
    Route::post('/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel');
    Route::post('/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('approve');
    Route::post('/{purchase_order}/convert', [PurchaseOrderController::class, 'convertToPurchase'])->name('convert');
});

// Solicitudes de Consumo Interno
Route::group(['prefix' => 'consumption-requests', 'as' => 'consumption-requests.'], function() {
    Route::get('/', [ConsumptionRequestController::class, 'index'])->name('index');
    Route::get('/create', [ConsumptionRequestController::class, 'create'])->name('create');
    Route::post('/', [ConsumptionRequestController::class, 'store'])->name('store');
    Route::get('/{consumption_request}/print', [ConsumptionRequestController::class, 'print'])->name('print');
    Route::get('/{consumption_request}', [ConsumptionRequestController::class, 'show'])->name('show');
    Route::post('/{consumption_request}/dispatch', [ConsumptionRequestController::class, 'dispatchRequest'])->name('dispatch');
    Route::post('/{consumption_request}/receive', [ConsumptionRequestController::class, 'receive'])->name('receive');
    Route::post('/{consumption_request}/generate-purchase-order', [ConsumptionRequestController::class, 'generatePurchaseOrder'])->name('generate-purchase-order');
    Route::post('/{consumption_request}/cancel', [ConsumptionRequestController::class, 'cancel'])->name('cancel');
    Route::post('/{consumption_request}/approve', [ConsumptionRequestController::class, 'approve'])->name('approve');
    Route::post('/{consumption_request}/observe', [ConsumptionRequestController::class, 'observe'])->name('observe');
});

// Ventas (Redireccionado a Consumo)
Route::redirect('pos', '/admin/consumption-requests/create')->name('pos');
Route::post('pos', [\App\Http\Controllers\Admin\PosController::class, 'store'])->name('pos.store');
Route::post('pos/update-context', [\App\Http\Controllers\Admin\PosController::class, 'updateContext'])->name('pos.update-context');
Route::post('pos/update-receipt-type', [\App\Http\Controllers\Admin\PosController::class, 'updateReceiptType'])->name('pos.update-receipt-type');
Route::get('api/pos/products', [\App\Http\Controllers\Admin\PosController::class, 'searchProducts'])->name('api.pos.products');
Route::get('quotations', [\App\Http\Controllers\Admin\QuotationController::class, 'index'])->name('quotations.index');
Route::get('quotations/create', [\App\Http\Controllers\Admin\PosController::class, 'index'])->name('quotations.create')->defaults('type', 'quotation');
Route::get('quotations/{quotation}/edit', [\App\Http\Controllers\Admin\PosController::class, 'index'])->name('quotations.edit');
Route::post('quotations/{quotation}/cancel', [\App\Http\Controllers\Admin\QuotationController::class, 'cancel'])->name('quotations.cancel');
Route::post('quotations/{id}/convert-to-sale', [\App\Http\Controllers\Admin\QuotationController::class, 'convertToSale'])->name('quotations.convert-to-sale');
Route::get('sales', [\App\Http\Controllers\Admin\SaleController::class, 'index'])->name('sales.index');
Route::get('sales/report-pdf', [\App\Http\Controllers\Admin\SalesReportController::class, 'downloadPdf'])->name('sales.report-pdf');
Route::get('sales/report-excel', [\App\Http\Controllers\Admin\SalesReportController::class, 'downloadExcel'])->name('sales.report-excel');
Route::get('sales/{sale}', [App\Http\Controllers\Admin\SaleController::class, 'show'])->name('sales.show');
Route::post('sales/{sale}/annul', [\App\Http\Controllers\Admin\SaleController::class, 'annul'])->name('sales.annul');

Route::get('accounts-receivable', [AccountReceivableController::class, 'index'])->name('accounts-receivable.index');
Route::post('accounts-receivable/{account_receivable}/payment', [AccountReceivableController::class, 'recordPayment'])->name('accounts-receivable.payment');
Route::get('accounts-receivable/{account_receivable}/history', [AccountReceivableController::class, 'getHistory'])->name('accounts-receivable.history');
Route::get('sales/{sale}/print', \App\Http\Controllers\Admin\SalePrintController::class)->name('sales.print');
Route::get('quotations/{quotation}/print', \App\Http\Controllers\Admin\QuotationPrintController::class)->name('quotations.print');

Route::get('movements', [MovementController::class, 'index'])->name('movements.index');
Route::get('movements/create', [MovementController::class, 'create'])->name('movements.create');
Route::post('movements', [MovementController::class, 'store'])->name('movements.store');
Route::get('movements/search-products', [MovementController::class, 'searchProducts'])->name('movements.search-products');
Route::get('movements/{movement}', [MovementController::class, 'show'])->name('movements.show');
Route::get('movements/{id}/print', \App\Http\Controllers\Admin\MovementPrintController::class)->name('movements.print');

Route::controller(\App\Http\Controllers\Admin\TransferController::class)
    ->prefix('transfers')
    ->name('transfers.')
    ->middleware('permission:manage-transfers')
    ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}', 'show')->name('show');
    Route::get('/{id}/edit', 'edit')->name('edit');
    Route::put('/{id}', 'update')->name('update');
    Route::post('/{id}/approve', 'approve')->name('approve');
    Route::post('/{id}/reject', 'reject')->name('reject');
    Route::post('/{id}/cancel', 'cancel')->name('cancel');
    Route::post('/{id}/dispatch', 'dispatch')->name('dispatch');
    Route::post('/{id}/receive', 'receive')->name('receive');
    Route::post('/{id}/status', 'setStatus')->name('status');
    Route::get('/api/search-products', 'searchProducts')->name('search-products');
});
Route::get('kardex', [KardexController::class, 'index'])->name('kardex.index');
Route::get('kardex/export-pdf', [\App\Http\Controllers\Admin\KardexExportController::class, 'downloadPdf'])->name('kardex.export-pdf');
Route::get('kardex/export-excel', [\App\Http\Controllers\Admin\KardexExportController::class, 'downloadExcel'])->name('kardex.export-excel');

// BI Dashboard
Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
Route::get('api/products', \App\Http\Controllers\Api\ProductSearchController::class)->name('api.products.index');

// Selects asíncronos
Route::get('api/selects/warehouses', [\App\Http\Controllers\Api\ApiSelectController::class, 'warehouses'])->name('api.selects.warehouses');
Route::get('api/selects/providers', [\App\Http\Controllers\Api\ApiSelectController::class, 'providers'])->name('api.selects.providers');
Route::get('api/selects/categories', [\App\Http\Controllers\Api\ApiSelectController::class, 'categories'])->name('api.selects.categories');
Route::get('api/selects/products', [\App\Http\Controllers\Api\ApiSelectController::class, 'products'])->name('api.selects.products');
Route::get('api/selects/unit-measures', [\App\Http\Controllers\Api\ApiSelectController::class, 'unitOfMeasures'])->name('api.selects.unit-measures');
Route::get('api/sidebar', [\App\Http\Controllers\Api\SidebarController::class, 'index'])->name('api.sidebar');

// Administración (Solo Admins y Super Admins)
Route::middleware('role:Admin|Administrador|admin|administrador|Super-admin|Super Admin|Super Administrador|super administrador')->group(function () {
    Route::get('users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::post('users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');

    // Gestión de Roles y Permisos
    Route::controller(\App\Http\Controllers\SuperAdmin\RoleController::class)->group(function () {
        Route::get('roles', 'index')->name('roles.index');
        Route::post('roles', 'store')->name('roles.store');
        Route::put('roles/{role}', 'update')->name('roles.update');
        Route::delete('roles/{role}', 'destroy')->name('roles.destroy');
        
        // Permisos
        Route::post('permissions', 'storePermission')->name('permissions.store');
        Route::put('permissions/{permission}', 'updatePermission')->name('permissions.update');
        Route::delete('permissions/{permission}', 'destroyPermission')->name('permissions.destroy');
    });
});

// Configuración de Perfil y Cuenta
Route::get('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
Route::put('password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('password.update');
Route::get('company', [\App\Http\Controllers\Admin\CompanyController::class, 'edit'])->name('company.edit')->middleware('permission:manage-company');
Route::post('company', [\App\Http\Controllers\Admin\CompanyController::class, 'update'])->name('company.update')->middleware('permission:manage-company');
