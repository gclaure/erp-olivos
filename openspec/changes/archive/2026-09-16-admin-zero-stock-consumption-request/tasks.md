## 1. Frontend Interaction and Validation for Admin Role

- [x] 1.1 Add `isAdmin` computed property and enable card interaction with unbounded `maxQty` in `ProductCard.vue`
- [x] 1.2 Enable zero-stock item addition in `ProductDetailModal.vue` for Admins while keeping stock details visible
- [x] 1.3 Update `CartSidebar.vue` to keep submit button enabled for Admins while retaining informative stock shortage warnings

## 2. Backend Validation and Persistence

- [x] 2.1 Update `SaveConsumptionRequest.php` `withValidator` logic to permit zero/exceeded stock consumption for Admin and Super Admin roles

## 3. Verification and Asset Build

- [x] 3.1 Rebuild frontend assets with `npm run build` and ensure zero compilation errors
- [x] 3.2 Verify role-based behavior: Admin sees visible stock counts and can submit with 0 stock, Consumidor sees abstract "Disponible", and non-consumption POS rules remain intact
