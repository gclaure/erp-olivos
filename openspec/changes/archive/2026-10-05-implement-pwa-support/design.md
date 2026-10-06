# Design

## Context

Véase `proposal.md` para la motivación. Actualmente:
1. `routes/web.php` expone una ruta `/manifest.json`, pero no está enlazada en `inertia.blade.php`.
2. `public/sw.js` y `public/offline.html` existen como esbozos desconectados y sin soporte para las especificidades de Inertia.js (CSRF, Partial Reloads, cabeceras `X-Inertia`).
3. No existe un composable en Vue ni botón en la interfaz para capturar `beforeinstallprompt` o informar al usuario del estado de la conexión.

## Goals / Non-Goals

**Goals:**
- Configurar PWA instalable con score óptimo en Lighthouse: manifest completo, meta tags, iconos conformes (192, 512, maskable) y service worker activo.
- Implementar un Service Worker robusto que use Cache-First para `/build/assets/*` (archivos versionados con hash de Vite) y fuentes, Network-First para páginas HTML/Inertia, y Network-Only para peticiones mutativas (`POST`, `PUT`, `DELETE`).
- Diseñar una pantalla `offline.html` consistente con el diseño visual del sistema (Zinc / Emerald / Dark Mode) y soporte de reintento.
- Proveer un composable reactivo `usePwa` en Vue 3 y exponer un botón de instalación en el Navbar (`AdminNavbar.vue`).

**Non-Goals:**
- Implementar una cola transaccional offline para ventas locales complejas (Background Sync de inventario/facturación en IndexedDB). Esto se mantendrá fuera de alcance para evitar inconsistencias de stock en cajas concurrentes en esta fase.

## Decisions

### 1. Service Worker: Manual en `public/sw.js` vs plugin Vite
- **Decisión**: Implementar y mantener un Service Worker optimizado en `public/sw.js`.
- **Razón**: En aplicaciones Laravel + Inertia.js, las peticiones HTTP se dividen entre vistas Blade iniciales y peticiones AJAX de Inertia con headers `X-Inertia`. Un Service Worker manual otorga control explícito sobre qué requests interceptar, cómo manejar caídas de red y cómo evitar cachear respuestas CSRF o payloads de autenticación obsoletos.
- **Alternativa considerada**: `vite-plugin-pwa` con Workbox. Descartada por complejidad innecesaria al integrarse con el routing dinámico de Laravel y el endpoint `/manifest.json` que depende de `CompanyFacade`.

### 2. Estrategia de Caching por categoría de recursos
- **Assets compilados de Vite (`/build/assets/*`) y Fonts**: Cache First con expiración y revalidación en segundo plano. Dado que Vite incluye hashes en los nombres de archivo (ej. `app-CSpCBJmQ.js`), son inmutables y seguros de cachear.
- **Navegaciones (`request.mode === 'navigate'`) y peticiones Inertia (`X-Inertia`)**: Network First. Se intenta obtener la respuesta del servidor; si no hay conexión (offline), se sirve `/offline.html` para navegaciones completas.
- **Mutaciones (`POST`, `PUT`, `DELETE`, `PATCH`) y WebSockets**: Network Only directo al servidor.

### 3. Registro y Composable Reactivo (`usePwa.js`)
- **Decisión**: Crear `resources/js/composables/usePwa.js` exportando `isOnline`, `canInstall`, `isStandalone`, `installApp()` y `checkUpdate()`.
- **Integración UI**: En `AdminNavbar.vue`, mostrar un botón "Instalar App" con icono de descarga/pantalla únicamente cuando `canInstall` sea verdadero y no esté en modo standalone.

## Risks / Trade-offs

- **[Riesgo: El Service Worker sirve assets antiguos tras un nuevo despliegue en producción]** → Mitigación: El Service Worker escucha `activate` para eliminar caches con nombres de versión obsoletos y ejecuta `clients.claim()`. Al mismo tiempo, los assets de Vite tienen hashes únicos en su nombre.
- **[Riesgo: Detección offline en iOS Safari]** → Mitigación: Safari no dispara `beforeinstallprompt`, por lo que el composable detectará Safari y ofrecerá instrucciones simples ("Compartir > Agregar a pantalla de inicio") o permanecerá pasivo sin generar errores.
