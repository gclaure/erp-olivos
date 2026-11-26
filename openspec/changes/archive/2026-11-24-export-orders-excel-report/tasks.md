# Tasks

## 1. Backend: Clase de Exportación y Endpoint

- [x] 1.1 Crear `app/Exports/OrdersExport.php` implementando `FromQuery`, `WithHeadings`, `WithMapping`, `WithStyles`, `ShouldAutoSize`, `WithEvents` y `WithChunkReading` con formato numérico y fila de totales al pie.
- [x] 1.2 Implementar el método `exportExcel(Request $request)` en `app/Http/Controllers/Admin/OrderController.php` reutilizando los mismos filtros aplicados en `index()`.
- [x] 1.3 Registrar la ruta `admin.orders.export-excel` en `routes/admin.php` protegida con el middleware `permission:manage-orders`.

## 2. Frontend: Botón de Exportación y Conexión de Filtros

- [x] 2.1 Integrar el botón "Exportar Excel" en el Header de `resources/js/Pages/Admin/Orders/Index.vue` con iconografía y diseño distinguido para modo claro y oscuro (`frontend-design`).
- [x] 2.2 Implementar la función reactiva `exportExcel` en `Index.vue` que tome los `localFilters` activos y ejecute la descarga directa.
- [x] 2.3 Compilar assets con `npm run build` y verificar la validez sintáctica de PHP (`php -l`) y Vue.
