# Proposal

## Why

A pesar de los ajustes previos, el modal "Solicitar Modificación de Producto" sigue resultando confuso y abrumador para el personal de almacén durante la operación diaria. La interfaz expone términos técnicos ("to_product", "situación física declarada"), campos numéricos paralelos ("preparadas en almacén" vs "entregadas físicamente") y validaciones de sumas matemáticas que generan fricción y dudas sobre qué producto regresa y cuál se entrega. Se requiere una simplificación radical y directa basada en el lenguaje natural del almacenero: un flujo guiado en 3 pasos sencillos.

## What Changes

- **Flujo Guiado en 3 Pasos Directos**:
  - **Paso 1: ¿Qué pasa con el producto que estaba pedido?** Muestra el producto original y pregunta en lenguaje directo dónde está: *(•) Sigue en almacén (nunca salió)* vs *(•) El solicitante lo tiene y lo devolverá a almacén*. Se eliminan los campos numéricos manuales y las sumas visibles en el caso estándar.
  - **Paso 2: ¿Qué producto nuevo le vas a entregar?** Buscador intuitivo de insumos compatibles; al seleccionar uno, muestra una tarjeta limpia con el producto nuevo, stock disponible y campo directo para la cantidad a entregar.
  - **Paso 3: Motivo de la sustitución**: Campo para detallar la justificación que revisará el administrador.
- **Eliminación Total de Carga Matemática**: El sistema calcula por detrás las variables de `quantity_prepared` y `quantity_physically_delivered` a partir de la selección del usuario.
- **Tokens Olivos y Responsividad**: Diseño limpio y accesible con paleta Verde Olivo (`primary`) y Crema/Zinc (`secondary`), garantizando funcionamiento fluido en pantallas móviles y desktop.

## Capabilities

### New Capabilities
- `consumption-request-modification-modal-ui`: Interfaz secuencial en 3 pasos directos para la solicitud de sustitución de productos en pedidos de consumo, orientada a la experiencia de usuario del personal de almacén.

### Modified Capabilities
<!-- Ninguna capability existente modifica sus contratos de backend -->

## Impact

- **Frontend**: `resources/js/Components/ConsumptionModification/RequestModificationModal.vue`.
- **Backend**: Cero cambios en contratos de API, base de datos o migraciones; mantiene compatibilidad con `POST /admin/consumption-requests/{consumption_request}/modifications/{detail}`.
