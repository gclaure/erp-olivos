# Tasks

## 1. Configuración de Metadatos y Manifiesto PWA

- [x] 1.1 Actualizar `resources/views/inertia.blade.php` vinculando `<link rel="manifest" href="/manifest.json">`, meta tags de tema (`theme-color`), e iconos y flags para iOS (`apple-mobile-web-app-capable`, `apple-touch-icon`).
- [x] 1.2 Revisar y perfeccionar la respuesta del endpoint `/manifest.json` en `routes/web.php` asegurando estructura completa, display standalone, colores acordes y propósitos `any` y `maskable` para los iconos.

## 2. Service Worker y Pantalla Offline

- [x] 2.1 Reimplementar `public/sw.js` con estrategias de caching optimizadas (Cache-First para assets de Vite y webfonts, Network-First para navegaciones y requests Inertia, Network-Only para mutaciones y WebSockets) y ciclo de vida ágil (`skipWaiting`/`clients.claim`).
- [x] 2.2 Rediseñar `public/offline.html` con la estética Zinc/Emerald del sistema, modo oscuro automático y botón para recargar la aplicación.

## 3. Integración en Cliente y Experiencia de Usuario

- [x] 3.1 Crear el composable `resources/js/composables/usePwa.js` para registrar el service worker en `resources/js/app.js`, capturar el evento `beforeinstallprompt`, monitorear la conectividad (`isOnline`) y verificar si corre en modo standalone.
- [x] 3.2 Añadir un botón discreto "Instalar App" en `resources/js/Layouts/Partials/AdminNavbar.vue` visible únicamente cuando la PWA sea instalable en el dispositivo y no esté ejecutándose como standalone.

## 4. Verificación y Pruebas

- [x] 4.1 Ejecutar `npm run build` y comprobar la correcta carga del Service Worker y Manifiesto en el navegador sin errores en la consola.
