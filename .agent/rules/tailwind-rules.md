---
trigger: always_on
---

# Laravel + Inertia + Vue 3 Skill Guide

```yaml
---
name: laravel-inertia-vue-architecture
version: 1.0
description: |
  Estándar de arquitectura, performance, Tailwind responsivo y calidad de código
  para Laravel 12 + Inertia.js + Vue 3 + Tailwind CSS + PostgreSQL.
stack:
  - Laravel 12
  - Inertia.js
  - Vue.js 3 (Composition API)
  - Tailwind CSS 3
  - PostgreSQL
  - PHP 8.2+
---
```

# Tailwind CSS — Buenas Prácticas y Responsividad

# Principios Generales

- Usa únicamente utilidades de Tailwind; evitar CSS personalizado salvo casos excepcionales justificados.
- Nunca usar estilos inline.
- Mobile-first obligatorio.
- Nunca hardcodear tamaños en píxeles.
- Usar escala de espaciado Tailwind.
- Usar variables del `tailwind.config.js` para tokens.
- No repetir valores mágicos.

---

# Breakpoints Obligatorios

Todo componente, vista, modal y tabla debe soportar:

| Breakpoint | Prefijo | Mínimo |
|-----------|---------|--------|
| Mobile S | base | 320px |
| Mobile L | sm | 640px |
| Tablet | md | 768px |
| Tablet L | lg | 1024px |
| Desktop | xl | 1280px |
| Desktop L | 2xl | 1536px |

Ejemplo:

```html
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
```

---

# Layout y Estructura

Reglas:

- Layout principal usa:

```html
min-h-screen
```

- Contenedor principal:

```html
w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8
```

- Dos columnas:

```html
flex flex-col lg:flex-row gap-4
```

- Nunca:

```html
w-96
```

para contenedores principales.

Ejemplo:

```html
<div class="flex flex-col lg:flex-row gap-4 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <aside class="w-full lg:w-64 flex-shrink-0"></aside>
  <main class="flex-1 min-w-0"></main>
</div>
```

---

# Navegación y Sidebar (Vue 3)

Usar Vue state.
No Alpine.

```vue
<script setup>
import { ref } from 'vue'
const open = ref(false)
</script>

<template>
<button
 @click="open=!open"
 class="lg:hidden p-2 rounded-lg"
>
 Menú
</button>

<div
 v-if="open"
 @click="open=false"
 class="fixed inset-0 bg-black/50 z-20 lg:hidden"
></div>

<aside
 :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
 class="fixed lg:static inset-y-0 left-0 z-30 w-64 bg-white border-r transform transition-transform"
>
</aside>
</template>
```

Reglas:

- Sidebar drawer móvil.
- fixed desktop.
- área táctil mínima:

```html
min-h-[44px]
```

---

# Tablas Responsivas — Reglas Estrictas

Toda tabla:

- Debe tener:

```html
overflow-x-auto
```

- Columnas secundarias ocultables:

```html
hidden sm:table-cell
hidden md:table-cell
hidden lg:table-cell
```

- Acciones siempre visibles.
- Considerar versión card en móvil.

Ejemplo:

```html
<div class="overflow-x-auto rounded-lg border border-gray-200">
<table class="min-w-full divide-y divide-gray-200">
<thead class="bg-gray-50">
<tr>
<th class="px-4 py-3">Nombre</th>
<th class="hidden sm:table-cell px-4 py-3">Categoría</th>
<th class="hidden md:table-cell px-4 py-3">Stock</th>
<th class="hidden lg:table-cell px-4 py-3">Precio</th>
<th class="px-4 py-3">Acciones</th>
</tr>
</thead>
</table>
</div>
```

---

# Formularios Responsivos

Usar:

```html
grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4
```

Campos full:

```html
col-span-1 sm:col-span-2 lg:col-span-3
```

Botones:

```html
flex flex-col sm:flex-row gap-2 justify-end
```

Inputs:

```html
w-full
```

Nunca anchos fijos.

Ejemplo:

```html
<form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
<div class="col-span-1 sm:col-span-2 lg:col-span-3">
<input class="w-full" />
</div>
</form>
```

---

# Modales Responsivos

