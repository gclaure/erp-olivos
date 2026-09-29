# responsive-table-scroll-helper-visibility Specification

## Purpose

Define la visibilidad responsiva de la barra de asistencia y botones de desplazamiento lateral para tablas contables y de catálogo, ocultándola en pantallas mayores a 15 pulgadas y en celulares.

## Requirements

### Requirement: Ocultación de barra de asistencia en pantallas de 15 pulgadas o superiores
La barra de asistencia y botones de navegación horizontal SHALL permanecer oculta (`2xl:hidden`) en pantallas de 15.6 pulgadas o superiores (ancho de viewport >= 1536px) donde todas las columnas caben sin desbordamiento.

#### Scenario: Visualización en monitor de escritorio o laptop de 15.6 pulgadas
- **WHEN** un usuario accede a las tablas de Productos, Kardex o Solicitudes de Consumo en un viewport con ancho mayor o igual a 1536px
- **THEN** la barra superior de asistencia con botones de desplazamiento no se renderiza en la pantalla, mostrando una interfaz limpia y sin redundancia.

### Requirement: Ocultación de barra de asistencia en pantallas móviles
La barra de asistencia y botones de navegación horizontal SHALL permanecer oculta (`hidden`) en dispositivos móviles (ancho de viewport < 768px), donde las tablas operan bajo el diseño de tarjetas apiladas.

#### Scenario: Visualización en smartphone
- **WHEN** un usuario accede a las vistas en un viewport con ancho menor a 768px
- **THEN** la barra superior de asistencia con botones de desplazamiento permanece oculta.

### Requirement: Activación de barra de asistencia en laptops y pantallas intermedias
La barra de asistencia y botones de navegación horizontal SHALL mostrarse de manera activa y sticky (`md:flex 2xl:hidden`) únicamente en viewports entre 768px y 1535px (típico de laptops de 13 y 14 pulgadas).

#### Scenario: Visualización en laptop de 13 o 14 pulgadas
- **WHEN** un usuario accede a las vistas en un viewport con ancho entre 768px y 1535px
- **THEN** la barra superior de asistencia se muestra en posición sticky top-0 con los botones de desplazamiento a la izquierda y derecha plenamente operativos.
