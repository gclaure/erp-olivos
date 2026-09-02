## ADDED Requirements

### Requirement: Aligned System Permissions Matrix
The system SHALL define a cohesive set of permissions matching the active ERP modules (Inventario, Consumos, Compras, Administración) and remove obsolete commercial sales permissions.

#### Scenario: Display available permissions in Edit Role modal
- **WHEN** an authorized administrator opens the "Editar Rol" modal in Roles management
- **THEN** the modal lists available permissions with clear labels and descriptions:
  - "Registrar Consumo" (`create-consumption`): Permite solicitar insumos y registrar pedidos de consumo interno desde el catálogo.
  - "Gestionar Consumos" (`manage-consumption`): Revisa, aprueba, despacha y cancela solicitudes de consumo interno.
  - "Gestionar Inventario" (`manage-inventory`): Controla stock, ajustes manuales (mermas) y movimientos (Kardex).
  - "Gestionar Productos" (`manage-products`): Crea y edita el catálogo de productos y unidades de medida.
  - "Gestionar Categorías" (`manage-categories`): Organiza y clasifica productos en categorías.
  - "Gestionar Almacenes" (`manage-warehouses`): Organiza los depósitos físicos de mercancía.
  - "Crear Compras" (`create-purchases`): Registra nuevas facturas de compra de mercancía.
  - "Gestionar Compras" (`manage-purchases`): Ver historial de compras, órdenes de compra y estados de pago.
  - "Gestionar Proveedores" (`manage-providers`): Administra la información de los abastecedores.
  - "Gestionar Usuarios" (`manage-users`): Administra las cuentas de acceso de los usuarios.
  - "Gestionar Roles" (`manage-roles`): Define perfiles de seguridad y asigna permisos.
  - "Gestionar Datos de Empresa" (`manage-company`): Modifica logo y configuración legal de la compañía.
  - "Administrar Sucursales" (`manage-branches`): Gestiona las sedes físicas de la empresa.
  - "Ver Reportes BI" (`view-reports`): Accede a analíticas de compras, inventario y consumos.

### Requirement: Role assignment and Sidebar authorization
The system SHALL authorize sidebar navigation based on Spatie permissions assigned to each role.

#### Scenario: Consumer role authorization
- **WHEN** a user with role "Consumidor" logs into the system
- **THEN** the user possesses `create-consumption` and has access to the "Consumos" menu (Registrar Consumo y Solicitudes) without hardcoded role logic in sidebar filters.

#### Scenario: Warehouse role authorization
- **WHEN** a user with role "Almacén" logs into the system
- **THEN** the user possesses `manage-inventory`, `manage-consumption`, and `manage-purchases`, displaying the corresponding operational menus.
