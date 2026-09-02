## Why

Actualmente, las rutas de solicitudes de consumo (`/admin/consumption-requests/create` y `/admin/consumption-requests`) se encuentran ubicadas dentro del menú desplegable "Inventario". Esto sobrecarga la sección de inventario y dificulta que los usuarios (en especial con rol Consumidor y Almacén) ubiquen de forma rápida y clara las acciones operativas de consumos internos. Crear un menú desplegable dedicado llamado **Consumos** mejora la jerarquía visual y la ergonomía de navegación en el sistema.

## What Changes

- Se extraen las rutas `admin.consumption-requests.create` ("Registrar Consumo") y `admin.consumption-requests.index` ("Consumos Solicitados") del grupo "Inventario".
- Se crea un nuevo grupo desplegable de primer nivel en el sidebar denominado **Consumos** con icono descriptivo (`clipboard-document-list` o `assignment`).
- Se anidan ambas opciones (`Registrar Consumo` y `Consumos Solicitados`) como elementos hijos dentro del nuevo menú **Consumos**.
- Se mantienen las reglas de control de acceso y visibilidad por roles:
  - **Consumidor**: Visualiza el grupo "Consumos" con acceso a "Registrar Consumo" y "Consumos Solicitados".
  - **Almacén**: Visualiza el grupo "Consumos" con acceso exclusivo a "Consumos Solicitados" (ocultando "Registrar Consumo").
  - **Administrador / Super Admin**: Visualiza el grupo "Consumos" con acceso completo a ambas opciones.

## Capabilities

### New Capabilities
- `consumption-requests-sidebar-menu`: Estructuración de un módulo de navegación independiente en el sidebar para la gestión de solicitudes y registro de consumo interno.

### Modified Capabilities
<!-- Sin modificaciones en specs de requerimientos funcionales previos -->

## Impact

- `app/Services/SidebarService.php`: Modificación en la definición y filtrado de los items del menú del sidebar.
- `resources/js/Layouts/AdminLayout.vue` y `resources/js/Layouts/Partials/AdminNavbar.vue`: Verificación de consistencia del renderizado y breadcrumb / label de rutas activas.
