# Proposal

## Why

El sistema de inventario y punto de venta (POS) es utilizado activamente en cajas, almacenes y dispositivos móviles. Para brindar una experiencia nativa rápida, instalable en teléfonos, tablets y computadoras de escritorio, y que soporte fallas transitorias de conectividad sin colapsar la pantalla, se requiere implementar las capacidades completas de Progressive Web App (PWA) de acuerdo con los estándares modernos y el skill de mejores prácticas `pwa-development`.

## What Changes

- **Vinculación de Manifiesto y Metadatos PWA (`resources/views/inertia.blade.php`)**:
  - Incorporar `<link rel="manifest" href="/manifest.json">`.
  - Agregar meta tags de tema (`theme-color`) y compatibilidad para iOS Safari (`apple-mobile-web-app-capable`, `apple-mobile-web-app-status-bar-style`, `apple-mobile-web-app-title`, `apple-touch-icon`).
- **Ajustes al Manifiesto Dinámico (`routes/web.php`)**:
  - Asegurar la estructura requerida por Lighthouse y motores Chromium: nombres, `display: standalone`, `start_url`, iconos de `192x192`, `512x512` con propósitos `any` y `maskable`.
- **Service Worker Optimizado (`public/sw.js`)**:
  - Estrategia Cache-First para recursos estáticos versionados (`/build/assets/*`, webfonts, logos).
  - Estrategia Network-First con fallback a `/offline.html` para navegaciones y consultas Inertia.
  - Bypass estricto (Network-Only) para peticiones de mutación (`POST`, `PUT`, `DELETE`), login, pagos y WebSockets Reverb.
  - Gestión de ciclo de vida (`skipWaiting`, `clients.claim`) y limpieza automática de cachés antiguas.
- **Página Offline Rediseñada (`public/offline.html`)**:
  - Rediseñar con la paleta estética del sistema (Zinc / Emerald), soporte para tema oscuro y botón de reintento con recarga suave.
- **Registro de Service Worker y Composable de Instalación (`resources/js/composables/usePwa.js` y `resources/js/app.js`)**:
  - Registro seguro del Service Worker en entornos compatibles.
  - Composable `usePwa` para capturar `beforeinstallprompt`, verificar conectividad (`isOnline`) y detectar si la aplicación ya se ejecuta en modo standalone.
- **Acceso a Instalación en UI (`resources/js/Layouts/Partials/AdminNavbar.vue` o Sidebar)**:
  - Botón discreto y accesible para instalar la aplicación en el dispositivo cuando esté disponible.

## Capabilities

### New Capabilities
- `pwa-integration`: Proporciona capacidades completas de Progressive Web App (manifiesto, service worker con caching inteligente, pantalla offline y aviso/instalación en cliente).

### Modified Capabilities
<!-- Ninguna capability existente requiere modificación de requerimientos previos -->

## Impact

- **Frontend / Vistas**:
  - `resources/views/inertia.blade.php`: Metadatos PWA y manifest.
  - `public/sw.js`: Service worker optimizado.
  - `public/offline.html`: Pantalla offline renovada.
  - `resources/js/composables/usePwa.js`: Lógica de instalación y estado online/offline.
  - `resources/js/app.js`: Registro del Service Worker.
  - `resources/js/Layouts/Partials/AdminNavbar.vue`: Botón de instalación PWA.
- **Backend**:
  - `routes/web.php`: Validación y completitud de campos en `/manifest.json`.
