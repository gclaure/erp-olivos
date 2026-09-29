# Proposal

## Why

Actualmente, cuando una solicitud de consumo interno incluye insumos con stock parcial o en cero (ej. Azúcar Impalpable donde se solicitaron 3 y solo hay 2 en inventario físico), el sistema presenta fricciones operativas entre la aprobación, el despacho de almacén y la recepción de cocina:
1. El almacén debe tener visibilidad clara de las existencias físicas y poder despachar las unidades disponibles (ej. 2 de 3, o 0 si no hay inventario), generando el estado de despacho parcial y la orden de compra automática por el remanente.
2. Al llegar a cocina (área solicitante), la interfaz no mostraba el campo de entrada ni de observación para los productos entregados en cero (`quantity_delivered == 0`), y el backend arrojaba un error de validación impidiendo concluir la recepción conforme de la entrega.

Este cambio armoniza la visibilidad de faltantes para Almacén y garantiza que Cocina pueda registrar la recepción de todos los productos (incluyendo parciales y ceros) con sus observaciones correspondientes, completando el ciclo sin bloqueos.

## What Changes

- **Visibilidad Persistente de Faltantes para Almacén:** Asegurar que el banner de insumos faltantes y los semáforos de stock físico sigan visibles para el rol Almacén y Administrador en los estados `aprobado` y `despachado_parcial`.
- **Despacho Parcial y con Cero Unidades en Almacén:** Permitir que el almacenero entregue el stock disponible (ej. 2 de 3, o 0 de 3), registrando la entrega parcial y activando la solicitud de compra por el faltante sin bloqueos de interfaz ni excepciones.
- **Recepción Conforme en Cocina para Todos los Ítems:** Permitir que el usuario de Cocina (Consumidor) registre la cantidad recibida y la observación obligatoria de discrepancia para cualquier producto de la solicitud, incluidos aquellos con entrega en cero (`quantity_delivered == 0`).
- **Validación Backend Flexible en Recepción:** Ajustar `ConsumptionRequestService::receiveRequest` para aceptar observaciones en ítems entregados en 0 o con faltantes, permitiendo que la entrega conforme se registre exitosamente y afecte al Kardex únicamente por las unidades efectivamente recibidas.

## Capabilities

### New Capabilities
- `consumption-dispatch-reception-stock-flow`: Reglas de negocio e interacción de interfaz para el despacho de existencias parciales/cero por Almacén y la recepción conforme con observaciones por Cocina.

### Modified Capabilities
*(Ninguna)*

## Impact

- **Frontend:** `resources/js/Pages/Admin/ConsumptionRequest/Show.vue` (visibilidad de alertas en `despachado_parcial`, soporte para inputs de recepción y observaciones en ítems con entrega 0).
- **Backend:** `app/Services/ConsumptionRequestService.php` (método `receiveRequest` admitiendo observaciones y ceros sin lanzar excepción indebida).
