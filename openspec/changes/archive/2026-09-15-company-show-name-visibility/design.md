## Context

El switch de configuración "Mostrar nombre en el menú" (`show_name`) se almacena correctamente en la base de datos, pero el middleware de Inertia inyectaba un valor fijo `true` y el layout administrativo no contenía la lógica de renderizado del texto del nombre. Asimismo, la plantilla Blade de impresión PDF imprimía el título de la empresa sin consultar la configuración `show_name`.

## Goals / Non-Goals

**Goals:**
- Conectar reactivamente `show_name` desde la base de datos a través de `HandleInertiaRequests.php`.
- Renderizar en `AdminLayout.vue` el nombre de la empresa bajo el logo únicamente si `company.show_name` es verdadero y la barra lateral no está en modo mini.
- Evaluar en `receipt.blade.php` la condición `@if($company?->show_name ?? true)` antes de renderizar la clase `.company-name`.

**Non-Goals:**
- Cambiar la tipografía o el estilo general del PDF ni de la barra lateral.

## Decisions

### 1. Manejo en `AdminLayout.vue`
- Utilizar `company?.logo_url || '/img/logo-dark.png'` para el logo.
- Añadir un contenedor condicional con tipografía sobria y estilizada:
  `<span v-if="(company?.show_name ?? true) && !sidebarMini" class="text-xs font-black text-zinc-100 tracking-wider uppercase truncate max-w-[190px] mt-1">{{ company?.name }}</span>`

### 2. Manejo en `receipt.blade.php`
- Envolver `<div class="company-name">` en `@if($company?->show_name ?? true)`.
