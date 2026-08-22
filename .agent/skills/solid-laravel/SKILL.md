---
name: solid-laravel
description: Guía avanzada de principios SOLID aplicados al desarrollo con Laravel 12 para lograr código mantenible, escalable y desacoplado.
---

# Principios SOLID en Laravel 12

Esta habilidad define las directrices maestras para aplicar ingeniería de software de alta calidad en el ecosistema Laravel. El objetivo es evitar el "código espagueti" y asegurar que el sistema de inventario pueda crecer sin colapsar bajo su propia complejidad.

## When to use this skill
- Al diseñar nuevos módulos o funcionalidades desde cero.
- Al refactorizar controladores que han crecido demasiado (Fat Controllers).
- Al integrar servicios externos (Pasarelas de pago, APIs, Almacenamiento).
- Cuando se necesite mejorar la capacidad de realizar pruebas unitarias (Testing).
- Al definir estructuras de servicios e interfaces en el proyecto.

## How to use it

Para desarrollar bajo estándares SOLID en este proyecto, se deben seguir estas reglas estrictas:

### 1. S — Single Responsibility Principle (SRP)
**Regla:** Una clase debe tener una única razón para cambiar.
- **Controllers:** Únicamente orquestan la petición HTTP para devolver una respuesta de Inertia o JSON. No validan, no calculan lógica de negocio, ni envían notificaciones directamente.
- **Form Requests:** Encapsulan toda la lógica de validación.
- **Services/Actions:** Contienen la lógica de negocio pura.
- **Notifications/Mails:** Encapsulan la comunicación con el usuario.

### 2. O — Open/Closed Principle (OCP)
**Regla:** El código debe estar abierto para extensión, pero cerrado para modificación.
- **Strategy Pattern:** Usa interfaces para definir contratos de comportamiento (ej: `PaymentProcessorInterface`).
- **Service Container:** Usa `app()->bind()` o `app()->singleton()` en Service Providers para inyectar implementaciones específicas sin tocar el código que las usa.
- **Config-Driven:** Facilita la extensión mediante archivos de configuración en `config/*.php`.

### 3. L — Liskov Substitution Principle (LSP)
**Regla:** Las clases derivadas deben ser sustituibles por sus clases base.
- **Contratos:** Asegúrate de que las implementaciones de una interfaz no lancen excepciones inesperadas para métodos definidos (ej: no lanzar "MethodNotImplemented").
- **Composición vs Herencia:** Si un "hijo" rompe el comportamiento esperado del "padre", prefiere usar composición o interfaces separadas.

### 4. I — Interface Segregation Principle (ISP)
**Regla:** No obligues al código a depender de métodos que no utiliza.
- **Interfaces Pequeñas:** Divide interfaces grandes en interfaces específicas y granulares (ej: `RefundableInterface`, `VoidableInterface` en lugar de una única `MassivePaymentInterface`).
- **Contratos Enfocados:** Los servicios solo deben implementar lo que realmente necesitan ejecutar.

### 5. D — Dependency Inversion Principle (DIP)
**Regla:** Depende de abstracciones, no de implementaciones concretas.
- **Inyección por Constructor:** Inyecta interfaces en tus controladores y servicios.
- **Desacoplamiento de Laravel:** Evita el uso excesivo de Facades dentro de tus servicios de lógica de negocio; prefiere la inyección de dependencias para facilitar el testing.
- **Abstracción de Almacenamiento:** No dependas de `Storage::disk('local')` directamente en la lógica, depende de la interfaz `Illuminate\Contracts\Filesystem\Filesystem`.

### Estándares de la Industria (Métricas de Calidad)

Aunque SOLID es conceptual, utilizamos "Reglas de Dedo" basadas en estándares profesionales para determinar cuándo un código requiere refactorización urgente:

| Elemento | Límite Recomendado | Límite de Alarma Roja ("Smell") |
| :--- | :--- | :--- |
| **Métodos / Funciones** | 15 - 20 líneas | > 50 líneas (Refactorizar SRP urgente) |
| **Clases (Controllers/Actions)** | 100 - 300 líneas | > 400 líneas (God Class - Divisible) |
| **Componentes Vue** | 50 - 150 líneas | > 250 líneas (Requiere extracción a Composables o Sub-componentes) |
| **Archivos de Rutas** | Agrupados por dominio | > 300 líneas (Separar en `routes/*.php`) |

## Arquitectura Recomendada en este Proyecto

Al aplicar esta habilidad, asegúrate de mantener esta estructura de capas:
- `app/Http/Requests/`: Validación completa de entrada.
- `app/Services/` o `app/Actions/`: Lógica de negocio y procesos complejos.
- `app/Interfaces/` o `app/Contracts/`: Definición de abstracciones.
- `app/Providers/`: Bindings de interfaces a implementaciones.

> [!IMPORTANT]
> Referencia siempre las `laravel-rules.md` para convenciones de tipado estricto y PSR-12 junto con estos principios.