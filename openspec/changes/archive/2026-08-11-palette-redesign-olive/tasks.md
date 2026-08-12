## 1. Config — escalas mode-aware

- [x] 1.1 En `tailwind.config.js`, convertir la escala `zinc` al patrón `rgb(var(--color-zinc-*) / <alpha-value>)` para todos los tonos 50–950
- [x] 1.2 Agregar entrada `indigo` a `tailwind.config.js` con el mismo patrón var-driven (`rgb(var(--color-indigo-*) / <alpha-value>)`)
- [x] 1.3 Convertir la escala `primary` de `tailwind.config.js` al patrón var-driven
- [x] 1.4 Verificar que `secondary` y los colores de estado/semánticos existentes (`*-app`) permanecen sin cambios

## 2. CSS — paleta clara y dark preservado

- [x] 2.1 En `resources/css/app.css`, reescribir el bloque `:root` con la paleta olivo/crema en triplets: `--color-background 250 249 245`, `--color-surface 255 255 255`, `--color-border 229 226 217`, `--color-text-primary 8 8 8`, `--color-text-secondary 107 107 103`, `--color-primary 115 172 50`, `--color-primary-light 168 201 122`
- [x] 2.2 Agregar tokens nuevos en `:root`: `--color-primary-dark` (alias de primary-hover), `--color-text` (alias de text-primary), `--color-cream 241 237 226`, `--color-black 8 8 8`, `--color-white 255 255 255`
- [x] 2.3 Definir en `:root` las ramas `--color-zinc-50…600` con los neutros cálidos (`#FAF9F5`, `#F1EDE2`, `#E5E2D9`, `#D8D4C8`, `#B9B7AF`, `#8A8880`, `#6B6B67`), `--color-zinc-900 8 8 8`, y mantener `zinc-700/800/950` con valores actuales
- [x] 2.4 Definir en `:root` las ramas `--color-indigo-*` y `--color-primary-*` con la escala verde derivada de `#73AC32` (50 `244 249 236` … 950 `20 35 7`)
- [x] 2.5 Definir en `.dark` las ramas completas `--color-zinc-*` (valores actuales), `--color-indigo-*` (escala índigo default de Tailwind) y `--color-primary-*` (escala índigo custom actual) para que ningún valor claro se filtre al oscuro
- [x] 2.6 Verificar que el bloque `.dark` de `app.css` mantiene sin cambios las variables semánticas existentes
- [x] 2.7 Recalibrar los scrollbars de `app.css` (`#d4d4d8`/`#a1a1aa`) a tonos cálidos de la nueva paleta

## 3. Storefront — defaults de marca

- [x] 3.1 En `database/seeders/EcommerceSettingSeeder.php`, cambiar `primary_color` a `#73ac32` y `secondary_color` a `#080808`
- [x] 3.2 En `app/Http/Middleware/HandleInertiaRequests.php`, actualizar los fallbacks: `primary_color '#73ac32'`, `secondary_color '#080808'`, `tertiary_color '#080808'`

## 4. Verificación

- [x] 4.1 Compilar sin errores (`npm run build`)
- [x] 4.2 Verificar en modo claro: layouts Admin/SuperAdmin, páginas con `bg-app`/`bg-surface`, botones y acentos verdes en componentes con `bg-primary-*`/`bg-indigo-*`
- [x] 4.3 Verificar en modo oscuro: estructura slate intacta, acentos índigo intactos, sin valores cálidos filtrados
- [x] 4.4 Verificar storefront público con defaults nuevos (acento verde sobre base negra)
- [x] 4.5 Verificar que la opacidad (`bg-primary-app/70`, etc.) sigue funcionando tras la conversión a triplets
