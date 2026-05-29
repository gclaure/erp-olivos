<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    notifications: Object,
    filters: Object
});

const filter = ref(props.filters.filter || 'all');

watch(filter, (newFilter) => {
    router.get(route('admin.notifications.index'), { filter: newFilter }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
});

const markAsRead = (id) => {
    router.post(route('admin.notifications.read', id), {}, {
        preserveScroll: true
    });
};

const markAllAsRead = () => {
    router.post(route('admin.notifications.read-all'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                title: 'Éxito',
                text: 'Todas las notificaciones han sido marcadas como leídas.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#000000',
            });
        }
    });
};

const deleteNotification = (id) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción eliminará la notificación de forma permanente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#f43f5e',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#000000',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.notifications.destroy', id), {
                preserveScroll: true
            });
        }
    });
};

const deleteAllNotifications = () => {
    Swal.fire({
        title: '¿Eliminar todas?',
        text: "Esta acción eliminará permanentemente todas tus notificaciones.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#4f46e5',
        confirmButtonText: 'Sí, eliminar todo',
        cancelButtonText: 'Cancelar',
        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#000000',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.notifications.destroy-all'), {
                preserveScroll: true
            });
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
        default: return 'notifications';
    }
};

const getTitle = (type) => {
    switch (type) {
        case 'out_of_stock': return 'PRODUCTO AGOTADO';
        case 'low_stock': return 'Alerta de Stock Mínimo';
        case 'purchase_received': return 'Nueva Compra Recibida';
        case 'loss_warning': return 'ALERTA DE PÉRDIDA';
        case 'inventory_discrepancy': return 'Ajuste de Inventario';
        case 'transfer_discrepancy': return 'DISCREPANCIA TRANSFERENCIA';
        default: return 'Mensaje del Sistema';
    }
};

const getTitleClass = (type) => {
    switch (type) {
        case 'out_of_stock': return 'text-rose-600 dark:text-rose-500';
        case 'purchase_received': return 'text-emerald-600 dark:text-emerald-500';
        case 'loss_warning': return 'text-purple-600 dark:text-purple-500 font-black';
        case 'inventory_discrepancy': return 'text-orange-600 dark:text-orange-500';
        case 'transfer_discrepancy': return 'text-red-600 dark:text-red-500 font-bold';
        default: return '';
    }
};

const getIconContainerClass = (type) => {
    let base = 'w-10 h-10 rounded-xl flex items-center justify-center border ';
    switch (type) {
        case 'out_of_stock': return base + 'bg-rose-100 dark:bg-rose-500/10 text-rose-600 dark:text-rose-500 border-rose-200 dark:border-rose-500/20 animate-pulse';
        case 'low_stock': return base + 'bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500 border-amber-200 dark:border-amber-500/20';
        case 'purchase_received': return base + 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 border-emerald-200 dark:border-emerald-500/20';
        case 'loss_warning': return base + 'bg-purple-100 dark:bg-purple-500/10 text-purple-600 dark:text-purple-500 border-purple-200 dark:border-purple-500/20';
        case 'inventory_discrepancy': return base + 'bg-orange-100 dark:bg-orange-500/10 text-orange-600 dark:text-orange-500 border-orange-200 dark:border-orange-500/20';
        case 'transfer_discrepancy': return base + 'bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-500 border-red-200 dark:border-red-500/20 animate-pulse';
        default: return base + 'bg-indigo-100 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-500 border-indigo-200 dark:border-indigo-500/20';
    }
};
</script>

