# Proposal

## Why

En pantallas de laptop (13 pulgadas en adelante, viewport de 1280px a 1440px), la tabla de catálogo de productos en `/admin/products` se corta horizontalmente después de la columna "EPQ.", dejando las columnas "Und.", "Status" y "Acciones" (Editar y Eliminar) fuera del campo visual. Además, debido a la falta de definición de altura en las reglas de scrollbar para `overflow-x-auto`, el usuario no puede visualizar ni utilizar una barra de scroll horizontal visible para acceder a dichas acciones.

## What Changes

- **Scrollbar Horizontal Visible y Funcional**: Habilitar en la tabla de productos y estilos globales de scrollbar un grosor (`height: 6px`) y estilos táctiles/visuales visibles y arrastrables para contenedores con `overflow-x-auto`.
- **Columna de Acciones Fija (Sticky)**: Fijar la columna de "Acciones" a la derecha (`sticky right-0`) con fondo opaco y sombra de elevación sutil para que los botones de "Editar" y "Eliminar" permanezcan permanentemente visibles e interactivos, sin importar la posición de desplazamiento horizontal.
- **Ancho Mínimo de Tabla Estructurado**: Establecer `min-w-[1020px]` en la tabla desktop para asegurar que todas las 8 columnas mantengan su legibilidad y proporciones sin comprimirse indebidamente.

## Capabilities

### New Capabilities
- `product-table-horizontal-scroll`: Requisitos y escenarios de usabilidad para la visualización de la tabla de productos en pantallas de 13 pulgadas y laptops, garantizando scrollbar horizontal visible y columna de acciones persistente.

### Modified Capabilities
<!-- Ninguna especificación existente cambia sus requerimientos de negocio -->

## Impact

- Frontend: `resources/js/Pages/Admin/Product/Index.vue` y utilidades de scrollbar en `resources/css/app.css`.
- Sin cambios en controladores PHP ni backend.
