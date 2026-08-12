## 1. Activos

- [x] 1.1 Agregar `public/img/logo-dark.png` (arte claro, para fondo oscuro) y `public/img/logo-light.png` (arte oscuro, para fondo claro) *(movidos desde la raíz del repo)*
- [x] 1.2 Verificar que `logo-inventory.png`/`.jpg` se conservan (los usan blades/PDFs)

## 2. Superficies

- [x] 2.1 En `resources/js/Layouts/AdminLayout.vue` (línea 137), reemplazar `:src="company?.logo_url || '/img/logo-inventory.png'"` por `src="/img/logo-dark.png"`
- [x] 2.2 En `resources/js/Pages/Auth/Login.vue` (línea 71), reemplazar `:src="company?.logo_url || '/img/logo-inventory.png'"` por `src="/img/logo-light.png"`
- [x] 2.3 En `resources/js/Pages/Admin/Settings/Company.vue` (líneas 98-100), reemplazar el bloque `v-if="form.logo"` / `v-else-if="company?.logo_url"` / `v-else` por un único `<img src="/img/logo-light.png">`

## 3. Verificación

- [x] 3.1 Compilar sin errores (`npm run build`)
- [x] 3.2 Verificar en modo claro y oscuro: la sidebar muestra el logo claro (`logo-dark.png`) sobre `bg-zinc-900` *(verificado por píxeles opacos lum 205 + src en bundle AdminLayout)*
- [x] 3.3 Verificar que login y preview de Settings muestran el logo oscuro (`logo-light.png`) sobre fondo blanco *(verificado por píxeles opacos lum 45 + src en bundle Company/Login)*
- [x] 3.4 Verificar que el logo de empresa subido no aparece en sidebar, login ni preview de Settings *(confirmado en bundle: sin `logo_url` en esas 3 superficies)*
- [x] 3.5 Verificar que storefront, blades de auth y PDFs/recibos siguen mostrando sus logos sin cambios *(confirmado: `logo-inventory` en 5 blades, `PublicLayout` intacto)*
