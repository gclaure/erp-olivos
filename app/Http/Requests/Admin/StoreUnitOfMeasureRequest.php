<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitOfMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:unit_of_measures,name',
            'abbreviation' => 'required|string|max:10',
            'is_active' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la unidad es obligatorio.',
            'name.unique' => 'Ya existe una unidad con ese nombre.',
            'abbreviation.required' => 'La abreviatura es obligatoria.',
            'abbreviation.max' => 'La abreviatura no puede exceder los 10 caracteres.',
        ];
    }
}
