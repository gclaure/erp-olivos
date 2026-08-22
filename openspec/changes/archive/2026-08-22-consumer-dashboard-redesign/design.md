# Design: Rediseño Visual Ejecutivo de "Mi Panel de Consumo"

## Visual Tokens & Palette

| Token | Light Mode | Dark Mode | Propósito |
|-------|------------|-----------|-----------|
| **Brand Primary** | `#4f46e5` (Indigo-600) | `#6366f1` (Indigo-400) | Acciones clave, acentos de marca |
| **Brand Accent** | `#059669` (Emerald-600) | `#10b981` (Emerald-400) | Estado finalizado, área activa, CTA |
| **Warning/Pending** | `#d97706` (Amber-600) | `#f59e0b` (Amber-400) | Pedidos en curso / pendientes |
| **Alert/Observed** | `#e11d48` (Rose-600) | `#f43f5e` (Rose-400) | Solicitudes observadas con urgencia |
| **Card Surface** | `#ffffff` / `border-zinc-200/70` | `#1e293b` (Secondary-800) / `border-secondary-700/80` | Superficies Bento |

## Layout Structure

```
ConsumerDashboard.vue
├── Executive Hero Header:
│   ├── Greeting & User Area Pill
│   ├── Filter Chips (Month / Year)
│   └── CTA "+ Nueva Solicitud" (Link to admin.consumption-requests.create)
│
├── Observed Requests Alert Banner (Conditional if observed > 0)
│
├── Bento KPI 4-Card Grid:
│   ├── 1. Mis Solicitudes (Indigo / Sparkline / Count)
│   ├── 2. Pendientes de Despacho (Amber / Pulse Badge)
│   ├── 3. Observadas (Rose / Action Required Link)
│   └── 4. Finalizadas (Emerald / Success Rate)
│
├── Charts Grid (2 columns: 2/3 + 1/3):
│   ├── Left: Tendencia de Solicitudes Anual (Smooth Line Chart with Area Gradient)
│   └── Right: Pedidos por Categoría (Doughnut Chart with Custom Pill Legends)
│
└── Secondary Grid (2 columns: 1/3 + 2/3):
    ├── Left: Top 5 Insumos más Pedidos (Horizontal Bars / Ranking)
    └── Right: Solicitudes Recientes (Refined table + Mobile cards)
```

## Component Updates
- `resources/js/Pages/Admin/ConsumerDashboard.vue`:
  - Reemplazo completo de la plantilla con el nuevo sistema de diseño Bento.
  - Configuración optimizada de Chart.js con degradados de Canvas nativos y tooltips modernos.
