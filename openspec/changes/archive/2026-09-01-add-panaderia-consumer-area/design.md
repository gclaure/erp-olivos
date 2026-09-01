## Context

En el sistema, los usuarios con rol `Consumidor` representan áreas operativas que solicitan insumos a los almacenes (`requested_by`). Históricamente se tenían las opciones `Cocina`, `Pastelería` y `Eventos`. Se incorpora el área `Panadería` para permitir la asignación correcta a los colaboradores del sector de panadería.

## Goals / Non-Goals

**Goals:**
- Incluir `Panadería` en la lista desplegable de Áreas en `UserModal.vue`.
- Actualizar las reglas de validación en `UserController` (`store` y `update`) para admitir `Panadería` y `Panaderia`.
- Actualizar la validación de `SaveConsumptionRequest` para admitir `Panadería` y `Panaderia` en `requested_by`.
- Actualizar el mensaje de advertencia en `ConsumptionRequestController@store`.

**Non-Goals:**
- Modificar el esquema de base de datos (las columnas `users.area` y `consumption_requests.requested_by` son `varchar(150)` y `varchar(255)` respectivamente).

## Decisions

- Mantener consistencia visual y de datos utilizando la ortografía oficial en español con tilde (`Panadería`), admitiendo a la vez `Panaderia` en las reglas `in:...` para evitar fallos si se envían datos sin tilde.
