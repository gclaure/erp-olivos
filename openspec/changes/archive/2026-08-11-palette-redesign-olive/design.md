## Context

La app usa tres sistemas de color simultáneos:

1. **Variables semánticas** (`resources/css/app.css` + tokens `*-app` en `tailwind.config.js`): `--color-primary`, `--color-background`, etc. Se usan en ~20 componentes (layouts + páginas Admin/SuperAdmin).
2. **Escalas hardcodeadas** (`tailwind.config.js`): `zinc` (neutros light), `secondary` (slate, estructura dark), `primary` (índigo custom), además de la escala `indigo` por defecto de Tailwind. Dominan la app (~2.000+ usos de `zinc`, ~421 de `indigo`, ~35 de `primary`).
3. **Marca del storefront** (`brand-*`): colores dinámicos desde BD con defaults en `EcommerceSettingSeeder.php` y fallback en `HandleInertiaRequests.php`.

El modo oscuro actual está pintado con `secondary` (slate) como estructura y acentos índigo; las superficies/hovers oscuros usan además tonos altos de `zinc` (`dark:bg-zinc-800`, `dark:hover:bg-zinc-800`, etc.). **Requiere conservarse intacto.**

La nueva paleta de identidad (olivo/crema/cálidos) está formulada en el vocabulario del sistema de variables semánticas, pero la app se pinta mayoritariamente con escalas hardcodeadas. Cambiar solo `app.css` dejaría el shell cálido y el contenido frío (inconsistencia visible).

## Goals / Non-Goals

**Goals:**
- Adoptar la nueva identidad olivo/crema en **modo claro** en toda la app: acento verde `#73AC32` (con `#3F6B20` y `#A8C97A`), fondos `#FAF9F5`/`#FFFFFF`, crema `#F1EDE2`, borde `#E5E2D9`, texto `#080808`, texto secundario `#6B6B67`
- Preservar **íntegro** el modo oscuro actual: estructura `secondary` (slate), acentos índigo, superficies/hovers oscuros
- Aplicar el cambio **sin editar templates** (`.vue`/`.blade.php`): la paleta vive en `app.css` + `tailwind.config.js`
- Alinear los tokens semánticos (`bg-app`, `bg-surface`, `primary-app`) con la nueva paleta para eliminar el desajuste shell-vs-contenido
- Actualizar los defaults de marca del storefront a acento verde sobre base negra

**Non-Goals:**
- NO se edita ningún componente Vue ni Blade (la escala `secondary` y los colores de estado `emerald/amber/rose/sky` quedan intactos)
- NO se diseñan nuevas variantes oscuras de la paleta olivo
- NO se migra físicamente clases (`bg-indigo-*` → `bg-primary-*`, etc.)
- NO se toca la identidad de marca guardada en BD de tenants existentes (solo defaults)
- NO se cambia el nombre de las clases de marca `brand-*`

## Decisions

### 1. Escalas `zinc`/`indigo`/`primary` dependientes de variables CSS (mode-aware)

**Decisión**: Convertir en `tailwind.config.js` las escalas `zinc`, `indigo` (nueva entrada) y `primary` al patrón `rgb(var(--color-*) / <alpha-value>)`, igual que los tokens semánticos existentes. `app.css` define los valores en `:root` (paleta clara cálida/verde) y en `.dark` (valores actuales, sin cambio visual).

```
  tailwind.config.js                    app.css
  ─────────────────────                 ────────────────────────────
  zinc:  { 50: 'rgb(var(--color-zinc-50)/<alpha-value>)', ... }
  indigo:{ 50: 'rgb(var(--color-indigo-50)/<alpha-value>)', ... }   :root  → cálido/verde (nueva marca)
  primary:{...}                                                       .dark → valores ACTUALES (sin cambio)
```

**Alternativas consideradas:**
- **Remap estático de valores** (cambiar solo los hex en `tailwind.config.js`): rechazado porque `zinc` se usa en ambos modos (`bg-zinc-50` en claro y `dark:bg-zinc-800` en oscuro); un remap global altera el dark mode que se quiere conservar. Mismo problema con `indigo` (~50% de sus usos son dark).
- **Migración física de clases**: rechazado por tamaño (~2.500 ocurrencias), alto riesgo de regresión y diffs enormes.
- **Alias `indigo` → `primary`**: rechazado por mayor churn en templates; var-driven logra el mismo resultado en config.

**Razón**: Es el único enfoque que aplica la nueva identidad al 100% del claro sin alterar el oscuro y sin tocar templates. Además es coherente con el patrón semántico que el repo ya usa para los tokens `*-app`.

### 2. Resolución del conflicto `zinc-900` (texto claro vs body oscuro)

**Decisión**: `:root` define `zinc-900 → #080808` (texto principal en claro). `.dark` define `zinc-900 → #171717` (actual). Así `text-zinc-900` en claro pasa a negro puro, mientras `dark:bg-zinc-900` conserva su valor actual. El costo: los ~106 `bg-zinc-900` sin prefijo `dark:` (elementos oscuros dentro de páginas claras) pasan de `#18181b` a `#080808` — imperceptible, ambos son negro.

### 3. Ramas de color propuestas

**Verde (acento)** para `:root` de `indigo` y `primary`, con anchors en la paleta:

