## Context

El personal de almacén es el encargado directo de gestionar el inventario y entregar insumos solicitados internamente. En el sistema actual, la tabla `role_has_permissions` no tiene vinculado el permiso `manage-consumption` al rol `Almacén`. Al mismo tiempo, `SidebarService.php` condiciona la visibilidad del ítem "Consumos Solicitados" a la posesión del permiso `manage-consumption`.

Debido a esto, la opción de menú queda oculta y el almacenero no puede gestionar las solicitudes entrantes desde la barra de navegación. Adicionalmente, el usuario ha instruido que **toda alteración de datos o permisos debe realizarse exclusivamente a través de migraciones de Laravel**.

## Goals / Non-Goals

**Goals:**
- Sincronizar mediante una migración de Laravel el permiso `manage-consumption` en el rol `Almacén`.
- Garantizar que el ítem "Consumos Solicitados" aparezca en el menú lateral del personal de almacén.
- Preservar la integridad de los roles `Consumidor` y `Administrador`, sin alterar sus comportamientos ni validaciones.
- Mantener la restricción de que Almacén solo despacha/gestiona solicitudes, sin permitirle crear solicitudes de consumo para sí mismo.

**Non-Goals:**
- No se modificará el catálogo de productos ni la lógica de venta o POS.
- No se alterará el ciclo de vida de entrega y Kardex implementado previamente.

## Decisions

### 1. Migración idempotente con Spatie Permission
- **Decisión**: Crear una migración estándar de Laravel (`database/migrations/...`) que busque los roles `Almacén` y `almacen` y les conceda el permiso `manage-consumption` mediante `$role->givePermissionTo('manage-consumption')`.
- **Alternativa descartada**: Modificar la base de datos con sentencias SQL manuales o Tinker. Descartado por requerimiento explícito del usuario para mantener trazabilidad y control de versiones.

### 2. Visibilidad en `SidebarService.php`
- **Decisión**: En `SidebarService.php`, el submenú "Consumos Solicitados" ya evalúa `$user->can('manage-consumption')`. Una vez aplicada la migración, se validará que el grupo "Consumos" se renderice de forma limpia para Almacén mostrando únicamente "Consumos Solicitados".
- **Alternativa descartada**: Hardcodear verificaciones de nombres de roles en el Sidebar. Se prefiere respetar el sistema semántico de permisos de Spatie.

## Risks / Trade-offs

- **[Riesgo]** Conflicto de caché de permisos en Spatie tras la migración.  
  → **Mitigación**: Ejecutar `app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions()` dentro de la migración y correr `php artisan permission:cache-reset`.

- **[Riesgo]** Exposición accidental de la pantalla de creación a Almacén.  
  → **Mitigación**: El ítem "Registrar Consumo" exige `create-consumption`, el cual Almacén no posee, y el método `ConsumptionRequestController::create` mantiene su control `abort(403)`.
