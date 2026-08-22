<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'has_expiration' => $this->boolean('has_expiration'),
            'show_in_ecommerce' => $this->boolean('show_in_ecommerce'),
            'type' => $this->input('type', ProductType::RAW_MATERIAL->value),
            'min_stock' => $this->input('type') === ProductType::SUPPLY->value ? ($this->input('min_stock') ?: 0) : $this->input('min_stock'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:250',
            'type' => ['required', Rule::enum(ProductType::class)],
            'warehouse_ids' => [
                'required_if:type,' . ProductType::RAW_MATERIAL->value,
                'nullable',
                'array',
            ],
            'warehouse_ids.*' => 'exists:warehouses,id',
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'code')
                    ->ignore($this->route('product')),
            ],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'min_stock' => [
                'required_if:type,' . ProductType::RAW_MATERIAL->value,
                'nullable',
                'numeric',
                'min:0',
            ],
            'is_active' => 'required|boolean',
            'has_expiration' => 'required|boolean',
            'show_in_ecommerce' => 'required|boolean',
            'unit_of_measure_id' => 'required|exists:unit_of_measures,id',
            'units_per_package' => 'required|numeric|min:0.0001',
            'package_name' => 'nullable|string|max:50',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'drive_links' => 'nullable|array',
            'drive_links.*' => 'url',
            'new_images' => 'nullable|array',
            'new_images.*' => 'image|max:5120',
            'location' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')
                    ->ignore($this->route('product')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'type.required' => 'El tipo de producto es obligatorio.',
            'warehouse_ids.required_if' => 'Selecciona al menos un almacén para materia prima.',
            'min_stock.required_if' => 'El stock mínimo es obligatorio para materia prima.',
            'code.unique' => 'El código ya está en uso.',
            'category_ids.required' => 'Selecciona al menos una categoría.',
            'unit_of_measure_id.required' => 'La unidad de medida es obligatoria.',
        ];
    }
}
