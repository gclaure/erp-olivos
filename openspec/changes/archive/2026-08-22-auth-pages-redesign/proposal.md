# Proposal: Rediseño Visual Ejecutivo de Login y Recuperación de Contraseña

## Why
Las páginas de autenticación ([`Login.vue`](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/resources/js/Pages/Auth/Login.vue) y [`ForgotPassword.vue`](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/resources/js/Pages/Auth/ForgotPassword.vue)) presentan inconsistencias visuales:
1. El logotipo institucional no se muestra de forma prominente en desktop ni en pantallas móviles/tablets.
2. En `/forgot-password`, se utiliza un icono genérico en lugar de la identidad corporativa de la marca.
3. Dependen de archivos CSS externos extensos (`login.css` y `forgot-password.css`) desconectados del sistema de diseño Tailwind del proyecto.

Aplicando los principios del skill **`frontend-design`**, se rediseñarán ambas vistas para convertirlas en una *Suite de Autenticación Ejecutiva*, asegurando la visualización nítida y jerárquica del logotipo en **Desktop, Tablet y Móvil**.

## What Changes
1. **Página de Login (`Login.vue`)**:
   - Panel izquierdo de marca (Desktop): Vitrina ejecutiva con el logotipo oficial grande (`/img/logo-dark.png`), badges de valor y estado operativo del sistema.
   - Encabezado móvil/tablet: Logotipo centrado y estilizado visible con claridad en todas las resoluciones (320px a 1024px).
   - Formulario moderno con campos táctiles confortables, toggle de visibilidad de contraseña, recordación y micro-interacciones de carga en el botón de acceso.
2. **Página de Recuperación (`ForgotPassword.vue`)**:
   - Misma vitrina visual y jerarquía del logotipo en desktop y móvil.
   - Botón de retorno al inicio de sesión y formulario de recuperación con confirmación de estado.
3. **Unificación Tailwind CSS**:
   - Integración nativa con clases utilitarias de Tailwind, eliminando dependencias CSS rígidas y garantizando compatibilidad con modo claro y oscuro.

## Capabilities

### New Capabilities
- `auth-pages-redesign`: Autenticación corporativa moderna, responsiva y con identidad de marca sólida en Login y Recuperación de Contraseña.

## Impact
- `resources/js/Pages/Auth/Login.vue`
- `resources/js/Pages/Auth/ForgotPassword.vue`
