<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    records: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const date_from = ref(props.filters.date_from || '');
const date_to = ref(props.filters.date_to || '');

const updateFilters = debounce(() => {
    router.get(route('admin.purchase-orders.index'), {
        search: search.value,
        status: status.value,
        date_from: date_from.value,
        date_to: date_to.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, status, date_from, date_to], () => {
    updateFilters();
});

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    date_from.value = '';
    date_to.value = '';
};

</script>

<template>
    <Head title="Solicitudes de Compra" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Solicitudes de Compra</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Requisiciones internas y órdenes pendientes de aprobación</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <Link :href="route('admin.purchase-orders.create')" 
                      class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-lg">add_circle</span> Crear Solicitud
                </Link>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 shadow-sm mb-6 overflow-hidden">
            <div class="p-4 flex flex-col lg:flex-row gap-4 items-center">
                <!-- Buscador -->
                <div class="flex-1 w-full relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors text-xl">search</span>
                    </div>
                    <input v-model="search" type="text" 
                        placeholder="Buscar por proveedor o notas..." 
                        class="w-full pl-11 pr-4 py-2.5 bg-zinc-50 dark:bg-gray-600 border border-zinc-200 dark:border-gray-500 rounded-xl text-sm dark:text-white focus:bg-white dark:focus:bg-gray-500 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
                
                <!-- Otros Filtros -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full lg:w-auto">
                    <!-- Estado -->
                    <select v-model="status"
                            class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-gray-600 border border-zinc-200 dark:border-gray-500 rounded-xl text-sm dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium">
                        <option value="">Todos los Estados</option>
                        <option value="pendiente">⏳ Pendiente</option>
                        <option value="aprobada">👍 Aprobada por Admin</option>
                        <option value="completada">✅ Completada / Comprada</option>
                        <option value="cancelada">❌ Rechazada</option>
                    </select>

                    <!-- Fecha Desde -->
                    <input v-model="date_from" type="date"
                           class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-gray-600 border border-zinc-200 dark:border-gray-500 rounded-xl text-sm dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium"
                           placeholder="Desde">

                    <!-- Fecha Hasta -->
                    <input v-model="date_to" type="date"
                           class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-gray-600 border border-zinc-200 dark:border-gray-500 rounded-xl text-sm dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium"
                           placeholder="Hasta">
                </div>

                <!-- Botón de Limpiar -->
                <button v-if="search || status || date_from || date_to" @click="clearFilters"
                        class="w-full lg:w-auto px-4 py-2.5 border border-zinc-200 dark:border-gray-500 rounded-xl bg-zinc-50 dark:bg-gray-600/50 hover:bg-zinc-100 dark:hover:bg-gray-600 transition-colors text-zinc-600 dark:text-zinc-300 font-bold text-sm">
                    Limpiar
                </button>
            </div>
        </div>

        <!-- Tabla / Listado -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden shadow-sm">
            <!-- Vista Desktop -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                        <tr class="text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <th class="px-6 py-4">#</th>
                            <th class="px-6 py-4">Fecha</th>
                            <th class="px-6 py-4">Proveedor</th>
                            <th class="px-6 py-4">Almacén Destino</th>
                            <th class="px-6 py-4">Usuario Solicitante</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Total Est.</th>
                            <th class="px-6 py-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                        <tr v-for="(order, i) in records.data" :key="order.id"
                            class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors group">
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                {{ records.meta.from + i }}
                            </td>
                            <td class="px-6 py-4 text-zinc-900 dark:text-white capitalize">
                                {{ order.date_formatted }}
                            </td>
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                                {{ order.provider?.name || '—' }}
                            </td>
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                {{ order.warehouse?.name || '—' }}
                            </td>
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                {{ order.user?.name || '—' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="order.status === 'cancelada'"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400 border border-rose-200 dark:border-rose-800/30">
                                    Rechazada
                                </span>
                                <span v-else-if="order.status === 'pendiente'"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 border border-amber-200 dark:border-amber-800/30 animate-pulse">
                                    Pendiente
                                </span>
                                <span v-else-if="order.status === 'aprobada'"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-sky-100 dark:bg-sky-900/30 text-sky-800 dark:text-sky-400 border border-sky-200 dark:border-sky-800/30">
                                    Aprobada
                                </span>
                                <span v-else-if="order.status === 'completada'"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/30">
                                    Completada
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-zinc-900 dark:text-white">
                                Bs. {{ formatNumber(order.total) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <Link :href="route('admin.purchase-orders.show', order.id)"
                                          class="w-9 h-9 flex items-center justify-center text-blue-600 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-600 hover:text-white border border-blue-200 dark:border-blue-800 rounded-lg transition-all shadow-sm"
                                          title="Ver Detalle">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="records.data.length === 0">
                            <td colspan="8" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center opacity-40">
                                    <span class="material-symbols-outlined text-6xl mb-4 text-zinc-300 dark:text-zinc-600">assignment_turned_in</span>
                                    <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-sm">Sin solicitudes registradas</p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-2">Las solicitudes aparecerán aquí cuando se registren</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Vista Móvil (Cards) -->
            <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-gray-700">
                <div v-for="(order, i) in records.data" :key="order.id" 
                     class="p-4 space-y-4 bg-white dark:bg-gray-800/50">
                    
                    <!-- Header Info -->
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-zinc-900 dark:text-white capitalize">{{ order.date_formatted }}</span>
                            <span class="text-[10px] text-zinc-400 uppercase tracking-widest mt-0.5">Por: {{ order.user?.name || '—' }}</span>
                        </div>
                        <span v-if="order.status === 'cancelada'"
                              class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/20">
                            Rechazada
                        </span>
                        <span v-else-if="order.status === 'pendiente'"
                              class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-800/20">
                            Pendiente
                        </span>
                        <span v-else-if="order.status === 'aprobada'"
                              class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 border border-sky-100 dark:border-sky-800/20">
                            Aprobada
                        </span>
                        <span v-else-if="order.status === 'completada'"
                              class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                            Completada
                        </span>
                    </div>

                    <!-- Details Grid -->
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Proveedor</span>
                            <span class="text-xs font-bold text-zinc-900 dark:text-white">{{ order.provider?.name || '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Almacén Destino</span>
                            <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ order.warehouse?.name || '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Total Est.</span>
                            <span class="text-sm font-black text-zinc-900 dark:text-white">Bs. {{ formatNumber(order.total) }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end items-center gap-3 pt-2">
                        <Link :href="route('admin.purchase-orders.show', order.id)"
                              class="w-full py-2 flex items-center justify-center gap-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 active:scale-95 transition-all border border-indigo-100 dark:border-indigo-800/20 font-bold text-xs">
                            <span class="material-symbols-outlined text-base">visibility</span> Ver Detalles
                        </Link>
                    </div>
                </div>

                <!-- Empty State Móvil -->
                <div v-if="records.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800">
                    <div class="flex flex-col items-center opacity-40 py-10">
                        <span class="material-symbols-outlined text-4xl mb-4 text-zinc-300 dark:text-zinc-600">assignment_turned_in</span>
                        <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-sm">Sin solicitudes registradas</p>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="records.meta && records.meta.links" class="px-6 py-6 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50/50 dark:bg-gray-600 flex justify-center">
                <nav class="flex gap-1.5">
                    <template v-for="(link, i) in records.meta.links" :key="i">
                        <button v-if="link.url"
                                @click="router.get(link.url, { search, status, date_from, date_to }, { preserveState: true })"
                                v-html="link.label"
                                :class="[
                                    'px-4 py-2 text-[11px] font-black rounded-xl transition-all duration-200',
                                    link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30 scale-110' : 
                                    'bg-white dark:bg-gray-700 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-gray-600 shadow-sm border border-zinc-200 dark:border-gray-600'
                                ]"
                        />
                        <span v-else v-html="link.label" class="px-4 py-2 text-[11px] font-black text-zinc-300 dark:text-zinc-800" />
                    </template>
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
