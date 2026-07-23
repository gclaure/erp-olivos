---
name: creador-de-habilidades
description: Utilidad para que Antigravity pueda generar, estructurar y documentar nuevas habilidades (.opencode/skills) en el workspace siguiendo las mejores prácticas y en idioma español.
---

# Creador de Habilidades

## When to use this skill
- Cuando el usuario solicita crear una nueva capacidad o "skill" personalizada en el proyecto.
- Cuando se detecta un patrón de trabajo repetitivo que podría beneficiarse de ser documentado como una habilidad.
- Cuando se desea estandarizar la creación de habilidades en español dentro del workspace.

## How to use it
Para crear una nueva habilidad, se deben seguir estos pasos rigurosamente:

### 1. Definición y Estructura
- Crear una carpeta única en `.opencode/skills/<nombre-kebab-case>/`.
- El nombre debe ser descriptivo (ej: `autenticacion-premium`, `reportes-avanzados`).

### 2. Formato del archivo SKILL.md
El archivo `SKILL.md` debe comenzar con el frontmatter YAML:
```yaml
---
name: nombre-de-la-habilidad
description: Descripcion clara y en tercera persona de que hace la habilidad.
---
```

### 3. Secciones Obligatorias (en Español)
- `# Título de la Habilidad`: Un nombre legible.
- `## When to use this skill`: Lista de escenarios donde el asistente debe activar esta habilidad automatically.
- `## How to use it`: Guía detallada paso a paso, incluyendo:
    - Convenciones de código (Laravel, Livewire, Tailwind).
    - Reglas de negocio específicas (ej: manejo de decimales para dinero).
    - Ejemplos de uso.
    - Scripts o recursos adicionales si aplican.

### 4. Lenguaje y Tono
- El contenido de las instrucciones debe estar estrictamente en **español**.
- Usar un tono profesional, técnico y autoritario.
- Referenciar las `laravel-rules.md` y `tailwind-rules.md` si la habilidad involucra desarrollo web.

### 5. Archivos Adicionales (Opcional)
- Si la habilidad requiere scripts, guardarlos en el subdirectorio `scripts/`.
- Si requiere ejemplos prácticos, usar el subdirectorio `examples/`.
