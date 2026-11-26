# orders/excel-export Specification

## Purpose
Permite a los usuarios y administradores exportar el registro e historial de pedidos y reservas de clientes a un archivo Excel (.xlsx) estructurado, aplicando de manera estricta los filtros activos de búsqueda, fechas, clientes, modalidades y estados.

## Requirements

### Requirement: Exportación de pedidos a Excel respetando filtros activos
El sistema SHALL permitir descargar un archivo de hoja de cálculo en formato `.xlsx` con el listado de pedidos y reservas resultante de los filtros seleccionados en la vista (`search`, `registered_from`, `registered_to`, `delivery_from`, `delivery_to`, `delivery_mode`, `customer_id`, `status`).

#### Scenario: Descarga de pedidos con filtros específicos
- **WHEN** el usuario aplica filtros (por ejemplo rango de fechas o cliente específico) y presiona la acción de exportar a Excel
- **THEN** el sistema genera y descarga un archivo `.xlsx` que contiene únicamente los pedidos que satisfacen los filtros aplicados, sin límite de paginación

#### Scenario: Descarga sin filtros adicionales
- **WHEN** el usuario no tiene filtros activos y presiona la acción de exportar a Excel
- **THEN** el sistema genera y descarga el archivo `.xlsx` con la totalidad de los pedidos registrados en el sistema

### Requirement: Estructura, formato de moneda y totales del reporte
El archivo Excel generado SHALL contener encabezados institucionales, columnas claras con los datos esenciales de cada pedido (Folio, Fecha de Registro, Registrado por, Cliente, Teléfono, Fecha de Entrega, Hora, Modalidad, Dirección, Resumen de Productos, Subtotal, Descuento, Total, Monto Pagado, Saldo Pendiente y Estado), autoajuste de anchos de columna y una fila final de totales calculando la suma del Total, Monto Pagado y Saldo Pendiente en Bolivianos (Bs.).

#### Scenario: Formato visual y fila de sumatoria
- **WHEN** se abre el archivo descargado en cualquier software compatible con hojas de cálculo
- **THEN** las celdas numéricas de moneda presentan formato decimal monetario (`#,##0.00`), las fechas tienen formato `d/m/Y`, y al final de la tabla se encuentra una fila de totales destacada con las sumas correspondientes

### Requirement: Botón de exportación en la interfaz de usuario
La vista de historial de pedidos (`/admin/orders`) SHALL proveer un botón de exportación accesible en la cabecera de la página junto a las acciones principales, el cual construye dinámicamente el enlace de descarga con los parámetros de los filtros activos en tiempo real.

#### Scenario: Activación de la descarga desde la interfaz
- **WHEN** el usuario hace clic en el botón de exportar Excel en la interfaz
- **THEN** el navegador inicia la descarga del archivo `.xlsx` sin recargar la página ni alterar el estado de la vista
