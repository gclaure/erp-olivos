<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasRole(['Admin', 'Administrador', 'admin', 'administrador']);

        if (!$user || (!$user->is_super_admin && !$isAdmin)) {
            abort(403, 'Acceso restringido a administradores.');
        }

        return $next($request);
    }
}
