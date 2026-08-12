## Why

La app usa `logo-inventory.png` como logo por defecto y permite logo por empresa, pero ahora existe una dupla de marca: `logo-dark.png` y `logo-light.png`. El nombre describe el fondo para el que fue diseñado cada archivo (`logo-dark` = arte claro para fondos oscuros; `logo-light` = arte oscuro para fondos claros). Cada variante solo es legible sobre el fondo correcto. Las superficies del admin tienen fondos fijos, así que el logo debe elegirse por el fondo de la superficie, no por el modo claro/oscuro.

## What Changes

- Agregar los activos `public/img/logo-dark.png` (arte claro, para fondo oscuro) y `public/img/logo-light.png` (arte oscuro, para fondo claro)
- Sidebar del Admin (`AdminLayout.vue`): mostrar siempre `/img/logo-dark.png` (fondo `bg-zinc-900` fijo, incluso en modo claro)
- Login (`Login.vue`, brand móvil): mostrar siempre `/img/logo-light.png` (`.right-panel` siempre `#ffffff`)
- Preview de Settings (`Company.vue`): mostrar siempre `/img/logo-light.png` (fondo `bg-white` fijo)
- Ignorar el logo de empresa en esas tres superficies (se elimina la referencia a `company.logo_url` de las mismas)
- NO se tocan: storefront (`PublicLayout` con `settings.logo_url`), PDFs/recibos (logo de empresa / `logo-inventory.jpg`), blades de auth

## Capabilities

### New Capabilities

- Ninguna

### Modified Capabilities

- `app-visual-theme`: Agregar el requirement "Logo de marca por variante según el fondo" — la dupla `logo-dark`/`logo-light` reemplaza el logo por defecto en las superficies de fondo fijo del admin (sidebar, login, preview de settings), ignorando el logo de empresa
## Impact

- **Activos**: `public/img/logo-dark.png`, `public/img/logo-light.png` (nuevos; `logo-inventory.png`/`.jpg` se conservan para PDFs/recibos)
- **Código**: 3 archivos Vue (`AdminLayout.vue`, `Login.vue`, `Company.vue`) — solo la etiqueta `img` del logo
- **Sin cambios**: storefront, blades, middleware, base de datos, colores de estado
- **Riesgo**: la subida de logo en Settings deja de tener efecto visible en sidebar/login/preview (feature queda inerte); el usuario aceptó ignorar el logo de empresa
