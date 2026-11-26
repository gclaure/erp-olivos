# dashboard/bodas-orders-dashboard Specification

## Purpose

Define el panel de control exclusivo para el rol Bodas enfocado en el registro y listado de pedidos sin elementos de logística.

## Requirements

### Requirement: Métricas e indicadores operativos de pedidos
El Dashboard de Bodas SHALL presentar tarjetas de métricas centradas exclusivamente en el registro de pedidos: total de pedidos tomados hoy, total de pedidos registrados en el mes/año, reservas activas y reservas anuladas, excluyendo indicadores de logística o despacho.

#### Scenario: Visualización de métricas de registro
- **WHEN** se carga el Dashboard de Bodas
- **THEN** se visualizan contadores de pedidos registrados hoy, total de pedidos en el período, pedidos activos y pedidos cancelados.

### Requirement: Lista de pedidos recientes y accesos rápidos
El Dashboard de Bodas SHALL presentar como contenido principal una tabla de pedidos recientemente registrados con folio, cliente, teléfono, fecha de registro, fecha solicitada, resumen de productos, estado y accesos directos para registrar nuevo pedido y consultar el historial completo.

#### Scenario: Visualización de pedidos recientes
- **WHEN** un usuario con rol Bodas visualiza su Dashboard
- **THEN** la sección principal muestra la tabla de pedidos registrados recientemente con acceso a su detalle y a la impresión de la comanda técnica PDF.
