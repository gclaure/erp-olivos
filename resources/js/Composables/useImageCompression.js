import imageCompression from 'browser-image-compression';

export function useImageCompression() {
    const compressAndConvertToWebP = async (file, options = {}) => {
        const defaultOptions = {
            maxSizeMB: 0.8,
            maxWidthOrHeight: 1280,
            useWebWorker: true,
            fileType: options.fileType || 'image/webp',
            initialQuality: 0.8
        };

        const finalOptions = { ...defaultOptions, ...options };

        try {
            const compressedBlob = await imageCompression(file, finalOptions);
            
            // Determinar extensión basada en el tipo de salida
            const extension = finalOptions.fileType.split('/')[1];
            const fileName = file.name.replace(/\.[^/.]+$/, "") + "." + extension;
            return new File([compressedBlob], fileName, {
                type: finalOptions.fileType,
                lastModified: Date.now()
            });
        } catch (error) {
            console.error('Error en compresión:', error);
            throw error;
        }
    };

    return {
        compressAndConvertToWebP
    };
}
