## ADDED Requirements

### Requirement: Bloqueo del buscador de compras sin almacén de destino
El sistema SHALL mantener inactivo y deshabilitado el buscador asíncrono de productos en el formulario de creación de compras (`/admin/purchases/create`) mientras el usuario no haya seleccionado un almacén de destino (`form.warehouse_id`).

#### Scenario: Usuario accede al formulario sin seleccionar almacén
- **WHEN** el usuario carga la vista de creación de compra y el campo de almacén está vacío
- **THEN** el input del buscador de productos se muestra deshabilitado (`disabled`) con estilo visual inactivo y mensaje guía indicando que debe seleccionar primero un almacén

#### Scenario: Usuario selecciona un almacén de destino
- **WHEN** el usuario selecciona un almacén en el selector de almacenes
- **THEN** el input del buscador de productos se habilita de inmediato para permitir la búsqueda

### Requirement: Filtrado estricto a Materia Prima en buscador de compras
El endpoint de búsqueda asíncrona de productos para compras (`ApiSelectController@products`) SHALL permitir filtrar por tipo de producto y, cuando se use en el contexto de compras, MUST devolver exclusivamente productos con `type = 'materia_prima'`, excluyendo los productos de tipo `insumo`.

#### Scenario: Búsqueda de productos en formulario de compra
- **WHEN** el usuario escribe una búsqueda en el buscador de compras
- **THEN** el sistema envía la solicitud con el filtro de tipo `materia_prima`
- **AND** la lista de resultados contiene únicamente materias primas, excluyendo insumos

### Requirement: Visualización de stock disponible por almacén en resultados de compra
El endpoint de búsqueda de productos SHALL calcular el stock disponible en el almacén especificado por el parámetro `warehouse_id`, y la interfaz de compras SHALL mostrar dicho stock en cada elemento del dropdown de resultados.

#### Scenario: Visualización de producto con stock en el almacén seleccionado
- **WHEN** se listan los resultados de búsqueda para un almacén seleccionado
- **THEN** cada ítem muestra el nombre, código, precio referencial y el stock disponible numérico junto a su unidad de medida en dicho almacén
- **AND** resalta visualmente si el stock es cero o positivo
