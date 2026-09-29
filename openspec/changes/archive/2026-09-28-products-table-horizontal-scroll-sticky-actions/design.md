# Design

## Context

En el dashboard administrativo, la vista de catálogo de productos (`resources/js/Pages/Admin/Product/Index.vue`) utiliza un layout con sidebar fijo (`w-64`). En laptops con pantallas de 13 pulgadas (1280px - 1440px), el espacio horizontal disponible para la tabla ronda los 970px. La tabla contiene 8 columnas con texto descriptivo y badges que superan ese ancho, lo que corta las columnas de la derecha. El contenedor cuenta con `overflow-x-auto`, pero la ausencia de estilos explícitos de scrollbar horizontal y la posición estática de las acciones dificultan la navegación y provocan la pérdida visual de los botones de acción.

## Goals / Non-Goals

**Goals:**
- Proporcionar una barra de scroll horizontal visible y manipulable tanto con mouse como con trackpad en el contenedor de la tabla de productos.
- Implementar la columna de `Acciones` con posición fija a la derecha (`sticky right-0`), asegurando que siempre esté visible y operable independientemente de la posición de scroll.
- Asegurar un fondo sólido y opaco en la columna sticky (`bg-white` en modo claro, `bg-secondary-800` en modo oscuro) con sombra lateral para separar visualmente las columnas que pasan por debajo.
- Mantener las 8 columnas actuales sin alterar su contenido ni estructura de datos.

**Non-Goals:**
- No se ocultarán columnas secundarias (`hidden lg:...`) para respetar la directiva explícita de conservar todas las columnas existentes.
- No se modificarán vistas móviles (`md:hidden`), las cuales ya operan bajo el sistema de tarjetas apiladas.

## Decisions

### 1. Definición de Scrollbar Horizontal en CSS Global y Componente
- **Decisión**: Extender la utilidad `.custom-scrollbar` en `resources/css/app.css` agregando `height: 6px;` para pseudo-elementos `::-webkit-scrollbar`, asegurando que los contenedores horizontales muestren barra visible en WebKit/Blink (Safari, Chrome, Edge), además de soporte para `scrollbar-width: thin` y `scrollbar-color`.
- **Alternativa descartada**: Dejar el scroll nativo de macOS sin estilos: causa invisibilidad por defecto a menos que el usuario active la preferencia en el sistema operativo.

### 2. Columna de Acciones Sticky a la Derecha (`sticky right-0`)
- **Decisión**:
  - En `<th>` de Acciones: `sticky right-0 z-20 bg-zinc-50 dark:bg-zinc-800/90 shadow-[-6px_0_10px_-3px_rgba(0,0,0,0.06)]`.
  - En `<td>` de Acciones: `sticky right-0 z-10 bg-white dark:bg-secondary-800/95 group-hover:bg-zinc-50 dark:group-hover:bg-secondary-700 shadow-[-6px_0_10px_-3px_rgba(0,0,0,0.06)]`.
  - Esto garantiza que al desplazarse a la izquierda o derecha, los botones de Editar y Eliminar siempre estén al alcance del cursor.
- **Alternativa descartada**: Colocar acciones en un dropdown flotante: añade clics innecesarios y rompe la consistencia visual existente.

### 3. Ancho Mínimo de Tabla (`min-w-[1020px]`)
- **Decisión**: Aplicar `min-w-[1020px]` a la etiqueta `<table>` para que mantenga su proporción estética sin que las celdas se apretujen o deformen cuando el viewport sea inferior a 1280px.

## Risks / Trade-offs

- **[Riesgo]** Superposición de texto o transparencia en la columna sticky durante el scroll.
  → **Mitigación**: Asignar fondos totalmente opacos que respeten los estados normales y hover de cada fila tanto en light mode (`bg-white`, hover `bg-zinc-50`) como en dark mode (`bg-secondary-800`, hover `bg-secondary-700`), acompañados de z-index adecuado (`z-20` para thead, `z-10` para tbody).
- **[Riesgo]** Conflicto con esquinas redondeadas en `rounded-3xl` del contenedor padre.
  → **Mitigación**: Mantener `overflow-hidden` en el contenedor tarjeta exterior y ubicar el scroll en el wrapper interno `overflow-x-auto custom-scrollbar`.
