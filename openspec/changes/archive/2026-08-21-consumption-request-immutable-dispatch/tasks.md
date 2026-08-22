## 1. Actualización de la Vista de Despacho en ConsumptionRequest/Show.vue

- [x] 1.1 Modificar la columna de despacho en la tabla desktop (`td v-if="canUserDispatch"`) para que la cantidad sea fija/bloqueada y eliminar los textareas individuales y botón de dictado de despacho
- [x] 1.2 Modificar la sección de despacho en las tarjetas móviles (`md:hidden`) para que la cantidad sea fija/bloqueada y eliminar los textareas individuales y botón de dictado de despacho
- [x] 1.3 Limpiar variables reactivas y métodos en desuso manteniendo la observación de discrepancia de recepción (`diff`) intacta

## 2. Verificación y Compilación

- [x] 2.1 Ejecutar `pnpm build` para asegurar la compilación de estilos y componentes
- [x] 2.2 Validar que el flujo de despacho funcione correctamente enviando las cantidades fijadas
