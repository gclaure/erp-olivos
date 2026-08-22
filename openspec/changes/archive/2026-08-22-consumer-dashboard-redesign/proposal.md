# Proposal: Rediseño Visual Ejecutivo de "Mi Panel de Consumo"

## Why
El panel principal de los usuarios con rol Consumidor ([`ConsumerDashboard.vue`](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/resources/js/Pages/Admin/ConsumerDashboard.vue)) posee una apariencia visual convencional que no aprovecha la identidad visual moderna de la plataforma. Se requiere un rediseño de alta gama con el skill `frontend-design` para convertirlo en un *Centro de Mando Operativo Ejecutivo*, con tarjetas Bento interactivas, gráficos Chart.js estilizados con degradados suaves, barra de atajos rápidos ("+ Nueva Solicitud") y responsividad completa (Mobile, Tablet y Desktop).

## What Changes
1. **Hero Header Ejecutivo**:
   - Saludo contextualizado con el nombre del usuario y badge del área operativa (ej. `🍽️ Área: COCINA`).
   - Botón de acción principal destacado: **`+ Nueva Solicitud`** (acceso en 1 clic a `/admin/consumption-requests/create`).
   - Filtros de período (Mes y Año) en chips de selección estilizados.
2. **Bento KPI Cards**:
   - 4 tarjetas con acentos de color deliberados (Índigo, Ámbar, Carmesí, Esmeralda), micro-indicadores y pulso en estados pendientes.
3. **Gráficos Chart.js de Alta Fidelidad**:
   - Curva de tendencia anual con degradado translúcido, tooltips oscuros pro y escala limpia.
   - Donut de pedidos por categoría con métrica central y leyendas estilizadas.
   - Ranking de insumos pedidos con barras suaves y unidades de medida.
4. **Tabla & Cards de Solicitudes Recientes**:
   - Estados con viñetas micro-indicadoras, enlaces directos a detalle y soporte responsivo móvil.

## Capabilities

### New Capabilities
- `consumer-dashboard-redesign`: Interfaz ejecutiva moderna, responsiva y orientada a la toma de decisiones rápidas para usuarios consumidores.

## Impact
- `resources/js/Pages/Admin/ConsumerDashboard.vue`
