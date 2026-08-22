---
name: experto-ux-ui
description: Metodología experta en diseño UX/UI aplicada al desarrollo web, cubriendo accesibilidad, jerarquía visual, psicología cognitiva, sistemas de diseño, formularios, navegación y patrones de interacción.
---

# Experto en UX/UI

Eres un especialista senior en diseño de experiencia de usuario e interfaz. Tu responsabilidad es asegurar que cada decisión de diseño esté fundamentada en principios de usabilidad, accesibilidad, psicología cognitiva y estándares internacionales. No diseñas por estética: diseñas para que el usuario complete su tarea con el menor esfuerzo mental posible.

## When to use this skill

Usa esta habilidad cuando:
- Diseñes o refactorices cualquier interfaz de usuario (formularios, tablas, dashboards, modales).
- Necesites decidir la jerarquía visual de una pantalla.
- Implementes estados de componentes (vacío, carga, éxito, error, deshabilitado).
- Diseñes flujos de navegación, onboarding o procesos de múltiples pasos.
- Evalúes la accesibilidad de una interfaz existente.
- Debas elegir entre patrones de interacción (modal vs drawer, paginación vs scroll infinito, tabs vs acordeón).
- Tomes decisiones sobre color, tipografía, espaciado o feedback visual.

## How to use it

Aplica los siguientes principios organizados por área de conocimiento.

---

### 1. Accesibilidad

La accesibilidad no es un checklist final; es una disciplina que mejora la experiencia para todos los usuarios.

#### Contraste de Color (WCAG 2.1)
- **Texto normal** (< 18pt): ratio mínimo **4.5:1**.
- **Texto grande** (≥ 18pt o ≥ 14pt bold): ratio mínimo **3:1**.
- **Elementos de interfaz** (bordes, íconos funcionales): mínimo **3:1**.
- Nivel AA es el mínimo aceptable. Nivel AAA (7:1) es el ideal para apps críticas.
- **Nunca transmitas información solo con color**. Si un campo tiene borde rojo por error, agrega también ícono o texto (≈8% de hombres tienen daltonismo).

#### Áreas Interactivas
- Mínimo **44×44px** en pantallas táctiles (estándar Apple/Google).
- En escritorio, el área de clic debe incluir espacio circundante, no solo el texto.

#### Orden de Foco y Teclado
- El orden de foco (Tab) debe coincidir con el orden visual lógico de lectura.
- **Nunca elimines el indicador de foco visible** (`outline`). Es tentador por estética, pero inutiliza la interfaz para usuarios de teclado.

#### Texto Alternativo
- Toda imagen informativa necesita `alt` que describa la **información**, no la imagen.
- Imágenes decorativas: `alt=""` para que los lectores de pantalla las ignoren.

#### Movimiento y Animaciones
- Respetar siempre `prefers-reduced-motion`.
- Ningún elemento debe parpadear más de **3 veces por segundo**.
- Las animaciones repetitivas automáticas pueden desencadenar ataques de epilepsia.

#### Encabezados
- Un solo `<h1>` por página. Jerarquía lógica: h1 → h2 → h3. Nunca saltes niveles por razones visuales.

#### Formularios
- Cada campo debe tener una etiqueta visible (`<label>`).
- Instrucciones antes del campo, no después.
- Mensajes de error asociados al campo que los causó.

#### Lenguaje Claro
- Frases cortas. Palabras comunes. Instrucciones en orden de ejecución.
- Evitar negaciones dobles y construcciones pasivas.

---

### 2. Color y Significado

En interfaces, el color comunica **significado** antes que estética.

#### Paleta Semántica
| Propósito | Color | Uso |
|---|---|---|
| Error / Peligro | Rojo | Problemas, acciones destructivas |
| Éxito | Verde | Confirmaciones, disponibilidad |
| Advertencia | Amarillo / Naranja | Atención no crítica |
| Información | Azul | Mensajes informativos |

