<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Facades\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MultiBranchMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to admin routes
        if (!$request->is('admin*')) {
            return $next($request);
        }

        $user = $request->user();

        // If not logged in or doesn't have a verified email, let other middleware handle it
        if (!$user) {
            return $next($request);
        }

        // If branch and warehouse are already active, proceed
        if (Branch::hasActiveBranch() && Branch::getActiveWarehouseId()) {
            return $next($request);
        }

        // Avoid infinite loop if we are already on the selection page
        if ($request->routeIs('admin.branch.select') || $request->is('admin/select-branch*')) {
            return $next($request);
        }

        if ($user->is_super_admin) {
            // Super Admin: Auto-seleccionar la primera sucursal
            $branch = \App\Models\Branch::first();
            if ($branch) {
                Branch::setActiveBranch($branch->id);
            }
            return $next($request);
        }

        $branch = $user->branch;

        if (!$branch || !$branch->is_active) {
            // No active branch assigned to normal user, proceed as is (other guards will handle)
            return $next($request);
        }

        // Auto-activate the single branch for normal users
        Branch::setActiveBranch($branch->id);

        return $next($request);
    }
}
