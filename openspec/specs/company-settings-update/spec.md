# Company Settings Update

Especificación para la actualización y validación de la configuración de empresa en el panel administrativo.

## Requirements

### Requirement: Actualización de configuración de empresa mediante CompanyFacade
El sistema SHALL validar y permitir la actualización de la información de la empresa utilizando `CompanyFacade::getCompany()` para resolver los datos vigentes de la empresa, sin requerir servicios ni clases `tenant`.

#### Scenario: Administrador actualiza la configuración de la empresa con éxito
- **WHEN** un Administrador con permiso `manage-company` envía el formulario de actualización en `/admin/company`
- **THEN** el sistema valida los campos contra `CompanyFacade::getCompany()`
- **AND** la petición se procesa sin lanzar excepciones de resolución de clases (`BindingResolutionException`)
- **AND** los datos de la empresa se actualizan correctamente

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
