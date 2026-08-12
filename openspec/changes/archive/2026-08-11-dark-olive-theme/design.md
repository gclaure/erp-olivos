## Context

Tras `palette-redesign-olive`, el modo claro ya usa la identidad olivo/crema, pero el modo oscuro se preservó deliberadamente en su tema anterior: estructura `secondary` (slate azulado), acentos índigo, neutros `zinc` oscuros. La arquitectura mode-aware quedó montada (`zinc`, `indigo`, `primary` como `rgb(var(--color-*) / <alpha-value>)`), pero `secondary` sigue siendo una escala estática hex en `tailwind.config.js`.

La paleta objetivo (acento `#73AC32`, `#3F6B20`, `#A8C97A`, negro `#080808`, crema `#F1EDE2`) es una paleta de modo claro. El modo oscuro requiere **derivar** valores: verde olivo para el acento y la estructura, crema para el texto, y fondo casi negro derivado de `#080808`.

Este cambio contradice el requirement "Modo oscuro se preserva íntegro" de la capacidad `app-visual-theme`, que debe renombrarse/modificarse.

## Goals / Non-Goals

**Goals:**
- Llevar la identidad olivo al modo oscuro: acento verde `#73AC32`, estructura de superficies verde-olivo, texto crema `#F1EDE2`, fondo body casi negro
- Conservar el modo claro **exactamente** como quedó tras `palette-redesign-olive`
- No editar templates (`.vue`/`.blade.php`): el cambio vive en `tailwind.config.js` + `app.css`
- Convertir `secondary` a var-driven manteniendo los valores light actuales (la luz no cambia)
- Dejar el registro especificativo coherente: renombrar/modificar el requirement de dark en `app-visual-theme`

**Non-Goals:**
- NO se modifica el modo claro ni las ramas verdes de `indigo`/`primary` en `:root`
- NO se toca el storefront (ya es verde/negro desde `palette-redesign-olive`)
- NO se cambian los colores de estado (`success/warning/danger/info`)
- NO se migra clases ni se reescriben templates

## Decisions

### 1. `secondary` → var-driven, luz intacta, dark olivo

**Decisión**: Convertir `secondary` en `tailwind.config.js` al patrón `rgb(var(--color-secondary-*) / <alpha-value>)`. En `app.css`, `:root` define `--color-secondary-*` con los valores slate actuales (`#f8fafc`…`#0f172a`) y `.dark` los redefine con la rampa olivo oscura.

**Alternativas consideradas:**
- Cambiar los hex de `secondary` estático por olivo: rechazado porque `secondary` tiene un uso minoritario en modo claro (~10%) que se teñiría de verde.
- Añadir una escala nueva (`olive`) y migrar clases: rechazado por churn innecesario; var-driven logra lo mismo en config.

**Razón**: misma técnica que `zinc`/`indigo`/`primary`; cambia solo el dark sin tocar la luz y sin editar templates.

### 2. Rampa olivo oscura para `secondary` (`.dark`)

```
50 #F1EDE2  crema (texto claro)     600 #565F50
100 #E4DFD2                          700 #3C4335  bordes/divisores
200 #C9CDBD                          800 #262B20  superficies (cards)
300 #A9B19C  acentos claros          900 #151812  body
400 #8A9480  texto secundario       950 #0B0D08  profundo
500 #6E7868
```

