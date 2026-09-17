## 1. Reubicación del Kardex y Débito de Stock a la Entrega

- [x] 1.1 Remover la inserción de Kardex y decremento de stock de `ConsumptionRequestDispatchService::dispatch`
- [x] 1.2 Implementar el débito de `stocks` y registro en `kardex` (`KardexMovementType::ADJUSTMENT_OUT`) dentro de `ConsumptionRequestService::receiveRequest`
- [x] 1.3 Garantizar que solo los ítems inventoriables con cantidad recibida/entregada mayor a 0 generen movimiento de Kardex

## 2. Desbloqueo del Catálogo de Consumo

- [x] 2.1 Actualizar `resources/js/Pages/Admin/POS/Partials/ProductCard.vue` para que en modo consumo (`operationType === 'consumption'`) no inhabilite tarjetas por stock 0 ni use `cursor-not-allowed`
- [x] 2.2 Ajustar el stepper móvil y límites de cantidad en `ProductCard.vue` para permitir solicitar cantidades sin restricción de inventario en consumo
- [x] 2.3 Verificar que `ProductDetailModal.vue` y `CartSidebar.vue` permitan la adición y confirmación de consumos con insumos sin stock
- [x] 2.4 En modo consumo / rol consumidor, ocultar cualquier aviso de falta de stock ("Sin stock / Pedir", reservas) y mostrar la etiqueta "Disponible" para todos los productos en `ProductCard.vue` y `ProductDetailModal.vue`
- [x] 2.5 Eximir la validación de stock disponible en `SaveConsumptionRequest.php` exclusivamente para usuarios con el rol Consumidor, manteniendo el control estricto para los demás roles

## 3. Indicadores de Stock y Faltantes para Almacén

- [x] 3.1 Exponer en `ConsumptionRequestResource` o controlador el cálculo de disponibilidad de stock para cada solicitud en el listado de consumo
- [x] 3.2 Incorporar en `resources/js/Pages/Admin/ConsumptionRequest/Index.vue` badges semáforo (🟢 Stock Completo, 🟡 Faltante Parcial, 🔴 Sin Stock)
- [x] 3.3 Validar que en `resources/js/Pages/Admin/ConsumptionRequest/Show.vue` el panel de faltantes se muestre de forma destacada con acceso a generación de compra

## 4. Verificación y Pruebas

- [x] 4.1 Ejecutar prueba de ciclo completo: creación de solicitud con stock 0 -> despacho sin Kardex -> entrega con Kardex registrado -> verificación de saldos en `stocks`
