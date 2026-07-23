---
name: optimizacion-imagenes-webp
description: Estándar para la compresión y conversión de imágenes a WebP en el frontend y backend, garantizando alto rendimiento y baja latencia sin pérdida perceptible de calidad, con excepciones para logotipos.
---

# Optimización de Imágenes y Conversión a WebP

Esta habilidad establece el estándar para el manejo de archivos multimedia en el proyecto, asegurando que todas las imágenes (especialmente banners y fotos de productos) sean optimizadas antes de su almacenamiento.

## When to use this skill
- Al implementar nuevas funcionalidades de carga de archivos (uploads).
- Al modificar formularios que incluyan imágenes de productos, banners, o galerías.
- Cuando se detecten problemas de rendimiento o errores de tipo `PostTooLargeException` por imágenes pesadas.
- Al realizar auditorías de performance (Lighthouse) para mejorar los tiempos de carga de la tienda pública.

## How to use it

### 1. Optimización en el Frontend (Obligatorio)
Para evitar que el servidor reciba archivos pesados y prevenir errores de límite de POST, se debe procesar la imagen en el cliente antes de enviarla mediante Inertia.

**Uso del Composable `useImageCompression`:**
Importar y usar el composable en componentes Vue 3:

```javascript
import { useImageCompression } from '@/Composables/useImageCompression';

const { compressAndConvertToWebP } = useImageCompression();

const handleFileUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    try {
        const optimizedFile = await compressAndConvertToWebP(file, {
            maxSizeMB: 0.8,
            maxWidthOrHeight: 1280
        });
        form.image = optimizedFile;
    } catch (error) {
        console.error('Error al optimizar:', error);
    }
};
```

### 2. Configuración por Tipo de Imagen
- **Banners/Hero**: Máximo 1MB, ancho máximo 1920px.
- **Productos**: Máximo 0.8MB, ancho máximo 1280px.
- **Logotipos (Excepción)**:
    - NO forzar a WebP si se requiere fidelidad absoluta en PNG transparente.
    - Mantener formato original o usar compresión mínima (maxSizeMB: 0.5).
    - El backend debe respetar el formato si es un logo.

### 3. Procesamiento en el Backend (Laravel)
El `EcommerceService` o servicios similares deben asegurar la conversión final y el almacenamiento eficiente.

```php
// Ejemplo en Service
private function processImage(UploadedFile $file, string $directory): string
{
    $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
    
    // ... lógica de conversión a WebP con GD o Imagick ...
    
    imagewebp($image, $path, 80); // Calidad 80 para balance óptimo
}
```

### 4. Reglas Estrictas
- **Mobile-first**: Las imágenes optimizadas cargan más rápido en dispositivos móviles.
- **Sin placeholders**: Siempre usar imágenes reales u optimizadas.
- **Nomenclatura**: Renombrar archivos a nombres descriptivos o UUIDs únicos para evitar colisiones.
- **Casts de Modelos**: Asegurar que las URLs de las imágenes estén correctamente mapeadas en los Resources de Inertia.

## Referencias
- Ver `resources/js/Composables/useImageCompression.js` para la implementación técnica.
- Ver `app/Services/EcommerceService.php` para el procesamiento en el servidor.
