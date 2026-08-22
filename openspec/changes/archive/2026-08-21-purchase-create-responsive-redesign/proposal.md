## Why

La pantalla de creación de compras (`/admin/purchases/create`) presenta fallas ergonómicas severas en dispositivos móviles (smartphones 320px–430px) y tablets: el buscador y su lista de resultados desbordan la pantalla cortando columnas de precios, los textos de estado colisionan, los contenedores desperdician espacio y la tabla del carrito genera un scroll horizontal incómodo que dificulta la edición táctil de cantidades, costos y vencimientos en planta o almacén.

## What Changes

- **Buscador Asíncrono y Dropdown Responsivo**: Cabecera apilable en móvil (`flex-col sm:flex-row`), placeholder conciso y resultados estructurados en 2 filas jerárquicas para móviles (`< 640px`) y distribución horizontal en desktop (`≥ 640px`), garantizando que el stock y los precios nunca se corten.
- **Carrito de Compras con Patrón Dual**:
  - Vista Móvil (`md:hidden`): Tarjetas operativas individuales (**Mobile Stacked Cards**) con pares etiqueta-valor, selectores y controles táctiles de al menos 44px, selector de formato integrado, inputs centrados y subtotal por línea.
  - Vista Desktop (`hidden md:block`): Tabla tabular clásica estilizada y optimizada.
- **Formulario de Datos Básicos y Acciones**: Adaptación de grids a `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`, reducción de padding interno en móvil (`p-4 sm:p-6 lg:p-8`) y botones de acción apilables al 100% de ancho con `flex-col-reverse sm:flex-row`.
- **Barra de Totales Adaptada**: Panel de total general claro y accesible en pantallas táctiles.

## Capabilities

### New Capabilities
- `purchase-create-responsive-ui`: Especificación de interfaz adaptativa para el módulo de compras que cubre el comportamiento en móvil, tablet y escritorio con el patrón dual de tarjetas y tablas.

### Modified Capabilities

## Impact

- Frontend: `resources/js/Pages/Admin/Purchase/Create.vue` y componentes auxiliares.
- Sin cambios en el backend ni en las migraciones de base de datos.
