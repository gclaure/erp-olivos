<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';
import SaleDetailsModal from './Partials/SaleDetailsModal.vue';
import SaleAnnulModal from './Partials/SaleAnnulModal.vue';
import ReportFilter from './Partials/ReportFilter.vue';

const props = defineProps({
    sales: Object,
    filters: Object,
    warehouses: Array,
    users: Array,
    initialDates: Object,
});

const search = ref(props.filters.search || '');
const warehouseFilter = ref(props.filters.warehouseFilter || '');

const showDetailsModal = ref(false);
const showAnnulModal = ref(false);
const selectedSale = ref(null);
const selectedSaleId = ref(null);

const updateFilters = debounce(() => {
    router.get(route('admin.sales.index'), {
        search: search.value,
        warehouseFilter: warehouseFilter.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, warehouseFilter], () => {
    updateFilters();
});

const viewDetails = async (id) => {
    try {
        const response = await axios.get(route('admin.sales.show', id));
        selectedSale.value = response.data.data;
        showDetailsModal.value = true;
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'No se pudo cargar el detalle de la venta.', 'error');
    }
};

const openAnnulModal = (id) => {
    selectedSaleId.value = id;
    showAnnulModal.value = true;
};

const printSale = (id) => {
    const format = usePage().props.activePOS?.receipt_type || 
                  usePage().props.company?.receipt_type || 'media';
    const url = route('admin.sales.print', { sale: id, format: format });
    window.open(url, '_blank');
};

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

</script>

<template>
    <Head title="Historial de Ventas" />

    <AdminLayout>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Historial de Ventas</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Registro histórico de transacciones de venta</p>
            </div>
            <div class="w-full">
                <ReportFilter :initial-dates="initialDates" :users="users" :is-admin="isAdmin" />
            </div>
        </div>

        <!-- Filtros de Tabla -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 mb-6 shadow-sm">
            <div class="p-4 flex flex-col lg:flex-row gap-4">
                <div class="relative flex-1 group">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 group-focus-within:text-indigo-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Buscar por cliente o N°..." 
                           class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none">
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    <select v-model="warehouseFilter"
                            class="w-full sm:w-64 px-3 py-2.5 border border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none">
                        <option value="">Filtrar por Almacén</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden shadow-sm">
            <div class="hidden md:block overflow-x-auto custom-scrollbar">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">N°</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Vendedor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Almacén</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Subtotal</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Desc. Global</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Total Pagado</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Saldo Pendiente</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Notas</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                        <tr v-for="sale in sales.data" :key="sale.id" class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs font-black text-indigo-700 dark:text-indigo-400">
                                {{ sale.formatted_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-900 dark:text-white capitalize">
                                {{ sale.formatted_date }}
                            </td>
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ sale.client.name }}</td>
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">{{ sale.user.name }}</td>
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">{{ sale.warehouse.name }}</td>
                            <td class="px-6 py-4 text-right text-zinc-900 dark:text-white">
                                {{ formatMoney(sale.subtotal) }}
                            </td>
                            <td class="px-6 py-4 text-right text-rose-600">
                                {{ formatMoney(sale.global_discount) }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-zinc-900 dark:text-white">
                                {{ formatMoney(sale.total) }}
                            </td>
                            <td class="px-6 py-4 text-right text-emerald-600 font-medium">
                                {{ formatMoney(sale.total_payment) }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold" :class="sale.pending_balance > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                {{ formatMoney(sale.pending_balance) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                    :class="[
                                        sale.status === 'completada' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400' : '',
                                        sale.status === 'pendiente' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400' : '',
                                        sale.status === 'cancelada' ? 'bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400' : '',
                                        !['completada', 'pendiente', 'cancelada'].includes(sale.status) ? 'bg-zinc-100 dark:bg-gray-600 text-zinc-800 dark:text-zinc-300' : ''
                                    ]">
                                    {{ sale.status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400 max-w-xs truncate" :title="sale.notes">
                                {{ sale.notes || '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="viewDetails(sale.id)" 
                                            class="w-7 h-7 flex items-center justify-center rounded-full bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors" 
                                            title="Ver Detalle">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </button>
                                    <button @click="printSale(sale.id)"
                                            class="w-7 h-7 flex items-center justify-center rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"
                                            title="Imprimir Factura">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231a1.125 1.125 0 0 1-1.12-1.227L6.34 18m11.318-4.171a41.07 41.07 0 0 0-11.318 0m11.318 0c.232-.23.442-.47.63-.718m-11.948.718c-.23-.23-.442-.47-.63-.718m12.578-.625a1.875 1.875 0 0 0-1.875-1.875h-.375V4.312c0-.621-.504-1.125-1.125-1.125H8.938c-.621 0-1.125.504-1.125 1.125v5.063h-.375a1.875 1.875 0 0 0-1.875 1.875v.375c0 .156.015.31.044.458m12.166 0c.03-.149.044-.303.044-.458m-11.411 0c.03-.149.044-.303.044-.458" />
                                        </svg>
                                    </button>
                                    <button v-if="sale.status !== 'cancelada'"
                                            @click="openAnnulModal(sale.id)" 
                                            class="w-7 h-7 flex items-center justify-center rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors" 
                                            title="Anular Venta">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="sales.data.length === 0">
                            <td colspan="13" class="px-6 py-12 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-zinc-300 dark:text-zinc-600 mx-auto mb-3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                </svg>
                                <p class="text-zinc-500 dark:text-zinc-400 font-medium">Sin ventas registradas</p>
                                <p class="text-sm text-zinc-400 dark:text-zinc-500 mt-1">Las ventas aparecerán aquí cuando se registren</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-gray-700">
                <div v-for="sale in sales.data" :key="sale.id" 
                     class="p-4 space-y-4 bg-white dark:bg-gray-800/50">
                    
                    <!-- Header Info -->
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">{{ sale.formatted_number }}</span>
                            <span class="text-xs font-bold text-zinc-900 dark:text-white capitalize mt-0.5">{{ sale.formatted_date }}</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest" 
                            :class="[
                                sale.status === 'completada' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20' : '',
                                sale.status === 'pendiente' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-800/20' : '',
                                sale.status === 'cancelada' ? 'bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/20' : '',
                            ]">
                            {{ sale.status_label }}
                        </span>
                    </div>

                    <!-- Details Grid -->
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Cliente</span>
                            <span class="text-xs font-bold text-zinc-900 dark:text-white truncate max-w-[180px]">{{ sale.client.name }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Total</span>
                            <span class="text-sm font-black text-zinc-900 dark:text-white">Bs. {{ formatMoney(sale.total) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Saldo</span>
                            <span :class="['text-xs font-black', sale.pending_balance > 0 ? 'text-rose-600' : 'text-emerald-600']">
                                Bs. {{ formatMoney(sale.pending_balance) }}
                            </span>
                        </div>
                    </div>

                    <!-- Action Row -->
                    <div class="flex justify-end items-center gap-3 pt-2">
                        <button @click="viewDetails(sale.id)" 
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 active:scale-90 transition-all border border-emerald-100 dark:border-emerald-800/20" 
                                title="Ver Detalle">
                            <span class="material-symbols-outlined text-lg">visibility</span>
                        </button>
                        <button @click="printSale(sale.id)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 active:scale-90 transition-all border border-indigo-100 dark:border-indigo-800/20"
                                title="Imprimir Factura">
                            <span class="material-symbols-outlined text-lg">print</span>
                        </button>
                        <button v-if="sale.status !== 'cancelada'"
                                @click="openAnnulModal(sale.id)" 
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 active:scale-90 transition-all border border-rose-100 dark:border-rose-800/20" 
                                title="Anular Venta">
                            <span class="material-symbols-outlined text-lg">block</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State for Mobile -->
                <div v-if="sales.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800">
                    <div class="flex flex-col items-center opacity-40 py-10">
                        <span class="material-symbols-outlined text-4xl mb-4 text-zinc-300 dark:text-zinc-600">shopping_cart</span>
                        <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-sm">Sin ventas registradas</p>
                    </div>
                </div>
            </div>
            
            <!-- Paginación -->
            <div v-if="sales.meta.last_page > 1" class="px-6 py-4 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50 dark:bg-gray-600 flex justify-center">
                <nav class="flex gap-1">
                    <Link v-for="(link, i) in sales.meta.links" :key="i"
                          :href="link.url || '#'"
                          v-html="link.label"
                          :class="[
                              'px-4 py-2 text-sm font-bold rounded-xl transition-all',
                              link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 
                              link.url ? 'bg-white dark:bg-gray-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' : 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'
                          ]"
                    />
                </nav>
            </div>
        </div>

        <!-- Modals -->
        <SaleDetailsModal 
            :show="showDetailsModal" 
            :sale="selectedSale" 
            @close="showDetailsModal = false" 
            @print="printSale"
        />
        
        <SaleAnnulModal 
            :show="showAnnulModal" 
            :sale-id="selectedSaleId" 
            @close="showAnnulModal = false" 
        />
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 6px;
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
}
</style>
