## Context

El sistema gestiona roles y permisos mediante `spatie/laravel-permission`. La vista de administración de roles ([Roles/Index.vue](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/resources/js/Pages/SuperAdmin/Roles/Index.vue)) y su controlador ([RoleController.php](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/app/Http/Controllers/SuperAdmin/RoleController.php)) presentan una lista de 20 permisos heredados, muchos de los cuales hacen referencia a ventas comerciales y puntos de venta ya no vigentes en este ERP.

## Goals / Non-Goals

**Goals:**
- Actualizar la tabla de permisos en PostgreSQL:
  - Renombrar `create-sales` a `create-consumption`.
  - Agregar `manage-consumption` si no existe o reutilizar `manage-sales` renombrado.
  - Eliminar permisos obsoletos (`manage-clients`, `manage-deliveries`, `manage-pos`, `pos-access`, `manage-transfers`, `manage-settings`) o asegurar que no ensucien la UI de roles.
- Actualizar diccionarios de traducción y descripciones en `RoleController.php`, `RoleResource.php`, `Roles/Index.vue` y `AddRoleModal.vue`.
- Asegurar que `Consumidor` tenga asignado `create-consumption`, `Almacén` tenga sus permisos operativos, y `Administrador` mantenga acceso total.
- Actualizar `SidebarService.php` para asociar las rutas de consumo a sus permisos correspondientes (`create-consumption` y `manage-consumption`).

**Non-Goals:**
- No se cambian rutas HTTP existentes.
- No se altera la estructura de tablas de `roles` y `permissions` (solo su contenido/datos).

## Decisions

### 1. Definición de la matriz limpia de permisos
```php
$permissionTranslations = [
    'create-consumption' => 'Registrar Consumo',
    'manage-consumption' => 'Gestionar Consumos',
    'manage-inventory'   => 'Gestionar Inventario',
    'manage-products'    => 'Gestionar Productos',
    'manage-categories'  => 'Gestionar Categorías',
    'manage-warehouses'  => 'Gestionar Almacenes',
    'create-purchases'   => 'Crear Compras',
    'manage-purchases'   => 'Gestionar Compras',
    'manage-providers'   => 'Gestionar Proveedores',
    'manage-users'       => 'Gestionar Usuarios',
    'manage-roles'       => 'Gestionar Roles',
    'manage-company'     => 'Gestionar Datos de Empresa',
    'manage-branches'    => 'Administrar Sucursales',
    'view-reports'       => 'Ver Reportes BI',
];
```

### 2. Diccionario de descripciones en el frontend
En `Roles/Index.vue` y `AddRoleModal.vue`:
```js
const permissionDescriptions = {
    'create-consumption': 'Permite solicitar insumos y registrar pedidos de consumo interno desde el catálogo.',
    'manage-consumption': 'Revisa, aprueba, despacha y cancela solicitudes de consumo interno.',
    'manage-inventory': 'Controla stock, ajustes manuales (mermas) y movimientos (Kardex).',
    'manage-products': 'Crea y edita el catálogo de productos y unidades de medida.',
    'manage-categories': 'Organiza y clasifica productos en categorías.',
    'manage-warehouses': 'Organiza los depósitos físicos de mercancía.',
    'create-purchases': 'Registra nuevas facturas de compra de mercancía.',
    'manage-purchases': 'Ver historial de compras, órdenes de compra y estados de pago.',
    'manage-providers': 'Administra la información de los abastecedores.',
    'manage-users': 'Administra las cuentas de acceso de los usuarios.',
    'manage-roles': 'Define perfiles de seguridad y asigna permisos.',
    'manage-company': 'Modifica logo y configuración legal de la compañía.',
    'manage-branches': 'Gestiona las sedes físicas de la empresa.',
    'view-reports': 'Accede a analíticas de compras, inventario y consumos.',
};
```

### 3. Migración y Seeding de BD
- Crear una migración que actualice de forma segura los registros en `permissions` y sincronice las relaciones `role_has_permissions`.

## Risks / Trade-offs

- **[Risk]** Que usuarios con rol Consumidor pierdan acceso tras renombrar permisos.
  - **Mitigación**: La migración y el seeder asignan explícitamente `create-consumption` a todos los roles `Consumidor`.
