## Context

Los usuarios con roles operativos (Consumidor, Pastelería, Cocina, etc.) registran solicitudes de consumo que ingresan en estado `pendiente`. Hasta ahora, no existía un mecanismo para que el autor modifique su solicitud si necesita agregar o quitar insumos antes de que el administrador la apruebe.

## Goals / Non-Goals

**Goals:**
- Proporcionar la ruta `GET /admin/consumption-requests/{id}/edit` que carga la interfaz del POS (`resources/js/Pages/Admin/POS/Index.vue`) adaptada para editar la solicitud existente.
- Pre-cargar en el carrito los productos existentes con sus cantidades, notas y almacén bloqueado al de la solicitud.
- Manejar la actualización mediante `PUT /admin/consumption-requests/{id}` delegando la transacción atómica a `ConsumptionRequestService::updateRequest`.
- Proteger el endpoint a nivel de backend: autorizar únicamente si `status === 'pendiente'`, `approved_at === null` y `user_id === Auth::id()`.
- Agregar el botón de acceso directo `[ ✏️ Editar Solicitud ]` en `Show.vue` condicionado por `can_edit`.

**Non-Goals:**
- No permitir cambiar de almacén en una solicitud ya creada (los productos agregados deben pertenecer al mismo almacén).
- No permitir edición una vez aprobada, despachada o cancelada.

## Decisions

### 1. Reutilización de la Interfaz POS (`Admin/POS/Index.vue`)
- Pasar una prop `editingRequest` con:
  - `id`: ID de la solicitud
  - `number`: Número formateado
  - `notes`: Notas existentes
  - `items`: Lista de productos formateados para el carrito (`id`, `name`, `code`, `quantity`, `unit_of_measure`, `image_path`, `type`, `is_inventoriable`)
- En el POS, si `editingRequest` está presente, el botón principal del carrito dirá `[ GUARDAR CAMBIOS ]` y enviará un `router.put(route('admin.consumption-requests.update', editingRequest.id), payload)`.

### 2. Sincronización en `ConsumptionRequestService::updateRequest`
```php
public function updateRequest(ConsumptionRequest $consumptionRequest, array $data, array $items): ConsumptionRequest
{
    return DB::transaction(function () use ($consumptionRequest, $data, $items) {
        // Validaciones estrictas de estado y propiedad
        if ($consumptionRequest->status !== 'pendiente' || !is_null($consumptionRequest->approved_at)) {
            throw new Exception("Solo se pueden editar solicitudes en estado pendiente de aprobación.");
        }
        if ($consumptionRequest->user_id !== Auth::id() && !Auth::user()->is_super_admin) {
            throw new Exception("Solo el creador de la solicitud puede editarla.");
        }

        $consumptionRequest->update([
            'notes' => $data['notes'] ?? $consumptionRequest->notes,
        ]);

        $itemProductIds = collect($items)->pluck('id')->toArray();

        // Eliminar detalles que ya no están en el carrito
        $consumptionRequest->details()->whereNotIn('product_id', $itemProductIds)->delete();

        // Actualizar o crear nuevos detalles
        foreach ($items as $item) {
            $consumptionRequest->details()->updateOrCreate(
                ['product_id' => $item['id']],
                [
                    'quantity_requested' => $item['quantity'],
                    'quantity_delivered' => 0.0,
                ]
            );
        }

        return $consumptionRequest;
    });
}
```

### 3. Exposición de `can_edit` en `ConsumptionRequestResource.php`
- `can_edit`: `$this->status === 'pendiente' && is_null($this->approved_at) && ($this->user_id === auth()->id() || auth()->user()?->is_super_admin)`
