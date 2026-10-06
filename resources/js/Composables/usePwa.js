import { ref, computed } from 'vue';

// Estado global Singleton para compartir entre componentes
const deferredPrompt = ref(null);
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const isStandalone = ref(false);
const isRegistered = ref(false);
const hasUpdate = ref(false);

const checkStandalone = () => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(display-mode: standalone)').matches ||
           window.navigator.standalone === true ||
           document.referrer.includes('android-app://');
};

if (typeof window !== 'undefined') {
    isStandalone.value = checkStandalone();

    // 1. Escuchar cambios de conectividad
    window.addEventListener('online', () => {
        isOnline.value = true;
    });

    window.addEventListener('offline', () => {
        isOnline.value = false;
    });

    // 2. Capturar evento de instalación de navegador
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt.value = e;
    });

    // 3. Cuando la app ya fue instalada
    window.addEventListener('appinstalled', () => {
        deferredPrompt.value = null;
        isStandalone.value = true;
    });

    // 4. Cambios en el modo de visualización
    window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
        isStandalone.value = e.matches;
    });
}

export function usePwa() {
    const canInstall = computed(() => {
        return !!deferredPrompt.value && !isStandalone.value;
    });

    const installApp = async () => {
        if (!deferredPrompt.value) return false;

        try {
            deferredPrompt.value.prompt();
            const { outcome } = await deferredPrompt.value.userChoice;
            deferredPrompt.value = null;
            return outcome === 'accepted';
        } catch (error) {
            console.error('Error al solicitar instalación PWA:', error);
            return false;
        }
    };

    const registerServiceWorker = async () => {
        if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
            return;
        }

        if (isRegistered.value) return;

        window.addEventListener('load', async () => {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js', {
                    scope: '/'
                });

                isRegistered.value = true;

                // Detectar si hay actualización pendiente
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                hasUpdate.value = true;
                            }
                        });
                    }
                });
            } catch (error) {
                console.warn('Registro de Service Worker falló:', error);
            }
        });
    };

    return {
        isOnline,
        canInstall,
        isStandalone,
        hasUpdate,
        installApp,
        registerServiceWorker
    };
}
