## Why

Tras hacer obligatoria la aprobación del Administrador, la vista de detalle del Consumidor sigue mostrando mensajes y paneles orientados a Almacén (“En Espera de Despacho”, “Acciones de Almacén”, alerta de faltantes). Hay que alinear copy y acciones visibles con el rol Consumidor y el estado real de la solicitud.

## What Changes

- Banner Consumidor en `pendiente`: mensaje de **pendiente de aprobación del Administrador** (reemplaza “En Espera de Despacho”).
- Banner Consumidor en `aprobado`: mensaje de **en espera de despacho** (el texto actual reubicado y ajustado).
- Panel de acciones para Consumidor: **no mostrar** el título “Acciones de Almacén”; mostrar solo acciones del Consumidor bajo un título propio (p. ej. “Mis Acciones”).
- Cancelar: visible para Consumidor **solo en `pendiente`** (no en `aprobado`).
- Ocultar al Consumidor la alerta de **insumos faltantes** (lenguaje de almacén).
- Confirmar Recepción se mantiene en `despachado` / `despachado_parcial`.
- Sin cambios de backend ni de creación de solicitudes. Sin cambios en banners/acciones de Almacén o Admin salvo lo necesario para no romper el panel compartido.

## Capabilities

### New Capabilities

- `consumer-request-detail-ui`: presentación y acciones del rol Consumidor en el detalle de solicitud de consumo

### Modified Capabilities

None (el lifecycle ya cubre reglas de negocio; este change es UI del Consumidor)

## Impact

- `resources/js/Pages/Admin/ConsumptionRequest/Show.vue` únicamente
