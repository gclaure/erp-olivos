<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')->id ?? $this->route('warehouse');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                'unique:warehouses,name,' . $warehouseId
            ],
            'address' => [
                'nullable',
                'string',
                'max:300'
            ],
            'is_active' => [
                'boolean'
            ],
        ];
    }
}
