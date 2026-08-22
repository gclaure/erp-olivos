---
name: proyecto-whatsapp-gateway
description: Habilidad para identificar y redireccionar tareas o consultas hacia el proyecto del WhatsApp Gateway (whasapp-dependency) y su integración con el backend whasapp-erp.
---

# Proyecto WhatsApp Gateway

Esta habilidad le indica al agente la ubicación y los detalles técnicos del proyecto WhatsApp Gateway para que pueda redireccionar cualquier tarea de desarrollo, configuración o depuración relacionada al mismo, así como su interacción con el backend.

## When to use this skill
Usa esta habilidad cuando:
- El usuario solicite realizar cambios, depurar, arrancar o configurar la integración de WhatsApp.
- Se mencionen los términos "whatsapp", "gateway", "baileys" o el microservicio de mensajería.
- Se requiera modificar la comunicación vía webhook con el backend `whasapp-erp` o cambiar las credenciales/sesiones de WhatsApp.

## How to use it
Cuando esta habilidad esté activa, debes orientar todas tus acciones al proyecto de WhatsApp Gateway y al backend correspondiente:

### 1. Ubicación y Estructura del Proyecto
- **WhatsApp Gateway (Microservicio)**:
  Ubicado en: `/Users/claure/Documents/whasapp-app/ERP-WHASAPP/whasapp-dependency`
  Desarrollado con **NestJS** y la librería **Baileys** (v7.0.0-rc13) para interactuar con la API de WhatsApp Web de forma multi-dispositivo y multi-sesión. Sigue una arquitectura limpia y principios SOLID con tipado estático estricto.
- **Backend (whasapp-erp)**:
  Ubicado en: `/Users/claure/Documents/whasapp-app/whasapp-erp`
  Desarrollado en **Java/Spring Boot** con **Arquitectura Hexagonal** (`adapters`, `application`, `domain`, `infrastructure`).

### 2. Variables de Entorno (`.env`) en `whasapp-dependency`
El archivo de configuración `.env` en la raíz de `whasapp-dependency` contiene:
- `PORT`: Puerto de escucha del microservicio (por defecto `3000`).
- `GATEWAY_API_KEY`: Llave de autenticación requerida en la cabecera `x-api-key`.
- `SPRING_BOOT_WEBHOOK_URL`: URL del webhook en `whasapp-erp` (arquitectura hexagonal) que recibe eventos y mensajes de WhatsApp.
- `WHATSAPP_AUTH_DIR`: Directorio de persistencia de credenciales de sesión (por defecto `./auth_info_baileys`, el cual está ignorado en Git por seguridad).

### 3. Endpoints Disponibles (Controladores Especializados)
Los endpoints expuestos se dividen en tres controladores específicos para cumplir con el Principio de Responsabilidad Única (SRP):

- **SessionController** (`src/whatsapp/controllers/session.controller.ts`):
  - **POST** `/whatsapp/:sessionId/connect`: Inicializa o reconecta la sesión de WhatsApp del identificador provisto.
  - **GET** `/whatsapp/:sessionId/qr`: Obtiene el código QR de vinculación en formato Base64.
  - **GET** `/whatsapp/:sessionId/status`: Verifica el estado de la conexión (`CONNECTING`, `CONNECTED`, `DISCONNECTED`, `QR_READY`).
  - **DELETE** `/whatsapp/:sessionId`: Desconecta, cierra sesión y elimina del disco las credenciales locales.

- **MessageController** (`src/whatsapp/controllers/message.controller.ts`):
  - **POST** `/whatsapp/:sessionId/send-text`: Envía un mensaje de texto.
  - **POST** `/whatsapp/:sessionId/send-media`: Envía archivos multimedia (imagen, video, audio, documento) descargados al vuelo desde una URL.

- **MediaController** (`src/whatsapp/controllers/media.controller.ts`):
  - **POST** `/whatsapp/:sessionId/media/download`: Descarga y streamea de forma stateless contenido cifrado recibido (usado por el backend `whasapp-erp`).

### 4. Comandos de Desarrollo en `whasapp-dependency`
El gestor de paquetes preferido en este subproyecto es **pnpm** (debido a la existencia de `pnpm-lock.yaml`).
- **Instalar dependencias**: `pnpm install`
- **Iniciar en desarrollo**: `pnpm run start:dev`
- **Compilar para producción**: `pnpm run build`
- **Iniciar producción**: `pnpm run start:prod`
- **Formatear código**: `pnpm run format`
- **Ejecutar análisis estático**: `pnpm run lint`
- **Ejecutar pruebas**: `pnpm run test`

### 5. Guía de Redirección
Si estás trabajando en el Gateway o en el backend `whasapp-erp` y el usuario te pide modificar o comprobar el comportamiento de la mensajería:
1. Cambiar tu contexto mental al directorio del proyecto correspondiente (`whasapp-dependency` para el gateway y `whasapp-erp` para el backend).
2. Asegurar que las comunicaciones sigan los contratos de interfaces y payload definidos con tipado estricto.
3. Informar al usuario en qué parte de la arquitectura hexagonal (`adapters/inbound` para controladores, `adapters/outbound` para integraciones externas, etc.) o del microservicio se realizaron los cambios.
