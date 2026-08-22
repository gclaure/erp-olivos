## 1. Rediseño Responsivo del Buscador y Dropdown

- [x] 1.1 Adaptar la cabecera del buscador en `Purchase/Create.vue` con `flex-col sm:flex-row` y espaciados responsivos
- [x] 1.2 Rediseñar los ítems del dropdown de búsqueda para mostrar 2 filas estructuradas en móvil (`< 640px`) y distribución horizontal en desktop (`≥ 640px`)
- [x] 1.3 Optimizar el placeholder y tamaño de texto del input para pantallas pequeñas

## 2. Implementación del Patrón Dual para Detalle de Compras

- [x] 2.1 Crear la vista de tarjetas operativas móviles (`md:hidden`) para cada producto en `form.details` con controles táctiles (44px min), selector de formato e inputs apilados
- [x] 2.2 Envolver la tabla HTML existente en `hidden md:block` manteniendo toda la funcionalidad y estilos de escritorio
- [x] 2.3 Adaptar el panel de Total General y botones de acción con `flex-col-reverse sm:flex-row` y ancho completo en móviles

## 3. Ajustes de Contenedores y Verificación

- [x] 3.1 Reducir paddings de contenedores a `p-4 sm:p-6 lg:p-8` en pasos 1, 2 y 3
- [x] 3.2 Probar la responsividad en resoluciones móviles (320px, 375px, 414px) y tablets (768px, 1024px)
- [x] 3.3 Ejecutar `pnpm build` para asegurar la compilación de estilos y componentes
