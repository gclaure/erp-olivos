<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RecordModificationReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('request-consumption-modification');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'returned_quantity' => ['required', 'numeric', 'gt:0'],
            'return_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'returned_quantity.required' => 'Debe ingresar la cantidad física devuelta.',
            'returned_quantity.gt' => 'La cantidad devuelta debe ser mayor que cero.',
        ];
    }
}