<template>
    <Head title="Historial de Notificaciones" />

    <AdminLayout>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white tracking-tight">Historial de Notificaciones</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Revisa todas tus alertas y mensajes del sistema</p>
            </div>
            <div class="flex items-center gap-2">
                <button v-if="notifications.data.length > 0"
                        @click="deleteAllNotifications"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-rose-50 dark:bg-rose-500/10 border border-rose-100 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 text-sm font-semibold rounded-lg hover:bg-rose-100 dark:hover:bg-rose-500/20 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-lg">delete_sweep</span>
                    Eliminar todo
                </button>
                <button v-if="notifications.data.some(n => !n.read_at)"
                        @click="markAllAsRead"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 text-zinc-700 dark:text-secondary-200 text-sm font-semibold rounded-lg hover:bg-zinc-50 dark:hover:bg-secondary-700 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-primary-500 dark:text-primary-400 text-lg">check_circle</span>
                    Marcar todo como leído
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex items-center gap-2 mb-6 p-1 bg-zinc-200/50 dark:bg-secondary-900/50 rounded-xl w-fit">
            <button @click="filter = 'all'" 
                    class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all"
                    :class="filter === 'all' ? 'bg-white dark:bg-secondary-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 dark:text-secondary-400 hover:text-zinc-700 dark:hover:text-secondary-200'">
                Todas
            </button>
            <button @click="filter = 'unread'" 
                    class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all"
                    :class="filter === 'unread' ? 'bg-white dark:bg-secondary-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 dark:text-secondary-400 hover:text-zinc-700 dark:hover:text-secondary-200'">
                No leídas
            </button>
            <button @click="filter = 'read'" 
                    class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all"
                    :class="filter === 'read' ? 'bg-white dark:bg-secondary-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 dark:text-secondary-400 hover:text-zinc-700 dark:hover:text-secondary-200'">
                Leídas
            </button>
        </div>

        <div class="bg-white dark:bg-secondary-800 rounded-2xl border border-zinc-200 dark:border-secondary-700 overflow-hidden shadow-sm mb-8">
            <div class="divide-y divide-zinc-100 dark:divide-secondary-700/50">
                <div v-for="notification in notifications.data" 
                     :key="notification.id" 
                     class="p-4 sm:p-6 hover:bg-indigo-50/40 dark:hover:bg-secondary-700/50 transition-colors relative group"
                     :class="!notification.read_at ? 'bg-secondary-50/80 dark:bg-secondary-900/40' : ''">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 mt-1">
                            <div :class="getIconContainerClass(notification.data.type)">
                                <span class="material-symbols-outlined text-lg">{{ getIcon(notification.data.type) }}</span>
                            </div>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center">
                                    <span :class="getTitleClass(notification.data.type)">
                                        {{ getTitle(notification.data.type) }}
                                    </span>
                                    <span v-if="!notification.read_at" class="inline-block w-2 h-2 rounded-full bg-primary-500 ml-2"></span>
                                </h3>
                                <div class="flex items-center gap-2">
                                    <button v-if="!notification.read_at" 
                                            @click="markAsRead(notification.id)" 
                                            class="p-2 text-zinc-400 dark:text-secondary-500 hover:text-primary-600 dark:hover:text-primary-500 hover:bg-white dark:hover:bg-secondary-700 rounded-lg border border-transparent hover:border-zinc-200 dark:hover:border-secondary-600 transition-all"
                                            title="Marcar como leído">
                                        <span class="material-symbols-outlined text-lg">check</span>
                                    </button>
                                    <button @click="deleteNotification(notification.id)" 
                                            class="p-2 text-zinc-400 dark:text-secondary-500 hover:text-red-600 dark:hover:text-red-500 hover:bg-white dark:hover:bg-secondary-700 rounded-lg border border-transparent hover:border-zinc-200 dark:hover:border-secondary-600 transition-all"
                                            title="Eliminar">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </div>
                            </div>
                            
                            <p class="text-sm text-zinc-600 dark:text-secondary-400 mt-1 leading-relaxed max-w-2xl">
                                {{ notification.data.message || 'Sin mensaje' }}
                            </p>
                            
                            <div class="flex items-center gap-4 mt-3">
                                <span class="text-xs text-zinc-400 dark:text-secondary-500 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm">schedule</span>
                                    {{ notification.created_at }}
                                </span>
                                <span v-if="notification.read_at" class="text-xs text-zinc-400 dark:text-secondary-500 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm text-emerald-500 dark:text-emerald-400">check_circle</span>
                                    Leído
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="notifications.data.length === 0" class="py-20 px-4 text-center">
                    <div class="w-16 h-16 bg-zinc-50 dark:bg-secondary-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-3xl text-zinc-300 dark:text-secondary-700">notifications_off</span>
                    </div>
                    <h3 class="text-zinc-900 dark:text-white font-bold">No hay notificaciones</h3>
                    <p class="text-sm text-zinc-500 dark:text-secondary-400 mt-1">Cuando ocurra algo importante, lo verás aquí.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.links && notifications.links.length > 3" class="px-6 py-5 border-t border-zinc-100 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900/30 flex justify-center">
                <nav class="flex gap-1">
                    <Link v-for="(link, i) in notifications.links" :key="i"
                          :href="link.url || '#'"
                          v-html="link.label"
                          :class="[
                              'px-4 py-2 text-sm font-bold rounded-xl transition-all',
                              link.active ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/20' : 
                              link.url ? 'bg-white dark:bg-secondary-800 text-zinc-500 dark:text-secondary-400 hover:bg-zinc-100 dark:hover:bg-secondary-700' : 'text-zinc-300 dark:text-secondary-600 cursor-not-allowed'
                          ]"
                    />
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>
