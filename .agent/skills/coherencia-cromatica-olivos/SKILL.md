---
name: coherencia-cromatica-olivos
description: Directrices estrictas de identidad visual y coherencia cromática para el sistema Los Olivos. Prohíbe la invención o variación arbitraria de colores (como índigo o violeta) y obliga al uso exclusivo de los tokens oficiales Olivo, Crema y Semánticos.
---

# Coherencia Cromática y Sistema de Diseño Los Olivos

Esta habilidad establece el estándar **estricto y obligatorio** de paleta de colores para cualquier vista, módulo, modal, formulario o componente nuevo o refactorizado en la aplicación.

---

## 1. Por qué existe esta habilidad

En ocasiones se comete el error de importar patrones por inercia de plantillas genéricas de Tailwind (como `indigo-600`, `blue-600` o `purple-600`). Esto fragmenta la identidad del producto y crea una experiencia inconsistente.

**Los Olivos tiene una identidad cromática única y artesanal**:
- **Acento Primario:** Verde Olivo (`#73AC32` / `primary`)
- **Superficies y Neutros Cálidos:** Crema (`#F1EDE2`) y Warm White (`#FAF9F5`)
- **Estructura en Modo Oscuro:** Olivo Profundo / Slate Cálido (`secondary`)
- **Estados Semánticos:** Esmeralda (éxito), Ámbar (advertencia), Rosa/Rojo (peligro/anulado) y Sky (informativo).

---

## 2. When to use this skill

- Siempre que se cree una nueva vista, página (`Pages/`), componente o modal.
- Al diseñar botones de acción, cabeceras, tablas, tarjetas KPI o badges de estado.
- Al auditar o refactorizar interfaces existentes para eliminar colores desalineados.

---

## 3. Matriz de Tokens Oficiales

### A. Elementos de Acción Principal (Botones de Guardar, Crear, Registrar)
| Elemento | Modo Claro | Modo Oscuro | PROHIBIDO USAR |
|---|---|---|---|
| **Botón Primario** | `bg-primary-600 hover:bg-primary-700 text-white` | `dark:bg-primary-600 dark:hover:bg-primary-500 text-white` | `bg-indigo-600`, `bg-blue-600` |
| **Botón Secundario** | `bg-white hover:bg-zinc-100 text-zinc-700 border border-zinc-200` | `dark:bg-secondary-800 dark:hover:bg-secondary-700 text-secondary-100 dark:border-secondary-700` | `bg-gray-100`, `bg-slate-200` |
| **Pill / Badge de Acento** | `bg-primary-50 text-primary-700 border border-primary-200` | `dark:bg-primary-950/40 dark:text-primary-300 dark:border-primary-800/40` | `bg-indigo-50`, `bg-purple-50` |
| **Foco en Inputs** | `focus:border-primary-500 focus:ring-primary-500/20` | `dark:focus:border-primary-500` | `focus:border-indigo-500` |

### B. Superficies, Contenedores y Estructura
| Contenedor | Modo Claro | Modo Oscuro |
|---|---|---|
| **Fondo Global (Body)** | `bg-zinc-50` o `bg-app` (`#FAF9F5`) | `dark:bg-secondary-900` (`#151812`) |
| **Tarjetas / Cards / Modales** | `bg-white border border-zinc-200/80` | `dark:bg-secondary-800 dark:border-secondary-700` |
| **Encabezados de Tabla** | `bg-zinc-50 text-zinc-500` | `dark:bg-secondary-900/50 dark:text-secondary-400` |
| **Hover de Filas** | `hover:bg-zinc-50/80` | `dark:hover:bg-secondary-700/60` |

### C. Estados Semánticos de Negocio (Excepciones Permitidas)
Los únicos colores fuera de la escala Olivo/Secondary permitidos son para **comunicar estado**:
- **Éxito / Completado / Pagado:** `emerald-500` / `emerald-600` (Fondo tenue: `bg-emerald-50 dark:bg-emerald-950/40`)
- **Peligro / Anulado / Cancelado:** `rose-600` / `rose-500` (Fondo tenue: `bg-rose-50 dark:bg-rose-950/40`)
- **Alerta / Pendiente / Advertencia:** `amber-500` / `amber-600` (Fondo tenue: `bg-amber-50 dark:bg-amber-950/40`)
- **Despacho / Delivery:** `sky-600` (Fondo tenue: `bg-sky-50 dark:bg-sky-950/40`)

---

## 4. Ejemplos de Implementación Correcta

### Botón Principal de Acción
```html
<!-- CORRECTO: Usa el verde olivo oficial de la marca -->
<button
    type="submit"
    class="inline-flex items-center justify-center h-11 px-5 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 shadow-md gap-2"
>
    <span class="material-symbols-outlined text-lg">add</span>
    Registrar Pedido
</button>

<!-- INCORRECTO (PROHIBIDO): Usar índigo o colores de plantilla -->
<button class="bg-indigo-600 hover:bg-indigo-700 text-white ..."> <!-- ❌ NUNCA -->
```

### Badge de Folio / Identificador
```html
<!-- CORRECTO: Integrado a la paleta de identidad -->
<span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 font-black text-xs border border-primary-200/60 dark:border-primary-800/40">
    #{{ order.formatted_number }}
</span>
```

### Input de Formulario con Foco de Marca
```html
<input
    type="text"
    class="w-full h-11 px-3.5 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-900 dark:text-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all outline-none"
/>
```

---

## 5. Checklist Obligatorio antes de Guardar una Vista

- [ ] ¿Hay alguna clase `indigo-*`, `blue-*`, `violet-*` o `purple-*` sin justificación de marca? → **Cámbiala a `primary-*`**.
- [ ] ¿Los botones principales usan `bg-primary-600`?
- [ ] ¿Los contenedores y modales en modo oscuro usan `bg-secondary-800` y `border-secondary-700`?
- [ ] ¿Los textos principales en dark usan `text-white` o `text-secondary-100`?
- [ ] ¿Los hovers en tablas y botones secundarios están probados en modo claro y oscuro?
