<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\ProductService;
use Illuminate\Validation\ValidationException;

trait HasSku
{
    /**
     * Genera un SKU conectándose al ProductService.
     * Valida que exista al menos el nombre (y opcionalmente la categoría) antes de llamar al servicio.
     *
     * @param string|null $name
     * @param array $categoryIds
     * @return string
     * @throws ValidationException
     */
    public function generateSku(?string $name, array $categoryIds = []): string
    {
        if (empty($name)) {
            throw ValidationException::withMessages([
                'name' => 'El nombre es requerido para generar el código.',
            ]);
        }

        if (empty($categoryIds)) {
            throw ValidationException::withMessages([
                'category_ids' => 'Debe seleccionar al menos una categoría para generar el código.',
            ]);
        }

        $service = app(ProductService::class);
        return $service->generateSku($name, $categoryIds);
    }
}
