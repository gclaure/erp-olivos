## Why

El logotipo oficial `logo-light.png` tiene un formato apaisado (1240 × 621 px) que, al ubicarse en un espacio reducido en la esquina izquierda junto a múltiples líneas de texto de sucursal, queda comprimido y con poca visibilidad. Reestructurar la cabecera del comprobante de consumo / media (`receipt.blade.php`) a un esquema de 3 columnas (Empresa/Sucursal a la izquierda, Logotipo ampliado y centrado en el medio, y Datos del Documento a la derecha) garantiza máxima nitidez, equilibrio y elegancia visual sin desbordar el alto de la página.

## What Changes

- **Rediseño de Cabecera de 3 Columnas en `receipt.blade.php`**:
  - **Columna Izquierda (36%)**: Datos de la empresa (Nombre comercial, Razón social, Sucursal, Dirección, Teléfono).
  - **Columna Central (30%)**: Logotipo `logo-light.png` centrado, con dimensiones ampliadas (`max-width: 145px; max-height: 70px`).
  - **Columna Derecha (34%)**: Tipo de comprobante, Número correlativo, Fecha de emisión y Badge de estado.
- **Ajustes de Estilos CSS en la plantilla de impresión**:
  - Eliminación del anidamiento de tablas comprimidas en `.branding-left`.
  - Configuración simétrica y alineación vertical media para centrado perfecto.

## Capabilities

### New Capabilities
- `centered-receipt-branding`: Diseño de cabecera tripartita balanceada con logotipo centrado para documentos impresos y PDFs corporativos.

### Modified Capabilities
- `consumption-request-pdf-redesign`: Actualización de la estructura de cabecera de la plantilla de recibo de consumo.

## Impact

- **Vistas Blade de Impresión**: `resources/views/admin/consumption-requests/receipt.blade.php`.
