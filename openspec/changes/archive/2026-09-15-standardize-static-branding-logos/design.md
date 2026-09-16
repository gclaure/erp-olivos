## Context

El sistema cuenta con recursos gráficos predefinidos en `/public/img/logo-dark.png` y `/public/img/logo-light.png`. Anteriormente existía un componente Dropzone en la vista de configuración de la empresa para subir logotipos dinámicos por empresa. Al estandarizar el sistema con los logos canónicos, se requiere eliminar el Dropzone y referenciar directamente los assets correspondientes según el contraste del fondo.

## Goals / Non-Goals

**Goals:**
- Retirar completamente el código de Dropzone (HTML, JS, CSS scoped) de `Company.vue`.
- Asegurar que `AdminLayout.vue` apunte de forma fija a `/img/logo-dark.png` en el sidebar oscuro.
- Asegurar que la vista imprimible `receipt.blade.php` apunte a `public_path('img/logo-light.png')` para el PDF de solicitud de consumo.
- Ajustar la tarjeta de previsualización en `Company.vue` para mostrar de forma limpia `/img/logo-light.png`.
- Limpiar validaciones y llamadas de archivo innecesarias en `UpdateCompanyRequest.php` / backend.

**Non-Goals:**
- Modificar el switch `show_name` (éste se mantiene activo para controlar si se muestra o no el texto del nombre comercial junto al logo).
- Eliminar los archivos físicos `/img/logo-dark.png` y `/img/logo-light.png`.

## Decisions

1. **Uso de `/img/logo-dark.png` en AdminLayout**:
   - *Decisión*: El sidebar del administrador tiene fondo oscuro (`bg-zinc-900`), por lo que requiere el logotipo con contraste para fondos oscuros.
   - *Alternativa*: Subir logos personalizados por usuario. Descartada para mantener uniformidad de marca y evitar fallos de carga o contrastes rotos.

2. **Uso de `public_path('img/logo-light.png')` en Recibos/PDF**:
   - *Decisión*: Las hojas impresas y comprobantes de consumo se imprimen en papel blanco/claro, por lo que requieren el arte para superficies claras.
   - *Alternativa*: Usar URLs dinámicas que en DomPDF / Snappy pueden fallar si no se resuelven rutas absolutas locales. Usar `public_path()` garantiza compatibilidad total con motores PDF en Laravel.

3. **Remoción total de Dropzone en Company.vue**:
   - *Decisión*: Eliminar el bundle y las referencias de dropzone para reducir el peso del bundle de Vue y evitar advertencias en consola.

## Risks / Trade-offs

- **[Riesgo]** Caché de navegador al cambiar referencias de logos en Vue.
  - *Mitigación*: Se ejecutan pruebas locales con `npm run build` o Hot Module Reload en Vite para asegurar la carga inmediata.
