## Context

El detalle de la solicitud de consumo (`ConsumptionRequest/Show.vue`) gestiona el flujo operativo entre el personal consumidor, la administración y los despachadores de almacén. Se requiere una interfaz más limpia, orientada al usuario y responsiva para tablets y teléfonos móviles.

## Goals / Non-Goals

**Goals:**
- Implementar un stepper de estado moderno (Solicitado ➔ Aprobado ➔ Despachado ➔ Entregado).
- Crear un header ejecutivo con chips de metadatos (solicitante, almacén, fecha, estado).
- Diseñar tarjetas de insumo móviles de alto rendimiento con grid 2x2 de cantidades y ubicación destacada.
- Diseñar una barra flotante de acciones rápidas (Sticky Action Bar) en móvil con soporte para safe-area y mínimo 44px de área táctil.
- En desktop (>= 1024px), estructurar un sidebar sticky con ficha operativa y panel de acciones.
- Soporte total para Dark Mode y Light Mode con tokens semánticos.

**Non-Goals:**
- No alterar rutas ni controladores de Laravel.
- No alterar lógica de base de datos ni modelos.

## Decisions

1. **Stepper Dinámico según Estado**:
   - `pendiente`: Paso 1 activo, paso 2 pendiente.
   - `aprobado`: Pasos 1 y 2 completados, paso 3 activo.
   - `despachado` / `despachado_parcial`: Pasos 1, 2 y 3 completados, paso 4 activo.
   - `entregado`: Todos completados con éxito.
   - `observado` / `cancelado`: Paso 2 en estado de alerta o detención.

2. **Diseño Híbrido Móvil / Tablet / Desktop**:
   - `hidden md:block`: Tabla desktop de alta densidad con scroll suave.
   - `md:hidden`: Tarjetas móviles limpias con separación de métricas e imágenes optimizadas con visualizador modal.

## Risks / Trade-offs

- [Interacción en móvil con modales de acción] → El sticky footer solo se activa cuando existen acciones viables para el rol actual y se oculta automáticamente cuando se abre una modal.
