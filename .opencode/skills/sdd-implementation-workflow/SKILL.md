---
name: sdd-implementation-workflow
description: >
  Flujo obligatorio de Specification Driven Development (SDD) que OpenCode debe
  seguir ANTES de escribir cualquier línea de código en un proyecto Laravel. Se
  activa SIEMPRE que se solicite implementar una nueva funcionalidad o modificar
  funcionalidad existente. Integra OpenSpec con el análisis del proyecto para
  garantizar consistencia.
---

# SDD — Flujo de Implementación Obligatorio (Laravel)

OpenCode NUNCA implementa código directamente. Siempre sigue este flujo de 9 pasos.

---

## Principio Fundamental

> La prioridad de OpenCode NUNCA es escribir más código.
> Su prioridad es **preservar la calidad del software**, respetar la arquitectura
> existente y desarrollar nuevas funcionalidades siguiendo SDD, garantizando
> consistencia, mantenibilidad y estabilidad a largo plazo.

---

## El Flujo — 9 Pasos Obligatorios

```
┌─────────────────────────────────────────────────────────┐
│                 FLUJO SDD OBLIGATORIO                    │
│                                                          │
│  1. LEER ESPECIFICACIÓN                                  │
│     └── openspec/changes/<name>/proposal.md              │
│         openspec/changes/<name>/design.md                │
│         openspec/changes/<name>/tasks.md                 │
│         openspec/changes/<name>/specs/                   │
│                                                          │
│  2. ANALIZAR PROYECTO                                    │
│     └── Entender contexto, capas afectadas, scope        │
│                                                          │
│  3. BUSCAR IMPLEMENTACIONES SIMILARES                    │
│     └── grep, explorar controllers/services existentes   │
│                                                          │
│  4. REUTILIZAR CÓDIGO EXISTENTE                          │
│     └── Servicios, repositorios, validaciones, patterns  │
│                                                          │
│  5. DISEÑAR LA SOLUCIÓN                                  │
│     └── Definir clases, interfaces, ubicaciones          │
│                                                          │
│  6. IMPLEMENTAR                                          │
│     └── Seguir convenciones del proyecto exactamente     │
│                                                          │
│  7. VALIDAR                                              │
│     └── Análisis estático, checklist de revisión         │
│                                                          │
│  8. REVISAR IMPACTO                                      │
│     └── ¿Rompí algo? ¿Modifiqué sin necesidad?          │
│                                                          │
│  9. FINALIZAR                                            │
│     └── Marcar tarea completa en tasks.md                │
│                                                          │
│  ⚠ NUNCA OMITIR NINGÚN PASO                             │
└─────────────────────────────────────────────────────────┘
```

---

## Detalle de Cada Paso

### Paso 1: Leer Especificación (Lectura Obligatoria y Contexto Mínimo)

Antes de escribir cualquier código, es de **Lectura Obligatoria** e inexcusable leer TODOS los artefactos de la especificación de OpenSpec (como `openspec/config.yaml`, `proposal.md`, `design.md`, `tasks.md` y `specs/`) para extraer los contratos de diseño e invariantes del negocio correspondientes.

Para evitar la saturación de tokens, aplica la regla de **Contexto Mínimo Necesario**: solicita únicamente los mapas de dependencias y los archivos fuente que sean directamente relevantes para el alcance (scope) de la tarea a resolver.

Si no existe especificación, **NO implementar**. Sugerir crear una con `/opsx-propose`.

### Paso 2: Analizar Proyecto

Antes de tocar código, entender:

- ¿Qué capas se van a afectar? (controller, service, repository, model, request, resource)
- ¿Qué módulo/feature corresponde (namespace o subdirectorio del dominio)?
- ¿Qué modelos, servicios, repositorios ya existen en ese módulo?
- ¿Hay dependencias cruzadas con otros módulos (eventos, jobs, otros servicios)?

