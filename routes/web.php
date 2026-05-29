<?php

use App\Facades\CompanyFacade;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin')->name('home');


Route::redirect('dashboard', '/admin')->name('dashboard');

Route::get('/manifest.json', function () {
    $company = CompanyFacade::getCompany();
    $name = $company?->name ?? config('app.name', 'Tu Inventario');
    
    // Logo y Favicon dinámicos
    $logoPath = $company?->logo_path ?? 'logo.jpeg';
    $logoUrl = asset('storage/' . $logoPath);
    if (!str_starts_with($logoPath, 'http') && !file_exists(public_path('storage/' . $logoPath))) {
        $logoUrl = asset('logo.jpeg');
    }
    $logoType = str_ends_with(strtolower($logoUrl), '.png') ? 'image/png' : 'image/jpeg';
    
    $faviconPath = 'storage/company/favicon.png';
    $faviconUrl = file_exists(public_path($faviconPath)) ? asset($faviconPath) : asset('favicon.ico');
    $faviconType = str_ends_with(strtolower($faviconUrl), '.svg') ? 'image/svg+xml' : (str_ends_with(strtolower($faviconUrl), '.png') ? 'image/png' : 'image/x-icon');

    return response()->json([
        "name" => "Sistema de Inventario - " . $name,
        "short_name" => $name,
        "description" => "Sistema profesional de gestión de inventarios y ventas.",
        "start_url" => "/admin",
        "scope" => "/",
        "display" => "standalone",
        "orientation" => "portrait",
        "background_color" => "#ffffff",
        "theme_color" => "#4f46e5",
        "icons" => [
            [
                "src" => $logoUrl,
                "sizes" => "192x192",
                "type" => $logoType,
                "purpose" => "any"
            ],
            [
                "src" => $logoUrl,
                "sizes" => "512x512",
                "type" => $logoType,
                "purpose" => "any"
            ],
            [
                "src" => $faviconUrl,
                "sizes" => "192x192",
                "type" => $faviconType,
                "purpose" => "any"
            ],
            [
                "src" => $faviconUrl,
                "sizes" => "512x512",
                "type" => $faviconType,
                "purpose" => "maskable"
            ]
        ]
    ], 200, ['Content-Type' => 'application/manifest+json']);
});



Route::get('/force-logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
})->name('force-logout');
