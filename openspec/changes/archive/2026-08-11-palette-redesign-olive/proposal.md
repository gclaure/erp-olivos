## Why

La identidad visual actual usa índigo/azul sobre grises fríos (zinc/slate), que no reflejan la marca olivo/crema deseada. La app está pintada con tres sistemas de color coexistentes (variables semánticas en `app.css`, escalas hardcodeadas en `tailwind.config.js` y colores de marca del storefront desde BD), por lo que un cambio de paleta coherente exige migrar los tres en conjunto; tocar solo uno produce inconsistencias visibles (shell cálido con contenido frío, acentos mezclados).

## What Changes

- Redefinir la paleta semántica de `resources/css/app.css` en modo claro con la nueva identidad: `primary #73AC32`, `primary-dark #3F6B20`, `primary-light #A8C97A`, fondo `#FAF9F5`, crema `#F1EDE2`, borde `#E5E2D9`, texto `#080808`, texto secundario `#6B6B67`, negro `#080808`, blanco `#FFFFFF`
- Mantener **sin cambios** el bloque `.dark` de `app.css` (el modo oscuro actual se conserva tal cual, incluido el acento índigo)
- Hacer que las escalas `zinc`, `indigo` y `primary` de `tailwind.config.js` sean dependientes de las variables CSS (`rgb(var(--color-*) / <alpha-value>)`), con valores claros cálidos/verde en `:root` y valores oscuros actuales en `.dark`, de modo que los cientos de clases hardcodeadas (`bg-zinc-*`, `text-indigo-*`, `bg-primary-*`) adopten la nueva identidad **sin editar templates** y **sin alterar el dark mode**
- Alinear los tokens semánticos ya existentes (`bg-app`, `bg-surface`, `primary-app`, `text-primary-app`) con la misma paleta para evitar el desajuste shell-vs-contenido
- Actualizar los defaults de marca del storefront público (`EcommerceSettingSeeder` y fallback en `HandleInertiaRequests`) a la nueva paleta: acento verde `#73AC32` sobre base negra `#080808`
- No se modifican los colores de estado (éxito/advertencia/peligro/info) ni la escala `secondary` (estructura oscura)

## Capabilities

### New Capabilities

- `app-visual-theme`: Sistema de identidad visual de la app (tokens semánticos, escalas Tailwind mode-aware y defaults de marca del storefront) bajo la paleta olivo/crema en modo claro, preservando íntegro el modo oscuro actual

### Modified Capabilities

- Ninguna (no cambia comportamiento funcional de las capacidades existentes)

## Impact

- **Frontend global**: `resources/css/app.css` — redefine `:root` (paleta clara) y agrega variables para las escalas `zinc`/`indigo`/`primary`; el bloque `.dark` se conserva intacto
- **Config**: `tailwind.config.js` — `zinc`, `indigo` y `primary` pasan de hex estático a `rgb(var(--color-*) / <alpha-value>)`
- **Storefront**: `database/seeders/EcommerceSettingSeeder.php` y `app/Http/Middleware/HandleInertiaRequests.php` — nuevos defaults de marca
- **Sin cambios**: ningún `.vue` ni `.blade.php` se edita; la escala `secondary` y los colores de estado quedan intactos
- **Riesgo**: los tenants/storefronts que ya tienen `primary_color`/`secondary_color` guardados en BD conservan sus colores viejos hasta re-seed o edición manual
