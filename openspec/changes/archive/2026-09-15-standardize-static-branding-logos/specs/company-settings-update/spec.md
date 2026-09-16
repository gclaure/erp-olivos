## ADDED Requirements

### Requirement: Formulario de empresa enfocado sin componente de carga de logo
La vista de configuración de empresa `/admin/company` SHALL gestionar exclusivamente los datos fiscales, comerciales, operacionales y la visibilidad del nombre (`show_name`), eliminando la interfaz Dropzone de carga de archivos.

#### Scenario: Visualización del formulario de configuración de empresa
- **WHEN** un administrador accede a `/admin/company`
- **THEN** la interfaz no renderiza componentes ni scripts Dropzone de carga de archivos
- **AND** renderiza los campos de Nombre Comercial, Razón Social, NIT, Teléfono, Correo, Cierre de Inventario, Método de Inventario y el Switch de visualización de nombre

#### Scenario: Envío de formulario sin archivo de logo
- **WHEN** el administrador modifica y envía el formulario de configuración de empresa
- **THEN** la solicitud se envía sin multipart de archivo de logo
- **AND** el backend actualiza la configuración de la empresa exitosamente
