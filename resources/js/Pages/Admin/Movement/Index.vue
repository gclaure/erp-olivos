<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import axios from 'axios';

const props = defineProps({
    movements: Object,
    warehouses: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const warehouseId = ref(props.filters.warehouse_id || '');

const showDetailModal = ref(false);
const selectedMovement = ref(null);
const isLoadingDetail = ref(false);

const updateFilters = debounce(() => {
    router.get(route('admin.movements.index'), {
        search: search.value,
        warehouse_id: warehouseId.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, warehouseId], () => {
    updateFilters();
});

const openDetail = async (id) => {
    isLoadingDetail.value = true;
    try {
        const response = await axios.get(route('admin.movements.show', id));
        selectedMovement.value = response.data.data;
        showDetailModal.value = true;
    } catch (error) {
        console.error('Error al cargar el detalle del movimiento:', error);
    } finally {
        isLoadingDetail.value = false;
    }
};

const closeDetail = () => {
    showDetailModal.value = false;
    selectedMovement.value = null;
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

</script>

<template>
    <Head title="Entradas / Salidas" />

    <AdminLayout>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white leading-tight">Entradas / Salidas</h1>
                <div class="mt-4 p-4 rounded-xl bg-zinc-50 dark:bg-secondary-700/50 border-l-4 border-indigo-500 flex items-start gap-4 shadow-sm max-w-3xl">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                        <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400 text-xl">search_insights</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Control de Movimientos Manuales</h4>
                        <p class="text-xs text-zinc-500 dark:text-secondary-400 mt-1 leading-relaxed">
                            Visualiza el historial de ajustes directos al stock que no provienen de procesos comerciales estándar. Utilice este módulo para auditar <span class="text-zinc-800 dark:text-secondary-200 font-bold italic">mermas, daños o cargas iniciales de almacén</span>.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('admin.movements.create')"
                      class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 active:scale-95 group">
                    <span class="material-symbols-outlined text-lg">add</span>
                    <span>Nuevo Movimiento</span>
                </Link>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-surface dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 shadow-sm mb-6 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors text-xl">search</span>
                    </div>
                    <input v-model="search"
                           type="text"
                           placeholder="Buscar por motivo..."
                           class="w-full pl-10 pr-4 py-2 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white placeholder-zinc-400">
                </div>
                
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors text-xl">home</span>
                    </div>
                    <select v-model="warehouseId"
                            class="w-full pl-10 pr-4 py-2 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white">
                        <option value="">Todos los almacenes</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-surface dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 shadow-sm overflow-hidden">
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-secondary-700 border-b border-zinc-200 dark:border-secondary-600 text-zinc-500 dark:text-secondary-300 font-bold uppercase tracking-tighter text-[11px]">
                            <th class="px-6 py-4">Fecha</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">Almacén</th>
                            <th class="px-6 py-4">Motivo / Concepto</th>
                            <th class="px-6 py-4">Usuario</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50">
                        <tr v-for="mv in movements.data" :key="mv.id"
                            class="hover:bg-zinc-50/80 dark:hover:bg-secondary-700/50 transition-colors group">
                            <td class="px-6 py-4 capitalize">
                                <span class="text-zinc-900 dark:text-white font-bold tracking-tight">{{ mv.date }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="mv.type === 'entrada'" 
                                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20 shadow-sm">
                                    <span class="material-symbols-outlined text-[14px]">trending_up</span>
                                    <span>Entrada</span>
                                </span>
                                <span v-else 
                                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/20 shadow-sm">
                                    <span class="material-symbols-outlined text-[14px]">trending_down</span>
                                    <span>Salida</span>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-secondary-300">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-zinc-400 dark:text-secondary-500 text-base">home</span>
                                    <span class="font-medium">{{ mv.warehouse?.name || '—' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-zinc-900 dark:text-white font-bold tracking-tight lowercase first-letter:uppercase text-sm">{{ mv.reason }}</p>
                                <p v-if="mv.notes" class="text-[10px] text-zinc-400 dark:text-secondary-500 mt-0.5 truncate max-w-xs italic">{{ mv.notes }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-zinc-100 dark:bg-secondary-700 flex items-center justify-center text-[10px] font-bold text-zinc-500 dark:text-secondary-400 border border-zinc-200 dark:border-secondary-600 shadow-inner">
                                        {{ mv.user?.name?.charAt(0).toUpperCase() || 'S' }}
                                    </div>
                                    <span class="text-xs font-semibold text-zinc-600 dark:text-secondary-300">{{ mv.user?.name || '—' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openDetail(mv.id)"
                                        class="w-9 h-9 inline-flex items-center justify-center rounded-xl text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-all active:scale-90 border border-transparent hover:border-indigo-100 dark:hover:border-indigo-800">
                                    <span v-if="!isLoadingDetail" class="material-symbols-outlined text-xl">visibility</span>
                                    <svg v-else class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="movements.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-zinc-50 dark:bg-secondary-700 rounded-2xl flex items-center justify-center mb-4 border-2 border-dashed border-zinc-200 dark:border-secondary-600">
                                        <span class="material-symbols-outlined text-4xl text-zinc-200 dark:text-secondary-600">swap_horiz</span>
                                    </div>
                                    <p class="text-zinc-500 dark:text-secondary-400 font-bold text-lg">No se encontraron movimientos</p>
                                    <p class="text-zinc-400 dark:text-secondary-500 text-xs mt-1">Prueba ajustando los filtros o registra uno nuevo.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-gray-700">
                <div v-for="mv in movements.data" :key="mv.id" 
                     class="p-4 space-y-4 bg-white dark:bg-gray-800/50">
                    
                    <!-- Basic Info -->
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-zinc-900 dark:text-white capitalize">{{ mv.date }}</span>
                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500 font-medium">Por: {{ mv.user?.name || '—' }}</span>
                        </div>
                        <span v-if="mv.type === 'entrada'" 
                              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                            Entrada
                        </span>
                        <span v-else 
                              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/20">
                            Salida
                        </span>
                    </div>

                    <!-- Details Grid -->
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Almacén</span>
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ mv.warehouse?.name || '—' }}</span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Motivo</span>
                            <span class="text-xs font-bold text-zinc-900 dark:text-white text-right max-w-[200px] line-clamp-2">{{ mv.reason }}</span>
                        </div>
                    </div>

                    <!-- Action Row -->
                    <div class="flex justify-end items-center pt-2">
                        <button @click="openDetail(mv.id)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 active:scale-90 transition-all border border-emerald-100 dark:border-emerald-800/20"
                                title="Ver Detalle">
                            <span v-if="!isLoadingDetail" class="material-symbols-outlined text-lg">visibility</span>
                            <svg v-else class="animate-spin h-5 w-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Empty State for Mobile -->
                <div v-if="movements.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800">
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-zinc-50 dark:bg-secondary-700 rounded-2xl flex items-center justify-center mb-4 border-2 border-dashed border-zinc-200 dark:border-secondary-600">
                            <span class="material-symbols-outlined text-4xl text-zinc-200 dark:text-secondary-600">swap_horiz</span>
                        </div>
                        <p class="text-zinc-500 dark:text-secondary-400 font-bold">No hay movimientos</p>
                    </div>
                </div>
            </div>
            
            <!-- Paginación -->
            <div v-if="movements.meta && movements.meta.links" class="px-6 py-5 border-t border-zinc-100 dark:border-secondary-700 bg-zinc-50/50 dark:bg-secondary-800/50 flex justify-center">
                <nav class="flex gap-1.5">
                    <Link v-for="(link, i) in movements.meta.links" :key="i"
                          :href="link.url || '#'"
                          v-html="link.label"
                          :class="[
                              'px-4 py-2 text-[11px] font-black rounded-xl transition-all duration-200',
                              link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30 scale-110' : 
                              link.url ? 'bg-white dark:bg-secondary-700 text-zinc-500 dark:text-secondary-300 hover:bg-zinc-100 dark:hover:bg-secondary-600 shadow-sm' : 'text-zinc-300 dark:text-secondary-800 cursor-not-allowed'
                          ]"
                    />
                </nav>
            </div>
        </div>

        <!-- Modal de Detalle -->
        <div v-if="showDetailModal" 
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/40 backdrop-blur-sm transition-all animate-in fade-in duration-300">
            <div class="bg-white dark:bg-secondary-900 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden border border-zinc-100 dark:border-secondary-700 animate-in zoom-in duration-300">
                <div v-if="selectedMovement">
                    <!-- Body del Modal -->
                    <div class="p-6 sm:p-8 bg-zinc-50/50 dark:bg-secondary-800/50 border-b border-zinc-100 dark:border-secondary-700">
                        <div class="flex items-center justify-between mb-8">
                            <h2 class="text-xl font-black text-zinc-900 dark:text-white tracking-tight uppercase">Detalle del Movimiento</h2>
                            <button @click="closeDetail" class="p-2 text-zinc-400 hover:text-rose-500 transition-colors hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div>
                                    <span class="text-[10px] uppercase font-black text-zinc-400 dark:text-secondary-500 tracking-widest block mb-2">Tipo de Ajuste</span>
                                    <span v-if="selectedMovement.type === 'entrada'" 
                                          class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/30 shadow-sm">
                                        <span class="material-symbols-outlined text-[16px]">trending_up</span>
                                        Entrada de Inventario
                                    </span>
                                    <span v-else 
                                          class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-100 dark:border-rose-800/30 shadow-sm">
                                        <span class="material-symbols-outlined text-[16px]">trending_down</span>
                                        Salida de Inventario
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-black text-zinc-400 dark:text-secondary-500 tracking-widest block mb-2">Motivo / Concepto</span>
                                    <p class="text-base font-black text-zinc-900 dark:text-white leading-tight tracking-tight italic">"{{ selectedMovement.reason }}"</p>
                                </div>
                            </div>
                            <div class="space-y-4 md:text-right">
                                <div class="flex flex-col">
                                    <span class="text-[10px] uppercase font-black text-zinc-400 dark:text-secondary-500 tracking-widest">Almacén de Destino</span>
                                    <span class="text-sm font-bold text-zinc-900 dark:text-white mt-1">{{ selectedMovement.warehouse?.name }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] uppercase font-black text-zinc-400 dark:text-secondary-500 tracking-widest">Fecha de Registro</span>
                                    <span class="text-sm font-bold text-zinc-900 dark:text-white mt-1 capitalize">{{ selectedMovement.date }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] uppercase font-black text-zinc-400 dark:text-secondary-500 tracking-widest">Registrado por</span>
                                    <span class="text-sm font-bold text-zinc-900 dark:text-white mt-1">{{ selectedMovement.user?.name }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedMovement.notes" class="mt-8 p-4 bg-white dark:bg-secondary-900 rounded-2xl border border-zinc-200/60 dark:border-secondary-700 shadow-sm border-l-4 border-l-indigo-500">
                            <span class="text-[10px] uppercase font-black text-zinc-400 dark:text-secondary-500 tracking-widest block mb-1.5">Observaciones adicionales</span>
                            <p class="text-xs text-zinc-600 dark:text-secondary-300 leading-relaxed font-medium">{{ selectedMovement.notes }}</p>
                        </div>
                    </div>

                    <!-- Tabla de Productos -->
                    <div class="p-0 bg-white dark:bg-secondary-900 max-h-[300px] overflow-y-auto custom-scrollbar">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-secondary-800 text-zinc-500 dark:text-secondary-400 font-black border-b border-zinc-100 dark:border-secondary-700 uppercase tracking-widest text-[9px] sticky top-0">
                                <tr>
                                    <th class="px-8 py-4 text-left">Producto / Código Técnico</th>
                                    <th class="px-8 py-4 text-right">Cantidad Ajustada</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-50 dark:divide-secondary-800">
                                <tr v-for="detail in selectedMovement.details" :key="detail.id"
                                    class="hover:bg-zinc-50/50 dark:hover:bg-secondary-800/50 transition-colors">
                                    <td class="px-8 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-black text-zinc-900 dark:text-white tracking-tight">{{ detail.product?.name }}</span>
                                            <span class="text-[10px] text-zinc-400 dark:text-secondary-500 font-mono mt-0.5 tracking-tighter">{{ detail.product?.code || 'S/C' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-4 text-right">
                                        <div class="inline-flex flex-col items-end">
                                            <span :class="[
                                                'font-mono text-lg font-black tracking-tighter',
                                                selectedMovement.type === 'entrada' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                                            ]">
                                                {{ selectedMovement.type === 'entrada' ? '+' : '-' }} {{ formatNumber(detail.quantity) }}
                                            </span>
                                            <span class="text-[9px] text-zinc-400 dark:text-secondary-500 uppercase font-black tracking-widest mt-0.5">Unidades</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-6 border-t border-zinc-100 dark:border-secondary-700 flex flex-col sm:flex-row justify-end items-center gap-3 bg-zinc-50/30 dark:bg-secondary-900">
                        <button @click="closeDetail"
                                class="w-full sm:w-auto px-6 py-2.5 text-sm font-bold text-zinc-500 dark:text-secondary-400 hover:bg-zinc-100 dark:hover:bg-secondary-800 rounded-xl transition-colors">
                            Cerrar Detalle
                        </button>
                        <a :href="route('admin.movements.print', selectedMovement.id)" 
                           target="_blank"
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 active:scale-95 group">
                            <span class="material-symbols-outlined text-lg">print</span>
                            <span>Imprimir Comprobante</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
}
</style>
