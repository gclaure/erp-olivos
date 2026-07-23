---
name: migracion-pixel-perfect
description: Metodología estricta para migrar vistas de Livewire/Alpine a Inertia.js/Vue 3 garantizando fidelidad visual total (pixel-perfect) y preservación absoluta de la funcionalidad original.
---

# Migración Pixel-Perfect (Livewire a Inertia)

## When to use this skill
- Cuando se requiere eliminar Livewire y Alpine.js de una vista existente.
- Cuando el usuario enfatiza que el diseño actual es INTOCABLE.
- Al migrar componentes complejos (Dashboard, Tablas, Formularios) de Blade a Vue 3.

## How to use it

### 1. Verificaciones Críticas de Infraestructura (PASO 0)
Antes de empezar la migración visual, es OBLIGATORIO asegurar que el entorno soporte Vue:
- **Tailwind Content**: Verificar en `tailwind.config.js` que el array `content` incluya `'./resources/js/**/*.vue'`. Sin esto, el diseño se verá roto (sin estilos).
- **Root View**: Confirmar en `HandleInertiaRequests.php` que `$rootView` apunta al archivo correcto (ej: `inertia` o `app`).
- **Build de Assets**: Tras cualquier cambio en clases de Tailwind en archivos Vue, se debe ejecutar `npm run build` o tener `npm run dev` activo.

### 2. Regla de Oro: El Diseño es Sagrado y Adaptable
- **PROHIBIDO** forzar un tema (oscuro o claro) si el original es adaptable. Usar siempre clases `dark:` para mantener la coherencia con el sistema del usuario.
- **PROHIBIDO** añadir, quitar o modificar clases de Tailwind CSS originales.
- **PROHIBIDO** cambiar la paleta de colores o degradados.
- **PROHIBIDO** intentar "mejorar" o modernizar el diseño al añadir nuevas funcionalidades. Si se añade un botón o una tabla nueva, DEBE usar los mismos estilos (padding, rounded, bg, border) que ya existen en el archivo.
- **Fidelidad de Iconos**: Si el original usa Heroicons (vía Blade/WireUI), la versión en Vue DEBE usar Heroicons (en formato SVG exacto). No se permiten familias de iconos alternativas.

### 3. La Fuente de Verdad: El Archivo Blade e Historial
- **Obligatorio**: Antes de codificar en Vue, leer el archivo `.blade.php` original.
- **Respeto al Trabajo Previo**: Si el usuario ya tiene una modal funcional con un estilo definido, **NO TOCAR** los tokens de diseño al implementar lógica nueva. La evolución funcional debe ser invisible estéticamente.

### 4. Cuadrículas y Alineación (Layout)
- **Consistencia en Columnas**: Si el diseño original usa 3 columnas para campos pequeños (precios, stocks), se debe mantener esa jerarquía en Vue usando `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`.
- **Dimensionamiento de Modales**: Para formularios medianos que se ven "desordenados" si son muy anchos, usar `max-w-3xl` como el tamaño mediano estándar.

### 5. Modelos y Compatibilidad Dual
Al migrar lógica de modelos (como accessors):
- **Evitar Breaking Changes**: Si un modelo tiene un método como `initials()`, no lo conviertas en un `Attribute` puro si aún existen archivos Blade que lo llaman como función.
- **Patrón Recomendado**: Mantener el método `public function initials()` y añadir un accessor `getInitialsAttribute()` para que funcione como propiedad `->initials` en Vue.

### 6. Eliminación de Rastros (Livewire/Alpine)
- Sustituir `x-data`, `x-show`, `x-on:click` por `ref()`, `v-if`, `@click` de Vue 3.
- Sustituir `wire:model`, `wire:click`, `wire:navigate` por `v-model`, `@click` y el componente `<Link>` de Inertia.
- Eliminar directivas `@livewireStyles` y `@livewireScripts` en la transición final del layout.

### 5. Flujo de Trabajo Obligatorio
1.  **Auditoría de Clases**: Copiar literalmente todas las clases del elemento raíz al componente Vue.
2.  **Extracción de Lógica**: Mapear cada método de la clase Livewire a un Controller de Inertia RESTful.
3.  **Mapeo de Props**: Asegurar que cada variable compartida en el `compact()` de Blade llegue como una Prop en Vue con el mismo nombre y formato.
4.  **Validación Dual**: Comparar el HTML renderizado de ambas versiones para detectar discrepancias.

## Ejemplos de Icons (Heroicons SVG)
Si el Blade usa `<x-icon name="home" />`, en Vue se debe usar el SVG original de Heroicons:
```html
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
</svg>
```

## Relación con otros estándares
- Sigue estrictamente las [laravel-rules.md](file:///Users/claure/Documents/LARAVEL/inventory-vue-ecomerce/laravel-rules.md).
- Sigue estrictamente las [tailwind-rules.md](file:///Users/claure/Documents/LARAVEL/inventory-vue-ecomerce/tailwind-rules.md).
