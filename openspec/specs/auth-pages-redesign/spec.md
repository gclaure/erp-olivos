# auth-pages-redesign

Especificación para el rediseño de las páginas de Login y Recuperación de Contraseña con soporte visual multi-dispositivo y logotipo institucional.

## Requirements

### Requirement: Logotipo Institucional Prominente en Todos los Dispositivos
Tanto la página de Login como la de Recuperación de Contraseña SHALL desplegar el logotipo institucional oficial en Desktop (panel vitrina izquierdo), Tablet y Móvil (cabecera centrada del formulario).

#### Scenario: Visualización en Móvil y Tablet
- **WHEN** un usuario accede a `/login` o `/forgot-password` desde un smartphone o tablet
- **THEN** observa el logotipo institucional centrado con dimensiones legibles y proporciones óptimas
- **AND** el formulario se ajusta con padding táctil de al menos 44px por campo interactivo

#### Scenario: Visualización en Desktop (Pantallas >= 1024px)
- **WHEN** un usuario accede a `/login` o `/forgot-password` desde un monitor o laptop
- **THEN** visualiza un layout a dos columnas donde el panel izquierdo exhibe el logotipo oficial, la propuesta de valor y el estado operativo del sistema

### Requirement: Experiencia de Formulario y Recuperación
El formulario de inicio de sesión SHALL incluir toggle de visibilidad de contraseña, recordación de sesión y estados de carga en el botón de envío. El formulario de recuperación SHALL incluir enlace de regreso y validación de correo.

#### Scenario: Envío de formulario de login
- **WHEN** el usuario ingresa credenciales válidas y hace clic en "Acceder al Sistema"
- **THEN** el botón muestra spinner de procesamiento y completa la autenticación sin bloqueos visuales
