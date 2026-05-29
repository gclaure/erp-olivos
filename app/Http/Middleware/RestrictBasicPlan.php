<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Facades\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictBasicPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::getActiveTenant();
        
        // Si no hay tenant (ej. superadmin), dejar pasar
        if (!$tenant) {
            return $next($request);
        }

        $plan = $tenant->currentPlan();

        // Si el plan es básico, restringir acceso
        if ($plan && $plan->slug === 'basico') {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Tu plan Básico no incluye acceso a esta funcionalidad. Por favor, actualiza tu plan para continuar.');
        }

        return $next($request);
    }
}
