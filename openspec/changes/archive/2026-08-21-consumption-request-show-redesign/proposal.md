## Why

La vista de detalle de solicitudes de consumo (`/admin/consumption-requests/{id}`) requiere una renovación visual y funcional bajo estándares de Frontend Design y Tailwind CSS responsivo. Actualmente, la interfaz carece de una línea de tiempo clara del ciclo de vida del pedido, presenta desalineaciones visuales en tabletas y dispositivos móviles, y las acciones clave requieren scroll excesivo en pantallas táctiles.

## What Changes

- **Header y Stepper de Ciclo de Vida**: Incorporación de un indicador visual interactivo (*Solicitado ➔ Aprobado ➔ Despachado ➔ Entregado*) con chips de metadatos (Área, Almacén, Fecha, Estado).
- **Rediseño Responsivo Adaptativo (Mobile / Tablet / Desktop)**:
  - **Desktop (>= 1024px)**: Layout de 2 columnas optimizado (70% tabla de insumos de alta densidad + 30% panel lateral sticky con ficha operativa y acciones).
  - **Tablet (768px - 1023px)**: Layout adaptativo con ficha colapsable y métricas de insumos en cards claras.
  - **Mobile (< 768px)**: Tarjetas de insumos con grid de métricas 2x2, áreas táctiles mínimas de 44px y barra inferior flotante (sticky bottom bar) para acciones rápidas de despacho/aprobación/recepción.
- **Jerarquía Visual y Tipografía Industrial**: Badges de ubicación en verde esmeralda para estanterías, números en tipografía monospace font-black, y micro-tarjetas con bordes sutiles.

## Capabilities

### New Capabilities
- `consumption-request-show-responsive-ui`: Experiencia visual responsiva y arquitectura de UI moderna para el detalle de solicitudes de consumo en móvil, tableta y escritorio.

### Modified Capabilities

## Impact

- Frontend: `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`.
- Sin cambios en backend ni base de datos.
