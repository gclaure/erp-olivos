<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Facades\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use App\Facades\Tenant;

class BranchSelectionController extends Controller
{
    /**
     * Switch the active branch context in the session.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'branch_id' => [
                'required', 
                Rule::exists('branches', 'id')->where('company_id', Tenant::getActiveTenantId())
            ],
        ]);

        $user = $request->user();
        
        // Security: Only admins or users assigned to the branch can switch.
        if (!$user->is_super_admin && 
            !$user->hasRole(['Admin', 'Administrador', 'admin', 'administrador']) && 
            $user->branch_id !== $request->branch_id) {
            return back()->with('error', 'No tienes permisos para acceder a esta sucursal.');
        }

        Branch::setActiveBranch($request->branch_id);

        return back()->with('success', "Sucursal cambiada a: " . Branch::getActiveBranchName());
    }
}
