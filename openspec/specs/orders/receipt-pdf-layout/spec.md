# orders/receipt-pdf-layout Specification

## Purpose
Define la estructura visual, proporciones tipográficas, renderizado de logotipo y balance de columnas en la comanda técnica de reservas en PDF.

## Requirements

### Requirement: Logotipo corporativo con proporciones naturales
El sistema SHALL renderizar el logotipo corporativo en la cabecera del PDF con un ancho máximo de 140px y altura automática proporcional, evitando distorsiones o reducciones excesivas producidas por DomPDF.

#### Scenario: Visualización nítida del logotipo
- **WHEN** se genera el PDF de una reserva en `/admin/orders/{id}/receipt-pdf`
- **THEN** el logotipo corporativo se muestra legible con proporciones horizontales nativas sin recortarse ni comprimirse.

### Requirement: Cabecera con balance simétrico y ficha de control
El sistema SHALL estructurar la cabecera en un panel de dos secciones equilibradas: a la izquierda datos institucionales y de sucursal; a la derecha el bloque de folio de reserva, badge de estado, fecha/hora de emisión y operador responsable.

#### Scenario: Distribución sin vacíos en la cabecera derecha
- **WHEN** un usuario visualiza o imprime el comprobante PDF
- **THEN** la sección superior derecha contiene el folio `#XXXXX`, el badge del estado operativo (`REGISTRADO` o `ANULADO`), la fecha de emisión completa y el nombre del usuario emisor, eliminando espacios vacíos.

### Requirement: Organización en dos columnas de cada producto solicitado
El sistema SHALL maquetar cada producto solicitado en una cuadrícula interna de dos columnas: columna izquierda (70%) para especificaciones técnicas, masas, rellenos, decoración y observaciones; columna derecha (30%) para la etiqueta de referencia y la muestra fotográfica adjunta.

#### Scenario: Producto con fotografía y nota de referencia
- **WHEN** un producto de la reserva contiene especificaciones y fotografías adjuntas
- **THEN** las especificaciones se alinean a la izquierda y la muestra fotográfica con su etiqueta de referencia se muestra enmarcada y alineada a la derecha de la tarjeta del ítem.

#### Scenario: Producto sin fotografía adjunta
- **WHEN** un producto no contiene archivos fotográficos adjuntos
- **THEN** la columna de especificaciones aprovecha el ancho completo de la tarjeta sin dejar espacios en blanco desbalanceados.

### Requirement: Preservación de la paleta cromática existente
El sistema SHALL mantener los colores de la comanda técnica (`#0f766e`, `#f8fafc`, `#e2e8f0`, `#1e293b`, etc.) sin introducir nuevas familias de color no autorizadas.

#### Scenario: Fidelidad de color en el comprobante
- **WHEN** se compila y genera el comprobante
- **THEN** los elementos decorativos, bordes y badges conservan la paleta verde azulada (teal) y neutros originales.
