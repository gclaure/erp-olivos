<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Facades\CompanyFacade;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = CompanyFacade::getTenantId();

        return [
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:200',
            'phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'is_main' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'is_main.boolean' => 'El campo central debe ser verdadero o falso.',
        ];
    }
}
