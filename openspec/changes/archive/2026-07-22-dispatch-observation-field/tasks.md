## 1. Frontend — Envío de observaciones

- [x] 1.1 En `handleDispatch()`, construir objeto `observations` desde `dispatchObservations` ref (filtrar strings vacíos, trim, max 500 chars)
- [x] 1.2 Agregar `observations` al payload del POST de Inertia junto a `quantities`

## 2. Frontend — UI de textarea por ítem

- [x] 2.1 Agregar textarea colapsado por cada detail row en el formulario de despacho (solo visible para Almacén en estados `aprobado`/`despachado_parcial`)
- [x] 2.2 Vincular textarea con `dispatchObservations[detail.id]`
- [x] 2.3 Limitar a 500 caracteres en frontend (maxlength + counter visual)

## 3. Frontend — Mostrar observation en detalle del ítem

- [x] 3.1 En la card de detail (desktop), agregar bloque "Observación de Despacho" con patrón visual naranja cuando `detail.observation` tenga contenido
- [x] 3.2 En la card de detail (mobile), agregar el mismo bloque de observación
- [x] 3.3 Asegurar que el bloque NO se muestre cuando `observation` es null/vacío

## 4. Frontend — Mostrar observation en timeline/historial

- [x] 4.1 En la sección de historial/timeline del Show.vue, si algún detail tiene observation de despacho, mostrarla como entry asociada al evento de despacho
- [x] 4.2 Usar el mismo patrón visual naranja para la observación en el timeline

## 5. Validación

- [x] 5.1 Validar que el envío funciona (observation llega al backend y se guarda en `consumption_request_details.observation`)
- [x] 5.2 Validar que despachar sin observación no genera errores
- [x] 5.3 Validar que la observation se muestra correctamente en desktop y mobile
- [x] 5.4 Ejecutar `openspec validate --changes dispatch-observation-field`
