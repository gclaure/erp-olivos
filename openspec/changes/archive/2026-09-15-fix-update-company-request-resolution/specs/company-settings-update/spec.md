## ADDED Requirements

### Requirement: Actualización de configuración de empresa mediante CompanyFacade
El sistema SHALL validar y permitir la actualización de la información de la empresa utilizando `CompanyFacade::getCompany()` para resolver los datos vigentes de la empresa, sin requerir servicios ni clases `tenant`.

#### Scenario: Administrador actualiza la configuración de la empresa con éxito
- **WHEN** un Administrador con permiso `manage-company` envía el formulario de actualización en `/admin/company`
- **THEN** el sistema valida los campos contra `CompanyFacade::getCompany()`
- **AND** la petición se procesa sin lanzar excepciones de resolución de clases (`BindingResolutionException`)
- **AND** los datos de la empresa se actualizan correctamente

#### Scenario: Intento de cambio de método de inventario con movimientos existentes
- **WHEN** un usuario intenta cambiar `inventory_method` en una empresa que ya cuenta con movimientos registrados (`has_inventory_movements = true`)
- **THEN** el validador añade un error en el campo `inventory_method` impidiendo el cambio
