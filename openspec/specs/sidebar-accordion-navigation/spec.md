# sidebar-accordion-navigation Specification

## Purpose
Proporciona un comportamiento de navegación tipo acordeón mutuamente excluyente en el menú lateral administrativo para optimizar el espacio vertical y la experiencia de usuario.

## Requirements

### Requirement: Exclusive Single Open Submenu
El menú lateral SHALL permitir que como máximo un submenú desplegable se encuentre abierto a la vez. Al interactuar con un menú cerrado, el sistema SHALL abrir dicho menú y cerrar de forma simultánea cualquier otro submenú que estuviera abierto.

#### Scenario: Opening a menu closes other open menus
- **WHEN** el usuario hace clic en el menú desplegable "Inventario" mientras "Consumos" se encuentra abierto
- **THEN** el sistema abre el menú "Inventario" y pliega automáticamente el menú "Consumos"

#### Scenario: Clicking active menu closes it
- **WHEN** el usuario hace clic sobre un menú desplegable que ya se encuentra abierto
- **THEN** el sistema pliega dicho menú dejando todos los menús cerrados

### Requirement: Active Route Auto-Expansion
Al navegar o recargar la aplicación en una ruta interna, el menú lateral SHALL identificar el grupo padre al que pertenece la ruta activa y desplegarlo de manera exclusiva para indicar visualmente el contexto de navegación al usuario.

#### Scenario: Page load with active child route
- **WHEN** la aplicación carga una página cuya ruta corresponde al submenú de "Consumos"
- **THEN** el menú "Consumos" se abre automáticamente y los demás grupos permanecen plegados

### Requirement: Olivos Visual Theme Alignment
Los elementos seleccionados, iconos activos y resaltados del menú lateral SHALL emplear exclusivamente la paleta oficial de Los Olivos (`primary-600` / `primary-700` y acentos `primary-400`), erradicando clases no permitidas como `indigo` o `violet`.

#### Scenario: Active item color rendering
- **WHEN** un enlace o menú se encuentra activo en el sidebar
- **THEN** su estilo de resaltado utiliza las clases de token `primary-600` y `primary-400/10` de Los Olivos
