## Design

### Approach

Modify `canUserDispatch` in Show.vue to check only for the Almacén role, removing Admin/Administrador/super_admin from the frontend condition. Update the backend `dispatchRequest` method to enforce the same restriction.

### Files Modified

- `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`
- `app/Http/Controllers/Admin/ConsumptionRequestController.php`

### Component Details

**Frontend — `canUserDispatch` computed property (line 26-36):**

Before:
```js
const hasRole = roles.includes('Almacén') || roles.includes('almacen') || 
                roles.includes('Admin') || roles.includes('admin') || 
                roles.includes('Administrador') || roles.includes('administrador') ||
                !!user.is_super_admin;
```

After:
```js
const hasRole = roles.includes('Almacén') || roles.includes('almacen');
```

This automatically hides all elements controlled by `canUserDispatch`:
- "Despachando" table header (line 696)
- Per-row dispatch inputs (line 844)
- Mobile dispatch section (line 1066)
- "Despachar Stock" button (line 1463)

**Backend — `dispatchRequest` method (line 269):**

Before:
```php
$canDispatch = $user ? ($user->hasRole(['Admin', 'admin', 'Administrador', 'administrador', 'Almacén', 'almacen']) || $user->is_super_admin) : false;
```

After:
```php
$canDispatch = $user ? $user->hasRole(['Almacén', 'almacen']) : false;
```

### Constraints

- `getRemainingStock` still uses `canUserDispatch` to compute display values — no change needed there since Admin won't see those elements
- Status badges (DISPONIBLE, SIN STOCK, etc.) are independent of dispatch role and remain visible to all