Acciones concretas:
```
1. Listar app/Http/Controllers/<Feature>/
2. Listar app/Services/<Feature>/ (o app/Services/*<Feature>*)
3. Listar app/Models/*<Feature>*
4. Listar app/Repositories/*<Feature>*
5. Listar app/Http/Requests/<Feature>/
6. Listar app/Http/Resources/<Feature>/
7. Revisar routes/api.php (o web.php) para rutas ya registradas del módulo
```

### Paso 3: Buscar Implementaciones Similares

Buscar en el proyecto funcionalidades similares a lo que se va a implementar:

- Si voy a crear un nuevo CRUD → buscar otro CRUD existente en el mismo dominio (ej. `CategoryService`, `UnitOfMeasureService`).
- Si voy a crear validación → buscar `Rules` custom similares en `app/Rules/`.
- Si voy a tocar un módulo transversal (ej. inventario) → leer el `Service` correspondiente existente (ej. `InventoryService`).
- Si voy a tocar un flujo transaccional (ej. ventas/pedidos) → leer el `Service` existente (ej. `OrderService`).

**El objetivo es copiar el patrón exacto, no inventar uno nuevo.**

### Paso 4: Reutilizar Código Existente

Antes de crear algo nuevo, verificar si ya existe:

| Necesito... | Buscar en... |
|---|---|
| Validar un campo | `app/Rules/` — ¿ya existe una custom rule? |
| Un enum de estado | `app/Enums/` — ¿ya existe el enum? |
| Un servicio helper | `app/Services/` — ¿puedo inyectar un servicio existente? |
| Una query | `app/Repositories/` o `scopes` del modelo — ¿ya existe una query similar? |
| Un response | `app/Http/Resources/` — ¿puedo reusar un resource existente? |
| Una validación de entrada | `app/Http/Requests/` — ¿ya existe un `FormRequest` similar? |

**Si existe, inyectarlo. No duplicar.**

### Paso 5: Diseñar la Solución

Definir exactamente qué clases se van a crear/modificar:

```
Nuevas clases:
  - app/Http/Controllers/<Feature>/XxxController.php
  - app/Services/Xxx/XxxServiceInterface.php
  - app/Services/Xxx/XxxService.php
  - app/Repositories/Xxx/XxxRepositoryInterface.php (si aplica)
  - app/Repositories/Xxx/XxxRepository.php (si aplica)
  - app/Http/Requests/Xxx/StoreXxxRequest.php
  - app/Http/Resources/Xxx/XxxResource.php

Clases/archivos modificados:
  - routes/api.php (nuevo grupo/ruta registrada explícitamente)
  - app/Providers/AppServiceProvider.php (o un ServiceProvider dedicado, para el binding Interface → Implementation)
  - app/Models/XxxModel.php (si es necesario)
```

### Paso 6: Implementar (Capas de Razonamiento y Carga de Skills Obligatorias)

**Antes de escribir una sola línea de código, identifica y carga explícitamente todos los skills aplicables al contexto de la tarea.**
No inicies la escritura de código hasta haber leído y cargado las instrucciones de los skills relevantes:
- `correccion-sobre-comodidad` (Aplicable obligatoriamente a toda tarea para resolver la raíz del problema).
- `clean-code-controllers` (Si estás creando o modificando controladores en `app/Http/Controllers/`).
- `clean-code-imports` (Si estás trabajando con lógica de importaciones/subidas de archivos).
- `laravel-conventions` y `project-architecture` (Para cualquier creación o modificación de clases del backend).
- `solid-principles` (Para el diseño y estructura de clases).
- `multi-tenancy-rules` (Si hay escritura de queries Eloquent/Query Builder o lógica dependiente del tenant).
- `domain-business-rules` (Si estás operando sobre la lógica de negocio).

