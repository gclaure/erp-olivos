## ADDED Requirements

### Requirement: Clear product status indicators during consumer reception
The system SHALL display an actionable and intuitive status badge for each product line in `Show.vue` while a consumption request is in the dispatched/reception phase.

#### Scenario: Consumer viewing dispatched items awaiting confirmation
- **WHEN** a user with the "Consumidor" role views a request in "despachado" or "despachado_parcial" status
- **THEN** the product row status badge indicates "📦 POR CONFIRMAR" instead of raw "DESPACHADO"

#### Scenario: Consumer inputs discrepancy in received quantity
- **WHEN** the consumer modifies the received quantity input such that it differs from the requested quantity
- **THEN** the product row displays a dynamic discrepancy badge highlighting the difference

#### Scenario: Request reception confirmed
- **WHEN** the consumer confirms reception and the request status changes to "entregado"
- **THEN** the product status badge shows "✅ RECIBIDO" or "⚠️ RECIBIDO CON DISCREPANCIA"

### Requirement: Reception helper banner for consumers
The system SHALL display a guiding banner when the consumer is viewing a request in "despachado" or "despachado_parcial" status reminding them to verify received quantities and click "Confirmar Recepción".

#### Scenario: Displaying reception helper banner
- **WHEN** the authenticated consumer opens a request in dispatched status
- **THEN** the system renders a prominent guide banner with quick action to submit reception
