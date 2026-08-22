## 1. Backend: Filtrado por Tipo de Producto

- [x] 1.1 Incluir el campo `type` en el select de `PosController::searchProducts`
- [x] 1.2 Agregar el filtro `when($request->filled('type'), ...)` en `PosController::searchProducts`

## 2. Frontend: Composable y Componentes de Catálogo

- [x] 2.1 Actualizar `useProductSearch.js` para soportar el parámetro reactivo `typeFilter`
- [x] 2.2 Integrar los chips de filtro por tipo (Todos, Insumos, Materia Prima) en `ProductCatalog.vue`
- [x] 2.3 Conectar el estado del filtro en `Index.vue`
- [x] 2.4 Validar la disponibilidad y renderizado de tarjetas en `ProductCard.vue` para insumos en modo consumidor

## 3. Verificación y Pruebas

- [x] 3.1 Probar la compilación de assets con `pnpm build`
- [x] 3.2 Verificar el filtrado dinámico en la interfaz de creación de solicitudes de consumo
