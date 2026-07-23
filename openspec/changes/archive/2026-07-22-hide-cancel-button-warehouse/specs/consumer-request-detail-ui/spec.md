## MODIFIED Requirements

### Requirement: Cancelar solo en pendiente para Consumidor

El Consumidor SHALL poder ver la acción Cancelar Solicitud únicamente cuando el estado sea `pendiente`. En estado `aprobado` el Consumidor MUST NOT ver Cancelar. Ningún usuario con rol distinto a Consumidor (Almacén, Admin/Administrador, super_admin) SHALL ver el botón Cancelar Solicitud en ningún estado.

#### Scenario: Cancelar visible en pendiente

- **WHEN** un Consumidor ve una solicitud en `pendiente`
- **THEN** MUST ver el botón Cancelar Solicitud

#### Scenario: Cancelar oculto tras aprobación

- **WHEN** un Consumidor ve una solicitud en `aprobado`
- **THEN** MUST NOT ver el botón Cancelar Solicitud

#### Scenario: Almacén no ve Cancelar en ningún estado

- **WHEN** un usuario con rol Almacén ve una solicitud en cualquier estado (`pendiente`, `aprobado`, `observado`, `despachado`)
- **THEN** MUST NOT ver el botón Cancelar Solicitud

#### Scenario: Admin no ve Cancelar en ningún estado

- **WHEN** un usuario con rol Admin/Administrador o super_admin ve una solicitud en cualquier estado
- **THEN** MUST NOT ver el botón Cancelar Solicitud
