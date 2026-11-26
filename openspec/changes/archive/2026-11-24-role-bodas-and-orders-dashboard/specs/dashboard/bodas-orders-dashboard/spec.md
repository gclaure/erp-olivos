# Spec Delta

## Purpose

Define el panel de control (Dashboard) específico para el rol Bodas centrado en el seguimiento y gestión de pedidos y reservas.

## ADDED Requirements

### Requirement: Renderizado del Dashboard especializado de Bodas
El sistema SHALL renderizar una vista de Dashboard dedicada (`Admin/BodasDashboard`) cuando un usuario con rol `Bodas` accede a la ruta `/admin`.

#### Scenario: Acceso al Dashboard de Bodas
- **WHEN** un usuario con rol `Bodas` y sin rol de Administrador accede a `/admin`
- **THEN** el sistema renderiza la vista `Admin/BodasDashboard` conteniendo métricas y listas exclusivas de pedidos.

### Requirement: Métricas e indicadores operativos de pedidos
El Dashboard de Bodas SHALL presentar tarjetas de métricas operativas que incluyan: total de reservas activas, entregas programadas para el día actual, entregas pendientes de la semana y pedidos cancelados.

#### Scenario: Visualización de indicadores operativos
- **WHEN** se carga el Dashboard de Bodas
- **THEN** se visualizan los contadores de pedidos con filtros por mes y año, además del contador de entregas de hoy.

### Requirement: Lista de próximas entregas y accesos rápidos
El Dashboard de Bodas SHALL listar las reservas con entrega próxima y accesos directos rápidos hacia el formulario de Registrar Pedido (`/admin/orders/create`) y el Historial de Pedidos (`/admin/orders`).

#### Scenario: Uso de accesos directos
- **WHEN** el usuario hace clic en "Registrar Nueva Reserva" en el Dashboard de Bodas
- **THEN** es redirigido a `/admin/orders/create` sin fricción.
