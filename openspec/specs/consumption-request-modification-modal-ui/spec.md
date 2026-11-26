# consumption-request-modification-modal-ui Specification

## Purpose
Proporciona una interfaz gráfica simplificada y directa en 3 pasos para que el almacenero solicite la sustitución de un producto sin cálculos matemáticos complejos ni ambigüedades sobre qué producto retorna y cuál se entrega.

## Requirements

### Requirement: Step 1 Physical Status of Original Product
El modal SHALL presentar el producto original en el Paso 1 y consultar directamente la ubicación física de las unidades despachadas mediante opciones sencillas: "Sigue en almacén (no salió)" o "El solicitante lo devolverá a almacén". El sistema SHALL calcular internamente las cantidades correspondientes sin exigir al usuario digitar sumas manuales en el caso estándar.

#### Scenario: Original product remained in warehouse
- **WHEN** el usuario selecciona la opción "Sigue en almacén"
- **THEN** el sistema asigna automáticamente la totalidad despachada a `quantity_prepared` y 0 a `quantity_physically_delivered`

#### Scenario: Original product was taken by requester
- **WHEN** el usuario selecciona la opción "El solicitante lo devolverá a almacén"
- **THEN** el sistema asigna 0 a `quantity_prepared` y la totalidad despachada a `quantity_physically_delivered`, indicando que se esperará su retorno físico

### Requirement: Step 2 New Substitute Product Selection
El modal SHALL permitir en el Paso 2 buscar y seleccionar el nuevo insumo a entregar. Al seleccionar el nuevo producto, el sistema SHALL ocultar la lista desplegable y exhibir una tarjeta de confirmación clara con el nombre del nuevo producto, su stock actual disponible y un input directo para la cantidad a entregar.

#### Scenario: Selecting new substitute product
- **WHEN** el usuario selecciona un producto del catálogo de sustitutos
- **THEN** se muestra la ficha del nuevo producto con su stock y la cantidad sugerida igual a la original para ajuste si es necesario

### Requirement: Step 3 Clear Reason Input
El modal SHALL solicitar en el Paso 3 el motivo del cambio con un texto de orientación claro antes de habilitar el botón de envío.

#### Scenario: Validating completion of 3 steps
- **WHEN** el usuario ha indicado la situación física, ha seleccionado un nuevo producto diferente o con cantidad modificada, y ha ingresado al menos 3 caracteres en el motivo
- **THEN** el botón "Solicitar Sustitución" se habilita para registrar la propuesta