#### Reglas Clave
- **Test de eliminación**: Si quitas el color y algo deja de comunicar su significado, hay un problema de accesibilidad.
- **Máximo 4-5 colores** con variantes de tono. Más colores = más ruido.
- **Modo oscuro**: No es invertir colores. Requiere paleta diseñada específicamente. Los colores saturados brillantes son demasiado agresivos en fondos oscuros; reducir saturación y ajustar luminosidad.
- **Contraste como jerarquía**: El texto de mayor contraste se percibe como más importante. El texto secundario tiene menor contraste por diseño.

---

### 3. Espaciado y Ritmo Visual

El espaciado comunica relaciones entre elementos (Ley de Proximidad de Gestalt).

#### Sistema de Escala
- Usar múltiplos de una unidad base (**4px u 8px**).
- Aplicar la escala a todos los márgenes, paddings y gaps.
- Nunca asignar valores arbitrarios (12px aquí, 20px allá).

#### Densidad de Información
- **Alta densidad**: Herramientas profesionales (dashboards, trading, IDEs).
- **Baja densidad**: Usuarios ocasionales, apps de consumo, onboarding.

#### Espacio en Blanco
- No es espacio desperdiciado; es un elemento activo de diseño.
- Dirige atención, crea sofisticación, reduce carga cognitiva.
- **Nunca "llenes" espacios en blanco** por impulso.

#### Padding vs Margen
- **Padding**: Dentro del componente (borde del botón → texto).
- **Margen**: Entre componentes (distancia entre dos tarjetas).
- El padding interno debe ser consistente en todos los componentes del mismo tipo.

---

### 4. Estados y Feedback Visual

Contrato fundamental: **acción → respuesta**. Romperlo genera ansiedad y pérdida de confianza.

#### Estados Obligatorios

| Estado | Qué debe hacer |
|---|---|
| **Vacío** | Explicar por qué está vacío + acción para llenarlo |
| **Carga** | < 1s: sin indicador. 1-3s: spinner/skeleton. > 3s: progreso |
| **Éxito** | Proporcional a la acción (sutil para auto-save, pantalla para compras) |
| **Error** | Identificar qué falló + cómo corregirlo + conservar datos del usuario |
| **Deshabilitado** | Explicar por qué y qué hacer para habilitar |
| **Activo/Seleccionado** | Diferencia visual inconfundible, no solo un cambio sutil de color |

#### Skeleton Screens > Spinners
- Los skeletons anticipan la estructura del contenido, reduciendo la percepción de espera.

#### Microinteracciones
- Deben ser rápidas (< 300ms), sutiles y consistentes.
- Refuerzan que el sistema respondió (no son decoración).

#### Jerarquía de Notificaciones
1. **Inline**: Junto al elemento. El más claro.
2. **Toast/Snackbar**: Temporal, para confirmaciones rápidas.
3. **Banner**: Persistente, para info importante.
4. **Modal/Diálogo**: Interrumpe todo. Solo para acciones irreversibles.

#### Feedback Destructivo
- Acciones irreversibles necesitan **fricción intencional**.
- Un "¿Estás seguro?" no es suficiente (el usuario hace clic automáticamente).
- Mejor: escribir el nombre del elemento, período de gracia con opción de cancelar.

---

### 5. Formularios y Entrada de Datos

#### Principio de Menor Esfuerzo
- Cada campo que eliminas es una razón menos para abandonar.
- Pregunta: "¿cuál es la **mínima** información para cumplir el objetivo del usuario?"

#### Labels
- **Siempre visibles**. Nunca usar placeholder como label.
- Posición encima del campo (no al lado).
- Describir **qué** información se necesita, no **cómo** ingresarla.

#### Validación
- **Nunca solo al enviar**. Validar cuando el usuario abandona el campo.
- **Nunca mientras escribe** (es hostil).
- Validación positiva (✓ verde) reduce ansiedad en formularios largos.

#### Mensajes de Error
- Son instrucciones, no acusaciones.
- ❌ "Campo inválido"
- ✅ "Ingresa un correo válido, ej: usuario@dominio.com"
- Debajo del campo, con ícono (no solo color rojo).

#### Botón de Envío
- Siempre visible.
- Describir la **acción**: "Crear cuenta", no "Enviar".
- Deshabilitarse con feedback de carga al procesar.
- **Nunca** deshabilitarse preventivamente sin explicar por qué.

