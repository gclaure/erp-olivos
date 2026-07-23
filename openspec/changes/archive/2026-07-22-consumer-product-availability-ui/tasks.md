## 1. Backend — reservas del usuario en búsqueda POS

- [x] 1.1 En `PosController::searchProducts`, añadir subquery correlacionada `my_consumption_reserved` (suma de `quantity_requested - quantity_delivered` filtrada por `product_id`, `warehouse_id`, `user_id` del auth y mismos status de reserva de consumo que la subquery global)
- [x] 1.2 Exponer `my_reserved_quantity` en `POSProductResource` (float ≥ 0; 0 si no hay user o no hay filas)
- [x] 1.3 Verificar que `reserved_quantity` global no cambia de semántica (sales + consumption total)

## 2. Frontend — ProductCard para Consumidor

- [x] 2.1 En `ProductCard.vue`, detectar rol Consumidor (`auth.user.roles`) y modo `operationType === 'consumption'`
- [x] 2.2 Si es vista consumidor: reemplazar badge numérico de stock por "Disponible" (verde) / "No disponible" (rojo) según `hasStock` (physical − reserved global)
- [x] 2.3 Si es vista consumidor: reemplazar badge `Res: N` por `Mis reservados: N` usando `my_reserved_quantity`, solo si N > 0
- [x] 2.4 Si es vista consumidor: ajustar `title`/tooltip para no incluir cantidad de stock
- [x] 2.5 Si NO es consumidor: mantener UI actual (stock numérico + `Res:` global)

## 3. Verificación

- [ ] 3.1 Revisar manualmente como Consumidor en `/admin/consumption-requests/create`: sin números de stock, badge "Mis reservados" correcto, disponible/no disponible coherente con reservas ajenas
- [ ] 3.2 Revisar como Admin en la misma ruta: stock numérico y `Res:` global sin regresión
- [ ] 3.3 Confirmar que productos sin stock neto siguen no clickeables
