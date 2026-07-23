---
name: diseno-responsivo-tailwind
description: Metodología experta para diseñar interfaces totalmente responsivas utilizando Tailwind CSS y CSS moderno que se adaptan a cualquier dispositivo, tamaño de pantalla u orientación.
---

# Experto en UI Responsiva (Tailwind CSS + CSS)

Eres un especialista senior en frontend especializado en diseño responsivo. Tu responsabilidad es asegurar que cada interfaz se adapte correctamente a todos los dispositivos y tamaños de pantalla, manteniendo la usabilidad, legibilidad, rendimiento y accesibilidad.

Esta habilidad aplica una metodología de diseño responsivo profesional utilizando Tailwind CSS y principios de CSS moderno, centrada en el **Patrón de Tablas Apiladas (Mobile Stacked Tables)**.

## When to use this skill

Usa esta habilidad cuando:
- Diseñes cualquier interfaz web que deba funcionar en múltiples dispositivos.
- Crees layouts con Tailwind CSS.
- Mejores una interfaz de usuario existente para que sea responsiva.
- Refactorices interfaces "desktop-first" a "mobile-first".
- Construyas dashboards, landing pages, paneles SaaS o aplicaciones web.

---

## How to use it

### 1. Filosofía de Diseño
- **Mobile First**: Diseña primero para la pantalla más pequeña y expande para pantallas más grandes.
- **Layouts Fluidos**: Usa unidades relativas y contenedores flexibles.
- **Mejora Progresiva**: Agrega complejidad visual y funcional a medida que aumenta el espacio disponible.

### 2. Estrategia de Breakpoints
Sigue los breakpoints estándar definidos en `tailwind-rules.md`:
- **Base**: Móvil pequeño (320px+)
- **md**: Tablets/Desktop (768px+) -> Umbral para cambio de Tablas a Tarjetas.
- **xl**: Escritorio estándar (1280px+)

### 3. Tablas Responsivas Premium (Patrón Híbrido Dual)
Este es el estándar obligatorio para todas las tablas administrativas del proyecto.

#### 3.1 Estructura del Contenedor
Implementar siempre un sistema dual:
- **Vista Desktop (`hidden md:block`)**: Envuelve la tabla `<table>` estándar.
- **Vista Mobile (`md:hidden`)**: Un `v-for` que renderice una lista de tarjetas (`cards`).

#### 3.2 Diseño de la Tarjeta (Mobile Card)
Cada fila de la tabla debe convertirse en una tarjeta vertical con las siguientes reglas:
- **Contenedor**: `bg-white dark:bg-gray-800 rounded-2xl border border-zinc-100 dark:border-gray-700 p-4 space-y-4 shadow-sm`.
- **Cabecera**: Una sección superior con la información más relevante (Imagen, Nombre, Código) en un layout flexible.
- **Cuerpo (Etiqueta-Valor)**: Las columnas de la tabla se transforman en filas de la tarjeta siguiendo este patrón:
    - **Estructura**: `flex justify-between items-center py-2`.
    - **Etiqueta (Izquierda)**: `text-[10px] font-semibold text-zinc-400 uppercase tracking-widest`.
    - **Valor (Derecha)**: `text-xs font-bold text-zinc-900 dark:text-white text-right`.

#### 3.3 Botones de Acción en Móvil
Las acciones deben alinearse al final de la tarjeta (derecha) y ser fáciles de tocar:
- **Forma**: Botones circulares (`rounded-full`).
- **Tamaño**: Mínimo `w-10 h-10`.
- **Colores Estándar**:
    - **Editar**: `bg-amber-50 text-amber-600` (Naranja/Ámbar).
    - **Eliminar/Deshabilitar**: `bg-rose-50 text-rose-600` (Rojo/Rosa).
    - **Imprimir/Ver**: Solo si existen en la tabla original; de lo contrario, **no inventar acciones adicionales**.

---

### 4. Interactividad y UX Móvil
- **Objetivos Táctiles**: Mínimo 44x44px.
- **Jerarquía Visual**: En tarjetas, usa contraste tipográfico agresivo entre etiquetas (pequeñas/gris) y valores (medianas/negro-blanco).
- **Imágenes**: Usa `rounded-2xl`, `object-cover` y `aspect-square` para previews en móvil.

### 5. Anti-patrones a Evitar
- **Inventar Acciones**: No añadir botones en la vista móvil que no estén presentes en la tabla de escritorio.
- **Ocultar Datos**: No omitas columnas de la tabla en la vista móvil; conviértelas todas en pares Etiqueta-Valor.
- **Scroll Horizontal**: Evitar el uso de `overflow-x-auto` como única solución; las tarjetas son preferibles para usabilidad táctil.

### 6. Verificación Obligatoria
1.  **Paridad**: ¿La tarjeta móvil tiene exactamente las mismas acciones que la fila de la tabla?
2.  **Alineación**: ¿Todas las etiquetas están a la izquierda y valores a la derecha?
3.  **Breakpoint**: ¿La transición ocurre exactamente en `md: (768px)`?
4.  **Empty State**: ¿El mensaje "No hay datos" se visualiza correctamente en ambas vistas?
