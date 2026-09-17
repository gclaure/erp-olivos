## ADDED Requirements

### Requirement: Ejecución de Kardex y decremento de stock en la entrega conforme
El sistema SHALL ejecutar el decremento de existencias en la tabla `stocks` y la inserción del movimiento cronológico en `kardex` (`KardexMovementType::ADJUSTMENT_OUT`) únicamente cuando la solicitud de consumo es recepcionada y entregada conforme (`ConsumptionRequestService::receiveRequest`).

#### Scenario: Despacho de almacén no altera Kardex
- **WHEN** el usuario de Almacén despacha una solicitud aprobada (`ConsumptionRequestDispatchService::dispatch`)
- **THEN** el sistema registra las cantidades preparadas/despachadas (`quantity_delivered`) y el estado `despachado` o `despachado_parcial`
- **AND** MUST NOT decrementar el balance en la tabla `stocks` ni crear registros de salida en `kardex` en este paso

#### Scenario: Recepción conforme descuenta stock y registra Kardex
- **WHEN** el solicitante o almacenero confirma la recepción de los insumos entregados (`receiveRequest`)
- **THEN** el sistema descuenta las existencias en `stocks` por la cantidad efectivamente entregada (`quantity_received` o `quantity_delivered`) para cada ítem inventoriable
- **AND** registra el movimiento `ADJUSTMENT_OUT` en `kardex` con el costo promedio vigente y referencia a la solicitud de consumo
- **AND** actualiza el estado de la solicitud a `entregado`
