# Consumer Consumption Catalog UI

Delta spec para el rol Consumidor en el catálogo de solicitudes de consumo.

## ADDED Requirements

### Requirement: Consumidor selecciona cantidad sin exponer stock

En la vista de creación de solicitudes de consumo en pantallas menores a `lg`, un usuario con rol Consumidor SHALL poder seleccionar la cantidad a solicitar mediante el stepper de la tarjeta, sin que se le muestre el número de stock disponible como límite visible.

#### Scenario: Consumidor ajusta cantidad en el stepper

- **WHEN** un Consumidor en pantalla menor a `lg` usa el stepper de una tarjeta disponible
- **THEN** puede ajustar la cantidad a solicitar dentro del rango permitido
- **AND** la tarjeta MUST NOT mostrar el número de stock disponible ni en el stepper ni en su tooltip

#### Scenario: Consumidor intenta superar el stock

- **WHEN** un Consumidor intenta superar el stock disponible con `+` o escribiendo en el input
- **THEN** la cantidad se recorta al máximo permitido en silencio
- **AND** la tarjeta MUST NOT indicar el número de unidades del límite
