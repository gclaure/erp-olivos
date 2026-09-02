## Context

Las vistas de autenticación (`Login.vue` y `ForgotPassword.vue`) son la puerta de entrada visual al sistema ERP Los Olivos. Actualmente emplean clases Tailwind basadas en tonos genéricos esmeralda/índigo (`bg-emerald-500`, `text-emerald-400`, `ring-emerald-500`, etc.). Se transformarán para utilizar exclusivamente los 6 tokens de color solicitados por el usuario.

## Goals / Non-Goals

**Goals:**
- Aplicar estrictamente la paleta de 6 colores en `/login` y `/forgot-password`:
  - `PRIMARY`: `#73AC32` (Verde Olivo)
  - `SECONDARY`: `#44773C` (Verde Bosque)
  - `ACCENT`: `#A8C98A` (Verde Salvia)
  - `TEXT`: `#050607` (Negro Olivo)
  - `BACKGROUND`: `#FAF9F5` (Marfil)
  - `SURFACE`: `#E9E9E6` (Gris Piedra)
- Armonizar el componente `AuthThreeCanvas.vue` con la misma paleta.

**Non-Goals:**
- No alterar variables globales de `app.css` ni `tailwind.config.js` que impacten el panel administrativo o resto del sistema.

## Decisions

### 1. Clases de Color Específicas / Tailwind Arbitrary Values
- Botones de acción principal: `bg-[#73AC32] hover:bg-[#44773C] text-white shadow-lg shadow-[#73AC32]/25`
- Focus states: `focus:ring-2 focus:ring-[#73AC32]/20 focus:border-[#73AC32]`
- Textos y enlaces:
  - Links: `text-[#44773C] hover:text-[#73AC32]`
  - Headings: `text-[#050607]` (en modo claro) o `text-white`
  - Subtítulos: `text-zinc-400` / `text-[#6B6B67]`
- Badges & highlights: `bg-[#73AC32]/15 border-[#73AC32]/30 text-[#A8C98A]`
