# pwa-integration Specification

## Purpose
Habilita capacidades Progressive Web App (PWA) de nivel de producción que permiten instalar la aplicación de inventario en dispositivos móviles y de escritorio, optimizando la carga de assets y proveyendo un fallback amigable ante desconexiones de red.

## Requirements

### Requirement: Web App Manifest and PWA Meta Headers
The application SHALL provide a compliant web app manifest endpoint and render all essential PWA and iOS compatibility meta tags in the root Inertia document.

#### Scenario: Manifest and meta tags rendering
- **WHEN** any user loads the application in a web browser
- **THEN** the HTML head contains a valid `<link rel="manifest" href="/manifest.json">`, theme-color meta tags, apple-touch-icon links, and iOS standalone capability flags.

#### Scenario: Dynamic manifest content
- **WHEN** the browser fetches `/manifest.json`
- **THEN** the system returns a JSON response containing valid name, short_name, start_url, standalone display mode, background/theme colors, and icons with 192x192 and 512x512 dimensions supporting any and maskable purposes.

### Requirement: Intelligent Service Worker and Offline Caching
The application SHALL register a service worker implementing cache-first strategies for hashed static assets, network-first for Inertia navigations with offline fallback, and strict network-only bypass for mutation requests.

#### Scenario: Service worker installation and activation
- **WHEN** the client browser loads the application for the first time
- **THEN** the service worker registers successfully, precaches the offline fallback page, and claims active clients upon activation.

#### Scenario: Offline navigation fallback
- **WHEN** the user device loses internet connection and attempts to navigate to an uncached route
- **THEN** the service worker serves the customized `offline.html` page without generating standard browser network crash errors.

#### Scenario: Data mutations bypass cache
- **WHEN** the application sends POST, PUT, DELETE, or WebSocket requests
- **THEN** the service worker forwards the requests directly to the network without caching responses.

### Requirement: App Installation and Connectivity UX
The application UI SHALL detect when the app is installable and provide an intuitive prompt to install the PWA, while reflecting network status changes gracefully.

#### Scenario: Install prompt availability
- **WHEN** a supported browser triggers the `beforeinstallprompt` event and the app is not already running standalone
- **THEN** an installation button becomes visible in the user interface allowing the user to trigger the native installation dialog.

#### Scenario: App already running in standalone mode
- **WHEN** the application is launched as an installed standalone PWA
- **THEN** the installation prompts and buttons are automatically hidden.
