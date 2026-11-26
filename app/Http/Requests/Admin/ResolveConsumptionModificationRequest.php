<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ResolveConsumptionModificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('approve-consumption-modification');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $isReject = str_ends_with((string) $this->route()?->getName(), '.reject');

        if ($isReject) {
            return [
                'resolution_notes' => ['required', 'string', 'min:3', 'max:1000'],
            ];
        }

        return [
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
            'purchase_resolutions' => ['nullable', 'array'],
            'purchase_resolutions.*.purchase_order_detail_id' => ['required', 'uuid', 'exists:purchase_order_details,id'],
            'purchase_resolutions.*.decision' => ['required', 'string', 'in:cancelar,mantener'],
            'purchase_resolutions.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $resolutions = $this->input('purchase_resolutions', []);
                if (!is_array($resolutions)) {
                    return;
                }

                foreach ($resolutions as $idx => $resolution) {
                    $decision = (string) ($resolution['decision'] ?? '');
                    $notes = trim((string) ($resolution['notes'] ?? ''));

                    if ($decision === 'mantener' && strlen($notes) < 3) {
                        $validator->errors()->add(
                            "purchase_resolutions.{$idx}.notes",
                            'Debe ingresar una nota justificativa de al menos 3 caracteres al mantener una línea de compra.'
                        );
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resolution_notes.required' => 'Debe ingresar una nota explicando el motivo del rechazo.',
            'resolution_notes.min' => 'La nota de resolución debe tener al menos 3 caracteres.',
            'purchase_resolutions.*.purchase_order_detail_id.required' => 'Identificador de línea de compra requerido.',
            'purchase_resolutions.*.decision.in' => 'La decisión de compra debe ser cancelar o mantener.',
        ];
    }
}
