## Context

La vista `/admin/purchases/create` está optimizada para escritorio, pero en pantallas pequeñas genera problemas visuales severos: textos desalineados en la cabecera del buscador, resultados de productos cortados horizontalmente en el dropdown y dificultad para editar ítems dentro de una tabla de 7 columnas en un teléfono móvil.

## Goals / Non-Goals

**Goals:**
- Implementar el patrón híbrido dual en `Purchase/Create.vue`:
  - `md:hidden`: Tarjetas operativas individuales para cada ítem en el carrito con controles táctiles (44px min).
  - `hidden md:block`: Tabla tabular completa optimizada.
- Rediseñar el dropdown de búsqueda para soportar estructura en 2 filas jerárquicas en móvil y horizontal en desktop, evitando cualquier corte o desbordamiento horizontal.
- Adaptar los paddings de contenedores (`p-4 sm:p-6 lg:p-8`) y los formularios del Paso 1 para fluidez móvil total.
- Apilar los botones de acción en móvil a ancho completo con `flex-col-reverse sm:flex-row`.

**Non-Goals:**
- No alterar la lógica de negocio, endpoints ni modelos de base de datos.
- No modificar otras vistas no relacionadas del sistema.

## Decisions

1. **Patrón Dual en Vue 3 (`v-for` en cards móvil + `v-for` en `<tbody>` desktop)**:
   - *Razón*: Permite que el usuario móvil tenga una experiencia nativa táctil sin scroll horizontal ni zoom, mientras que los usuarios en desktop mantienen la densidad de información propia de una tabla administrativa.

2. **Estructura del Dropdown Responsivo**:
   - Usar clases de Tailwind `flex flex-col sm:flex-row sm:items-center justify-between gap-3`.
   - En móvil, fila superior (Nombre + Código + Badge) y fila inferior con métricas (Stock en Almacén + Precio Referencial).

3. **Acciones Táctiles y Accesibilidad**:
   - Inputs numéricos con `text-base` en móvil para evitar auto-zoom en iOS Safari (`font-size >= 16px`), y botones con altura mínima táctil de 44px.

## Risks / Trade-offs

- **[Riesgo]** Duplicación de bindings de inputs (`v-model="item.quantity"`) entre la tarjeta móvil y la fila de tabla.
  - **Mitigación**: Vue 3 actualiza reactivamente el mismo objeto del arreglo `form.details`, por lo que cambiar de resolución o usar cualquier vista sincroniza instantáneamente el estado del formulario.
