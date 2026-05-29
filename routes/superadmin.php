<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
// Gestión de Empresas eliminada

// Gestión de Planes
Route::controller(\App\Http\Controllers\SuperAdmin\PlanController::class)->group(function () {
    Route::get('plans', 'index')->name('plans.index');
    Route::post('plans', 'store')->name('plans.store');
    Route::put('plans/{plan}', 'update')->name('plans.update');
    Route::delete('plans/{plan}', 'destroy')->name('plans.destroy');
    Route::post('plans/{plan}/toggle-active', 'toggleActive')->name('plans.toggle-active');
});

// Gestión de Suscripciones
Route::controller(\App\Http\Controllers\SuperAdmin\SubscriptionController::class)->group(function () {
    Route::get('subscriptions', 'index')->name('subscriptions.index');
    Route::post('subscriptions/assign', 'assign')->name('subscriptions.assign');
    Route::post('subscriptions/{subscription}/extend', 'extend')->name('subscriptions.extend');
    Route::post('subscriptions/{subscription}/cancel', 'cancel')->name('subscriptions.cancel');
    Route::post('subscriptions/{subscription}/renew', 'renew')->name('subscriptions.renew');
    Route::post('subscriptions/{subscription}/change-plan', 'changePlan')->name('subscriptions.change-plan');
    Route::post('subscriptions/{subscription}/calculate-proration', 'calculateProration')->name('subscriptions.calculate-proration');
});

// Gestión de Pagos
Route::controller(\App\Http\Controllers\SuperAdmin\PaymentController::class)->group(function () {
    Route::get('payments', 'index')->name('payments.index');
    Route::post('payments', 'store')->name('payments.store');
    Route::delete('payments/{payment}', 'destroy')->name('payments.destroy');
    Route::post('payments/{payment}/mark-as-paid', 'markAsPaid')->name('payments.mark-as-paid');
});

// Gestión de Asesores
Route::controller(\App\Http\Controllers\SuperAdmin\AsesorController::class)->group(function () {
    Route::get('asesores', 'index')->name('asesores.index');
    Route::post('asesores', 'store')->name('asesores.store');
    Route::put('asesores/{asesore}', 'update')->name('asesores.update');
    Route::delete('asesores/{asesore}', 'destroy')->name('asesores.destroy');
});

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