Al implementar cada tarea, ejecuta obligatoriamente estas tres fases estructuradas:
1. **Fase de Pensamiento (Chain-of-Thought)**: Escribe un bloque `### 🧠 Análisis` antes de realizar cualquier cambio en el código, evaluando detalladamente casos extremos, manejo de errores e invariantes de OpenSpec.
2. **Fase de Calidad Estricta**: Queda prohibido el uso de tipos sin declarar (respetar `declare(strict_types=1)` y type hints en propiedades, parámetros y retornos). Exige métodos con Responsabilidad Única (SRP) e idempotencia en el diseño lógico.
3. **Fase de Autocrítica (Self-Correction)**: Realiza un ciclo de revisión interna del código modificado o creado buscando posibles vulnerabilidades de rendimiento (N+1, falta de índices), coherencia de base de datos (migraciones, foreign keys) o lógica antes de aplicar los cambios finales.

Escribir el código siguiendo EXACTAMENTE las convenciones del proyecto:
- **DTOs nuevos**: usar clases `readonly` (PHP 8.2+) o Data Objects (ej. `spatie/laravel-data`), no arrays asociativos sueltos.
- **Mensajes de error 400/422**: en español, siguiendo la convención del proyecto.
- **Sin firma de autor** en comentarios de clase.

### Paso 7: Validar y Auto-corregir

**Al finalizar la escritura del código, ejecuta obligatoriamente el checklist de revisión (`code-review-checklist-laravel`).**
Debes evaluar minuciosamente cada punto del checklist sobre los archivos modificados y/o creados, y corregir de manera automática e inmediata cualquier violación a las reglas de diseño antes de dar la tarea por completada o avanzar a la revisión de impacto.

Adicionalmente, verificar que el código pase el análisis estático/formateo del proyecto (ej. `./vendor/bin/pint`, `./vendor/bin/phpstan analyse`) si están configurados.

### Paso 8: Revisar Impacto

Preguntarse:

- ¿Modifiqué alguna funcionalidad existente sin que la especificación lo pidiera?
- ¿Cambié algún contrato público (API response, ruta, status code)?
- ¿Introduje un patrón nuevo que no existía en el proyecto?
- ¿Toqué alguna migración de forma que rompa datos o `factories`/`seeders` existentes?
- ¿Hay riesgo de regresión en otra funcionalidad?

Si la respuesta es SÍ a cualquiera → **DETENER y revisar**.

### Paso 9: Finalizar

- Marcar la tarea como completada en `tasks.md`: `- [ ]` → `- [x]`
- Describir lo que se hizo y detallar los tests ejecutados.
- Especificar la clase de pruebas diseñada para cubrir esta tarea (`XxxTest.php` en `tests/Feature/` o `tests/Unit/`, usando Pest o PHPUnit según la convención del proyecto), incluyendo el uso de `RefreshDatabase`/factories y mocks (`Mockery`) donde aplique.

---

## Protección del Proyecto

OpenCode NUNCA debe:

| Acción prohibida | Por qué |
|---|---|
| Modificar funcionalidad existente sin necesidad | Riesgo de regresión |
| Cambiar la arquitectura | No es su decisión |
| Introducir nuevos patrones | Inconsistencia con el resto del código |
| Crear duplicación | Siempre reutilizar |
| Romper contratos públicos (API) | Los frontends/consumidores dependen de ellos |
| Cambiar comportamiento existente | Sin especificación que lo pida |

Cuando sea necesario modificar código existente: **cambio mínimo posible**.

---

## Integración con OpenSpec

| Necesito... | Comando |
|---|---|
| Explorar una idea | `/opsx-explore` |
| Crear una nueva especificación | `/opsx-propose <nombre>` |
| Implementar tareas de una spec | `/opsx-apply <nombre>` |
| Archivar un cambio completado | `/opsx-archive <nombre>` |
| Listar cambios activos | `openspec list --json` |
| Ver estado de un cambio | `openspec status --change "<nombre>" --json` |

---

## Anti-patrones a Evitar

- ❌ "Voy a implementar esto rápido sin leer la spec"
- ❌ "Creo mi propio patrón porque el existente no me gusta"
- ❌ "Modifico este servicio existente para agregar mi feature"
- ❌ "No necesito buscar si ya existe algo parecido"
- ❌ "Uso `Model::all()` o una query sin scope de tenant porque es más fácil"
- ❌ "Pongo la lógica en el controller porque son pocos pasos"
