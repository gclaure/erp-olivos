# Spec Delta

## Purpose

Define el rol operativo Bodas con permisos predeterminados y restricciones de acceso para gestionar exclusivamente reservas de pedidos.

## ADDED Requirements

### Requirement: Existencia del rol Bodas con permiso predeterminado
El sistema SHALL contar con el rol `Bodas` registrado en el sistema de roles y permisos con el permiso `manage-orders` asignado de manera predeterminada.

#### Scenario: Usuario con rol Bodas autenticado
- **WHEN** un usuario que posee únicamente el rol `Bodas` inicia sesión en el sistema
- **THEN** el sistema reconoce que cuenta con el permiso `manage-orders` y puede acceder a las rutas del módulo de pedidos.

### Requirement: Restricción de acceso a módulos administrativos y de inventario
El sistema SHALL denegar el acceso a usuarios con rol `Bodas` a cualquier sección administrativa, de inventario, compras, reportes o configuración general que no forme parte de la gestión de pedidos.

#### Scenario: Intento de acceso a inventario o compras
- **WHEN** un usuario con rol `Bodas` intenta navegar directamente a `/admin/products` o `/admin/purchases`
- **THEN** el sistema bloquea el acceso retornando error 403 Forbidden o redirigiéndolo a su panel autorizado.

### Requirement: Menú lateral adaptado para Bodas
El sistema SHALL mostrar en el menú lateral de un usuario con rol `Bodas` únicamente su Dashboard operativo y la sección de Pedidos (Registrar Pedido e Historial de Pedidos), ocultando el resto de las secciones.

#### Scenario: Renderizado del menú lateral
- **WHEN** el usuario con rol `Bodas` carga la interfaz administrativa
- **THEN** el menú lateral solo presenta las opciones "Dashboard" y "Pedidos".
