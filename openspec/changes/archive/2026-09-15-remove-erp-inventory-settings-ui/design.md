## Context

El sistema de inventario y consumos Olivos está enfocado en la administración interna de insumos, solicitudes de consumo y recepción de pedidos. La interfaz de Configuración de Empresa (`Company.vue`) contenía una sección llamada "Configuración de Inventario (ERP-Grade)" con dos controles:
1. `inventory_method` (Método de valuación: Promedio Ponderado o PEPS/FIFO).
2. `inventories_closed_until` (Fecha de corte/cierre contable de inventario).

En un entorno de control de insumos interno sin módulo de ventas minoristas ni contabilidad multimetódica, estos campos no aportan valor operativo al usuario administrador y añaden complejidad innecesaria.

## Goals / Non-Goals

**Goals:**
- Remover de `resources/js/Pages/Admin/Settings/Company.vue` la sección visual de "Configuración de Inventario (ERP-Grade)".
- Limpiar el estado reactivo del formulario `form` en `Company.vue` removiendo `inventory_method` e `inventories_closed_until`.
- Ajustar `UpdateCompanyRequest.php` para que la validación no exija estos campos del frontend, manteniendo compatibilidad transparente con la base de datos y modelos existentes.

**Non-Goals:**
- No se modificará la estructura de la tabla `companies` ni se crearán migraciones destructivas (las columnas permanecen con sus valores por defecto).
- No se alterará el servicio `KardexService` ni su estrategia de cálculo (Promedio Ponderado).

## Decisions

### 1. Limpieza de interfaz y formulario Vue 3
- Se elimina el bloque HTML correspondiente a "Configuración de Inventario (ERP-Grade)".
- Se eliminan del formulario `useForm` las propiedades `inventory_method` e `inventories_closed_until`.
- *Alternativa descartada*: Ocultar con `v-if="false"` o `display:none`. Se descarta para mantener el código limpio y sin elementos muertos.

### 2. Tratamiento en UpdateCompanyRequest
- En `UpdateCompanyRequest.php`, se eliminan o hacen `sometimes|nullable` las reglas para `inventory_method` e `inventories_closed_until`, y se evita fallos al no recibir estos campos en el payload.
- Al guardar la empresa, si estos campos no se envían, la instancia `Company` conservará su valor previo en base de datos.

## Risks / Trade-offs

- **[Riesgo]** Si un método del backend asumía que `validated()['inventory_method']` siempre existía en el request de actualización de empresa.
  → **Mitigación**: `CompanyController::update` y `CompanyService` actualizan los datos pasando el array validado. Si no están en `$validated`, el modelo no sobreescribe esas columnas. Se verificará `CompanyService` y `CompanyController` para confirmar la inocuidad.