```
50 #F4F9EC   100 #E8F2D9   200 #D1E5B3   300 #A8C97A  ← primary-light
400 #8DBB52  500 #73AC32   ← primary      600 #5C8C28
700 #3F6B20  ← primary-dark 800 #2F5118   900 #223C12  950 #142307
```

**Cálidos (neutros claro)** para `:root` de `zinc` (50–600), anclados en la paleta:

```
50 #FAF9F5  ← background   100 #F1EDE2  ← cream    200 #E5E2D9  ← border
300 #D8D4C8  400 #B9B7AF  (muted)        500 #8A8880
600 #6B6B67  ← text-secondary           900 #080808  ← text/black
700/800/950: se mantienen los valores actuales (elementos oscuros en páginas claras)
```

**`.dark`**: `zinc` → valores actuales (`#fafafa`…`#0a0a0a`), `indigo` → escala índigo por defecto de Tailwind (`#eef2ff`…`#1e1b4b`), `primary` → escala índigo custom actual (`#f5f3ff`…`#1e1b4b`, `600 #5A4CFA`).

### 4. Tokens semánticos (`app.css` `:root`) — nueva paleta en triplets RGB

Se actualizan los valores del bloque `:root` manteniendo el **formato triplet** (requerido por `rgb(var(--x) / <alpha-value>)` para el modificador de opacidad): `--color-background 250 249 245`, `--color-surface 255 255 255`, `--color-border 229 226 217`, `--color-primary 115 172 50`, `--color-primary-light 168 201 122`, `--color-text-secondary 107 107 103`. El bloque `.dark` NO se modifica.

**Alias**: se agregan `--color-primary-dark` (alias de `--color-primary-hover`) y `--color-text` (alias de `--color-text-primary`), más los nuevos tokens `--color-cream`, `--color-black` y `--color-white`. `tailwind.config.js` sigue leyendo los nombres existentes; los alias quedan disponibles para uso futuro sin romper nada.

### 5. Storefront — defaults de marca verde sobre negro

**Decisión**: `primary_color → #73AC32`, `secondary_color → #080808`, `tertiary_color → #080808` (en `EcommerceSettingSeeder.php` y en el fallback de `HandleInertiaRequests.php`). El hover de marca (`primary_color + 'dd'`) sigue funcionando porque es sufijo de alpha sobre hex.

**Alternativa considerada**: base verde oliva profundo (`#1b2a10`). Rechazada en favor de `#080808` (existe en la paleta como `--color-black`) para mantener el look de contraste alto.

### 6. No se tocan `secondary` ni colores de estado

`secondary` (slate) es exclusivamente estructura oscura → intacto. `emerald/amber/rose/sky` (estados) no forman parte de la nueva paleta → intactos. Los scrollbars de `app.css` (`#d4d4d8`/`#a1a1aa`) se recalibran a tonos cálidos para coherencia visual.

## Risks / Trade-offs

- **Riesgo**: Algún componente usa `zinc-*` en modo oscuro SIN variante `dark:` y ahora resuelve al valor claro cálido → **Mitigación**: `.dark` define la rama `zinc` completa con los valores actuales, evitando que valores claros se "filtren" al oscuro. Verificación visual post-cambio (QA).
- **Riesgo**: Conversión a var-driven cambia sutilmente el comportamiento de opacidad (`bg-zinc-50/50` etc.) si algún valor no respeta el formato triplet → **Mitigación**: mantener estrictamente triplets; prueba de compilación de Tailwind.
- **Riesgo**: Tenants con `primary_color`/`secondary_color` ya guardados conservan la marca vieja → **Mitigación**: documentado; se actualiza solo el default/seed (decisión consciente, no destructiva).
- **Trade-off**: var-driven agrega ~40 declaraciones de variables en `app.css` → A cambio elimina el doble sistema de color y habilita futuros "theme switches" en un solo lugar (coherente con la arquitectura semántica del skill `modo-oscuro-fullstack`).
- **Riesgo**: Clases con tonos no estándar (`zinc-750`, `zinc-450`, `zinc-150`, `zinc-850`) no generan utilidades (probablemente hoy ya son no-op) → **Mitigación**: se ignoran, no se corrigen en este cambio.

## Migration Plan

1. Editar `tailwind.config.js`: `zinc`/`indigo`/`primary` → var-driven.
2. Editar `resources/css/app.css`: nuevo `:root` (paleta clara + ramas `zinc`/`indigo`/`primary`) y `.dark` completo con valores actuales; actualizar scrollbar.
3. Actualizar defaults de marca en `EcommerceSettingSeeder.php` y `HandleInertiaRequests.php`.
4. Compilar (`npm run build`) y verificar claro/oscuro en: AdminLayout, SuperAdminLayout, páginas con `bg-app`/`bg-surface`, storefront público, y dark mode completo.

**Rollback**: revertir el commit; los cambios son declarativos (CSS/config), sin migraciones de datos. Las tiendas que conservan colores en BD no se ven afectadas en ningún caso.

## Open Questions

- ¿Los tenants existentes con marca guardada deben re-seedearse para adoptar el nuevo look, o se dejan como están? (default: dejar; es no destructivo)
- ¿El scrollbar de la app y del login (`login.css`) entran en el alcance visual? (default: app.css sí, login.css se revisa en QA)
