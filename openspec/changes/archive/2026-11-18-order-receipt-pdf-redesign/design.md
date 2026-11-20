# Design

## Context

El PDF de comanda de pedidos (`order-receipt-pdf.blade.php`) es procesado por `barryvdh/laravel-dompdf` (motor DomPDF en PHP). DomPDF tiene limitaciones con propiedades CSS modernas como `flexbox`, `grid` y `object-fit: contain`. Para lograr un layout predecible, simétrico y nítido, se requiere maquetación basada en tablas (`<table>`) con anchos porcentuales fijos y reglas CSS inline/internas compatibles con DomPDF.

## Goals / Non-Goals

**Goals:**
- Ajustar el renderizado del logo corporativo a su proporción nativa (1240×621 px), definiendo un ancho de 140px y alto automático (`height: auto; max-height: 55px;`).
- Transformar la cabecera en dos columnas balanceadas (55% izquierda para branding/sucursal, 45% derecha para folio, estado y trazabilidad de emisión).
- Estructurar el card de producto en dos columnas internas (`68%` izquierda para specs/decoración/observaciones, `32%` derecha para referencia fotográfica enmarcada).
- Mantener estrictamente la paleta existente `#0f766e` y tokens neutros sin alteración cromática.

**Non-Goals:**
- No modificar el backend, controladores, consultas Eloquent ni rutas de pedidos.
- No alterar otros formatos de impresión de ventas o consumo de almacén.

## Decisions

### 1. Cabecera basada en tabla de 2 columnas balanceadas
- **Decisión:** Agrupar en la celda derecha de la cabecera el número de reserva, badge de estado, fecha completa y operador de emisión.
- **Alternativa descartada:** Cabecera de 3 columnas (logo centro, empresa izquierda, folio derecha), descartada porque comprime innecesariamente el espacio horizontal en hojas A4 estándar cuando el nombre comercial es largo.

### 2. Estructura interna de producto en dos columnas
- **Decisión:** Implementar una sub-tabla dentro de cada `.item-card`:
  - `td.item-specs-col` (68%): título, especificaciones (masas, rellenos, alto), decoración y notas operativas.
  - `td.item-ref-col` (32%): contenedor de referencia con badge `REFERENCIA: TAL CUAL IMAGEN` y foto miniatura centrada de 85×85 px. Si no hay fotos, la columna izquierda se expande al 100%.
- **Alternativa descartada:** Dejar las fotos apiladas al pie del producto, lo que creaba vacíos verticales y desalineación.

### 3. Tratamiento de imagen para DomPDF
- **Decisión:** Continuar cargando la imagen local en Base64 `data:image/png;base64,...` para evitar problemas de resolución de rutas o acceso de red en sandbox, aplicando ancho explícito en píxeles.

## Risks / Trade-offs

- **[Riesgo: Múltiples fotos por ítem desbordan la celda derecha]** → **Mitigación:** Si hay más de una foto, se muestran en una cuadrícula compacta de 2 por fila (42×42 px cada una) o se limita la altura del contenedor con `overflow: hidden`.
