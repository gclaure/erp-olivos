# consumption-requests-table-horizontal-scroll Specification

## Purpose

Garantiza la visualización íntegra y navegación lateral ergonómica en la tabla de solicitudes de consumo mediante scrollbar visible, columna de acciones sticky y barra de asistencia superior permanente.

## Requirements

### Requirement: Ancho mínimo y contenedor con scroll horizontal visible
La tabla de solicitudes de consumo en vista de escritorio SHALL contar con un ancho mínimo estricto de `1180px` y un contenedor con scrollbar horizontal permanente `.table-scrollbar-visible` de 10px de altura con contraste óptico en modo claro y oscuro.

#### Scenario: Visualización en resoluciones de 13 pulgadas o portátiles
- **WHEN** un usuario accede a `/admin/consumption-requests` en una pantalla con viewport disponible menor a 1280px
- **THEN** la tabla mantiene su ancho mínimo de 1180px sin deformar las columnas y presenta una barra de scroll horizontal visible y manipulable en la parte inferior del contenedor.

### Requirement: Columna de acciones sticky a la derecha
La columna de `Acciones` ("Ver Detalle") de la tabla de solicitudes de consumo SHALL permanecer fijada a la derecha (`sticky right-0`) con fondo opaco y sombra separadora mientras el usuario se desplaza lateralmente.

#### Scenario: Desplazamiento horizontal en la tabla
- **WHEN** el usuario desplaza la tabla horizontalmente hacia la izquierda o derecha
- **THEN** la columna de `Acciones` permanece permanentemente visible sobre el extremo derecho sin transparentar los contenidos de las celdas que pasan por debajo.

### Requirement: Barra de navegación horizontal sticky permanente
La vista SHALL incluir una barra superior de asistencia con botones de navegación lateral (`‹` y `›`) con posicionamiento `sticky top-0 z-30` que permanezca visible en todo momento mientras se realiza scroll vertical a lo largo de las solicitudes.

#### Scenario: Uso de botones de navegación asistida en cualquier fila
- **WHEN** el usuario realiza scroll vertical hacia abajo explorando las solicitudes de consumo y hace clic en el botón `chevron_right` o `chevron_left`
- **THEN** la barra de navegación se mantiene anclada en el borde superior del viewport y desplaza suavemente la tabla en la dirección seleccionada sin requerir que el usuario suba al inicio de la página.
