# Spec Delta

## ADDED Requirements

### Requirement: Purga automática diaria de notificaciones antiguas con ventana móvil de 90 días
El sistema SHALL ejecutar diariamente de manera automatizada una rutina de limpieza que elimine de la base de datos todas las notificaciones registradas (tanto con estado leído como no leído) cuya antigüedad sea estrictamente superior a 90 días respecto a la fecha actual de ejecución.

#### Scenario: Notificaciones mayores a 90 días son eliminadas
- **WHEN** el comando programado de purga se ejecuta automáticamente o de forma manual
- **AND** existen notificaciones en la tabla cuya fecha de creación es anterior a `now()->subDays(90)`
- **THEN** el sistema MUST eliminar permanentemente dichos registros de la base de datos
- **AND** el sistema MUST registrar en los logs de la aplicación la cantidad de registros eliminados

#### Scenario: Notificaciones de los últimos 90 días se conservan intactas
- **WHEN** el comando programado de purga se ejecuta
- **AND** existen notificaciones (leídas o no leídas) creadas dentro de la ventana de los últimos 90 días
- **THEN** el sistema MUST conservar intactos todos esos registros para consulta del usuario
