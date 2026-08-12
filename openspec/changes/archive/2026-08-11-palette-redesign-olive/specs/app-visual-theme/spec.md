## ADDED Requirements

### Requirement: Paleta de identidad olivo en modo claro

El sistema SHALL adoptar en modo claro la paleta de identidad olivo/crema: acento primario `#73AC32` con variantes oscura `#3F6B20` y clara `#A8C97A`, fondo `#FAF9F5`, superficie `#FFFFFF`, crema `#F1EDE2`, borde `#E5E2D9`, texto `#080808`, texto secundario `#6B6B67`. Esta paleta MUST aplicarse tanto a los tokens semánticos de `app.css` como a las escalas Tailwind usadas por las clases de utilidad, sin requerir cambios en templates.

#### Scenario: Los tokens semánticos usan la nueva paleta en claro

- **WHEN** la app se muestra en modo claro y un componente usa los tokens `bg-app`, `bg-surface`, `primary-app`, `text-primary-app` o `text-secondary-app`
- **THEN** los colores MUST corresponder a la paleta olivo: fondo `#FAF9F5`, superficie `#FFFFFF`, acento `#73AC32`, texto `#080808` y texto secundario `#6B6B67`

#### Scenario: Las clases de utilidad de la escala zinc adoptan neutros cálidos

- **WHEN** un componente usa clases `bg-zinc-50`, `bg-zinc-100`, `border-zinc-200`, `text-zinc-500` o `text-zinc-900` en modo claro
- **THEN** MUST resolverse a los neutros cálidos de la nueva paleta (`#FAF9F5`, `#F1EDE2`, `#E5E2D9`, `#8A8880`/`#6B6B67`, `#080808`) sin modificar el template

#### Scenario: Las clases de utilidad indigo y primary usan verde en claro

- **WHEN** un componente usa clases `bg-indigo-600`, `text-indigo-600`, `border-indigo-500`, `ring-indigo-500`, `bg-primary-600` o `text-primary-500` en modo claro
- **THEN** MUST resolverse a la escala verde derivada del acento `#73AC32` sin modificar el template

### Requirement: Modo oscuro se preserva íntegro

El sistema MUST mantener el modo oscuro actual sin cambios visuales: estructura de superficies `secondary` (slate), acentos índigo y tonos de `zinc` oscuros. El bloque `.dark` de `app.css` y la escala `secondary` de `tailwind.config.js` MUST permanecer funcionalmente idénticos a los actuales.

#### Scenario: Estructura oscura sin cambios

- **WHEN** la app se muestra en modo oscuro y un componente usa `bg-secondary-900`, `bg-secondary-800`, `dark:bg-zinc-800`, `dark:bg-zinc-900`, `dark:border-secondary-700` o `dark:hover:bg-secondary-700`
- **THEN** los colores MUST ser idénticos a los del modo oscuro actual (slate `#171923`/`#222530`, zinc `#262626`/`#171717`, etc.)

#### Scenario: Acento índigo se conserva en modo oscuro

- **WHEN** la app se muestra en modo oscuro y un componente usa `dark:text-indigo-400`, `dark:bg-indigo-900` o `dark:bg-primary-900`
- **THEN** los colores MUST seguir siendo índigo (escala `#eef2ff`…`#1e1b4b`), no verde

#### Scenario: Valores claros no se filtran al modo oscuro

- **WHEN** la app se muestra en modo oscuro
- **THEN** ninguna clase `zinc-*`, `indigo-*` o `primary-*` MUST resolver a los valores cálidos/verdes del modo claro

### Requirement: Variables semánticas en formato RGB triplet

El sistema MUST mantener las variables de color semánticas en formato de triplets separados por espacios (p. ej. `115 172 50`) para que el patrón `rgb(var(--color-*) / <alpha-value>)` de Tailwind siga soportando modificadores de opacidad (`bg-primary/70`, etc.).

#### Scenario: Opacidad funcionando tras el cambio

- **WHEN** un componente usa una clase con modificador de opacidad sobre un token semántico (p. ej. `bg-primary-app/70`)
- **THEN** el color MUST renderizarse con la opacidad aplicada sin errores de compilación

### Requirement: Storefront adopta la nueva marca por default

El storefront público SHALL usar por defecto la nueva identidad: acento `#73AC32` sobre base `#080808`. Los defaults MUST actualizarse en el seeder de e-commerce y en el fallback del middleware de Inertia. Las tiendas con colores de marca guardados en base de datos MUST conservar sus valores (el cambio aplica solo a defaults).

#### Scenario: Nuevo default con acento verde

- **WHEN** una tienda no tiene `primary_color` configurado (o se crea una tienda nueva con el seeder)
- **THEN** el acento de marca MUST ser `#73AC32`

#### Scenario: Nuevo default con base negra

- **WHEN** una tienda no tiene `secondary_color` configurado
- **THEN** la base/fondo de marca MUST ser `#080808`

#### Scenario: Tiendas con marca guardada no se alteran

- **WHEN** una tienda tiene `primary_color`/`secondary_color` persistidos en base de datos
- **THEN** sus colores MUST permanecer sin cambios
