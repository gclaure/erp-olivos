## Context

En el sistema de inventario y consumo interno, los usuarios con rol `Consumidor` representan áreas u departamentos operativos que solicitan insumos a los almacenes (`requested_by`). Anteriormente se contaban con las opciones `Cocina`, `Pastelería`, `Panadería` y `Eventos`. Se incorporan las áreas `Producción` y `Despacho` para permitir la asignación correcta a los colaboradores de estos sectores operativos.

## Goals / Non-Goals

**Goals:**
- Extender la lista de opciones de áreas operativas en la interfaz de gestión de usuarios (`UserModal.vue`).
- Actualizar las reglas de validación en `UserController.php` (`store` y `update`) para admitir `Producción`, `Produccion` y `Despacho`.
- Actualizar la validación de `SaveConsumptionRequest` para admitir `Producción`, `Produccion` y `Despacho` en `requested_by`.
- Actualizar los mensajes informativos en `ConsumptionRequestController.php` y `CartSidebar.vue`.

**Non-Goals:**
- Modificar la estructura de base de datos (las columnas `users.area` y `consumption_requests.requested_by` ya son de tipo string flexible).
- Alterar el flujo de aprobación o despacho de solicitudes de consumo.

## Decisions

- **Tolerancia a acentos en validación backend**: En las reglas de validación `in:` de Laravel, incluir tanto `Producción` como `Produccion` para evitar rechazos accidentales si clientes o APIs omiten la tilde, manteniendo `Producción` como valor estándar en la UI de Vue.
- **Lista centralizada en UI**: Registrar los nuevos objetos en la constante `areas` de `UserModal.vue` (`{ label: 'Producción', value: 'Producción' }` y `{ label: 'Despacho', value: 'Despacho' }`).

## Risks / Trade-offs

- *[Riesgo de inconsistencia de nombres en reportes o filtros existentes]* $\rightarrow$ Los filtros y búsquedas en `ConsumptionRequestController` usan consultas tipo `ilike` o comparaciones directas de cadenas, por lo que las nuevas opciones se integrarán de forma transparente y retrocompatible.
