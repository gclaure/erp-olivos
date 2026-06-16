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

        if (!$user) {
            abort(403, 'Acceso restringido a administradores.');
        }

        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Tu cuenta ha sido desactivada por el administrador.');
        }

        $isAdmin = $user->hasRole(['Admin', 'Administrador', 'admin', 'administrador']);

        if (!$user->is_super_admin && !$isAdmin) {
            abort(403, 'Acceso restringido a administradores.');
        }

        return $next($request);
    }
}
