# Proposal

## Why

El comprobante técnico en PDF de reservas de pedidos (`/admin/orders/{id}/receipt-pdf`) presenta problemas de legibilidad y balance visual: el logotipo corporativo se renderiza distorsionado y excesivamente reducido debido a restricciones rígidas de dimensiones incompatibles con DomPDF, y la sección superior derecha queda desbalanceada y vacía. Además, en los ítems solicitados las fotografías de muestra y referencias quedan desconectadas de las especificaciones técnicas del producto.

Este cambio rediseña la estructura visual del PDF bajo las directrices del skill `frontend-design`, manteniendo rigurosamente la paleta de colores del comprobante (`#0f766e`, `#f8fafc`, `#e2e8f0`, etc.), garantizando nitidez en el logotipo, equilibrio simétrico en la cabecera y una maquetación clara de especificaciones y referencias fotográficas en cada producto.

## What Changes

- **Optimización y escala natural del logotipo:** Asignar dimensiones y proporciones naturales a `logo-light.png` (`width: 140px; height: auto; max-height: 55px;`) asegurando nitidez sin deformación por parte de DomPDF.
- **Cabecera técnica balanceada (Ficha de Control):** Llenar el vacío superior derecho organizando la cabecera en un panel de doble columna: a la izquierda datos corporativos y sucursal; a la derecha folio destacado (`#XXXXX`), badge de estado (`REGISTRADO` / `ANULADO`), fecha/hora de emisión y operador que registró.
- **Tarjetas gemelas de Cliente y Entrega:** Reestructurar los metadatos en dos tarjetas con acento lateral sobrio, destacando el cliente, contacto y la logística programada (fecha en texto completo, hora, modalidad y dirección).
- **Layout de producto en 2 columnas (70% / 30%):** Reorganizar cada producto solicitado para que las especificaciones, decoraciones y observaciones ocupen la columna izquierda, mientras la nota de referencia y las imágenes fotográficas se ubiquen alineadas en la columna derecha.
- **Preservación estricta de la paleta de colores:** No alterar ninguna tonalidad ni token de color actual del documento (`#0f766e`, `#f8fafc`, `#e2e8f0`, `#1e293b`, etc.).

## Capabilities

### New Capabilities
- `orders/receipt-pdf-layout`: Estructura visual equilibrada y legible para la comanda técnica de reservas en PDF, optimizando logotipo, cabecera informativa, trazabilidad operativa y referencias de productos.

### Modified Capabilities
<!-- None -->

## Impact

- **Plantilla Blade:** `resources/views/exports/orders/order-receipt-pdf.blade.php`.
- **Ruta afectada:** `GET /admin/orders/{id}/receipt-pdf`.
- **No afecta la base de datos ni modelos Eloquent.**
