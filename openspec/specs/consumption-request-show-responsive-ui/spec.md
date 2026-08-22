# consumption-request-show-responsive-ui

Especificación de interfaz moderna y diseño responsivo para el detalle de solicitudes de consumo interno.

## Requirements

### Requirement: Stepper del Ciclo de Vida y Header Ejecutivo
La vista SHALL renderizar un stepper visual que muestre las 4 etapas del pedido (Solicitado, Aprobado/Observado, Despachado, Entregado) con su estado activo/completado/alerta.

#### Scenario: Visualización del ciclo de vida
- **WHEN** un usuario consulta una solicitud en cualquier estado
- **THEN** el stepper refleja visualmente el punto exacto en el que se encuentra la solicitud con sus marcas de tiempo y responsables

### Requirement: Adaptabilidad Responsiva para Móvil y Tablet
La vista SHALL adaptarse a pantallas desde 320px hasta escritorios ultrawide mediante un diseño híbrido de tarjetas de alta densidad en móvil y tabla estructurada con sidebar sticky en escritorio.

#### Scenario: Visualización en móvil y tablet
- **WHEN** el usuario visualiza la solicitud en una pantalla menor a 1024px
- **THEN** los insumos se despliegan en tarjetas optimizadas con grid de métricas
- **AND** las acciones críticas están disponibles en una barra inferior flotante accesible con una sola mano

#### Scenario: Visualización en desktop
- **WHEN** el usuario visualiza la solicitud en pantallas de 1024px o superiores
- **THEN** se despliega una tabla completa con scroll suave y una columna lateral fija para la ficha operativa y acciones
