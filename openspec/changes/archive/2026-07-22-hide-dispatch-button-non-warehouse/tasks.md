## Tasks

- [x] Restrict `canUserDispatch` in Show.vue to Almacén role only: remove Admin, Administrador, and super_admin checks from the `hasRole` condition
- [x] Restrict backend `dispatchRequest` authorization to Almacén role only: update the `$canDispatch` check in ConsumptionRequestController.php
