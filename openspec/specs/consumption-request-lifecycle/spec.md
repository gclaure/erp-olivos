# Consumption Request Lifecycle

Flujo de ciclo de vida de solicitudes de consumo: aprobación, despacho, recepción y cancelación.

## Requirement: Aprobación obligatoria antes del primer despacho

El sistema SHALL permitir despachar una solicitud de consumo solo cuando su estado sea `aprobado` o `despachado_parcial`. El despacho desde `pendiente` o `observado` MUST ser rechazado en backend y la UI MUST NOT ofrecer la acción de despacho en esos estados.

### Scenario: Almacén intenta despachar solicitud pendiente

- **WHEN** un usuario con rol Almacén intenta despachar una solicitud en estado `pendiente`
- **THEN** el sistema MUST rechazar la operación con error de estado no permitido
- **AND** MUST NOT cambiar el stock ni el estado de la solicitud

### Scenario: Almacén despacha solicitud aprobada

- **WHEN** un usuario con rol Almacén despacha una solicitud en estado `aprobado`
- **THEN** el sistema MUST permitir el despacho según las reglas existentes de cantidades y stock
- **AND** MUST actualizar el estado a `despachado` o `despachado_parcial` según corresponda

### Scenario: Completar despacho parcial

- **WHEN** una solicitud está en estado `despachado_parcial`
- **THEN** Almacén MUST poder continuar despachando el restante sin nueva aprobación

### Scenario: UI oculta despacho si no está aprobada

- **WHEN** un usuario Almacén ve el detalle de una solicitud en estado `pendiente`
- **THEN** el botón "Despachar Stock" y la columna de despacho MUST NOT mostrarse como acción habilitada para despachar desde pendiente

## Requirement: Flujo de recepción cierra el ciclo del Consumidor

El Consumidor SHALL poder confirmar recepción solo en estados `despachado` o `despachado_parcial`. Tras una recepción exitosa el estado MUST quedar en `entregado` y el ciclo del Consumidor para esa solicitud finaliza.

### Scenario: Consumidor confirma recepción

- **WHEN** el Consumidor confirma la recepción de una solicitud despachada
- **THEN** el estado MUST ser `entregado`
- **AND** MUST registrarse `received_by_user_id` y `received_at`

## Requirement: Creación de solicitudes no se modifica

Este change MUST NOT alterar el endpoint, validaciones, UI ni notificaciones del flujo de creación (`store` / create) de solicitudes de consumo.

### Scenario: Crear solicitud permanece igual

- **WHEN** un Consumidor crea una solicitud de consumo
- **THEN** el comportamiento de creación MUST ser el existente previo a este change

## Requirement: Disponibilidad continua y bypass de Kardex para Insumos en Solicitudes de Consumo
El sistema SHALL permitir que los productos de tipo `insumo` estén permanentemente disponibles para ser seleccionados en solicitudes de consumo sin importar el balance de stock en almacén, y al momento del despacho MUST NOT descontar stock físico ni generar movimientos de salida en Kardex.

### Scenario: Selección de Insumo en solicitud de consumo
- **WHEN** un usuario crea o edita una solicitud de consumo y busca un producto de tipo `insumo`
- **THEN** el producto se muestra como disponible con indicador de no inventariable
- **AND** permite seleccionarlo independientemente de que el stock en almacén sea 0

### Scenario: Despacho de solicitud con Insumo
- **WHEN** el almacén despacha una solicitud aprobada que contiene líneas de productos tipo `insumo`
- **THEN** la cantidad despachada se registra en el detalle de la solicitud (`consumption_request_details`)
- **AND** MUST NOT decrementar el balance en la tabla `stocks`
- **AND** MUST NOT generar movimientos en la tabla `kardex` para dichos ítems

