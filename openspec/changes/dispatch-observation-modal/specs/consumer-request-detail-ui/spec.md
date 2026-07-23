## ADDED Requirements

### Requirement: Campo de observación opcional en modal de despacho

El sistema SHALL mostrar un campo de texto libre (textarea) dentro del modal SweetAlert de confirmación de despacho. El campo es opcional. El usuario Almacén SHALL poder escribir una observación general que se envíe al backend junto con las cantidades despachadas.

#### Scenario: Almacén escribe observación al despachar

- **WHEN** el Almacén presiona "Confirmar Despacho" y escribe una observación en el textarea del modal
- **AND** confirma el despacho
- **THEN** el sistema MUST enviar `dispatch_observation: "texto"` en el POST al backend
- **AND** la observación MUST guardarse en `consumption_request.dispatch_observation`

#### Scenario: Almacén despacha sin observación

- **WHEN** el Almacén presiona "Confirmar Despacho" sin escribir observación
- **AND** confirma el despacho
- **THEN** el sistema MUST enviar `dispatch_observation: ""` (cadena vacía)
- **AND** el backend MUST procesar el despacho sin errores

#### Scenario: Observación limitada a 500 caracteres

- **WHEN** el Almacén escribe más de 500 caracteres en el campo de observación
- **THEN** el sistema MUST truncar el texto a 500 caracteres antes de enviarlo

#### Scenario: Observación del modal es independiente de observaciones por ítem

- **WHEN** el Almacén tiene observaciones por ítem en los textareas del formulario
- **AND** escribe una observación en el modal de confirmación
- **THEN** el sistema MUST enviar ambas (`observations` por ítem y `dispatch_observation` del modal)
- **AND** ambas MUST almacenarse en columnas separadas
