<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';

// ── Notificaciones del navegador (inline) ────────────────────────────────────
let audioCtx = null;

const desbloquearAudio = () => {
    try {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        if (!audioCtx) {
            audioCtx = new Ctx();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
    } catch { /* silencioso */ }
};

// Registrar listeners globales para desbloquear el audio en móviles en el primer toque/click
if (typeof document !== 'undefined') {
    document.addEventListener('click', desbloquearAudio, { once: true });
    document.addEventListener('touchstart', desbloquearAudio, { once: true });
}

const _reproducirSonido = () => {
    try {
        if (!audioCtx) {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            audioCtx = new Ctx();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        const tocar = (f, t, d) => {
            const o = audioCtx.createOscillator(), g = audioCtx.createGain();
            o.connect(g); g.connect(audioCtx.destination);
            o.type = 'sine'; o.frequency.value = f;
            g.gain.setValueAtTime(0, t);
            g.gain.linearRampToValueAtTime(0.25, t + 0.01);
            g.gain.exponentialRampToValueAtTime(0.001, t + d);
            o.start(t); o.stop(t + d);
        };
        tocar(880,  audioCtx.currentTime,        0.18);
        tocar(1100, audioCtx.currentTime + 0.18, 0.35);
    } catch { /* silencioso */ }
};

const solicitarPermisoNotificaciones = async () => {
    if (!('Notification' in window)) return 'denied';
    if (Notification.permission === 'default') return await Notification.requestPermission();
    return Notification.permission;
};

const mostrarNotificacionBrowser = (titulo, cuerpo, url = null, conSonido = true) => {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    try {
        const n = new Notification(titulo, { body: cuerpo, icon: '/favicon.ico', lang: 'es' });
        if (url) n.onclick = () => { window.focus(); window.location.href = url; n.close(); };
        if (conSonido) _reproducirSonido();
        setTimeout(() => n.close(), 7000);
    } catch { /* silencioso */ }
};
// ─────────────────────────────────────────────────────────────────────────────

const page = usePage();
const notifications = computed(() => page.props.notifications?.latest || []);
const unreadCount = computed(() => page.props.notifications?.unreadCount || 0);

const open = ref(false);

const markAsRead = (id) => {
    router.post(route('admin.notifications.read', id), {}, {
        preserveScroll: true,
    });
};

const handleNotificationClick = (notification) => {
    if (notification.data?.consumption_request_id) {
        open.value = false;
        router.visit(route('admin.consumption-requests.show', { consumption_request: notification.data.consumption_request_id }));
    }
};

const markAllAsRead = () => {
    router.post(route('admin.notifications.read-all'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        }
    });
};

const getIcon = (type) => {
    switch (type) {
        case 'out_of_stock': return 'cancel';
        case 'low_stock': return 'warning';
        case 'purchase_received': return 'shopping_cart';
        case 'loss_warning': return 'bar_chart';
        case 'inventory_discrepancy': return 'settings';
        case 'transfer_discrepancy': return 'sync_alt';
        case 'new_consumption_request': return 'assignment';
        case 'consumption_request_dispatched': return 'local_shipping';
        case 'consumption_request_received': return 'check_circle';
        default: return 'info';
    }
};

const getTitle = (type) => {
    switch (type) {
        case 'out_of_stock': return 'PRODUCTO AGOTADO';
        case 'low_stock': return 'Alerta de Stock Mínimo';
        case 'purchase_received': return 'Nueva Compra';
        case 'loss_warning': return 'Alerta de Pérdida';
        case 'inventory_discrepancy': return 'Ajuste Stock';
        case 'transfer_discrepancy': return 'Mismatch Transferencia';
        case 'new_consumption_request': return 'Solicitud de Consumo';
        case 'consumption_request_dispatched': return 'Despacho de Consumo';
        case 'consumption_request_received': return 'Recepción de Consumo';
        default: return 'Notificación';
    }
};

const getTitleClass = (type) => {
    switch (type) {
        case 'out_of_stock': return 'text-rose-600 dark:text-rose-500';
        case 'purchase_received': return 'text-emerald-600 dark:text-emerald-500 uppercase text-[10px]';
        case 'loss_warning': return 'text-purple-600 dark:text-purple-500 uppercase text-[10px]';
        case 'inventory_discrepancy': return 'text-orange-600 dark:text-orange-500 uppercase text-[10px]';
        case 'transfer_discrepancy': return 'text-red-600 dark:text-red-500 uppercase text-[10px]';
        case 'new_consumption_request': return 'text-indigo-600 dark:text-indigo-500 uppercase text-[10px]';
        case 'consumption_request_dispatched': return 'text-emerald-600 dark:text-emerald-500 uppercase text-[10px]';
        case 'consumption_request_received': return 'text-blue-600 dark:text-blue-500 uppercase text-[10px]';
        default: return '';
    }
};

const getIconContainerClass = (type) => {
    let base = 'w-8 h-8 rounded-full flex items-center justify-center border ';
    switch (type) {
        case 'out_of_stock': return base + 'bg-rose-50 dark:bg-rose-500/10 text-rose-500 border-rose-100 dark:border-rose-500/20 animate-pulse';
        case 'low_stock': return base + 'bg-amber-50 dark:bg-amber-500/10 text-amber-500 border-amber-100 dark:border-amber-500/20';
        case 'purchase_received': return base + 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 border-emerald-100 dark:border-emerald-500/20';
        case 'loss_warning': return base + 'bg-purple-50 dark:bg-purple-500/10 text-purple-500 border-purple-100 dark:border-purple-500/20 animate-bounce';
        case 'inventory_discrepancy': return base + 'bg-orange-50 dark:bg-orange-500/10 text-orange-500 border-orange-100 dark:border-orange-500/20';
        case 'transfer_discrepancy': return base + 'bg-red-50 dark:bg-red-500/10 text-red-500 border-red-100 dark:border-red-500/20 animate-pulse';
        case 'new_consumption_request': return base + 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500 border-indigo-100 dark:border-indigo-500/20';
        case 'consumption_request_dispatched': return base + 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 border-emerald-100 dark:border-emerald-500/20';
        case 'consumption_request_received': return base + 'bg-blue-50 dark:bg-blue-500/10 text-blue-500 border-blue-100 dark:border-blue-500/20';
        default: return base + 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500 border-indigo-100 dark:border-indigo-500/20';
    }
};

onMounted(async () => {
    // Solicitar permiso de notificaciones del navegador al montar el layout
    await solicitarPermisoNotificaciones();

    if (window.Echo && page.props.auth?.user) {
        window.Echo.private(`notificaciones.${page.props.auth.user.id}`)
            .listen('.nueva.notificacion', (e) => {
                router.reload({ only: ['notifications'] });

                const urlDestino = e.tipo === 'new_consumption_request' || e.tipo === 'consumption_request_dispatched' || e.tipo === 'consumption_request_received'
                    ? route('admin.consumption-requests.index')
                    : null;

                mostrarNotificacionBrowser(
                    '🔔 Nueva notificación',
                    e.mensaje,
                    urlDestino,
                    true  // con sonido
                );
            });
    }
});
</script>

<template>
    <div class="relative">
        <!-- Bell Icon with Indicator -->
        <button @click="open = !open" 
                class="relative p-2 text-zinc-400 dark:text-zinc-500 hover:text-primary-600 dark:hover:text-primary-500 transition-all rounded-lg hover:bg-zinc-100 dark:hover:bg-secondary-700 focus:outline-none"
                :class="open ? 'bg-zinc-100 dark:bg-secondary-700 text-primary-600 dark:text-primary-500' : ''">
            <span class="material-symbols-outlined flex items-center justify-center">notifications</span>
            <span v-if="unreadCount > 0" class="absolute -top-0.5 -right-0.5 flex">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-5 min-w-[1.25rem] px-1 bg-red-500 text-[10px] items-center justify-center text-white font-black shadow-sm ring-2 ring-white dark:ring-zinc-900">
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </span>
        </button>

        <!-- Dropdown Panel -->
        <div v-if="open" 
             @click="open = false"
             class="fixed inset-0 z-[90]"></div>

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="open" 
                 class="fixed sm:absolute top-[68px] sm:top-auto left-4 right-4 sm:left-auto sm:right-0 mt-2 sm:w-96 bg-white dark:bg-secondary-800 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] border border-zinc-200 dark:border-secondary-700 overflow-hidden z-[100] origin-top">
                
                <div class="p-3 sm:p-4 border-b border-zinc-100 dark:border-secondary-700 flex items-center justify-between bg-zinc-50/50 dark:bg-secondary-900/30">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Notificaciones</h3>
                    <button v-if="unreadCount > 0" 
                            @click="markAllAsRead" 
                            class="text-[10px] sm:text-[11px] font-semibold text-primary-600 dark:text-primary-500 hover:text-primary-800 dark:hover:text-primary-400 transition-colors uppercase tracking-wider">
                        Marcar todo como leído
                    </button>
                </div>

                <div class="max-h-[350px] overflow-y-auto overflow-x-hidden custom-scrollbar">
                    <template v-if="notifications.length > 0">
                        <div v-for="notification in notifications" 
                             :key="notification.id" 
                             @click="handleNotificationClick(notification)"
                             class="px-4 py-3 hover:bg-zinc-50 dark:hover:bg-secondary-700/50 transition-colors border-b border-zinc-50 dark:border-secondary-700/50 last:border-0 relative group"
                             :class="notification.data.consumption_request_id ? 'cursor-pointer' : ''">
                             <div class="flex gap-3">
                                 <div class="flex-shrink-0 mt-1">
                                     <div :class="getIconContainerClass(notification.data.type)">
                                         <span class="material-symbols-outlined text-[16px]">{{ getIcon(notification.data.type) }}</span>
                                     </div>
                                 </div>
                                 <div class="flex-1 min-w-0">
                                     <p class="text-xs font-bold text-zinc-900 dark:text-zinc-50 leading-tight" 
                                        :class="getTitleClass(notification.data.type)">
                                         {{ getTitle(notification.data.type) }}
                                     </p>
                                     <p class="text-[11px] text-zinc-600 dark:text-zinc-400 mt-1 leading-relaxed">
                                         {{ notification.data.message }}
                                     </p>

                                     <div v-if="notification.data.consumption_request_id" class="mt-1.5 flex items-center text-[10px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline gap-0.5">
                                         <span class="material-symbols-outlined text-[11px]">open_in_new</span>
                                         Ver Solicitud
                                     </div>

                                     <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1.5 flex items-center gap-1">
                                         <span class="material-symbols-outlined text-xs">schedule</span>
                                         {{ notification.created_at }}
                                     </p>
                                 </div>
                                <button @click.stop="markAsRead(notification.id)" 
                                        class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-zinc-300 dark:text-secondary-600 hover:text-primary-600 dark:hover:text-primary-500"
                                        title="Marcar como leído">
                                    <span class="material-symbols-outlined text-[18px]">check</span>
                                </button>
                            </div>
                        </div>
                    </template>
                    <div v-else class="py-12 px-4 text-center">
                        <div class="w-12 h-12 bg-zinc-50 dark:bg-secondary-900/50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="material-symbols-outlined text-zinc-300 dark:text-secondary-700 text-2xl">notifications_off</span>
                        </div>
                        <p class="text-xs font-medium text-zinc-400 dark:text-zinc-500">No tienes notificaciones pendientes</p>
                    </div>
                </div>

                <div class="p-2 bg-zinc-50 dark:bg-secondary-900/30 border-t border-zinc-100 dark:border-secondary-700 text-center">
                    <Link :href="route('admin.notifications.index')" 
                          class="text-[11px] font-bold text-zinc-500 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-500 transition-colors py-1 block">
                        Ver historial completo
                    </Link>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>