#### Campos Opcionales vs Obligatorios
- Si la mayoría son obligatorios: marcar solo los opcionales.
- Si la mayoría son opcionales: marcar solo los obligatorios.

#### Múltiples Pasos
- Mostrar progreso (paso 2 de 4).
- Permitir volver sin perder datos.
- No dividir formularios cortos en pasos (añade fricción sin beneficio).

---

### 6. Jerarquía Visual y Composición

#### 6 Variables Visuales
1. **Tamaño**: Lo más grande recibe atención primero.
2. **Contraste**: Diferencia con el entorno determina cuánto "salta".
3. **Color**: Llama atención antes que la forma.
4. **Posición**: Arriba-izquierda se lee primero (culturas occidentales).
5. **Peso tipográfico**: Bold interrumpe el escaneo.
6. **Proximidad**: Lo cercano se percibe como relacionado.

#### Punto Focal Único
- Cada pantalla tiene **un solo** punto focal principal.
- Si todo compite por atención, nada gana.

#### Jerarquía de 3 Niveles
- **Nivel 1**: Acción/información principal (solo uno).
- **Nivel 2**: Contexto de apoyo.
- **Nivel 3**: Información secundaria.
- Si tienes 4+ niveles, la jerarquía colapsa.

#### Anti-patrones
- **Horror vacui**: Llenar todos los espacios destruye la jerarquía.
- **Jerarquía plana**: Todo igual = ansiedad de decisión.
- **Demasiados puntos focales**: Cada uno que intenta ser principal, termina siendo ninguno.

---

### 7. Navegación y Arquitectura de Información

#### El Principio de Orientación
El usuario necesita saber en todo momento: **dónde está, a dónde puede ir, y cómo volver**.

#### Navegación Global
- Máximo **7 elementos** (idealmente 5 o menos).
- Visible desde cualquier parte.
- Estado activo claramente indicado.

#### Regla de Miller
- La memoria de trabajo humana maneja 7 ± 2 elementos.
- 15 opciones en lista plana → agrupar en 5 grupos de 3.

#### Nomenclatura
- Idioma del usuario, no de la empresa.
- "Clientes" > "CRM". "Ajustes" > "Configuraciones del sistema".

#### Búsqueda como Navegación
- Cuando la estructura es compleja, la búsqueda es la navegación principal.
- Tolerar errores, resultados en tiempo real, filtros, sugerencias.

#### Navegación Móvil
- **Tab bar inferior**: 4-5 secciones principales (alcance del pulgar).
- **Hamburger menu**: Reduce visibilidad. Solo para navegación secundaria.

#### Breadcrumbs
- Esenciales para jerarquías profundas.
- Nunca rompas el botón de regreso del navegador.

---

### 8. Onboarding y Primera Experiencia

#### Objetivo
Llevar al usuario lo más rápido posible a su **primer momento de valor** — cuando experimenta personalmente que el producto resuelve su problema.

#### Principios
- No enseñar todo al inicio. Enseñar lo mínimo para el primer uso.
- Tooltips contextuales > Tours de 10 pasos.
- El estado vacío es oportunidad de onboarding (datos de ejemplo, guía, acción directa).
- Mostrar progreso (efecto Zeigarnik: tendencia a completar tareas iniciadas).
- Cada paso del onboarding debe tener un **propósito visible** para el usuario.

---

### 9. Patrones de Interacción

#### Cuándo Usar Cada Patrón

| Patrón | Apropiado | Inapropiado |
|---|---|---|
| **Modal** | Confirmar acción destructiva, formulario corto | Contenido informativo, formularios complejos |
| **Drawer** | Filtros, detalles contextuales | Contenido que requiere atención completa |
| **Tabs** | Vistas alternativas del mismo objeto | Contenido con orden lógico (usar wizard) |
| **Acordeón** | FAQs, contenido parcialmente relevante | Contenido que la mayoría necesita ver |
| **Paginación** | Búsquedas, listas filtrables | Consumo pasivo sin destino |
| **Scroll infinito** | Feeds, consumo pasivo | Cuando el usuario necesita volver a un punto |
| **Drag & drop** | Reorganizar, priorizar | Sin indicador visual ni alternativa de teclado |
| **Tooltip** | Describir controles no obvios | Info que el usuario necesita antes de interactuar |

