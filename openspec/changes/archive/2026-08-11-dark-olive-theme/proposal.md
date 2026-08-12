## Why

El modo oscuro quedó fuera del rebrand: usa estructura slate (azulada) con acentos índigo, mientras la identidad de la app es olivo/crema. En claro la marca es verde, en oscuro azul — incoherencia visual. El usuario quiere que la misma paleta olivo (acento `#73AC32`, texto crema, fondo casi negro) se aplique también al modo oscuro.

## What Changes

- Recolorear el modo oscuro a la identidad olivo: acento verde `#73AC32` (con `#3F6B20` y `#A8C97A`), estructura de superficies verde-olivo derivada, texto crema `#F1EDE2`, fondo body casi negro derivado de `#080808`
- Convertir la escala `secondary` de `tailwind.config.js` (hoy estática, slate) al patrón var-driven `rgb(var(--color-secondary-*) / <alpha-value>)`, de modo que `:root` conserve los valores slate actuales (la luz no cambia) y `.dark` use la rampa olivo oscura
- Actualizar en `app.css` el bloque `.dark` de las ramas `zinc`, `indigo` y `primary` a valores olivo; `indigo`/`primary` en `.dark` pasan a la misma rampa verde del modo claro
- Actualizar las variables semánticas del bloque `.dark` (`--color-background`, `--color-surface`, `--color-border`, `--color-primary*`, `--color-text*`) a la variante olivo oscura
- **BREAKING (requirement)**: El requirement "Modo oscuro se preserva íntegro" de `app-visual-theme` se renombra y modifica a "Modo oscuro con identidad olivo" — el dark mode ya NO se preserva tal cual; adopta la paleta de marca
- NO se editan templates; NO cambia el modo claro; el storefront ya es verde/negro y no requiere cambios

## Capabilities

### New Capabilities

- Ninguna

### Modified Capabilities

- `app-visual-theme`: Modificar (y renombrar) el requirement "Modo oscuro se preserva íntegro" → "Modo oscuro con identidad olivo". El dark mode deja de conservar el tema índigo/slate y adopta la paleta olivo (acento verde, estructura olivo, texto crema)

## Impact

- **Config**: `tailwind.config.js` — la escala `secondary` pasa a var-driven; el resto de escalas no cambia
- **CSS**: `resources/css/app.css` — bloque `.dark` recoloreado a olivo (ramas `zinc`/`indigo`/`primary` + variables semánticas + nueva rama `secondary` en `.dark`)
- **Sin cambios**: modo claro (`.root`), templates `.vue`/`.blade.php`, storefront, colores de estado
- **Riesgo**: `secondary` tiene un pequeño uso en modo claro (~10%) — mitigado porque `:root` conserva los valores slate actuales, manteniendo la luz idéntica
