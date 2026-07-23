---
name: inertia-optimizacion-payload
description: Habilidad avanzada para optimizar el rendimiento de aplicaciones Laravel + Inertia.js mediante la eliminación total de overfetching, uso de Partial Reloads y gestión reactiva correcta en Vue 3.
---

# Inertia - Optimización de Payload y Rendimiento Avanzado

## When to use this skill

* Cuando el JSON de respuesta de Inertia contiene datos redundantes o no utilizados en la página.
* Para implementar filtros y paginación ultra-rápidos sin recargar componentes innecesarios.
* Cuando se detectan problemas de reactividad al usar datos globales (`usePage`).
* Para estandarizar el manejo de formularios y errores mediante `useForm`.
* Cuando se desea aprovechar al máximo las capacidades de PostgreSQL en el stack.

---

## Estrategias Maestras de Optimización

### 1. Partial Reloads (Técnica #1)

Es la herramienta más poderosa de Inertia para eliminar overfetching en navegaciones internas (filtros, búsquedas, paginación). Permite recargar solo fragmentos específicos del payload.

✅ **Implementación en Vue (Frontend):**
```javascript
import { router } from '@inertiajs/vue3'

// Solo recarga 'products' y 'filters', ignora el resto del payload
const handleSearch = () => {
    router.get(route('admin.products.index'), { search: searchQuery.value }, {
        only: ['products', 'filters'],
        preserveState: true,
        preserveScroll: true,
    })
}
```

✅ **Implementación en Laravel (Backend):**
Utilizar `Inertia::lazy()` para datos costosos que solo deben calcularse si se solicitan explícitamente vía `only`.

```php
return Inertia::render('Products/Index', [
    'products' => $query->paginate(20)->withQueryString(),
    'stats' => Inertia::lazy(fn() => $this->getExpensiveStats()), // No se ejecuta en navegaciones normales
    'filters' => $request->only(['search', 'status']), // Liviano, siempre disponible
]);
```

---

### 2. Reactividad con `usePage()`

**Antipatrón Crítico**: Desestructurar props de `usePage()` rompe la reactividad en Vue 3.

❌ **Incorrecto (Pierde reactividad):**
```javascript
const { user } = usePage().props
```

✅ **Correcto (Reactivo):**
```javascript
const user = computed(() => usePage().props.auth.user)
```
Esto asegura que la UI se actualice correctamente tras navegaciones parciales o actualizaciones de flash messages.

---

### 3. Paginación y Filtros

Para una experiencia de usuario fluida, la paginación debe preservar el estado de la consulta y el scroll.

*   **Laravel**: Usar siempre `->withQueryString()` para que los filtros (search, sort) se mantengan al cambiar de página.
*   **Vue**: Usar `preserveScroll: true` para evitar que la página salte al inicio al navegar entre resultados.

```php
// Backend
'items' => Product::paginate(20)->through(fn($p) => [
    'id' => $p->id,
    'name' => $p->name,
    // ...
])->withQueryString()
```

---

### 4. useForm() y Manejo de Errores

Reemplaza `axios` manual en el 90% de los casos. `useForm` gestiona automáticamente estados de carga, errores de validación y prevención de race conditions.

```javascript
import { useForm } from '@inertiajs/vue3'

const form = useForm({ email: '', password: '' })

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}

// Acceso a estados: form.processing, form.errors.email, form.wasSuccessful
```

---

### 5. Inyección en Template Raíz (Layout Data)

Para datos persistentes pesados (Sidebar, Configuración) que no cambian entre páginas.

✅ **Blade (`app.blade.php`):**
```html
<script>
    window.initialConfig = @json(app(POSService::class)->getConfig());
</script>
```

✅ **Vue (Composables/Singleton):**
```javascript
// Definido fuera del setup para persistir en la SPA
const globalConfig = ref(window.initialConfig || {});
```

---

### 6. Optimizaciones Específicas PostgreSQL

Aprovechar las capacidades del motor para reducir la carga en la aplicación:

*   **Columnas JSONB**: Usar para datos de configuración o metadatos variables, evitando JOINs costosos.
*   **Select Selectivo**: En PostgreSQL, omitir columnas grandes (`text`, `jsonb`) mediante `select()` explícito acelera drásticamente la transferencia de datos.
*   **Índices Parciales**: Crear índices para consultas filtradas frecuentes (ej: `where is_active = true`).

---

## Checklist de Calidad Obligatorio

- [ ] **Partial Reloads**: ¿Se usan `only` en filtros y paginación para evitar recargar el Layout?
- [ ] **Reactividad**: ¿Todos los accesos a `usePage().props` están envueltos en `computed()`?
- [ ] **useForm**: ¿Se utiliza `useForm()` para envíos de datos en lugar de llamadas manuales a axios?
- [ ] **Lazy Loading**: ¿Los datos costosos en el controlador usan `Inertia::lazy()` o clausuras `fn()`?
- [ ] **Paginación**: ¿Se usa `withQueryString()` en el backend y `preserveScroll: true` en el frontend?
- [ ] **Payload Limpio**: ¿Se eliminaron campos innecesarios (`created_at`, `updated_at`, etc.) usando `->through()`?
- [ ] **Indices DB**: ¿Existen índices en PostgreSQL para cada campo usado en `where()` y `orderBy()`?

---

## Regla Final

> "El rendimiento en Inertia no es magia; es la disciplina de enviar solo los bytes estrictamente necesarios para el estado actual de la vista."
