# App Visual Theme

Especificación del tema visual de la aplicación: paleta de identidad olivo/crema en modo claro, modo oscuro con identidad olivo, variables semánticas en formato RGB triplet para soporte de opacidad en Tailwind, y adopción de la nueva marca por default en el storefront.

## Requirement: Paleta de identidad olivo en modo claro

El sistema SHALL adoptar en modo claro la paleta de identidad olivo/crema: acento primario `#73AC32` con variantes oscura `#3F6B20` y clara `#A8C97A`, fondo `#FAF9F5`, superficie `#FFFFFF`, crema `#F1EDE2`, borde `#E5E2D9`, texto `#080808`, texto secundario `#6B6B67`. Esta paleta MUST aplicarse tanto a los tokens semánticos de `app.css` como a las escalas Tailwind usadas por las clases de utilidad, sin requerir cambios en templates.

### Scenario: Los tokens semánticos usan la nueva paleta en claro

- **WHEN** la app se muestra en modo claro y un componente usa los tokens `bg-app`, `bg-surface`, `primary-app`, `text-primary-app` o `text-secondary-app`
- **THEN** los colores MUST corresponder a la paleta olivo: fondo `#FAF9F5`, superficie `#FFFFFF`, acento `#73AC32`, texto `#080808` y texto secundario `#6B6B67`

### Scenario: Las clases de utilidad de la escala zinc adoptan neutros cálidos

- **WHEN** un componente usa clases `bg-zinc-50`, `bg-zinc-100`, `border-zinc-200`, `text-zinc-500` o `text-zinc-900` en modo claro
- **THEN** MUST resolverse a los neutros cálidos de la nueva paleta (`#FAF9F5`, `#F1EDE2`, `#E5E2D9`, `#8A8880`/`#6B6B67`, `#080808`) sin modificar el template

### Scenario: Las clases de utilidad indigo y primary usan verde en claro

- **WHEN** un componente usa clases `bg-indigo-600`, `text-indigo-600`, `border-indigo-500`, `ring-indigo-500`, `bg-primary-600` o `text-primary-500` en modo claro
- **THEN** MUST resolverse a la escala verde derivada del acento `#73AC32` sin modificar el template

## Requirement: Modo oscuro con identidad olivo

El sistema SHALL aplicar en modo oscuro la identidad olivo de marca: acento verde `#73AC32`, estructura de superficies verde-olivo derivada del negro `#080808`, texto principal crema `#F1EDE2`. La escala `secondary` y las ramas `zinc` del bloque `.dark` de `app.css` MUST resolver a la rampa olivo oscura (body `#151812`, superficies `#262B20`, bordes `#3C4335`, texto secundario `#8A9480`). Las ramas `indigo`/`primary` en modo oscuro MUST resolver a la misma escala verde del modo claro. El modo claro MUST permanecer sin cambios.

### Scenario: Estructura oscura con tonos olivo

- **WHEN** la app se muestra en modo oscuro y un componente usa `bg-secondary-900`, `bg-secondary-800`, `dark:bg-zinc-800`, `dark:border-secondary-700` o `dark:hover:bg-secondary-700`
- **THEN** los colores MUST resolver a la rampa olivo oscura: body `#151812`, superficies `#262B20`, bordes `#3C4335`, sin tonos azulados slate

### Scenario: Acento verde en modo oscuro

- **WHEN** la app se muestra en modo oscuro y un componente usa `dark:text-indigo-400`, `dark:bg-indigo-900`, `dark:bg-indigo-500`, `bg-primary-600` o `dark:bg-primary-900`
- **THEN** los colores MUST resolver a la escala verde olivo (`#8DBB52`, `#223C12`, `#73AC32`, `#5C8C28`, `#223C12`), no a índigo

### Scenario: Texto crema en modo oscuro

- **WHEN** la app se muestra en modo oscuro y un componente usa `dark:text-zinc-100`, `dark:text-zinc-200`, `text-secondary-50` o `text-primary-app`
- **THEN** el texto de alto énfasis MUST resolver a crema `#F1EDE2` y el texto secundario (`dark:text-secondary-400`, `dark:text-zinc-400`) a tonos olivo-claro (`#8A9480`, `#9BA68F`)

### Scenario: Valores claros no se filtran al modo oscuro

- **WHEN** la app se muestra en modo oscuro
- **THEN** ninguna clase `zinc-*`, `indigo-*` o `primary-*` MUST resolver a los valores cálidos del modo claro (`#FAF9F5`, `#F1EDE2`, etc.)

### Scenario: Modo claro no se ve afectado

- **WHEN** la app se muestra en modo claro
- **THEN** los colores MUST ser idénticos a los del tema claro actual (cálidos `#FAF9F5`/`#F1EDE2`/`#E5E2D9` y acento `#73AC32`), sin cambios por la redefinición del bloque `.dark`

## Requirement: Variables semánticas en formato RGB triplet

El sistema MUST mantener las variables de color semánticas en formato de triplets separados por espacios (p. ej. `115 172 50`) para que el patrón `rgb(var(--color-*) / <alpha-value>)` de Tailwind siga soportando modificadores de opacidad (`bg-primary/70`, etc.).

### Scenario: Opacidad funcionando tras el cambio

- **WHEN** un componente usa una clase con modificador de opacidad sobre un token semántico (p. ej. `bg-primary-app/70`)
- **THEN** el color MUST renderizarse con la opacidad aplicada sin errores de compilación

## Requirement: Storefront adopta la nueva marca por default

El storefront público SHALL usar por defecto la nueva identidad: acento `#73AC32` sobre base `#080808`. Los defaults MUST actualizarse en el seeder de e-commerce y en el fallback del middleware de Inertia. Las tiendas con colores de marca guardados en base de datos MUST conservar sus valores (el cambio aplica solo a defaults).

### Scenario: Nuevo default con acento verde

- **WHEN** una tienda no tiene `primary_color` configurado (o se crea una tienda nueva con el seeder)
- **THEN** el acento de marca MUST ser `#73AC32`

### Scenario: Nuevo default con base negra

- **WHEN** una tienda no tiene `secondary_color` configurado
- **THEN** la base/fondo de marca MUST ser `#080808`

### Scenario: Tiendas con marca guardada no se alteran

- **WHEN** una tienda tiene `primary_color`/`secondary_color` persistidos en base de datos
- **THEN** sus colores MUST permanecer sin cambios

## Requirement: Logo de marca por variante según el fondo

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
