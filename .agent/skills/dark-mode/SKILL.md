---
name: modo-oscuro-fullstack
description: Estrategias y estándares semánticos para implementar un modo oscuro robusto y escalable en el stack Laravel, Livewire, Tailwind CSS mediante el uso de tokens (Primary/Secondary).
---

# Modo Oscuro Robusto (Fullstack) - Estándar Semántico

Esta habilidad establece los estándares para un sistema de modo oscuro profesional basado en una arquitectura centralizada de tokens de color (**Primary y Secondary**) en lugar de hardcodear variables específicas como `slate` o `indigo`.

## Arquitectura Semántica

Toda la paleta de colores de la aplicación está centralizada en `tailwind.config.js`. Esto garantiza que un cambio de marca o de identidad visual ("theme switch") aplique globalmente sin tener que tocar cientos de archivos.

### 1. Paleta de Superficies (`Secondary`)
Los fondos y contenedores estructurales de la aplicación usan la familia `secondary`. En el modo oscuro actual, mapea a grises azulados elegantes:

- **Fondo de Aplicación (Body):** `bg-secondary-900`
- **Superficies Principales (Cards/Modales/Tablas):** `bg-secondary-800`
- **Cabeceras y Secciones (Atenuadas):** `bg-secondary-700/50` o `bg-secondary-600`
- **Bordes y Divisores:** `border-secondary-700` o `border-secondary-600`
- **Textos Secundarios (Labels/Apoyo):** `text-secondary-400`
- **Textos Principales:** Preferible `text-white` o `text-secondary-50`

### 2. Acentos de Acción (`Primary`)
Los elementos de llamada a la acción (botones, switches, iconos destacados) usan la familia `primary`. 

- **Botones Principales:** `bg-primary-600 hover:bg-primary-700`
- **Iconos Destacados:** `text-primary-500` o `text-primary-600`
- **Fondos tenues de alerta/estados:** `bg-primary-500/10` o `bg-primary-900/30`

## Reglas de Implementación

### 1. Estrategia de Control: `selector`
- El proyecto usa `darkMode: 'selector'` en `tailwind.config.js`.

### 2. Tablas y Hovers de Alto Contraste
Para garantizar la legibilidad técnica en tablas:
- **Hover de Fila Activa:** En lugar de forzar un color HEX estático, se debe usar la opacidad semántica. 
```html
<style>
    .dark .row-hover-fix:hover {
        background-color: rgb(var(--color-secondary-700) / 0.5) !important;
    }
</style>
```

### 3. Regla de Oro: Prohibición de Hardcoding y Colores Específicos
- **ESTRICTAMENTE PROHIBIDO:** Usar clases crudas como `bg-[#0f172a]`, `bg-slate-900`, `bg-gray-800`, `bg-zinc-800` o `bg-zinc-50` de forma directa para estructura.
- **USO SEMÁNTICO:** Preferir siempre los tokens `primary` y `secondary` definidos en la configuración de Tailwind.

### 4. Estándares de Interacción (Hovers y Resaltados)
- **Regla de Oro del Hover:** Está ESTRICTAMENTE PROHIBIDO dejar que un hover de modo claro (`hover:bg-zinc-50` o `white`) se filtre al modo oscuro. 
- **Solución Obligatoria:** Siempre se debe definir un hover oscuro sólido: `dark:hover:bg-secondary-700` o `dark:hover:bg-secondary-700/60`.
- **Modo Oscuro Hovers:** Usar `dark:hover:bg-secondary-700`. Nunca dejar que el fondo se vuelva blanco al pasar el cursor, ya que oculta el texto claro.

### 5. Componentes y Modales
- Toda card o modal en modo oscuro debe apoyarse sobre `bg-secondary-800`.
- Eliminar sombras invasivas y dejar que el contraste entre `secondary-900` (Body) y `secondary-800` (Cards) marque el ritmo visual.

## Gestión de Modales y Capas Superpuestas

Para evitar errores visuales cuando un modal abre a otro (ej: Nuevo Cliente dentro de Confirmar Venta):

### 1. Jerarquía de Z-Index
- **Nivel 1 (Modal Base):** `z-[1000]`
- **Nivel 2 (Modal Anidado/Superior):** `z-[1100]`
- **Nivel 3 (Tooltips/Notificaciones):** `z-[1200]` o superior.

