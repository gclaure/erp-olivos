## Context

Actualmente, al invocar el método `ConsumptionRequestController::store`, la respuesta de Laravel redirige al listado (`route('admin.consumption-requests.index')`). En puntos de registro rápido de consumo, esto obliga al usuario a volver a navegar a `/admin/consumption-requests/create` para registrar una segunda orden.

## Goals / Non-Goals

**Goals:**
- Cambiar la ruta de redirección en `ConsumptionRequestController::store` de `admin.consumption-requests.index` a `admin.consumption-requests.create`.
- Garantizar que los datos de flash (`success`, `success_data.id`) se conserven y sean consumidos por el componente Inertia `Admin/POS/Index.vue`.
- Asegurar que la apertura de la ventana de impresión (`window.open`) y el reseteo del carrito (`clearCart`) sigan ejecutándose correctamente en el callback `onSuccess`.

**Non-Goals:**
- Modificar el flujo de edición (`ConsumptionRequestController::update`), el cual debe continuar redirigiendo al detalle (`show`) o listado correspondiente.
- Alterar el flujo de cotizaciones o ventas POS estándar si existen bifurcaciones.

## Decisions

- **Modificación en `ConsumptionRequestController::store`**:
  - Reemplazar `return redirect()->route('admin.consumption-requests.index')->with(...)` por `return redirect()->route('admin.consumption-requests.create')->with(...)`.
  - Mantener exactamente el mismo payload en el método `with()`, conteniendo `success` y `success_data` con la llave `id`.
- **Compatibilidad con `Admin/POS/Index.vue`**:
  - Al permanecer en la misma página `/admin/consumption-requests/create`, Inertia actualizará los props de la página actual (`page.props.flash`), ejecutará el callback `onSuccess` del `router.post`, disparando `window.open(printUrl, '_blank')`, `clearCart()` y la alerta modal de confirmación con SweetAlert2.

## Risks / Trade-offs

- **[Persistencia de formularios / estado local]** → El llamado a `clearCart()` en `onSuccess` garantiza que los insumos agregados previamente se limpien y el formulario quede listo para ingresar nuevos ítems sin recarga forzada de página.
