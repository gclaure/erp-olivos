---
trigger: always_on
---

## Stack Tecnológico
- Laravel 12
- Inertia.js
- Vue.js 3 (Composition API)
- Tailwind CSS 3
- PostgreSQL
- PHP 8.2+
Responde siempre en español.

---

## si necesitas conectarte a la base de datos esta en mi local los credenciales son:
DB_DATABASE=inventory_gclaure
DB_USERNAME=postgres
DB_PASSWORD=admin123

## PHP y Laravel

- Usa PHP 8.2+ con tipado estricto en todos los archivos: `declare(strict_types=1);`
- Usa **tipos en propiedades, parámetros y retornos** en todas las clases y métodos.
- Prefiere **clases de tipo readonly** para DTOs y objetos de transferencia de datos.
- Sigue el estándar **PSR-12** para el estilo del código.
- Usa **enums de PHP 8.1+** en lugar de constantes para valores predefinidos.
- Evita usar `mixed` como tipo; sé siempre explícito.
- Nunca uses `var_dump`, `dd`, `dump` en código de producción.

---

## Arquitectura y Estructura

- Sigue la estructura de directorios estándar de Laravel estrictamente.
- Toda la lógica de negocio compleja va en **Services** o **Actions** dentro de `app/Services/` o `app/Actions/`.
- Expón los servicios principales mediante **Facades** en `app/Facades/`.
- Los **Form Requests** van en `app/Http/Requests/` y manejan toda la validación.
- Los **Resources** van en `app/Http/Resources/` de forma obligatoria para limpiar y transformar la data antes de enviarla a Vue/Inertia.
- No pongas lógica de negocio en controladores; los controladores solo orquestan y devuelven `Inertia::render()`.
- No pongas lógica de negocio en los modelos; solo relaciones, scopes y accessors/mutators.

---

## Modelos y Eloquent

- Define siempre `$fillable` en cada modelo (nunca uses `$guarded = []` en producción).
- Define todas las relaciones Eloquent con sus tipos de retorno explícitos.
- Usa **Eloquent Scopes** para encapsular consultas reutilizables.
- Usa **Accessors y Mutators** con la sintaxis moderna de Laravel 9+.
- Evita el problema N+1: usa siempre **eager loading** (`with()`). Es crítico en Inertia para evitar errores en el frontend.
- Define **casts** en el modelo para fechas, booleans y JSON.

```php
protected $casts = [
    'active'     => 'boolean',
    'metadata'   => 'array', // o 'json' dependiendo del driver
    'created_at' => 'datetime',
];