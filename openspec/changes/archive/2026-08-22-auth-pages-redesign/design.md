# Design: Rediseño Visual Ejecutivo de Login y Recuperación de Contraseña

## Layout & Architecture

```
Auth Layout (Desktop >= 1024px)
┌──────────────────────────────────────┬──────────────────────────────────────┐
│ Left Showcase Column (50% - 55%):    │ Right Form Column (45% - 50%):       │
│ - Ambient dark slate/zinc backdrop   │ - Clean surface (light/dark adaptive)│
│ - Prominent Official Logo Container  │ - Welcome Heading & Subheading       │
│ - ERP Value Proposition Title        │ - Form Fields (Email, Password)      │
│ - Trust / Feature Badges             │ - Password Eye Toggle                │
│ - System Status Indicator (Pill)     │ - Remember Checkbox & Forgot Link    │
│                                      │ - Premium Submit Button (CTA)        │
│                                      │ - Footer Status & Version Tag        │
└──────────────────────────────────────┴──────────────────────────────────────┘

Auth Layout (Mobile & Tablet < 1024px)
┌─────────────────────────────────────────────────────────────────────────────┐
│ - Centered container (w-full max-w-md mx-auto)                              │
│ - Header: Official Logo (h-16 to h-20) + App Name Badge                     │
│ - Form Card: Rounded-3xl, borders, responsive inputs (min-h-[44px])         │
│ - Action Button + Footer links                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Component Tokens
- **Hero Background**: `bg-zinc-950 dark:bg-black text-white` con sutiles acentos radiales esmeralda/índigo.
- **Form Surface**: `bg-white dark:bg-secondary-850 text-zinc-900 dark:text-white`.
- **Inputs**: `bg-zinc-50 dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:ring-emerald-500 focus:border-emerald-500`.
- **Primary CTA**: `bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black shadow-lg shadow-emerald-600/25`.
