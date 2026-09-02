## Context

En el menú lateral administrativo de la aplicación (`app/Services/SidebarService.php` y `resources/js/Layouts/AdminLayout.vue`), las opciones operativas para solicitudes de consumo interno ("Registrar Consumo" y "Consumos Solicitados") están actualmente anidadas dentro de la sección "Inventario".

Esto genera una sobrecarga visual en la sección de inventario y dispersa los flujos de trabajo de los usuarios que solo interactúan con consumo interno (ej. rol Consumidor).

## Goals / Non-Goals

**Goals:**
- Separar las opciones de consumo interno de "Inventario" y organizarlas en un grupo de menú independiente denominado **Consumos**.
- Mantener la integridad de los filtros de permisos y visibilidad por roles (`filterByPermissions` en `SidebarService.php`).
- Preservar la compatibilidad con el breadcrumb/título activo de `AdminNavbar.vue` y el estado colapsable en `AdminLayout.vue`.

**Non-Goals:**
- No se modifican rutas, controladores ni endpoints backend (`routes/admin.php` permanece intacto).
- No se alteran vistas internas ni lógicas de negocio de las solicitudes de consumo.

## Decisions

### 1. Definición del nuevo grupo "Consumos" en SidebarService
- **Decisión**: Añadir un nuevo elemento de primer nivel en `$this->buildMenu()` inmediatamente después de "Inventario" y antes de "Compras":
  ```php
  [
      'label' => 'Consumos',
      'icon' => 'clipboard-document-list',
      'permission' => 'manage-inventory',
      'children' => [
          [
              'label' => 'Registrar Consumo',
              'icon' => 'computer-desktop',
              'route' => 'admin.consumption-requests.create',
              'permission' => 'manage-inventory',
          ],
          [
              'label' => 'Consumos Solicitados',
              'icon' => 'clipboard-document-list',
              'route' => 'admin.consumption-requests.index',
              'permission' => 'manage-inventory',
          ],
      ]
  ]
  ```
- **Alternativas consideradas**:
  - *Mantener un solo enlace directo a `/admin/consumption-requests`*: Descartado porque el usuario necesita acceso directo en un clic tanto a la creación como al listado.
  - *Crear una sección plana sin desplegable*: Descartado para mantener coherencia visual con "Inventario" y "Compras".

### 2. Visibilidad y Permisos
- En `filterByPermissions()`:
  - Cuando se evalúan los hijos de un grupo, si el rol es `Consumidor`, los hijos con rutas `admin.consumption-requests.*` son admitidos explícitamente y hacen que el menú padre `Consumos` se mantenga visible (`count($item['children']) > 0`).
  - Para el rol `Almacén`, `admin.consumption-requests.create` sigue bloqueado, por lo que `Consumos` se renderiza conteniendo únicamente `Consumos Solicitados`.

## Risks / Trade-offs

- **[Risk]** Que el rol Consumidor pierda visibilidad del nuevo grupo si `permission` en el padre está condicionado a `manage-inventory`.
  - **Mitigación**: `filterByPermissions` evalúa primero los `children` de forma recursiva; si los hijos tienen permiso/excepción válida para el usuario, el grupo padre se conserva automáticamente.