`secondary-700` (#3C4335) bordes, `800` (#262B20) superficies, `900` (#151812) body — reemplazan los slate `#333A48/#222530/#171923`.

### 3. Rampa `zinc` `.dark` → olivo-tinted

Los textos y superficies de `zinc` en dark pasan de gris frío a olivo-tinted, conservando la jerarquía de luminosidad:

```
100 #F1EDE2 (crema, texto alto)      600 #5C6654
200 #E5E2D9                          700 #424A3B  hovers
300 #CDD2C2  texto medio             800 #262B20  superficies
400 #9BA68F  texto atenuado          900 #171A12
500 #78826F                         950 #0C0F09
```

### 4. `indigo`/`primary` en `.dark` → misma rampa verde de `:root`

**Decisión**: El verde `#73AC32` funciona sobre fondos oscuros (luminosidad media). `.dark` reutiliza la rampa verde de `:root` (`#F4F9EC`…`#142307`). Como `:root` y `.dark` quedarían idénticos, se **eliminan los bloques `--color-indigo-*` y `--color-primary-*` del `.dark`** (las variables caen a `:root` por cascada). `dark:bg-indigo-900` → olivo profundo `#223C12`, `dark:text-indigo-400` → `#8DBB52`, `dark:bg-indigo-500`/`bg-primary-500` → `#73AC32`.

**Alternativa**: mantener los bloques con los mismos valores explícitamente. Rechazada por redundancia; la cascada ya lo resuelve.

### 5. Variables semánticas `.dark` → olivo

```
--color-background   21 24 18     #151812  body
--color-surface      38 43 32     #262B20  cards
--color-border       60 67 53     #3C4335
--color-text-primary 241 237 226  crema #F1EDE2
--color-text-secondary 138 148 128  #8A9480
--color-text-muted   110 120 104  #6E7868
--color-primary      115 172 50   #73AC32
--color-primary-hover 141 187 82  #8DBB52 (verde más claro sobre oscuro)
--color-primary-light 168 201 122 #A8C97A
```

Los colores de estado dark (emerald/amber/rose/sky) se conservan. `bg-app`/`bg-surface` en dark toman `#151812`/`#262B20`.

### 6. Acento en dark

**Decisión**: `#73AC32` (verde de marca) como acento principal, `#A8C97A` para acentos claros sobre fondo oscuro (`dark:text-indigo-300` etc.), `#8DBB52` como hover (`primary-app.hover` en dark). No se usa una variante más clara global para no diluir la marca.

## Risks / Trade-offs

- **Riesgo**: Cambio visual mayor en dark (slate azulado → olivo) puede afectar legibilidad en componentes con contraste ajustado → **Mitigación**: la rampa olivo conserva la luminosidad de los valores actuales; QA visual post-cambio en pantallas clave (tablas, formularios, modales, dark:hover).
- **Riesgo**: `secondary` en modo claro (~10% de usos) podría verse afectada si un valor `:root` no se define correctamente → **Mitigación**: `:root` define la rampa completa con los valores slate actuales; verificación de compilación.
- **Trade-off**: Eliminar los overrides `.dark` de `indigo`/`primary` reduce redundancia, pero hace el cambio de acento implícito (vía cascada) → aceptable, documentado en este diseño.
- **Riesgo**: `--color-primary-hover` en dark pasa a un verde más claro (`#8DBB52`); en claro sigue siendo `#3F6B20` → comportamiento mode-aware correcto, sin regresión en luz.

## Migration Plan

1. `tailwind.config.js`: `secondary` → var-driven (12 entradas).
2. `app.css`: agregar `--color-secondary-*` en `:root` (slate actual) y en `.dark` (olivo).
3. `app.css`: recolorear `.dark` — rama `zinc` olivo-tinted; eliminar overrides `indigo`/`primary`; variables semánticas → olivo.
4. `npm run build` y verificación estática del CSS compilado (dark resuelve a olivo, light a cálidos actuales).
5. QA visual: dark en Admin/SuperAdmin, tablas, modales, hovers; confirmar que light no cambió.

**Rollback**: revertir el commit; cambios declarativos, sin migraciones de datos.

## Open Questions

- ¿El texto principal en dark se fija en crema `#F1EDE2` puro o con tinte olivo? (default: crema puro de la paleta)
- ¿`dark:hover:bg-zinc-800` (superficie de hover) debe usar `#262B20` (igual que card) o un tono más claro `#2E3326` para distinguir el hover? (default: `#262B20`, igual que el actual `#262626`)
