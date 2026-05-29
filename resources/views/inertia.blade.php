<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Tu Inventario') }}</title>

        @php
            $company = \App\Facades\CompanyFacade::getCompany();
            $faviconPath = 'storage/company/favicon.png';
            $faviconUrl = file_exists(public_path($faviconPath)) ? asset($faviconPath) : asset('favicon.ico');
        @endphp
        <link rel="icon" type="image/png" href="{{ $faviconUrl }}">

        {{-- Fonts & Preload --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="preload" href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" as="style">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        
        {{-- Material Symbols --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        <script>
            window.initialSidebarItems = @json(auth()->check() ? app(\App\Services\SidebarService::class)->getItems() : []);
        </script>
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-zinc-100 text-zinc-900">
        @inertia
    </body>
</html>
