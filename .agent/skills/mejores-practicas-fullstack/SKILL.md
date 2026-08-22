---
name: mejores-practicas-fullstack
description: Directrices avanzadas para el desarrollo con Laravel 12, Inertia.js, Vue 3, Tailwind CSS y PostgreSQL, enfocado en rendimiento, mantenibilidad y escalabilidad.
---

# Mejores Prácticas Fullstack (Laravel + Vue 3)

Esta habilidad define los estándares de excelencia técnica para este proyecto. El objetivo es construir aplicaciones robustas, escalables y extremadamente rápidas aprovechando las últimas capacidades de Laravel 12 en el backend y la reactividad de Vue 3 en el frontend mediante Inertia.js.

## When to use this skill

Usa esta habilidad cuando:
- Estés diseñando la arquitectura de un nuevo módulo o funcionalidad (ej. Catálogo, POS, Carrito).
- Escribas o refactorices controladores de Laravel que sirven vistas de Inertia.
- Construyas componentes y composables en Vue 3.
- Optimices el rendimiento de la base de datos PostgreSQL.
- Realices una revisión de código para asegurar el cumplimiento de los estándares del proyecto.

## How to use it

Sigue estos principios fundamentales divididos por capas de la aplicación:

### 1. Arquitectura y Lógica de Negocio (Laravel 12 & PHP 8.2+)
El objetivo es mantener controladores delgados y delegar la responsabilidad.

- **PHP Moderno**: Usa `declare(strict_types=1);`, `readonly classes` para DTOs (Data Transfer Objects) y `Enums` respaldados para estados (ej. `OrderStatus::Pending`).
- **Actions e Invocables**: Extrae la lógica transaccional compleja a servicios o clases "Action" de un solo propósito. Los controladores solo deben recibir la request, llamar a la Action y devolver la respuesta de Inertia.
    ```php
    class ProcessCheckoutAction {
        public function __invoke(CartData $data): Order {
            // Lógica compleja aquí
        }
    }
    ```
- **Tipado Estricto en Auth**: Confía en los tipos de retorno de los guardias de autenticación de Laravel para evitar chequeos de nulos redundantes.

### 2. Frontend y Estado (Inertia.js & Vue 3)
Maximiza el rendimiento del cliente y la separación de responsabilidades.

- **Composition API**: Escribe todos los componentes Vue usando `<script setup>`. Está estrictamente prohibido usar la antigua Options API.
- **Manejo de Formularios**: Usa siempre el helper `useForm` de Inertia para manejar envíos, estado de carga (`processing`) y despliegue automático de errores de validación del backend.
    ```javascript
    const form = useForm({
        name: '',
        price: 0,
    });
    // form.post('/products')
    ```
- **Lógica Reutilizable (Composables)**: Extrae la lógica de negocio del frontend (ej. cálculos de carrito, formateo de moneda, manejo de atajos de teclado) a funciones Composables (`useCart.ts`, `useCurrency.ts`).
- **Props Tipadas**: Define siempre las `props` que recibe Vue de Inertia usando tipos estrictos o interfaces de TypeScript/JSDoc para mantener la autocompletación.

### 3. UI y Componentización (Tailwind CSS 3)
Crea una interfaz consistente y fácil de mantener.

- **Componentes Vue Puros**: Extrae bloques UI repetitivos a componentes `.vue` puros (ej. `<ProductCard />`, `<PrimaryButton />`) en lugar de usar `@apply` masivamente en CSS o repetir clases kilométricas de Tailwind.
- **Slots para Flexibilidad**: Usa `<slot>` en Vue para crear componentes contenedores reutilizables (como Layouts, Modales o Dropdowns) sin acoplar fuertemente su contenido.
- **Gestión de Modales**: Controla la visibilidad de los modales con estado local (`ref(false)`) y usa `Teleport` nativo de Vue para renderizarlos en el `<body>` y evitar problemas de `z-index`.

### 4. Persistencia de Datos (PostgreSQL)
Optimiza el motor de base de datos para el negocio.

- **JSONB**: Usa columnas `jsonb` para datos dinámicos o especificaciones técnicas variables (muy común en repuestos automotrices). Eloquent los maneja fluidamente como arrays/objetos.
- **Búsqueda Avanzada**: Implementa la extensión `pg_trgm` para búsquedas Full-Text rápidas por similitud en códigos de producto o nombres.
- **Prevención de N+1 (Crítico en Inertia)**: Usa siempre Eager Loading (`with(['relacion'])`) en el controlador antes de enviar los datos a Vue. Si omites esto, el frontend no podrá acceder a las relaciones sin disparar errores.
- **API Resources**: Pasa los modelos de Eloquent por un `JsonResource` antes de enviarlos a Inertia para ocultar campos sensibles y reducir el payload.

---
*Nota: Siempre referencia `.agent/skills/inertia-vue-performance-optimizer/SKILL.md` para auditorías de rendimiento y `tailwind-rules.md` para convenciones de diseño.*