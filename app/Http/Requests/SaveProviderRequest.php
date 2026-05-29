<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveProviderRequest extends FormRequest
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
        $documentType = $this->input('document_type');

        $docNumberRule = in_array($documentType, ['CI', 'NIT'])
            ? ['nullable', 'regex:/^[0-9]{4,15}$/', 'max:15']
            : ['nullable', 'string', 'max:15'];

        return [
            'name' => ['required', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'document_type' => ['nullable', 'string', 'max:20'],
            'document_number' => $docNumberRule,
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:300'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.regex' => 'El formato del número de documento es inválido.',
        ];
    }
}
