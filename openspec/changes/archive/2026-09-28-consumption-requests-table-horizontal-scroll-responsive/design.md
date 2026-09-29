# Design

## Context

En el módulo de solicitudes de consumo (`resources/js/Pages/Admin/ConsumptionRequest/Index.vue`), la tabla de escritorio posee 8 columnas incluyendo números de folio, timestamps, áreas, almacén, solicitante, badges semáforo de disponibilidad y acciones. Al consultar el sistema en pantallas portátiles (13 pulgadas con resolución de 1280px a 1440px y sidebar lateral de 256px), el espacio horizontal disponible se reduce a menos de 1000px, comprimiendo las celdas.

## Goals / Non-Goals

**Goals:**
- Establecer un ancho mínimo contundente (`min-w-[1180px]`) en la tabla para mantener la armonía tipográfica y los badges de estado legibles.
- Implementar una barra de desplazamiento horizontal `.table-scrollbar-visible` (10px, redondeada, con track y thumb de alto contraste para light/dark mode).
- Fijar la columna de `Acciones` a la derecha (`sticky right-0`) para que "Ver Detalle" esté siempre accesible al desplazarse lateralmente.
- Incorporar una barra de asistencia de navegación horizontal `sticky top-0 z-30` con backdrop blur y botones ergonómicos `chevron_left` y `chevron_right`, que no se pierda al hacer scroll vertical hacia abajo.

**Non-Goals:**
- No alterar la vista móvil de tarjetas apiladas (`md:hidden`), la cual ya está optimizada para smartphones.
- No alterar los endpoints ni la lógica de permisos o filtrado en `ConsumptionRequestController`.

## Decisions

### 1. Barra de Asistencia con Posicionamiento Sticky (`sticky top-0 z-30`)
- **Decisión**: Colocar la barra de asistencia y botones de desplazamiento lateral fuera del contenedor con `overflow-hidden` con clase `sticky top-0 z-30 bg-white/95 dark:bg-secondary-800/95 backdrop-blur-md shadow-md border-zinc-200/90 dark:border-secondary-700/90`.
- **Razón**: Al navegar verticalmente a través de las 20 a 50 solicitudes de la página, la barra se mantiene anclada en el tope del área de contenido, evitando que el usuario deba subir al inicio de la página para presionar los botones `‹` o `›`.

### 2. Columna de Acciones Sticky a la Derecha (`sticky right-0`)
- **Decisión**:
  - En `th` de Acciones: `sticky right-0 z-20 bg-zinc-50 dark:bg-secondary-900 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] dark:shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.3)]`.
  - En `td` de Acciones: `sticky right-0 z-10 bg-white dark:bg-secondary-800 group-hover:bg-zinc-50 dark:group-hover:bg-secondary-700/40 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] dark:shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.3)]`.
- **Razón**: Permite inspeccionar columnas intermedias sin perder nunca la posibilidad de hacer clic en "Ver Detalle".

### 3. Ancho Mínimo de Tabla (`min-w-[1180px]`)
- **Decisión**: Definir `min-w-[1180px]` en la etiqueta `<table>` para que las columnas complejas (especialmente la de estado con indicadores de semáforo de insumos faltantes) nunca se colapsen.

### 4. Estilos Scoped `.table-scrollbar-visible`
- **Decisión**: Añadir las reglas de scrollbar de 10px en `<style scoped>` de `ConsumptionRequest/Index.vue`, compatibles con WebKit y Firefox (`scrollbar-width: thin; scrollbar-color`).

## Risks / Trade-offs

- **[Riesgo]** Superposición de fondos transparentes en la columna sticky durante el scroll.
  → **Mitigación**: Fondos 100% opacos correspondientes a cada estado de fila (normal, hover, seleccionada) en light y dark mode.
