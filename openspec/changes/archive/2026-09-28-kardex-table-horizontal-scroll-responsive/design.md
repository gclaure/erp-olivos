# Design

## Context

En `/admin/kardex`, la tabla contable maneja 13 columnas organizadas en un encabezado de dos niveles: Fecha, Producto (Almacén), Descripción, Entradas (Cant., C. Unit., Total), Salidas (Cant., C. Unit., Total), Saldos Totales (Cant., C. Prom., Total) y Registrado por Usuario. En laptops con pantallas de 13 pulgadas, el área disponible (~976px - 1100px) resulta estrecha para alojar todas las columnas sin desbordamiento. La tabla carece de `min-w` estructurado y de una barra de scroll horizontal visible en macOS y navegadores estándar.

## Goals / Non-Goals

**Goals:**
- Establecer un ancho mínimo de `min-w-[1380px]` en la tabla del Kardex para garantizar la separación y legibilidad de las 13 columnas contables.
- Aplicar la clase `.table-scrollbar-visible` con altura explícita (`height: 10px`) y riel/tirador contrastados para light y dark mode en el wrapper de la tabla.
- Integrar una barra de navegación superior con leyenda y botones de desplazamiento interactivos `‹` y `›` (`scrollTable('left')` y `scrollTable('right')`).
- Conservar los colores semánticos de las columnas (verde para Entradas, rojo para Salidas, índigo para Saldos).

**Non-Goals:**
- No se ocultarán columnas contables esenciales (Cant., C. Unit., C. Prom., Totales) para preservar la integridad analítica del Kardex.
- No se alterarán los cálculos ni la lógica de backend de exportación PDF o Excel.

## Decisions

### 1. Ancho Mínimo de 1380px
- **Decisión**: Fijar `min-w-[1380px]` en `<table>`. Con 13 columnas, cada bloque de 3 columnas (Entradas, Salidas, Saldos) requiere ~220px, más Fecha (~120px), Producto (~300px), Descripción (~200px) y Usuario (~100px). 1380px permite desplegar toda la información con máxima holgura.

### 2. Barra de Desplazamiento Permanente (`table-scrollbar-visible`)
- **Decisión**: Utilizar la clase `.table-scrollbar-visible` en el wrapper de la tabla y definir sus reglas en `<style scoped>` de `Kardex/Index.vue`:
  - `overflow-x: scroll !important`
  - `height: 10px !important`
  - Track: `#f1f5f9` (light) / `#181920` (dark)
  - Thumb: `#94a3b8` (light) / `#52525b` (dark) con bordes redondeados.

### 3. Asistencia de Desplazamiento por Botones
- **Decisión**: Agregar en el script `const tableContainer = ref(null)` y método `scrollTable(direction)` que desplace `380px` (el equivalente aproximado a un bloque contable completo) de forma fluida (`behavior: 'smooth'`).

## Risks / Trade-offs

- **[Riesgo]** Desbordamiento en pantallas pequeñas móviles.
  → **Mitigación**: `overflow-x: scroll` maneja perfectamente el desplazamiento táctil con `-webkit-overflow-scrolling: touch;`.
