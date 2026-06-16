<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Facades\CompanyFacade;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = CompanyFacade::getTenantId();
        $branchId = $this->route('branch'); // El parámetro en la ruta suele ser 'branch'

        return [
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:200',
            'phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'is_main' => [
                'boolean',
                function ($attribute, $value, $fail) use ($companyId, $branchId) {
                    if (!$value) {
                        // Validar que no se desmarque la sucursal central si es la única
                        $isCurrentlyMain = Branch::withoutGlobalScope('branch_scoped')
                            ->where('id', $branchId)
                            ->where('is_main', true)
                            ->exists();
                        
                        if ($isCurrentlyMain) {
                            $otherMainExists = Branch::withoutGlobalScope('branch_scoped')
                                ->where('company_id', $companyId)
                                ->where('is_main', true)
                                ->where('id', '!=', $branchId)
                                ->exists();
                            
                            if (!$otherMainExists) {
                                $fail('No puedes desmarcar la sucursal central. Debe haber al menos una sucursal central.');
                            }
                        }
                    }
                }
            ],
            'is_active' => 'boolean',
        ];
    }
}
