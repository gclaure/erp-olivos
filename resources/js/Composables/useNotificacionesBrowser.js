/**
 * Composable para notificaciones nativas del navegador + sonido.
 * Usa la Web Notifications API y la Web Audio API.
 * Compatible con Chrome, Edge, Firefox y Safari.
 */

/**
 * Reproduce un sonido tipo "chime" usando Web Audio API.
 * No requiere ningún archivo de audio externo.
 */
export const reproducirSonidoNotificacion = () => {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;

        const ctx = new AudioContext();

        const tocar = (frecuencia, inicio, duracion, volumen = 0.25) => {
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.value = frecuencia;
            gain.gain.setValueAtTime(0, inicio);
            gain.gain.linearRampToValueAtTime(volumen, inicio + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.001, inicio + duracion);
            osc.start(inicio);
            osc.stop(inicio + duracion);
        };

        const t = ctx.currentTime;
        tocar(880,  t,        0.18);  // A5 — primera nota
        tocar(1100, t + 0.18, 0.35);  // C#6 — segunda nota más larga
    } catch {
        // Silenciosamente ignora si el navegador no soporta Web Audio
    }
};

/**
 * Solicita el permiso de notificaciones del navegador.
 * Debe llamarse en onMounted, nunca dentro de un callback de socket.
 */
export const solicitarPermisoNotificaciones = async () => {
    if (!('Notification' in window)) {
        console.warn('[Notif] Este navegador no soporta la Web Notifications API.');
        return 'denied';
    }

    if (Notification.permission === 'default') {
        const resultado = await Notification.requestPermission();
        console.info(`[Notif] Permiso solicitado → ${resultado}`);
        return resultado;
    }

    console.info(`[Notif] Estado del permiso → ${Notification.permission}`);
    return Notification.permission;
};

/**
 * Muestra una notificación nativa del navegador con sonido opcional.
 * Solo funciona si el permiso ya fue concedido.
 *
 * @param {string}      titulo
 * @param {string}      cuerpo
 * @param {string|null} url        - URL a abrir al hacer clic (opcional)
 * @param {boolean}     conSonido  - reproducir sonido (default: true)
 */
export const mostrarNotificacionBrowser = (titulo, cuerpo, url = null, conSonido = true) => {
    if (!('Notification' in window)) {
        console.warn('[Notif] Notifications API no disponible.');
        return;
    }

    if (Notification.permission !== 'granted') {
        console.warn(`[Notif] No se puede mostrar notificación: permiso = "${Notification.permission}".`);
        return;
    }

    try {
        const notif = new Notification(titulo, {
            body: cuerpo,
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            lang: 'es',
            requireInteraction: false,
        });

        if (url) {
            notif.onclick = () => {
                window.focus();
                window.location.href = url;
                notif.close();
            };
        }

        if (conSonido) {
            reproducirSonidoNotificacion();
        }

        setTimeout(() => notif.close(), 7000);
    } catch (err) {
        console.error('[Notif] Error al crear la notificación:', err);
    }
};

// ─── Badge en pestaña del navegador ──────────────────────────────────────────

let _tituloOriginal = null;

/**
 * Actualiza el título de la pestaña con un badge de contador.
 * Llamar con count = 0 para limpiar el badge.
 *
 * @param {number} count - cantidad de nuevas notificaciones
 */
export const actualizarBadgeTab = (count) => {
    if (_tituloOriginal === null) {
        // Guardar el título original sin badge previo
        _tituloOriginal = document.title.replace(/^\(\d+\)\s/, '');
    }

    document.title = count > 0
        ? `(${count}) ${_tituloOriginal}`
        : _tituloOriginal;
};

// ─── Badge en el favicon del navegador ───────────────────────────────────────

let _faviconOriginalHref = null;

/**
 * Dibuja un punto rojo sobre el favicon para indicar notificaciones.
 * Llamar con count = 0 para restaurar el favicon original.
 *
 * @param {number} count - cantidad de nuevas notificaciones (0 para limpiar)
 */
export const actualizarFaviconBadge = (count) => {
    // Localizar el elemento <link rel="icon"> existente
    let link = document.querySelector("link[rel~='icon']");
    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
    }

    // Guardar el href original la primera vez
    if (_faviconOriginalHref === null) {
        _faviconOriginalHref = link.href || '/favicon.ico';
    }

    if (count === 0) {
        link.href = _faviconOriginalHref;
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width  = 32;
    canvas.height = 32;
    const ctx = canvas.getContext('2d');

    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.src = _faviconOriginalHref;

    img.onload = () => {
        // Dibujar favicon original
        ctx.drawImage(img, 0, 0, 32, 32);

        // Punto rojo en esquina superior derecha
        ctx.beginPath();
        ctx.arc(24, 8, 8, 0, 2 * Math.PI);
        ctx.fillStyle = '#ef4444';
        ctx.fill();

        // Número dentro del punto
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 9px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(count > 9 ? '9+' : String(count), 24, 8);

        link.href = canvas.toDataURL('image/png');
    };

    img.onerror = () => {
        // Si no carga el favicon, solo dibujar el punto rojo
        ctx.beginPath();
        ctx.arc(24, 8, 8, 0, 2 * Math.PI);
        ctx.fillStyle = '#ef4444';
        ctx.fill();
        link.href = canvas.toDataURL('image/png');
    };
};
