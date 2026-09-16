## Context

La base de datos ya contiene la columna `receipt_type` (`default 'media'`) en la tabla `companies`. Sin embargo, la vista `Company.vue` no contaba con los inputs para visualizar y cambiar este valor. Implementar este control en `Company.vue` y enlazarlo con `UpdateCompanyRequest` y `CompanyController` cierra el ciclo completo de configuración.

## Goals / Non-Goals

**Goals:**
- Presentar un selector interactivo tipo Radio Cards en `Company.vue` con iconos y descripciones claras para:
  - `media`: "Media Página / Carta (PDF)" (documentos membretados, firmas y archivos PDF).
  - `rollo`: "Rollo / Ticket Térmico (80mm)" (impresoras térmicas de punto de venta).
- Incluir `receipt_type` en `useForm` con valor por defecto `props.company?.receipt_type || 'media'`.
- Validar `receipt_type` en `UpdateCompanyRequest.php` (`Rule::in(['media', 'rollo'])`).
- Retornar `receipt_type` en el payload de `CompanyController::edit()`.

**Non-Goals:**
- Alterar el formato de las plantillas existentes de rollo o media en este cambio.
- Modificar configuraciones individuales por usuario (ésta es la configuración global de la empresa).

## Decisions

1. **Selector Visual tipo Tarjetas (Radio Cards)**:
   - *Decisión*: Diseñar dos tarjetas clicables con borde activo en índigo / verde corporativo (`border-indigo-600` o `border-emerald-600`), icono descriptivo y texto explicativo.
   - *Razón*: Mejora drásticamente la experiencia de usuario (UX) frente a un simple `<select>` plano, haciéndolo intuitivo y moderno.

2. **Validación estricta en Form Request**:
   - *Decisión*: `Rule::in(['media', 'rollo'])` para garantizar integridad de datos.

## Risks / Trade-offs

- **[Riesgo]** Ninguno. La base de datos ya cuenta con la columna y el backend ya utiliza este atributo en varias vistas.
