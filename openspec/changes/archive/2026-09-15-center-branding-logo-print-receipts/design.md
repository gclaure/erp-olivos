## Context

En `receipt.blade.php`, la cabecera anterior agrupaba el logo y el texto de la empresa en una sola celda a la izquierda (`.branding-left` con tabla anidada), forzando al logo a un tamaño reducido de `85px` que resultaba difícil de apreciar. Al cambiar a un esquema de 3 columnas (`branding-left`, `branding-center`, `doc-info-right`), el logo se coloca en el centro con un tamaño generoso (`max-width: 145px; max-height: 70px;`), equilibrando estéticamente la página.

## Goals / Non-Goals

**Goals:**
- Actualizar la tabla `.header-table` en `receipt.blade.php` a 3 columnas:
  - `.branding-left` (36% ancho, alineación izquierda para datos de sucursal y empresa).
  - `.branding-center` (30% ancho, alineación centrada y vertical media para el logotipo).
  - `.doc-info-right` (34% ancho, alineación derecha para metadatos del documento).
- Incrementar las dimensiones máximas del logo a `max-width: 145px; max-height: 70px;` con `object-fit: contain`.
- Mantener la compatibilidad total con el renderizador DomPDF / vistas de impresión.

**Non-Goals:**
- Modificar el contenido del cuerpo o las tablas de productos y firmas del recibo.
- Cambiar la paleta de colores verde olivo corporativa.

## Decisions

1. **Estructura de 3 celdas directas en lugar de anidamiento**:
   - *Decisión*: Reemplazar la sub-tabla dentro de `.branding-left` por tres celdas `<td>` directas en la fila principal del encabezado.
   - *Razón*: DomPDF maneja mucho mejor y sin solapamientos las tablas directas con porcentajes definidos.

2. **Dimensiones del Logo**:
   - *Decisión*: `max-width: 145px; max-height: 70px;`. Permite apreciar claramente los detalles del imagotipo y la tipografía de Los Olivos manteniendo las proporciones 2:1 del archivo original.

## Risks / Trade-offs

- **[Riesgo]** Salto de línea no deseado en textos de la sucursal si el ancho es muy angosto.
  - *Mitigación*: Se asigna el 36% a la izquierda y se mantiene tipografía compacta (7.5pt a 10pt) con interlineado adecuado.
