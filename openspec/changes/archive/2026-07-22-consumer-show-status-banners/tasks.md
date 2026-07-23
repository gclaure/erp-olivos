## 1. Banners Consumidor

- [x] 1.1 Cambiar banner `pendiente` + Consumidor a copy de pendiente de aprobación del Administrador
- [x] 1.2 Agregar banner `aprobado` + Consumidor con copy de espera de despacho (solicitud aprobada)

## 2. Panel de acciones Consumidor

- [x] 2.1 Ocultar título "Acciones de Almacén" cuando `isConsumidorRole`
- [x] 2.2 Mostrar título "Mis Acciones" para Consumidor cuando haya acciones visibles
- [x] 2.3 Cancelar: Consumidor solo si `status === 'pendiente'`; otros roles mantienen regla actual
- [x] 2.4 Evitar panel vacío (wrapper condicionado a contenido real por rol)

## 3. Alerta de faltantes

- [x] 3.1 Añadir `!isConsumidorRole` al banner de insumos faltantes

## 4. Verificación

- [x] 4.1 Confirmar que Recepcionar sigue visible en despachado/despachado_parcial para Consumidor
- [x] 4.2 Validar change con openspec validate
