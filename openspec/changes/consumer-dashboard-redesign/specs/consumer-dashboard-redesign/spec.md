# consumer-dashboard-redesign

Especificación para el rediseño ejecutivo de "Mi Panel de Consumo".

## Requirements

### Requirement: Hero Header de Mando Operativo
La vista SHALL mostrar un encabezado ejecutivo con saludo personalizado, chip de área operativa, selector de período mes/año estilizado y botón de acción principal para crear una nueva solicitud de consumo.

#### Scenario: Acceso rápido y selección de período
- **WHEN** el usuario ingresa al panel de consumo
- **THEN** visualiza su área operativa y puede cambiar el mes/año o hacer clic en "+ Nueva Solicitud" para ir directo al catálogo de pedidos

### Requirement: Bento KPI Grid
La vista SHALL desplegar 4 métricas clave (Total Mes, Pendientes, Observadas, Finalizadas) con tarjetas Bento que incluyan badges de estado, acentos cromáticos e indicadores visuales de alerta si hay solicitudes observadas.

#### Scenario: Visualización de alertas de solicitudes observadas
- **WHEN** el usuario tiene 1 o más solicitudes observadas
- **THEN** la tarjeta de observadas se resalta con acento carmesí y badge de atención requerida

### Requirement: Gráficos de Análisis Visual Pro
La vista SHALL renderizar gráficos Chart.js estilizados con curvas de tendencia suavizadas y degradados translúcidos, donut de categorías con contador central y ranking de insumos más solicitados.

#### Scenario: Renderizado responsivo de gráficos
- **WHEN** la vista se carga en modo claro u oscuro en cualquier pantalla (móvil, tablet, desktop)
- **THEN** los gráficos se adaptan a la paleta de color y dimensiones del contenedor
