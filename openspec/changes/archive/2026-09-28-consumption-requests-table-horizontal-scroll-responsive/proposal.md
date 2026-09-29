# Proposal

## Why

En pantallas de laptops (13 pulgadas o resoluciones entre 1280px y 1440px), la tabla de solicitudes de consumo (`/admin/consumption-requests`) contiene 8 columnas (Checkbox, Número, Fecha, Área Solicitante, Almacén Origen, Solicitado Por, Estado con semáforos de stock y Acciones). Al no poseer un ancho mínimo garantizado, las celdas se comprimen, cortando textos informativos e indicadores visuales de stock. Además, carece de barra de scroll horizontal visible y de controles de navegación asistida que permanezcan accesibles mientras se hace scroll vertical a través del listado de solicitudes, lo que degrada la experiencia de usuario de almaceneros y supervisores.

## What Changes

- Establecer un ancho mínimo contundente (`min-w-[1180px]`) en la tabla de solicitudes de consumo para preservar la legibilidad de todas las columnas, badges y semáforos de stock.
- Integrar la clase `.table-scrollbar-visible` en el contenedor con scroll horizontal, asegurando una barra de 10px con alto contraste tanto en modo claro como en modo oscuro.
- Implementar la columna de `Acciones` ("Ver Detalle") con anclaje fijo a la derecha (`sticky right-0`), garantizando que siempre permanezca visible y accesible al hacer scroll lateral.
- Añadir una barra de navegación horizontal asistida (`sticky top-0 z-30`) con backdrop blur y botones ergonómicos de desplazamiento (`scrollTable('left')` y `scrollTable('right')`), que se mantenga permanentemente visible al hacer scroll vertical hacia abajo por el listado de solicitudes.

## Capabilities

### New Capabilities
- `consumption-requests-table-horizontal-scroll`: Capacidad de navegación horizontal asistida y visualización responsiva con acciones sticky y scrollbar permanente en la tabla de solicitudes de consumo.

### Modified Capabilities
<!-- Ninguna capacidad previa cambia sus requerimientos de negocio -->

## Impact

- Frontend: `resources/js/Pages/Admin/ConsumptionRequest/Index.vue`.
- Sin cambios en base de datos, modelos ni controladores PHP backend.
