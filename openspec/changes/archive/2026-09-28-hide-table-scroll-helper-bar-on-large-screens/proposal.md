# Proposal

## Why

La barra de asistencia y botones de navegación rápida de las tablas contables y de catálogo (en Productos, Kardex y Solicitudes de Consumo) se diseñó para laptops y dispositivos donde el ancho de la tabla desborda el viewport (13 a 14 pulgadas). Sin embargo, en monitores de escritorio y pantallas de 15.6 pulgadas o superiores (resoluciones >= 1536px o 1080p nativo), la totalidad de las columnas cabe holgadamente sin desbordamiento horizontal. En estos dispositivos grandes y en celulares (donde ya se usan vistas apiladas en tarjetas), la barra resulta redundante y añade ruido visual innecesario en la parte superior.

## What Changes

- Modificar la visibilidad responsiva de la barra de asistencia y botones de navegación horizontal en las tres vistas clave:
  - `resources/js/Pages/Admin/Product/Index.vue`
  - `resources/js/Pages/Admin/Kardex/Index.vue`
  - `resources/js/Pages/Admin/ConsumptionRequest/Index.vue`
- Aplicar la combinación responsiva `hidden md:flex 2xl:hidden`:
  - **Móviles (< 768px)**: Oculto (`hidden`), respetando la experiencia móvil basada en cards.
  - **Laptops 13" - 14" (768px a 1535px)**: Visible (`md:flex`), asistiendo al usuario con controles suaves y permanentes (`sticky top-0 z-30`).
  - **Pantallas >= 15.6" y monitores de escritorio (>= 1536px)**: Oculto (`2xl:hidden`), dejando la interfaz limpia y despejada cuando no hay desbordamiento.

## Capabilities

### New Capabilities
- `responsive-table-scroll-helper-visibility`: Regla de visibilidad responsiva condicionada que oculta la barra de asistencia horizontal tanto en móviles como en pantallas grandes (15+ pulgadas), manteniéndola activa exclusivamente en laptops y pantallas medianas.

### Modified Capabilities
<!-- No se modifican capacidades previas -->

## Impact

- Frontend:
  - `resources/js/Pages/Admin/Product/Index.vue`
  - `resources/js/Pages/Admin/Kardex/Index.vue`
  - `resources/js/Pages/Admin/ConsumptionRequest/Index.vue`
- Sin impacto en base de datos ni backend.
