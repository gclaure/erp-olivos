## ADDED Requirements

### Requirement: Olivos brand palette applied to Login view
The `/login` view SHALL apply the specified 6-color palette (`PRIMARY #73AC32`, `SECONDARY #44773C`, `ACCENT #A8C98A`, `TEXT #050607`, `BACKGROUND #FAF9F5`, `SURFACE #E9E9E6`) to buttons, inputs, focus states, links, and value highlights.

#### Scenario: User visits login page
- **WHEN** the user navigates to `/login`
- **THEN** the submit button renders in Verde Olivo `#73AC32` (hover `#44773C`), input focus rings use `#73AC32`, links use `#44773C`, and brand badges use `#A8C98A`

### Requirement: Olivos brand palette applied to Forgot Password view
The `/forgot-password` view SHALL apply the specified 6-color palette to all form controls, action buttons, status messages, and navigation links.

#### Scenario: User visits forgot-password page
- **WHEN** the user navigates to `/forgot-password`
- **THEN** the recovery form button renders in Verde Olivo `#73AC32` (hover `#44773C`), the back link uses `#44773C` (hover `#73AC32`), and the card borders use `#E9E9E6`

### Requirement: Harmonized 3D Auth Particles
The `AuthThreeCanvas.vue` 3D background on authentication pages SHALL emit particle nodes in Olivos tones `#73AC32`, `#44773C`, and `#A8C98A`.

#### Scenario: Rendering interactive 3D background on desktop
- **WHEN** the desktop brand panel renders
- **THEN** the Three.js particle constellation renders with Verde Olivo and Verde Salvia node colors
