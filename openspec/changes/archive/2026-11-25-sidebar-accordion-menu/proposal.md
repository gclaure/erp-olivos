# Proposal

## Why

Actualmente, el menú lateral (`AdminLayout.vue`) almacena y despliega los menús desplegables de forma acumulativa, permitiendo que múltiples grupos (Inventario, Consumos, Pedidos, Compras, etc.) permanezcan abiertos simultáneamente. Esto genera una barra lateral excesivamente larga, obliga al usuario a realizar scroll vertical constante y reduce la fluidez de navegación. Implementar un comportamiento de acordeón garantiza que solo un grupo esté desplegado a la vez, cerrando automáticamente los demás y manteniendo la interfaz compacta y organizada.

## What Changes

- **Comportamiento Acordeón Exclusivo**: Al hacer clic en un menú desplegable (ej. *Inventario*), se despliegan sus subopciones y se cierran automáticamente todos los demás menús previamente abiertos.
- **Alternancia (Toggle) del Menú Activo**: Si el usuario vuelve a hacer clic sobre el menú que ya se encuentra abierto, este se pliega limpiamente.
- **Sincronización Inteligente de Ruta Activa**: Al navegar o recargar en una ruta hija, el sidebar detecta automáticamente a qué sección pertenece y mantiene abierto dicho menú de forma exclusiva.
- **Alineación Cromática Olivos**: Reemplazar cualquier clase residual de color índigo/azul en el sidebar por los tokens institucionales Verde Olivo (`primary-600/700`) y Crema/Zinc (`secondary`), respetando las reglas de coherencia cromática.

## Capabilities

### New Capabilities
- `sidebar-accordion-navigation`: Navegación en panel lateral bajo patrón de acordeón mutuamente excluyente con detección y despliegue del menú activo según la ruta actual.

### Modified Capabilities
<!-- Ninguna capability existente modifica sus requerimientos -->

## Impact

- **Frontend**: Componente `resources/js/Layouts/AdminLayout.vue`.
- **Experiencia de Usuario**: Navegación más rápida, sin saturación visual ni scroll excesivo en la barra lateral.
- **Backend / APIs**: Sin impacto en el backend ni en base de datos.
