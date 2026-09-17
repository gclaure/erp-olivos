## 1. Modificación de Redirección en Backend

- [x] 1.1 Actualizar la redirección de `ConsumptionRequestController::store` para apuntar a la ruta `admin.consumption-requests.create` preservando los datos de sesión flash (`success` y `success_data.id`)

## 2. Verificación y Flujo Frontend

- [x] 2.1 Verificar que al enviar el formulario desde `/admin/consumption-requests/create`, la respuesta mantenga al usuario en la misma vista, limpie el carrito reactivo y dispare la apertura de impresión del PDF
