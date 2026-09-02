## 1. Status Helper & Data Enrichment

- [x] 1.1 Crear helper reactivo `getDetailedStatusInfo(req)` en `resources/js/Pages/Admin/ConsumptionRequest/Index.vue` para desglosar la etapa operativa (Por Aprobar, Aprobado/En Almacén, Falta Stock, Despachado/Por Recibir, etc.)

## 2. Table & Mobile UI Updates

- [x] 2.1 Actualizar la columna de Estado en la tabla desktop de `Index.vue` con badge principal, sublabel contextual y alerta de falta de stock
- [x] 2.2 Actualizar el bloque de Estado en las tarjetas móviles (`lg:hidden`) de `Index.vue`

## 3. Verification

- [x] 3.1 Verificar la visualización de los diferentes estados en `/admin/consumption-requests`
- [x] 3.2 Compilar assets con `npm run build` y validar responsividad y dark mode
