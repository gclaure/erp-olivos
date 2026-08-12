## Context

Tras los cambios de identidad visual, la app usa `logo-inventory.png` como fallback y permite logo subido por empresa (`company.logo_url`, `settings.logo_url`). El usuario aportó una dupla de marca: `logo-dark.png` (arte oscuro) y `logo-light.png` (arte claro). Las superficies donde se muestra el logo tienen fondos **fijos**, no dependientes del modo:

- Sidebar del Admin: `bg-zinc-900` siempre (ambos modos)
- Login `.right-panel`: `#ffffff` fijo en `login.css` (sin variante `.dark`)
- Preview de Settings: `bg-white` fijo en la etiqueta `img`

El toggle de modo (`document.documentElement.classList.toggle('dark')`) no afecta estos fondos, por lo que el switch de logo se decide por superficie, no por modo.

## Goals / Non-Goals

**Goals:**
- Mostrar siempre la variante legible según el fondo fijo de cada superficie
- Reemplazar `logo-inventory.png` y el logo de empresa en las tres superficies del admin
- Cambio mínimo: solo las etiquetas `img` del logo (sin tocar storefront, blades ni PDFs)

**Non-Goals:**
- NO se toca el storefront (`PublicLayout` usa `settings.logo_url` y su propio sistema `brand-*`)
- NO se tocan PDFs/recibos (usan logo de empresa o `logo-inventory.jpg`, siempre fondo claro)
- NO se modifica el sistema de subida de logo (queda inerte en las superficies afectadas)
- NO se reemplaza `logo-inventory.png`/`.jpg` (siguen usándose en blades)

## Decisions

### 1. Mapeo fondo → variante

| Superficie | Fondo | Variante |
|---|---|---|
| Sidebar Admin (`AdminLayout.vue:137`) | `bg-zinc-900` (siempre oscuro) | `/img/logo-dark.png` (arte claro) |
| Login brand móvil (`Login.vue:71`) | `#ffffff` (siempre claro) | `/img/logo-light.png` (arte oscuro) |
| Preview Settings (`Company.vue:98-100`) | `bg-white` (siempre claro) | `/img/logo-light.png` (arte oscuro) |

**Razón**: el nombre del archivo describe el **fondo** para el que fue diseñado (`logo-dark` = para fondo oscuro = arte claro; `logo-light` = para fondo claro = arte oscuro). Validado midiendo el color promedio de los píxeles opacos: `logo-dark.png` tiene arte claro (lum 205), `logo-light.png` arte oscuro (lum 45).

### 2. Ignorar logo de empresa

**Decisión**: en las tres superficies se usa el `src` fijo de la dupla, eliminando la referencia a `company.logo_url`. La subida de logo en Settings deja de tener efecto visible en sidebar/login/preview.

**Alternativas consideradas**:
- Mantener prioridad del logo de empresa: rechazada, el usuario pidió forzar la dupla.
- Usar `dark:`/`hidden` para switch reactivo por modo: rechazado, no aplica porque los fondos no cambian con el modo.

### 3. Implementación

- `AdminLayout.vue:137`: `:src="company?.logo_url || '/img/logo-inventory.png'"` → `src="/img/logo-dark.png"`
- `Login.vue:71`: `:src="company?.logo_url || '/img/logo-inventory.png'"` → `src="/img/logo-light.png"`
- `Company.vue:98-100`: el bloque `v-if="form.logo"` / `v-else-if="company?.logo_url"` / `v-else` se reemplaza por un único `<img src="/img/logo-light.png">`

## Risks / Trade-offs

- **Riesgo**: si el arte de los PNG no coincidiera con la convención de nombres, el logo quedaría invisible → **Mitigación aplicada**: se midió el color promedio de píxeles opacos para validar que `logo-dark.png` es arte claro y `logo-light.png` arte oscuro; el mapeo quedó ajustado a los datos
- **Riesgo**: el `form.logo` (preview del archivo subido) desaparece del preview de Settings → aceptado, la subida queda inerte; si más adelante se quiere restaurar el logo por empresa, es un cambio separado
- **Trade-off**: `login-inventory.png`/`.jpg` permanecen en el repo solo para blades/PDFs → aceptable, evita tocar plantillas de impresión
