# Design

## Context

El layout administrativo `resources/js/Layouts/AdminLayout.vue` utiliza una variable reactiva `openMenus` (array) con almacenamiento en `localStorage`. Cuando el usuario hace clic en los items con submenú, el método actual añade la etiqueta al array con `push()`, lo que provoca que múltiples submenús queden abiertos al mismo tiempo.
Ver `proposal.md` y `specs/sidebar-accordion-navigation/spec.md`.

## Goals / Non-Goals

**Goals:**
- Convertir la lógica de apertura a modo acordeón: al hacer clic en un menú, se asigna únicamente ese menú a `openMenus` (`openMenus.value = [label]`); si ya estaba abierto, se vacía (`openMenus.value = []`).
- Sincronizar en el montaje (`onMounted`) o watcher de ruta la auto-apertura del submenú cuya ruta hija coincida con `route().current(child.route)`.
- Reemplazar estilos residuales de `indigo-600` / `indigo-400` en `AdminLayout.vue` por los tokens de diseño oficiales Verde Olivo `primary-600` / `primary-400`.

**Non-Goals:**
- No se altera la estructura de datos que provee los items del sidebar (`sidebarItems`).
- No se modifica la navegación móvil ni el botón de toggle sidebar mini.

## Decisions

1. **Estado reactivo simplificado**: Mantener `openMenus` como array de un solo elemento `[label]` para compatibilidad inmediata con `isMenuOpen(label)` en la plantilla, pero asegurando exclusividad mutua en `toggleMenu`.
2. **Detección de ruta activa en el montaje**: En `onMounted`, iterar `sidebarItems` buscando si algún hijo está activo para abrir su padre de forma natural.
3. **Persistencia en `localStorage`**: Seguir guardando el array con el único item abierto para que se preserve entre refrescos de página.

## Risks / Trade-offs

- **[Navegación rápida entre páginas de distintas secciones]**: Si el usuario navega a otra sección, el watcher/mounted actualizará el menú abierto de forma coherente sin desorientar al usuario.
