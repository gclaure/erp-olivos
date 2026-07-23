## 1. Frontend - Restringir botón Cancelar

- [x] 1.1 Actualizar la condición `v-if` del botón Cancelar Solicitud en `Show.vue:1665` para que solo sea visible cuando `isConsumidorRole && request.status === 'pendiente'`
- [x] 1.2 Verificar que el botón no aparezca para usuarios con rol Almacén, Admin/Administrador o super_admin en ningún estado

## 2. Specs

- [x] 2.1 Sincronizar delta spec de `consumer-request-detail-ui` con el main spec (`openspec/specs/consumer-request-detail-ui/spec.md`)
