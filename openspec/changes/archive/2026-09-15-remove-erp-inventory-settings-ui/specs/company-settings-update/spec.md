## MODIFIED Requirements

### Requirement: Formulario de empresa enfocado sin componente de carga de logo
La vista de configuración de empresa `/admin/company` SHALL gestionar exclusivamente los datos fiscales, comerciales, operacionales de impresión y la visibilidad del nombre (`show_name`), eliminando la interfaz Dropzone de carga de archivos y la sección de configuración de inventario ERP (`inventory_method` y `inventories_closed_until`).

#### Scenario: Visualización del formulario de configuración de empresa
- **WHEN** un administrador accede a `/admin/company`
- **THEN** la interfaz no renderiza componentes Dropzone de carga de archivos ni campos de método de valuación de inventario ni fecha de cierre contable
- **AND** renderiza los campos de Nombre Comercial, Razón Social, NIT, Teléfono, Correo, Formato de Impresión (`receipt_type`) y el Switch de visualización de nombre (`show_name`)

#### Scenario: Envío de formulario sin archivo de logo ni campos de inventario ERP
- **WHEN** el administrador modifica y envía el formulario de configuración de empresa
- **THEN** la solicitud se envía sin multipart de logo y sin exigir `inventory_method` ni `inventories_closed_until`
- **AND** el backend actualiza la configuración de la empresa exitosamente manteniendo la consistencia de datos

## REMOVED Requirements

### Requirement: Intento de cambio de método de inventario con movimientos existentes
**Reason**: El método de inventario ya no se gestiona ni se expone en la interfaz de configuración de empresa, ya que el sistema opera con Promedio Ponderado fijo para insumos.
**Migration**: Ninguna. La lógica de valuación es manejada automáticamente a nivel de servicio backend (`KardexService`).
