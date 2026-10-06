<?php

use App\Facades\CompanyFacade;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin')->name('home');


Route::redirect('dashboard', '/admin')->name('dashboard');

Route::get('/manifest.json', function () {
    $company = CompanyFacade::getCompany();
    $name = $company?->name ?? 'Olivos Catering';
    
    // Iconos PWA dedicados de alta resolución
    $icon192 = file_exists(public_path('icons/icon-192.png')) ? asset('icons/icon-192.png') : asset('img/logo-light.png');
    $icon512 = file_exists(public_path('icons/icon-512.png')) ? asset('icons/icon-512.png') : asset('img/logo-light.png');
    $maskable192 = file_exists(public_path('icons/icon-maskable-192.png')) ? asset('icons/icon-maskable-192.png') : $icon192;
    $maskable512 = file_exists(public_path('icons/icon-maskable-512.png')) ? asset('icons/icon-maskable-512.png') : $icon512;

    return response()->json([
        "id" => "/admin",
        "name" => $name,
        "short_name" => $name,
        "description" => "Sistema de gestión de inventarios y ventas de " . $name . ".",
        "start_url" => "/admin?source=pwa",
        "scope" => "/",
        "display" => "standalone",
        "orientation" => "any",
        "background_color" => "#FAF9F5",
        "theme_color" => "#3F6B20",
        "categories" => ["business", "productivity", "utilities"],
        "icons" => [
            [
                "src" => $icon192,
                "sizes" => "192x192",
                "type" => "image/png",
                "purpose" => "any"
            ],
            [
                "src" => $icon512,
                "sizes" => "512x512",
                "type" => "image/png",
                "purpose" => "any"
            ],
            [
                "src" => $maskable192,
                "sizes" => "192x192",
                "type" => "image/png",
                "purpose" => "maskable"
            ],
            [
                "src" => $maskable512,
                "sizes" => "512x512",
                "type" => "image/png",
                "purpose" => "maskable"
            ]
        ],
        "shortcuts" => [
            [
                "name" => "Punto de Venta (POS)",
                "short_name" => "POS",
                "description" => "Acceso directo al terminal de ventas",
                "url" => "/admin/pos?source=shortcut",
                "icons" => [
                    [
                        "src" => $icon192,
                        "sizes" => "192x192",
                        "type" => "image/png"
                    ]
                ]
            ],
            [
                "name" => "Catálogo de Productos",
                "short_name" => "Productos",
                "description" => "Gestión y catálogo de inventario",
                "url" => "/admin/products?source=shortcut",
                "icons" => [
                    [
                        "src" => $icon192,
                        "sizes" => "192x192",
                        "type" => "image/png"
                    ]
                ]
            ]
        ]
    ], 200, [
        'Content-Type' => 'application/manifest+json',
        'Cache-Control' => 'no-cache, private'
    ]);
});



Route::get('/force-logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
})->name('force-logout');