### 2. Uso de Teleport
Es **OBLIGATORIO** envolver los modales en `<Teleport to="body">` para evitar conflictos de contexto de apilamiento y asegurar que los fondos oscuros (`backdrops`) cubran toda la pantalla correctamente.

### 3. Encabezados y Footers Atenuados
Para dar profundidad, los encabezados y pies de página de los modales deben usar una variante ligeramente distinta del fondo principal:
- **Header/Footer:** `bg-zinc-50 dark:bg-secondary-800/50` o `bg-secondary-900/50`.
- **Borde Separador:** `border-zinc-100 dark:border-secondary-700`.

## Visibilidad Blindada en Inputs y Formularios

Para evitar que los plugins de formularios (como `@tailwindcss/forms`) o los componentes teletransportados rompan la visibilidad, se establecen estas reglas técnicas:

### 1. El Modificador de Importancia (`!`)
En inputs dentro de modales o layouts complejos, es **OBLIGATORIO** usar el prefijo `!` para forzar los tokens de modo oscuro:
- Fondo: `!dark:bg-secondary-900` o `dark:bg-secondary-900` (si no hay conflicto).
- Texto: `!dark:text-secondary-200` o `dark:text-secondary-200`.

### 2. Estilo POS Premium para Inputs
Para el módulo de ventas y afines, se deben seguir estos estilos para inputs:
- **Fondo:** `bg-zinc-50 dark:bg-secondary-900`.
- **Borde:** `border-zinc-100 dark:border-secondary-700/50`.
- **Texto:** `text-sm font-bold uppercase tracking-tight`.
- **Foco:** `focus:border-emerald-500 dark:focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10`.

### 3. Prohibición de Foco Claro
Está **ESTRICTAMENTE PROHIBIDO** usar `focus:bg-white` en un contexto de modo oscuro. El fondo del input debe permanecer en el token de superficie oscura asignado.

### 3. Placeholders y Acentos
- **Placeholders:** Usar siempre `dark:placeholder:text-secondary-500`.
- **Bordes de Acción:** En foco, usar el acento de marca o color de acción definido para el componente.

### 3. Placeholders y Acentos
- **Placeholders:** Usar siempre `dark:placeholder:text-secondary-500`.
- **Bordes de Acción:** En foco, usar el acento de marca o color de acción definido para el componente.

## Gestión de Especificidad y "Sangrado" de Color

En proyectos con múltiples librerías de UI (WireUI, Flux, @tailwindcss/forms), pueden ocurrir conflictos donde los inputs mantengan un fondo blanco en modo oscuro.

### 1. El Problema: Especificidad
Librerías como WireUI inyectan CSS con selectores de atributos o resets que pueden tener más fuerza que una clase simple de Tailwind. 

### 2. La Solución: Modificador de Importancia (`!`)
En lugar de usar estilos inline (`style="..."`), se debe usar el prefijo `!` de Tailwind para elevar la especificidad de forma controlada dentro del sistema de clases:
- **Correcto:** `!dark:bg-secondary-950 !dark:text-white`
- **Evitar:** `style="background-color: ..."` (Solo como último recurso absoluto).

### 3. Evitar el "Sangrado" de Fondo
Si un contenedor padre tiene `bg-white` y el input tiene un fondo con transparencia (ej. `bg-secondary-900/50`), el blanco del padre se filtrará visualmente. 
- **Regla:** En inputs de modales, usar siempre colores oscuros **sólidos** (sin opacidad) del sistema de tokens (`secondary-900` o `secondary-950`).

## Checklist de Validación Dark Mode
- [ ] ¿El fondo es `secondary-900` o `secondary-800`?
- [ ] ¿Los textos usan `secondary-50` o `white`?
- [ ] ¿Se han eliminado todos los `bg-white` y `hover:bg-zinc-50` del contexto oscuro?
- [ ] En inputs: ¿Se usó el modificador `!` (`!dark:bg-...`) para asegurar la visibilidad contra librerías externas?
- [ ] En inputs: ¿Se usaron colores sólidos para evitar el "sangrado" del fondo del padre?
