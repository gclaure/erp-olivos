## 1. Backend: Extensión de Endpoint de Búsqueda de Productos

- [x] 1.1 Modificar `ApiSelectController@products` para recibir y aplicar los filtros opcionales `type` y `warehouse_id`
- [x] 1.2 Incorporar en `ApiSelectController@products` el cálculo de stock disponible por almacén (`withSum` condicional) y exponer `type`, `stock` y `unit` en la respuesta JSON

## 2. Frontend: Bloqueo de Buscador y Filtrado en Compras

- [x] 2.1 Actualizar `resources/js/Pages/Admin/Purchase/Create.vue` para deshabilitar el input del buscador de productos mientras `!form.warehouse_id`, con estilos y placeholder explicativos
- [x] 2.2 Actualizar la función `searchProducts` en `Create.vue` para enviar `warehouse_id: form.warehouse_id` y `type: 'materia_prima'`
- [x] 2.3 Rediseñar cada ítem del dropdown de resultados en `Create.vue` para mostrar el stock disponible en el almacén seleccionado con indicador visual (badge de stock disponible o agotado)
- [x] 2.4 Replicar las mejoras correspondientes en `resources/js/Pages/Admin/PurchaseOrder/Create.vue` para consistencia en el módulo de compras

## 3. Verificación y Calidad

- [x] 3.1 Probar que al ingresar a `/admin/purchases/create` el buscador está inactivo
- [x] 3.2 Seleccionar un almacén y comprobar que el buscador se habilita de inmediato
- [x] 3.3 Buscar productos y verificar que NUNCA aparecen insumos en la lista de resultados
- [x] 3.4 Verificar que cada materia prima muestra con exactitud el stock disponible del almacén seleccionado
- [x] 3.5 Ejecutar `pnpm build` para validar que no haya errores de compilación
