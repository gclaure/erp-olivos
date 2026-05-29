<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AsyncSelect from '@/Components/Admin/AsyncSelect.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    records: Object,
    summaryStats: Object,
    selectedProduct: Object,
    warehouses: Array,
    filters: Object,
});

const productId = ref(props.filters.product_id || null);
const warehouseId = ref(props.filters.warehouse_id || null);
const type = ref(props.filters.type || null);
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const perPage = ref(props.filters.per_page || 25);
const sortOrder = ref(props.filters.sort_order || 'desc');

const loadingPdf = ref(false);
const loadingExcel = ref(false);

const updateFilters = debounce(() => {
    router.get(route('admin.kardex.index'), {
        product_id: productId.value,
        warehouse_id: warehouseId.value,
        type: type.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        per_page: perPage.value,
        sort_order: sortOrder.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 500);

watch([productId, warehouseId, type, dateFrom, dateTo, perPage, sortOrder], () => {
    updateFilters();
});

const clearFilters = () => {
    productId.value = null;
    warehouseId.value = null;
    type.value = null;
    dateFrom.value = props.filters.date_from; // Mantener rango por defecto o resetear a lo que mande el backend
    dateTo.value = props.filters.date_to;
    perPage.value = 25;
    sortOrder.value = 'desc';
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

const exportExcel = () => {
    loadingExcel.value = true;
    const url = route('admin.kardex.export-excel', {
        product_id: productId.value,
        warehouse_id: warehouseId.value,
        type: type.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        sort_order: sortOrder.value
    });
    window.location.href = url;
    setTimeout(() => loadingExcel.value = false, 3000);
};

const exportPdf = () => {
    loadingPdf.value = true;
    const url = route('admin.kardex.export-pdf', {
        product_id: productId.value,
        warehouse_id: warehouseId.value,
        type: type.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        sort_order: sortOrder.value
    });
    window.location.href = url;
    setTimeout(() => loadingPdf.value = false, 5000);
};

</script>

<template>
    <Head title="Kardex" />

    <AdminLayout>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white tracking-tight">Kardex</h1>
                <p class="text-sm text-zinc-500 dark:text-secondary-400 mt-1">Registro y análisis de movimientos de inventario</p>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                <button @click="exportExcel"
                        class="w-full sm:w-auto inline-flex justify-center items-center gap-x-2 text-sm font-bold rounded-xl border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all px-4 py-2.5 active:scale-95">
                    <span v-if="!loadingExcel" class="material-symbols-outlined text-lg">description</span>
                    <svg v-else class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Excel
                </button>
                <button @click="exportPdf"
                        class="w-full sm:w-auto inline-flex justify-center items-center gap-x-2 text-sm font-bold rounded-xl border border-transparent bg-rose-600 text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all px-4 py-2.5 active:scale-95">
                    <span v-if="!loadingPdf" class="material-symbols-outlined text-lg">picture_as_pdf</span>
                    <svg v-else class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    PDF
                </button>
            </div>
        </div>

        <!-- Tarjetas de Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-secondary-700 p-5 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-zinc-500 dark:text-secondary-400 uppercase tracking-widest">Total Entradas (Cant.)</p>
                        <p class="text-2xl font-black text-zinc-900 dark:text-white mt-1 tracking-tight">{{ formatNumber(summaryStats.total_entradas_qty) }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-emerald-600 text-xl">arrow_downward</span>
                    </div>
                </div>
                <p class="text-[10px] font-bold text-zinc-400 dark:text-secondary-500 mt-2">Bs. {{ formatNumber(summaryStats.total_entradas_val) }}</p>
            </div>

            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-secondary-700 p-5 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-zinc-500 dark:text-secondary-400 uppercase tracking-widest">Total Salidas (Cant.)</p>
                        <p class="text-2xl font-black text-zinc-900 dark:text-white mt-1 tracking-tight">{{ formatNumber(summaryStats.total_salidas_qty) }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-rose-600 text-xl">arrow_upward</span>
                    </div>
                </div>
                <p class="text-[10px] font-bold text-zinc-400 dark:text-secondary-500 mt-2">Bs. {{ formatNumber(summaryStats.total_salidas_val) }}</p>
            </div>

            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-secondary-700 p-5 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-zinc-500 dark:text-secondary-400 uppercase tracking-widest">Saldo Final (Cant.)</p>
                        <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1 tracking-tight">{{ formatNumber(summaryStats.saldo_qty) }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-indigo-600 text-xl">balance</span>
                    </div>
                </div>
            </div>

            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-secondary-700 p-5 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-zinc-500 dark:text-secondary-400 uppercase tracking-widest">Valor Final (Total)</p>
                        <p class="text-2xl font-black text-zinc-900 dark:text-white mt-1 tracking-tight">Bs. {{ formatNumber(summaryStats.saldo_val) }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-secondary-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-zinc-600 dark:text-secondary-300 text-xl">payments</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-secondary-700 mb-6 p-5 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-5 items-end">
                <div class="lg:col-span-2">
                    <label class="block text-[10px] font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Producto</label>
                    <AsyncSelect 
                        v-model="productId"
                        :endpoint="route('admin.api.selects.products')"
                        placeholder="Buscar producto por nombre o código..."
                        :initial-data="selectedProduct"
                    />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Almacén</label>
                    <select v-model="warehouseId"
                            class="w-full px-4 py-2.5 bg-white dark:bg-secondary-800 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm">
                        <option :value="null">Todos los almacenes</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Tipo</label>
                    <select v-model="type"
                            class="w-full px-4 py-2.5 bg-white dark:bg-secondary-800 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm">
                        <option :value="null">Todos los movimientos</option>
                        <option value="ENTRADA">Entradas</option>
                        <option value="SALIDA">Salidas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Desde</label>
                    <input type="date" v-model="dateFrom"
                           class="w-full px-4 py-2.5 bg-white dark:bg-secondary-800 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Hasta</label>
                    <input type="date" v-model="dateTo"
                           class="w-full px-4 py-2.5 bg-white dark:bg-secondary-800 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm">
                </div>


                <div class="lg:col-span-6 flex justify-between items-center mt-2 border-t border-zinc-100 dark:border-secondary-700 pt-5">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Mostrar</span>
                        <select v-model="perPage" 
                                class="bg-zinc-50 dark:bg-secondary-800 border-zinc-200 dark:border-secondary-700 rounded-lg text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 py-1.5 pl-3 pr-8 shadow-sm">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-xs font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest px-2">|</span>
                        <span class="text-xs font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Orden</span>
                        <select v-model="sortOrder" 
                                class="bg-zinc-50 dark:bg-secondary-800 border-zinc-200 dark:border-secondary-700 rounded-lg text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 py-1.5 pl-3 pr-8 shadow-sm">
                            <option value="asc">Ascendente</option>
                            <option value="desc">Descendente</option>
                        </select>
                    </div>
                    <button v-if="productId || warehouseId || type" 
                            @click="clearFilters" 
                            class="text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-800 transition-colors inline-flex items-center gap-1.5 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1.5 rounded-lg border border-indigo-100 dark:border-indigo-800">
                        <span class="material-symbols-outlined text-sm">delete_sweep</span> Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla Section -->
        <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-secondary-700 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-[11px] text-left whitespace-nowrap border-collapse">
                    <thead>
                        <tr class="bg-zinc-100 dark:bg-secondary-700 border-b border-zinc-200 dark:border-secondary-600">
                            <th rowspan="2" class="px-4 py-3 font-bold text-zinc-600 dark:text-secondary-300 uppercase tracking-wider border-r border-zinc-200 dark:border-secondary-600 align-middle">Fecha</th>
                            <th rowspan="2" class="px-4 py-3 font-bold text-zinc-600 dark:text-secondary-300 uppercase tracking-wider border-r border-zinc-200 dark:border-secondary-600 align-middle">Producto (Almacén)</th>
                            <th rowspan="2" class="px-4 py-3 font-bold text-zinc-600 dark:text-secondary-300 uppercase tracking-wider border-r border-zinc-200 dark:border-secondary-600 align-middle">Descripción</th>
                            <th colspan="3" class="px-4 py-3 font-black text-center text-emerald-700 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-900/20 uppercase tracking-wider border-r border-zinc-200 dark:border-secondary-600">Entrada</th>
                            <th colspan="3" class="px-4 py-3 font-black text-center text-rose-700 dark:text-rose-400 bg-rose-50/50 dark:bg-rose-900/20 uppercase tracking-wider border-r border-zinc-200 dark:border-secondary-600">Salida</th>
                            <th colspan="3" class="px-4 py-3 font-black text-center text-indigo-700 dark:text-indigo-300 bg-indigo-50/50 dark:bg-indigo-900/30 uppercase tracking-wider border-r border-zinc-200 dark:border-secondary-600">Saldos Totales</th>
                            <th rowspan="2" class="px-4 py-3 font-bold text-zinc-600 dark:text-secondary-300 uppercase tracking-wider align-middle text-center">Registrado</th>
                        </tr>
                        <tr class="bg-zinc-50 dark:bg-secondary-800 border-b border-zinc-200 dark:border-secondary-600">
                            <!-- Entradas -->
                            <th class="px-3 py-2 font-bold text-zinc-500 dark:text-secondary-400 text-center border-r border-zinc-200 dark:border-secondary-600">Cant.</th>
                            <th class="px-3 py-2 font-bold text-zinc-500 dark:text-secondary-400 text-right border-r border-zinc-200 dark:border-secondary-600">C. Unit.</th>
                            <th class="px-3 py-2 font-bold text-zinc-500 dark:text-secondary-400 text-right border-r border-emerald-200/50 bg-emerald-50/30 dark:bg-emerald-900/10">Total</th>
                            <!-- Salidas -->
                            <th class="px-3 py-2 font-bold text-zinc-500 dark:text-secondary-400 text-center border-r border-zinc-200 dark:border-secondary-600">Cant.</th>
                            <th class="px-3 py-2 font-bold text-zinc-500 dark:text-secondary-400 text-right border-r border-zinc-200 dark:border-secondary-600">C. Unit.</th>
                            <th class="px-3 py-2 font-bold text-zinc-500 dark:text-secondary-400 text-right border-r border-rose-200/50 bg-rose-50/30 dark:bg-rose-900/10">Total</th>
                            <!-- Totales -->
                            <th class="px-3 py-2 font-bold text-zinc-500 dark:text-secondary-400 text-center border-r border-zinc-200 dark:border-secondary-600">Cant.</th>
                            <th class="px-3 py-2 font-bold text-zinc-500 dark:text-secondary-400 text-right border-r border-zinc-200 dark:border-secondary-600">C. Prom.</th>
                            <th class="px-3 py-2 font-bold text-zinc-500 dark:text-secondary-400 text-right border-r border-zinc-200 dark:border-secondary-600 bg-indigo-50/30 dark:bg-indigo-900/10">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50 bg-surface">
                        <tr v-for="k in records.data" :key="k.id"
                            class="hover:bg-zinc-50/80 dark:hover:bg-secondary-700/50 transition-all duration-200 group">
                            <td class="px-4 py-3 text-zinc-600 dark:text-secondary-200 border-r border-zinc-100 dark:border-secondary-700/50">
                                <span class="font-bold tracking-tight">{{ k.date_formatted }}</span>
                                <div class="text-[10px] text-zinc-400 dark:text-secondary-500 font-mono mt-0.5">{{ k.time_formatted }}</div>
                            </td>
                            <td class="px-4 py-3 border-r border-zinc-100 dark:border-secondary-700/50">
                                <div class="font-black text-zinc-900 dark:text-white tracking-tight">{{ k.product.name }}</div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-[10px] font-bold text-zinc-400 dark:text-secondary-400 bg-zinc-100 dark:bg-secondary-700 px-1.5 rounded">{{ k.product.code || 'S/C' }}</span>
                                    <span class="text-zinc-300 dark:text-secondary-700">•</span>
                                    <span class="text-[10px] text-zinc-500 dark:text-secondary-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px] opacity-40">storefront</span>
                                        {{ k.warehouse.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 border-r border-zinc-100 dark:border-secondary-700/50">
                                <div class="flex flex-col gap-1.5">
                                    <span :class="[
                                        'inline-flex items-center w-fit px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest border',
                                        k.is_ingreso ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-emerald-100 dark:border-emerald-800/30' : 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400 border-rose-100 dark:border-rose-800/30'
                                    ]">
                                        {{ k.type_label }}
                                    </span>
                                    
                                    <div v-if="k.recordable" class="flex flex-wrap gap-1">
                                        <span v-if="k.recordable.payment_type" 
                                              class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-bold bg-zinc-100 dark:bg-secondary-700 text-zinc-500 dark:text-secondary-400 uppercase border border-zinc-200 dark:border-secondary-600">
                                            <span class="material-symbols-outlined text-[10px] mr-1 opacity-50">payments</span> {{ k.recordable.payment_type }}
                                        </span>
                                        <span v-if="k.recordable.delivery_mode" 
                                              class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-bold bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 uppercase border border-indigo-100 dark:border-indigo-800/30">
                                            <span class="material-symbols-outlined text-[10px] mr-1 opacity-50">local_shipping</span> {{ k.recordable.delivery_mode }}
                                        </span>
                                    </div>

                                    <div class="text-[10px] text-zinc-400 dark:text-secondary-500 italic max-w-[200px] truncate" :title="k.notes">
                                        {{ k.notes || 'Sin observaciones' }}
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Bloque ENTRADAS -->
                            <template v-if="k.is_ingreso">
                                <td class="px-3 py-3 text-center font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50/10 dark:bg-emerald-900/10 border-r border-zinc-100 dark:border-secondary-700/50">{{ formatNumber(k.quantity) }}</td>
                                <td class="px-3 py-3 text-right text-emerald-600 dark:text-emerald-400 bg-emerald-50/10 dark:bg-emerald-900/10 border-r border-zinc-100 dark:border-secondary-700/50">{{ formatNumber(k.unit_cost) }}</td>
                                <td class="px-3 py-3 text-right font-black text-emerald-700 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-900/20 border-r border-emerald-100 dark:border-secondary-700/50">{{ formatNumber(k.total_cost) }}</td>
                            </template>
                            <template v-else>
                                <td class="px-3 py-3 text-center text-zinc-300 dark:text-secondary-700 border-r border-zinc-100 dark:border-secondary-700/50">—</td>
                                <td class="px-3 py-3 text-center text-zinc-300 dark:text-secondary-700 border-r border-zinc-100 dark:border-secondary-700/50">—</td>
                                <td class="px-3 py-3 text-center bg-zinc-50/50 dark:bg-secondary-900/20 text-zinc-300 dark:text-secondary-700 border-r border-zinc-100 dark:border-secondary-700/50">—</td>
                            </template>

                            <!-- Bloque SALIDAS -->
                            <template v-if="k.is_salida">
                                <td class="px-3 py-3 text-center font-bold text-rose-700 dark:text-rose-400 bg-rose-50/10 dark:bg-rose-900/10 border-r border-zinc-100 dark:border-secondary-700/50">{{ formatNumber(k.quantity) }}</td>
                                <td class="px-3 py-3 text-right text-rose-600 dark:text-rose-400 bg-rose-50/10 dark:bg-rose-900/10 border-r border-zinc-100 dark:border-secondary-700/50">{{ formatNumber(k.unit_cost) }}</td>
                                <td class="px-3 py-3 text-right font-black text-rose-700 dark:text-rose-400 bg-rose-50/30 dark:bg-rose-900/20 border-r border-rose-100 dark:border-secondary-700/50">{{ formatNumber(k.total_cost) }}</td>
                            </template>
                            <template v-else>
                                <td class="px-3 py-3 text-center text-zinc-300 dark:text-secondary-700 border-r border-zinc-100 dark:border-secondary-700/50">—</td>
                                <td class="px-3 py-3 text-center text-zinc-300 dark:text-secondary-700 border-r border-zinc-100 dark:border-secondary-700/50">—</td>
                                <td class="px-3 py-3 text-center bg-zinc-50/50 dark:bg-secondary-900/20 text-zinc-300 dark:text-secondary-700 border-r border-zinc-100 dark:border-secondary-700/50">—</td>
                            </template>

                            <!-- Bloque TOTALES -->
                            <td class="px-3 py-3 text-center font-black text-indigo-700 dark:text-indigo-400 bg-indigo-50/10 dark:bg-indigo-900/10 border-r border-zinc-100 dark:border-secondary-700/50">{{ formatNumber(k.balance_quantity) }}</td>
                            <td class="px-3 py-3 text-right text-indigo-600 dark:text-indigo-400 bg-indigo-50/10 dark:bg-indigo-900/10 border-r border-zinc-100 dark:border-secondary-700/50">{{ formatNumber(k.avg_cost) }}</td>
                            <td class="px-3 py-3 text-right font-black text-indigo-800 dark:text-indigo-300 bg-indigo-50/40 dark:bg-indigo-900/20 border-r border-indigo-100 dark:border-secondary-700/50">{{ formatNumber(k.balance_total_cost) }}</td>

                            <!-- Usuario -->
                            <td class="px-4 py-3 text-center align-middle">
                                <div class="flex flex-col items-center">
                                    <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-[10px] font-bold text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 mb-1 shadow-inner">
                                        {{ k.user.name?.charAt(0).toUpperCase() || 'S' }}
                                    </div>
                                    <span class="text-[9px] font-bold text-zinc-500 dark:text-secondary-400 uppercase tracking-tighter truncate max-w-[60px]">{{ k.user.name || 'Sistema' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="records.data.length === 0">
                            <td colspan="12" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-zinc-50 dark:bg-secondary-800 rounded-2xl flex items-center justify-center mb-4 border-2 border-dashed border-zinc-200 dark:border-secondary-700">
                                        <span class="material-symbols-outlined text-4xl text-zinc-300 dark:text-secondary-600">inventory</span>
                                    </div>
                                    <p class="text-zinc-500 dark:text-secondary-400 font-bold text-lg">Sin registros en el Kardex</p>
                                    <p class="text-sm text-zinc-400 dark:text-secondary-500 mt-2 max-w-xs mx-auto">Vacíe sus filtros o registre movimientos para visualizar la tabla de saldos.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-zinc-50 dark:bg-secondary-800 border-t-2 border-zinc-200 dark:border-secondary-700">
                        <tr class="font-black text-zinc-900 dark:text-white">
                            <td colspan="3" class="px-4 py-4 text-right uppercase tracking-widest text-[10px] border-r border-zinc-200 dark:border-secondary-700">TOTALES DEL PERIODO</td>
                            
                            <td class="px-3 py-4 text-center text-emerald-700 dark:text-emerald-400 bg-emerald-50/20 dark:bg-emerald-900/10 border-r border-zinc-200 dark:border-secondary-700">{{ formatNumber(summaryStats.total_entradas_qty) }}</td>
                            <td class="px-3 py-4 border-r border-zinc-200 dark:border-secondary-700 bg-emerald-50/20 dark:bg-emerald-900/10"></td>
                            <td class="px-3 py-4 text-right text-emerald-800 dark:text-emerald-300 bg-emerald-50/50 dark:bg-emerald-900/20 border-r border-emerald-200 dark:border-secondary-700">{{ formatNumber(summaryStats.total_entradas_val) }}</td>
                            
                            <td class="px-3 py-4 text-center text-rose-700 dark:text-rose-400 bg-rose-50/20 dark:bg-rose-900/10 border-r border-zinc-200 dark:border-secondary-700">{{ formatNumber(summaryStats.total_salidas_qty) }}</td>
                            <td class="px-3 py-4 border-r border-zinc-200 dark:border-secondary-700 bg-rose-50/20 dark:bg-rose-900/10"></td>
                            <td class="px-3 py-4 text-right text-rose-800 dark:text-rose-300 bg-rose-50/50 dark:bg-rose-900/20 border-r border-rose-200 dark:border-secondary-700">{{ formatNumber(summaryStats.total_salidas_val) }}</td>
                            
                            <td class="px-3 py-4 text-center text-indigo-700 dark:text-indigo-400 bg-indigo-50/20 dark:bg-indigo-900/10 border-r border-zinc-200 dark:border-secondary-700">{{ formatNumber(summaryStats.saldo_qty) }}</td>
                            <td class="px-3 py-4 border-r border-zinc-200 dark:border-secondary-700 bg-indigo-50/20 dark:bg-indigo-900/10"></td>
                            <td class="px-3 py-4 text-right text-indigo-900 dark:text-indigo-200 bg-indigo-100/50 dark:bg-indigo-900/20 border-r border-indigo-200 dark:border-secondary-700">Bs. {{ formatNumber(summaryStats.saldo_val) }}</td>
                            
                            <td class="px-4 py-4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="records.meta && records.meta.links" class="px-6 py-5 border-t border-zinc-100 dark:border-secondary-700 bg-zinc-50/80 dark:bg-secondary-800/80 flex justify-center">
                <nav class="flex gap-1.5">
                    <Link v-for="(link, i) in records.meta.links" :key="i"
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
    </AdminLayout>
</template>

<style scoped>
/* Transiciones suaves */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Ocultar scrollbar en los filtros si es necesario */
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(0.5);
    cursor: pointer;
}

.dark input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}

/* Estilo para las celdas de la tabla */
td, th {
    transition: background-color 0.2s ease;
}
</style>
