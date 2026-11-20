# Tasks

## 1. Ajuste de Escala del Logotipo y Cabecera Corporativa

- [x] 1.1 Configurar dimensiones y proporciones de visualización del logo corporativo en `order-receipt-pdf.blade.php` (`width: 140px; height: auto; max-height: 55px;`) y verificar que mantenga su relación de aspecto sin distorsión en DomPDF.
- [x] 1.2 Reestructurar la celda derecha de la cabecera en una ficha técnica con número de reserva, badge de estado, fecha completa de emisión y operador responsable, verificando que no existan espacios en blanco vacíos.

## 2. Reorganización de Tarjetas de Cliente, Entrega y Productos

- [x] 2.1 Refactorizar la tabla `info-grid` en tarjetas gemelas de Cliente y Entrega con acentos `#0f766e`, verificando legibilidad y jerarquía de datos.
- [x] 2.2 Reorganizar cada `.item-card` en dos columnas (especificaciones a la izquierda y referencia/fotografías a la derecha), verificando que si no hay foto se expanda al ancho completo.
- [x] 2.3 Validar que todos los tokens de color (`#0f766e`, `#f8fafc`, `#e2e8f0`, `#1e293b`) permanezcan intactos y verificar visualmente la comanda en `/admin/orders/{id}/receipt-pdf`.