Usar:

```html
w-full sm:max-w-md lg:max-w-2xl
```

Padding:

```html
p-4 sm:p-6
```

Footer:

```html
flex flex-col-reverse sm:flex-row gap-2
```

Nunca ancho fijo.

Ejemplo Vue:

```vue
<Modal>
<div class="p-4 sm:p-6">
<h2 class="text-lg font-bold mb-4">
Crear Producto
</h2>

<div class="flex flex-col-reverse sm:flex-row gap-2 justify-end mt-6">
<button>Cancelar</button>
<button>Guardar</button>
</div>
</div>
</Modal>
```

---

# Cards y Paneles

Grid:

```html
grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4
```

Padding:

```html
p-4 sm:p-6
```

Métricas:

```html
text-xl sm:text-2xl lg:text-3xl font-bold
```

---

# Tipografía Responsiva

Títulos:

```html
text-xl sm:text-2xl lg:text-3xl
```

Subtítulos:

```html
text-base sm:text-lg
```

Body:

```html
text-sm sm:text-base
```

Labels:

```html
text-xs sm:text-sm
```

---

# Imágenes y Media

Usar:

```html
w-full h-auto
```

O:

```html
object-cover
```

Aspect ratios:

```html
aspect-square
aspect-video
```

Avatares:

```html
w-8 h-8 sm:w-10 sm:h-10
```

---

# Frontend (Vue + Inertia)

## Reglas

Usar:

- Composition API
- script setup
- composables
- Pinia si hay estado global complejo
- Inertia useForm para formularios
- SweetAlert2 para confirmaciones
- Chart.js para reportes
- Dropzone para uploads

---

# Prohibido

No usar:

- Alpine.js
- Wire UI
- Livewire patterns
- lógica duplicada servidor/cliente
- requests para abrir modales
- props gigantes en Inertia

---

# Performance Rules

## Props mínimas

Nunca:

```php
Product::all()
```

Siempre:

```php
Product::select('id','name')
->with('category:id,name')
->paginate(20)
```

---

## Estado local para UI

Modal:

```javascript
const open=ref(false)
```

No request.

---

## Composables

Extraer:

```javascript
useCart()
useFilters()
usePagination()
useNotifications()
```

---

## Watchers

Evitar watchers innecesarios.

Preferir:

```javascript
computed()
```

---

# PostgreSQL

Índices obligatorios para:

- phone
- code
- email
- document_number
- foreign keys
- filtros frecuentes

Nunca consultas sin índices.

---

# Calidad de Código

## Reglas

- Código autoexplicativo.
- Comentar solo por qué.
- SRP obligatorio.
- Preferir composición.
- Early return.
- No duplicación.
- Sin código comentado muerto.

---

## Evitar

```php
public function store(Request $request)
{
 if($request->has('name')){
   if(strlen($request->name)>3){
   }
 }
}
```

---

## Preferir

```php
public function store(StoreProductRequest $request): RedirectResponse
{
    $this->productService->create(
        $request->validated()
    );

    return redirect()->route(
        'products.index'
    );
}
```

---

# Checklist Obligatorio antes de Commit

## Responsividad

- [ ] Soporta 320px
- [ ] Probado 375px
- [ ] Probado 768px
- [ ] Probado 1280px
- [ ] Tablas con overflow-x-auto
- [ ] Forms apilan correctamente
- [ ] Modales no rompen móvil
- [ ] Sidebar drawer móvil
- [ ] Cards colapsan a una columna

---

## Performance

- [ ] Props mínimas
- [ ] Sin overfetching
- [ ] Sin N+1
- [ ] Queries con select
- [ ] Paginación
- [ ] Sin requests absurdos para UI local

---

## Vue Architecture

- [ ] Composition API
- [ ] script setup
- [ ] composables reutilizables
- [ ] No componente monstruo
- [ ] Estado bien ubicado

---

# Formato de Auditoría

Responder siempre:

## 1 Problemas encontrados
Lista numerada.

## 2 Código corregido
Vue / PHP / Tailwind.

## 3 Justificación
Por qué.

## 4 Impacto estimado
- reducción payload
- reducción queries
- mejora UX
```

