# Design

## Context

El almacenero necesita operar rápido en mostrador. La pantalla previa obligaba a procesar listas largas, múltiples cajas y números paralelos.
Ver `proposal.md` y `specs/consumption-request-modification-modal-ui/spec.md`.

## Goals / Non-Goals

**Goals:**
- Estructurar el modal en 3 bloques visualmente numerados:
  1. *Paso 1: ¿Qué pasa con el producto pedido originalmente?*
  2. *Paso 2: ¿Qué producto nuevo le vas a entregar?*
  3. *Paso 3: Motivo del cambio*.
- Reemplazar inputs de desglose numérico con dos tarjetas de selección directa tipo radio:
  - "Sigue en Almacén (nunca salió)"
  - "El solicitante lo devolverá a Almacén"
  *(con opción de desglose manual desplegable solo si fue una entrega mixta).*
- Reducir el buscador a un selector limpio que colapsa una vez elegido el producto nuevo.
- Cumplir tokens de diseño Olivos y responsividad completa.

**Non-Goals:**
- No se alteran rutas ni lógica de backend de Laravel.
- No se eliminan las validaciones de negocio de integridad en base de datos.

## Decisions

1. **Selector Guiado en 3 Pasos con Encabezados Grandes y Comprensibles**:
   - Cada paso tiene su título claro y autoexplicativo, eliminando palabras como "to_product", "physical_status", o fórmulas de suma.
2. **Cálculo Automático Transparente**:
   - Al marcar "Sigue en almacén", `form.quantity_prepared = detail.quantity_delivered` y `quantity_physically_delivered = 0`.
   - Al marcar "El solicitante lo devolverá", `form.quantity_prepared = 0` y `quantity_physically_delivered = detail.quantity_delivered`.
   - Si el producto aún no se había despachado (`quantity_delivered == 0`), el Paso 1 se simplifica automáticamente indicando que el producto aún no salió.

## Risks / Trade-offs

- **[Caso poco frecuente de entrega parcial mixta]**: Si de 10 unidades, 5 se entregaron y 5 quedaron en almacén.  
  *Mitigación*: Se incluye un botón pequeño "Ajuste manual de cantidades" para desplegar inputs numéricos solo si el usuario realmente lo necesita, manteniendo la pantalla limpia para el 99% de los casos normales.
