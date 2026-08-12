## ADDED Requirements

### Requirement: Logo de marca por variante según el fondo

El sistema SHALL mostrar la dupla de logos de marca según el fondo de cada superficie de fondo fijo en el admin, ignorando el logo subido por la empresa. La sidebar del Admin MUST mostrar siempre la variante de arte claro (`/img/logo-dark.png`) sobre su fondo oscuro fijo. El brand móvil del Login y el preview de Settings MUST mostrar siempre la variante de arte oscuro (`/img/logo-light.png`) sobre sus fondos claros fijos.

#### Scenario: Sidebar usa siempre la variante clara

- **WHEN** la sidebar del Admin se renderiza (en modo claro u oscuro)
- **THEN** MUST mostrar `/img/logo-dark.png`, sin importar el logo de empresa ni el modo

#### Scenario: Login usa siempre la variante oscura

- **WHEN** el brand móvil del Login se renderiza
- **THEN** MUST mostrar `/img/logo-light.png` sobre el panel `#ffffff`, sin importar el logo de empresa

#### Scenario: Preview de Settings usa siempre la variante oscura

- **WHEN** el preview del logo en Settings de Company se renderiza
- **THEN** MUST mostrar `/img/logo-light.png` sobre el fondo blanco, sin mostrar preview del archivo subido ni logo de empresa

#### Scenario: Logo de empresa ignorado en las superficies afectadas

- **WHEN** una empresa tiene `logo_url` configurado y se renderiza sidebar, login o preview de Settings
- **THEN** el logo de la empresa MUST NO mostrarse en esas superficies (solo la dupla)

#### Scenario: Fuera de alcance sin cambios

- **WHEN** se renderiza el storefront (`settings.logo_url`), blades de auth o PDFs/recibos (logo de empresa / `logo-inventory.jpg`)
- **THEN** el comportamiento MUST permanecer sin cambios
