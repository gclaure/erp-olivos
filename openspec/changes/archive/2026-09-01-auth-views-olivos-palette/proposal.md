## Why

Las pantallas de autenticación (`/login` y `/forgot-password`) actualmente utilizan acentos en tonos esmeralda e índigo genéricos de Tailwind. Se requiere alinear la identidad visual de estas vistas específicas con la paleta de marca oficial de Los Olivos (`PRIMARY #73AC32`, `SECONDARY #44773C`, `ACCENT #A8C98A`, `TEXT #050607`, `BACKGROUND #FAF9F5`, `SURFACE #E9E9E6`), sin alterar el resto del ERP.

## What Changes

- Aplicar la paleta de colores en `resources/js/Pages/Auth/Login.vue`:
  - Botón principal de acceso: Fondo `#73AC32` (Verde Olivo), hover `#44773C` (Verde Bosque).
  - Anillos de foco y selección en inputs y checkbox: `#73AC32`.
  - Enlaces de acción ("¿Olvidaste tu contraseña?", "Regístrate"): `#44773C` hover `#73AC32`.
  - Badges de valor y acentos: `#A8C98A` / `#73AC32`.
- Aplicar la paleta de colores en `resources/js/Pages/Auth/ForgotPassword.vue`:
  - Botón principal de envío de instrucciones: `#73AC32` hover `#44773C`.
  - Enlace de retorno al login y foco de inputs: `#44773C` y `#73AC32`.
  - Badges y alertas de sesión.
- Armonizar el fondo 3D en `resources/js/Components/AuthThreeCanvas.vue` con partículas en `#73AC32`, `#44773C` y `#A8C98A`.

## Capabilities

### New Capabilities
- `auth-views-olivos-palette`: Identidad visual y paleta oficial de Los Olivos aplicada exclusivamente a las vistas de autenticación (`/login` y `/forgot-password`).

### Modified Capabilities

## Impact

- Frontend:
  - `resources/js/Pages/Auth/Login.vue`
  - `resources/js/Pages/Auth/ForgotPassword.vue`
  - `resources/js/Components/AuthThreeCanvas.vue`