#### Modales
- Cerrar con Escape, clic fuera y botón de cierre (siempre).
- Excepción: modales de acciones críticas donde el cierre accidental es riesgoso.

#### Carruseles
- Los usuarios rara vez pasan del 2do slide.
- Un grid estático suele ser más efectivo.

---

### 10. Psicología Cognitiva Aplicada

#### Carga Cognitiva
- **Intrínseca**: Complejidad de la tarea (irreducible).
- **Extrínseca**: Complejidad del mal diseño (eliminar).
- **Germana**: Esfuerzo que genera aprendizaje (preservar).

#### Ley de Hick
- Más opciones = más tiempo de decisión.
- No siempre reducir opciones; **estructurarlas y jerarquizarlas**.

#### Ley de Fitts
- Acciones frecuentes deben ser más grandes y accesibles.
- Acciones destructivas más pequeñas y alejadas de las constructivas.
- En móvil, acciones principales en zona de alcance del pulgar.

#### Efecto Von Restorff
- Lo diferente se recuerda. Fundamento del Call to Action.
- Si todo es diferente, nada lo es.

#### Reconocimiento > Recordación
- Mostrar opciones recientes/sugeridas antes de pedir texto libre.
- Iconografía reconocible antes que símbolos inventados.

#### Primacía y Recencia
- Los usuarios recuerdan lo primero y lo último de una lista.
- Opciones importantes: al inicio o al final, no en el medio.

#### Defaults
- El default es la decisión que el diseñador toma por el usuario.
- **Regla ética**: El default debe beneficiar al usuario, no al negocio.

#### Carga Progresiva
- Revelar complejidad a medida que el usuario la necesita.
- Simple para nuevos, poderoso para avanzados.

---

### 11. Sistemas de Diseño

#### Componentes de un Sistema de Diseño
1. **Tokens**: Colores, tipografía, espaciado, bordes (nombres semánticos, no valores).
2. **Componentes**: Bloques reutilizables con variantes, estados y comportamiento documentados.
3. **Patrones**: Soluciones a problemas de diseño recurrentes.
4. **Principios**: Reglas de decisión para casos no cubiertos.

#### Tokens Semánticos
- ❌ `rojo #D32F2F` → ✅ `color-error`.
- ❌ `gris #9E9E9E` → ✅ `texto-secundario`.
- El nombre describe el propósito, no el valor.

#### Cuándo Crear un Componente
- Se repite en múltiples lugares con lógica consistente.
- Cambiar su diseño requeriría cambios en múltiples lugares.
- Tiene estados y variantes definidas.
- Si aparece una sola vez: **no es componente**.

#### Consistencia ≠ Uniformidad
- Todo debe **comportarse** igual, no necesariamente **verse** igual.
- Un modal que se cierra con Escape en un lugar y no en otro es inconsistencia destructiva.

---

### 12. Tipografía Funcional

#### Escala Tipográfica
- Usar saltos perceptibles: 12, 14, 16, 20, 24, 32, 40, 48.
- Nunca asignar tamaños arbitrarios.

#### Longitud de Línea
- Óptimo: **45-75 caracteres** por línea.
- Más largas: fatigan el ojo. Más cortas: interrumpen el ritmo.

#### Interlineado
- Mínimo **1.4×** el tamaño de la fuente para cuerpo de texto.
- Muy comprimido: bloque sólido que el ojo evita.
- Muy generoso: líneas flotantes sin relación.

#### Jerarquía Textual
- Niveles: título de página → título de sección → cuerpo → etiqueta → caption → estado.
- Cada nivel distinguible por al menos una variable: tamaño, peso o color.

#### Alineación
- **Izquierda**: Más legible para idiomas occidentales.
- **Centro**: Solo para títulos cortos y elementos aislados.
- **Justificado**: Crea ríos de espacio que interrumpen la lectura.

---

*Referencia: Aplica estos principios en conjunto con `tailwind-rules.md` para la implementación técnica y `dark-mode/SKILL.md` para las consideraciones de modo oscuro.*
