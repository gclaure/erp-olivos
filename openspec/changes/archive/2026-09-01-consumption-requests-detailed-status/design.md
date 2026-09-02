## Context

La vista de listado de solicitudes de consumo (`/admin/consumption-requests`) renderiza una tabla con columnas de fecha, número, solicitante, estado y acciones. El estado actual no brinda suficiente contexto operacional, mostrando solo "Pendiente" sin distinguir si requiere aprobación, si Almacén ya lo puede preparar o si falta stock para compras.

## Goals / Non-Goals

**Goals:**
- Estructurar una función helper en `resources/js/Pages/Admin/ConsumptionRequest/Index.vue` que determine el estado compuesto:
  - `status`: estado principal (ej. `pendiente`, `aprobado`, `despachado`, `despachado_parcial`, `entregado`, `cancelado`, `observado`).
  - `phaseLabel`: texto de la etapa (ej. "Por Aprobar", "Listo para Despacho", "Por Recibir", "Completado").
  - `actorHint`: texto o icono que indica quién debe actuar (ej. "Administración", "Almacén", "Solicitante").
  - `badgeClass`: clases Tailwind consistentes para tema claro y oscuro.
- Rediseñar la celda en la tabla desktop (`<td class="px-6 py-4 whitespace-nowrap text-center">`) y las cards móviles (`lg:hidden`).
- Mantener compatibilidad con los filtros de búsqueda existentes.

**Non-Goals:**
- No alterar las mutaciones de base de datos ni cambiar los endpoints existentes.

## Decisions

### 1. Estructura visual de la celda de Estado
```html
<div class="flex flex-col items-center gap-1">
    <!-- Badge Principal -->
    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider ..." >
        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
        {{ stateInfo.label }}
    </span>

    <!-- Sub-etiqueta de Etapa / Actor -->
    <span class="text-[9px] font-bold text-zinc-500 dark:text-secondary-400 flex items-center gap-1">
        {{ stateInfo.sublabel }}
    </span>

    <!-- Alerta Falta de Stock si aplica -->
    <span v-if="req.has_missing_stock && ..." class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-amber-600 bg-amber-50 ...">
        ⚠️ Falta Stock
    </span>
</div>
```

## Risks / Trade-offs

- **[Risk]** Altura de filas en tablas con múltiples sub-etiquetas.
  - **Mitigación**: Usar `gap-1`, tipografía compacta (`text-[9px]` y `text-[10px]`) y alineación centrada limpia.
