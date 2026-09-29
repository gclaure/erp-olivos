# Proposal

## Why

En la vista contable `/admin/kardex`, la tabla contiene 13 columnas organizadas en dos niveles de cabecera (Fecha, Producto, Descripción, Entradas, Salidas, Saldos Totales y Registrado por Usuario). En laptops con pantallas de 13 pulgadas o viewports entre 1280px y 1440px, la tabla carece de un ancho mínimo estructurado y de una barra de scroll horizontal visible, lo que comprime y corta las columnas de saldos e impide una navegación fluida entre los registros de entrada y salida de inventario.

## What Changes

- **Barra de Scroll Horizontal Visible y Permanente**: Aplicar la clase `.table-scrollbar-visible` con altura explícita (`height: 10px`), riel (track) contrastado y tirador (thumb) visible y arrastrable tanto en modo claro como en modo oscuro.
- **Ancho Mínimo Estructurado (`min-w-[1380px]`)**: Fijar un ancho mínimo en la tabla contable para que las 13 columnas preserven sus alineaciones numéricas, etiquetas de almacén y montos contables sin comprimirse ni desbordar de forma desordenada.
- **Barra Superior de Ayuda y Controles de Desplazamiento**: Incorporar una barra de navegación con indicador descriptivo y botones interactivos `‹` y `›` (`scrollTable('left')` / `scrollTable('right')`) para avanzar y retroceder de manera asistida y cómoda entre entradas, salidas y saldos.

## Capabilities

### New Capabilities
- `kardex-table-horizontal-scroll`: Requerimientos y escenarios para el desplazamiento horizontal visible, fluido y asistido de la tabla contable de Kardex en pantallas de 13 pulgadas y laptops.

### Modified Capabilities
<!-- Ninguna especificación existente cambia sus requisitos -->

## Impact

- Frontend: `resources/js/Pages/Admin/Kardex/Index.vue`.
- Sin cambios en controladores PHP ni base de datos.
