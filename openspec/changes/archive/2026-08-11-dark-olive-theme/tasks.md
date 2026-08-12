## 1. Config — secondary var-driven

- [x] 1.1 En `tailwind.config.js`, convertir la escala `secondary` al patrón `rgb(var(--color-secondary-*) / <alpha-value>)` para todos los tonos 50–950
- [x] 1.2 Verificar que `zinc`, `indigo`, `primary` y los tokens `*-app` permanecen sin cambios en `tailwind.config.js`

## 2. CSS — rampa olivo en `.dark`

- [x] 2.1 En `resources/css/app.css`, definir en `:root` la rama `--color-secondary-*` con los valores slate actuales (`248 250 252`…`15 23 42`)
- [x] 2.2 Definir en `.dark` la rama `--color-secondary-*` con la rampa olivo oscura (body `21 24 18`, superficies `38 43 32`, bordes `60 67 53`, texto `138 148 128`, crema `241 237 226`)
- [x] 2.3 Recolorear la rama `--color-zinc-*` del `.dark` a olivo-tinted (`241 237 226`…`12 15 9`), manteniendo la jerarquía de luminosidad
- [x] 2.4 Eliminar los bloques `--color-indigo-*` y `--color-primary-*` del `.dark` (caen por cascada a la rampa verde de `:root`)
- [x] 2.5 Actualizar las variables semánticas del `.dark`: `--color-background 21 24 18`, `--color-surface 38 43 32`, `--color-border 60 67 53`, `--color-text-primary 241 237 226`, `--color-text-secondary 138 148 128`, `--color-text-muted 110 120 104`, `--color-primary 115 172 50`, `--color-primary-hover 141 187 82`, `--color-primary-light 168 201 122`
- [x] 2.6 Verificar que los colores de estado del `.dark` (success/warning/danger/info) permanecen sin cambios

## 3. Verificación

- [x] 3.1 Compilar sin errores (`npm run build`)
- [x] 3.2 Verificar en el CSS compilado que `.dark` resuelve a olivo: `--color-secondary-900: 21 24 18`, `--color-zinc-900: 23 26 18`, `--color-indigo-600: 92 140 40` (verde, no índigo)
- [x] 3.3 Verificar que `:root` (modo claro) mantiene los valores cálidos/verdes de `palette-redesign-olive` sin cambios
- [x] 3.4 Verificar en modo oscuro que `dark:text-indigo-400`/`dark:bg-indigo-900` resuelven a verde (`141 187 82`/`34 60 18`)
- [x] 3.5 Verificar que la opacidad (`bg-secondary-800/70`, etc.) sigue funcionando tras la conversión a triplets
