# Design

## Context

Actualmente, las barras superiores de navegación horizontal en `Product/Index.vue`, `Kardex/Index.vue` y `ConsumptionRequest/Index.vue` se muestran de forma indiscriminada en monitores grandes de 15"+ donde todo el ancho de las tablas cabe de manera natural. Además, en `Kardex/Index.vue`, la barra no tenía la clase `hidden` para móviles, mostrándose innecesariamente en pantallas celulares.

## Goals / Non-Goals

**Goals:**
- Homogeneizar la regla de visibilidad en las tres vistas usando clases declarativas estándar de Tailwind CSS: `hidden md:flex 2xl:hidden`.
- Ocultar la barra de asistencia y sus botones en celulares (`< 768px`).
- Mostrar la barra sticky exclusivamente en laptops y pantallas intermedias (`768px` hasta `1535px`).
- Ocultar la barra en pantallas de 15.6 pulgadas en adelante y monitores de escritorio (`>= 1536px`).

**Non-Goals:**
- No alterar el método `scrollTable` ni la capacidad de hacer scroll manual o trackpad en monitores grandes.
- No alterar los anchos mínimos de las tablas ni la columna de acciones sticky.

## Decisions

### 1. Patrón Declarativo de Breakpoint Tailwind: `hidden md:flex 2xl:hidden`
- **Decisión**: Aplicar la combinación de clases:
  `class="hidden md:flex 2xl:hidden sticky top-0 z-30 ..."`
  en el contenedor de la barra de navegación de:
  - `resources/js/Pages/Admin/Product/Index.vue`
  - `resources/js/Pages/Admin/Kardex/Index.vue`
  - `resources/js/Pages/Admin/ConsumptionRequest/Index.vue`
- **Razón**:
  - `hidden`: Oculta en móviles (0 - 767px).
  - `md:flex`: Activa flexbox a partir de 768px (laptops 11"-14").
  - `2xl:hidden`: Oculta la barra a partir de 1536px (laptops 15.6"+ y monitores de escritorio 1080p, 2K, 4K).
- **Alternativas consideradas**:
  - Detección JS mediante ResizeObserver: Añade overhead reactivo y complejidad innecesaria para un problema que se resuelve de forma inmediata y sin flicker mediante CSS nativo.

## Risks / Trade-offs

- **[Riesgo]** Usuarios en laptops de 15.6" con escalado de pantalla al 150% (viewport efectivo 1280px).
  → **Mitigación**: El escalado de pantalla reduce el ancho CSS en píxeles a ~1280px, por lo que la regla `md:flex 2xl:hidden` se activará correctamente ya que 1280px < 1536px.
