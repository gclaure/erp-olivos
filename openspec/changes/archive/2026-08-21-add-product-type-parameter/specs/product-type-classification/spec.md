## ADDED Requirements

### Requirement: Clasificación de tipos de producto
El sistema SHALL permitir clasificar cada producto con un atributo `type` obligatorio, cuyos valores permitidos MUST ser `materia_prima` (Materia Prima) e `insumo` (Insumo no inventariable).

#### Scenario: Creación exitosa de producto como Materia Prima
- **WHEN** el usuario crea un producto con `type = 'materia_prima'` y proporciona un `min_stock >= 0`
- **THEN** el sistema guarda el producto exitosamente
- **AND** habilita el control de stock e inventario para dicho producto

#### Scenario: Creación exitosa de producto como Insumo
- **WHEN** el usuario crea un producto con `type = 'insumo'`
- **THEN** el sistema guarda el producto exitosamente
- **AND** no exige valor para `min_stock`

#### Scenario: Migración de productos existentes
- **WHEN** se ejecuta la migración de base de datos
- **THEN** todos los registros existentes en la tabla `products` MUST ser configurados con `type = 'materia_prima'`

### Requirement: Código de producto opcional y autogeneración de SKU
El sistema SHALL permitir que el campo `code` sea opcional en la creación de productos (tanto vía formulario web como importación Excel). Si el usuario no proporciona un código, el sistema MUST generar un SKU único automáticamente.

#### Scenario: Creación de producto sin código manual
- **WHEN** se crea un producto dejando el campo `code` vacío o nulo
- **THEN** el sistema genera un código SKU con el formato estándar basado en la categoría, nombre y año
- **AND** asigna dicho SKU antes de persistir el registro

#### Scenario: Creación de producto con código manual
- **WHEN** se crea un producto proporcionando un `code` explícito
- **THEN** el sistema respeta el código ingresado previa validación de unicidad

### Requirement: Soporte de tipo y autogeneración en plantilla de importación Excel
El sistema SHALL incluir la columna `tipo` en la plantilla descargable `ProductTemplateExport` y procesarla en `ProductImport`. Si la columna viene vacía, MUST asignar por defecto `materia_prima`. Si la columna `codigo_producto` viene vacía, MUST autogenerar el SKU para cada fila.

#### Scenario: Descarga de plantilla con nueva columna
- **WHEN** el usuario descarga la plantilla de importación de productos
- **THEN** el archivo Excel contiene la cabecera `tipo` junto con las demás columnas requeridas

#### Scenario: Importación de archivo Excel con insumos y códigos vacíos
- **WHEN** el usuario importa un archivo Excel con filas que tienen `tipo = 'INSUMO'` y sin `codigo_producto`
- **THEN** el sistema genera automáticamente el SKU para cada producto
- **AND** guarda los productos asignando su tipo respectivo (`insumo` o `materia_prima`)

### Requirement: Validación condicional de stock mínimo
El sistema SHALL exigir que `min_stock` sea un valor decimal mayor o igual a 0 cuando el producto sea de tipo `materia_prima`, y SHALL tratar `min_stock` como opcional o no aplicable cuando el producto sea de tipo `insumo`.

#### Scenario: Intento de guardar Materia Prima sin stock mínimo
- **WHEN** el usuario intenta crear o actualizar un producto con `type = 'materia_prima'` dejando `min_stock` vacío o nulo
- **THEN** el sistema MUST rechazar la petición con un error de validación indicando que el stock mínimo es obligatorio para materia prima

#### Scenario: Guardar Insumo sin stock mínimo
- **WHEN** el usuario intenta crear o actualizar un producto con `type = 'insumo'` sin indicar `min_stock`
- **THEN** el sistema MUST aceptar la petición y asignar `0` o valor nulo al stock mínimo

### Requirement: Exclusión de Kardex y Stock en Compras para Insumos
El sistema SHALL omitir el registro en `stocks` y en el `kardex` cuando se compre un producto clasificado como `insumo`, registrando únicamente el detalle de la compra (`purchase_details`) y la cuenta por pagar correspondiente.

#### Scenario: Registro de compra de Materia Prima
- **WHEN** se registra una compra que contiene un producto con `type = 'materia_prima'`
- **THEN** el sistema registra el detalle de la compra, actualiza el `stock` en el almacén de destino y registra el movimiento de entrada en `kardex`

#### Scenario: Registro de compra de Insumo
- **WHEN** se registra una compra que contiene un producto con `type = 'insumo'`
- **THEN** el sistema registra el detalle de la compra y la cuenta por pagar
- **AND** MUST NOT registrar ni modificar existencias en la tabla `stocks`
- **AND** MUST NOT registrar ningún movimiento en la tabla `kardex`
