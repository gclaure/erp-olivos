## Why

El sistema está orientado exclusivamente al control interno de insumos y solicitudes de consumo de personal, no a ventas minoristas o retail ERP multimetódico. La sección "Configuración de Inventario (ERP-Grade)" en el formulario de empresa (`Company.vue`), que incluye la selección de Método de Valuación (`inventory_method`) y Cierre Contable (`inventories_closed_until`), resulta innecesaria, genera confusión administrativa y ruido visual.

Eliminar esta sección de la UI simplifica la experiencia de administración, mientras que el backend mantiene intacta su lógica interna (Promedio Ponderado por defecto).

## What Changes

- **Eliminación UI**: Retirar la sección completa "Configuración de Inventario (ERP-Grade)" (campos `inventory_method` y `inventories_closed_until`) de la vista `Company.vue`.
- **Ajuste Formulario Frontend**: Remover `inventory_method` y `inventories_closed_until` del estado reactivo del formulario Inertia en `Company.vue`.
- **Ajuste Request Backend**: Hacer opcionales o remover la obligatoriedad de validación de `inventory_method` e `inventories_closed_until` en `UpdateCompanyRequest.php`, manteniendo valores por defecto seguros en backend sin romper el guardado.

## Capabilities

### Modified Capabilities
- `company-settings-update`: Modificar la especificación para remover la exigencia de visualización y edición en interfaz de los campos `inventory_method` e `inventories_closed_until`.

## Impact

- **Frontend**: `resources/js/Pages/Admin/Settings/Company.vue` (limpieza visual y de estado Inertia).
- **Backend Form Request**: `app/Http/Requests/Admin/UpdateCompanyRequest.php` (hacer opcionales o simplificar reglas de validación de campos de inventario).
- **Base de Datos / Servicios**: Sin impacto destructivo ni migraciones requeridas; el Kardex continúa operando con su estrategia por defecto (Promedio Ponderado).
