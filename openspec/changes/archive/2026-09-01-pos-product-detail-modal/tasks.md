## 1. Product Detail Modal Component

- [x] 1.1 Crear el componente `resources/js/Pages/Admin/POS/Partials/ProductDetailModal.vue` con visualización completa de datos, imágenes, badges y selector de cantidad para añadir al carrito
- [x] 1.2 Integrar reglas de visualización de stock (texto "Disponible" para Consumidor vs número exacto para Admin/Almacén)

## 2. Product Card and Catalog Integration

- [x] 2.1 Agregar el botón flotante de información en `resources/js/Pages/Admin/POS/Partials/ProductCard.vue` con `@click.stop`
- [x] 2.2 Conectar la emisión del evento `show-detail` en `ProductCatalog.vue` e `Index.vue`
- [x] 2.3 Permitir la adición al carrito directamente desde el modal invocando `addItem`

## 3. Verification

- [x] 3.1 Verificar en `/admin/consumption-requests/create` la apertura del modal al hacer clic en el botón de información
- [x] 3.2 Validar que hacer clic en la tarjeta fuera del botón de info mantenga su comportamiento de agregar al pedido
- [x] 3.3 Validar que el modal sea totalmente responsivo (móvil y desktop) y soporte dark mode
