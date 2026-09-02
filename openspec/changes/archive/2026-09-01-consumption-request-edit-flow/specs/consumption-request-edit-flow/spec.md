## ADDED Requirements

### Requirement: Owner permission to edit pending consumption requests
The system SHALL allow only the creator of a consumption request to edit its items and notes when the request is in "pendiente" status and has not yet been approved.

#### Scenario: Owner views edit button on a pending request
- **WHEN** the authenticated user is the creator of a consumption request (`user_id === auth()->id()`) AND the request status is "pendiente" AND `approved_at` is null
- **THEN** the system displays the "Editar Solicitud" action button in the consumption request details view

#### Scenario: Non-owner or approved request hides edit button
- **WHEN** the authenticated user is not the creator OR the request status is not "pendiente" OR `approved_at` is not null
- **THEN** the system does not allow editing and hides the "Editar Solicitud" button

### Requirement: Edit interface preloaded with existing items
The system SHALL provide an edit interface at `/admin/consumption-requests/{id}/edit` that preloads the catalog and cart with the current items and notes.

#### Scenario: Navigating to edit view
- **WHEN** the owner clicks "Editar Solicitud"
- **THEN** the system redirects to `/admin/consumption-requests/{id}/edit` with the items in the cart sidebar, warehouse pre-locked to the request's warehouse, and existing notes populated

### Requirement: Updating consumption request items and notes
The system SHALL update existing detail lines, add newly selected products, remove deleted items, and update notes upon submission.

#### Scenario: Saving valid edits
- **WHEN** the owner submits modified quantities, added products, or updated notes
- **THEN** the system persists changes in a database transaction, updates the consumption request details, and redirects to the show view with a success notification

#### Scenario: Attempting to edit an approved or non-owned request
- **WHEN** a user attempts to submit `PUT /admin/consumption-requests/{id}` on an approved request or a request owned by another user
- **THEN** the system rejects the request with a 403 Forbidden error
