<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    activeTab: {
        type: String,
        default: 'consumptions'
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    warehouses: {
        type: Array,
        default: () => []
    },
    categories: {
        type: Array,
        default: () => []
    },
    consumers: {
        type: Array,
        default: () => []
    },
    reportData: {
        type: Object,
        default: () => ({
            paginated: { data: [], links: [] },
            summary: {}
        })
    }
});

const currentTab = ref(props.activeTab || 'consumptions');

// Filters state
const filterForm = ref({
    user_id: props.filters.user_id || '',
    warehouse_id: props.filters.warehouse_id || '',
    status: props.filters.status || '',
    movement_type: props.filters.movement_type || '',
    stock_condition: props.filters.stock_condition || 'with_stock',
    category_id: props.filters.category_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    search: props.filters.search || '',
});

const isFiltering = ref(false);

const applyFilters = () => {
    isFiltering.value = true;
    router.get(route('admin.reports.index'), {
        tab: currentTab.value,
        ...filterForm.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isFiltering.value = false;
        }
    });
};

const debouncedApply = debounce(() => {
    applyFilters();
}, 400);

const switchTab = (tab) => {
    currentTab.value = tab;
    // reset tab-specific filters if switching
    if (tab === 'stock') {
        filterForm.value.stock_condition = filterForm.value.stock_condition || 'with_stock';
    }
    applyFilters();
};

const setQuickDate = (period) => {
    const today = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const toYMD = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    if (period === 'this_month') {
        const first = new Date(today.getFullYear(), today.getMonth(), 1);
        const last = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        filterForm.value.date_from = toYMD(first);
        filterForm.value.date_to = toYMD(last);
    } else if (period === 'last_month') {
        const first = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const last = new Date(today.getFullYear(), today.getMonth(), 0);
        filterForm.value.date_from = toYMD(first);
        filterForm.value.date_to = toYMD(last);
    } else if (period === 'last_30_days') {
        const past = new Date();
        past.setDate(today.getDate() - 30);
        filterForm.value.date_from = toYMD(past);
        filterForm.value.date_to = toYMD(today);
    } else if (period === 'clear') {
        filterForm.value.date_from = '';
        filterForm.value.date_to = '';
    }
    applyFilters();
};

const clearAllFilters = () => {
    filterForm.value = {
        user_id: '',
        warehouse_id: '',
        status: '',
        movement_type: '',
        stock_condition: currentTab.value === 'stock' ? 'with_stock' : '',
        category_id: '',
        date_from: '',
        date_to: '',
        search: '',
    };
    applyFilters();
};

// Export Links Builder
const buildExportUrl = (type, format) => {
    const params = new URLSearchParams();
    Object.entries(filterForm.value).forEach(([key, val]) => {
        if (val !== null && val !== undefined && val !== '') {
            params.append(key, val);
        }
    });

    const routeName = `admin.reports.${type}.${format}`;
    const baseUrl = route(routeName);
    const queryString = params.toString();
    return queryString ? `${baseUrl}?${queryString}` : baseUrl;
};

// Formatters
const formatCurrency = (val) => {
    return 'Bs ' + Number(val || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatNumber = (val) => {
    return Number(val || 0).toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
};

const getStatusBadge = (status) => {
    const map = {
        pending: { label: 'Pendiente', class: 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800' },
        approved: { label: 'Aprobado', class: 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800' },
        dispatched: { label: 'Despachado', class: 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800' },
        received: { label: 'Recibido', class: 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' },
        observed: { label: 'Observado', class: 'bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-800' },
        cancelled: { label: 'Cancelado', class: 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800' },
    };
    return map[status] || { label: status || '—', class: 'bg-zinc-100 text-zinc-700 border-zinc-200' };
};

const isIngresoMovement = (type) => {
    return ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA', 'AJUSTE_POSITIVO', 'COMPRA'].includes(String(type).toUpperCase());
};

const isSalidaMovement = (type) => {
    return ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA', 'AJUSTE_NEGATIVO', 'CONSUMO', 'MERMA'].includes(String(type).toUpperCase());
};
</script>

<template>
    <Head title="Reportes Operativos" />

    <AdminLayout>
        <div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-gray-800 p-6 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm">
                <div class="space-y-1">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-800">
                            <span class="material-symbols-outlined text-2xl">description</span>
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white tracking-tight">
                                Centro de Reportes Operativos
                            </h1>
                            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">
                                Consultas auditadas, control de consumo de insumos y balances de almacén
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Export Action Buttons -->
                <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                    <a :href="buildExportUrl(currentTab, 'pdf')"
                       target="_blank"
                       class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-sm hover:shadow-rose-600/20 active:scale-95 transition-all">
                        <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                        <span>Exportar PDF</span>
                    </a>
                    <a :href="buildExportUrl(currentTab, 'excel')"
                       class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm hover:shadow-emerald-600/20 active:scale-95 transition-all">
                        <span class="material-symbols-outlined text-lg">table_view</span>
                        <span>Exportar Excel</span>
                    </a>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-zinc-100 dark:bg-gray-800/60 p-1.5 rounded-2xl border border-zinc-200/80 dark:border-gray-700">
                <button @click="switchTab('consumptions')"
                        :class="[
                            'flex items-center justify-center gap-3 px-4 py-3 rounded-xl font-bold text-xs sm:text-sm transition-all',
                            currentTab === 'consumptions' 
                                ? 'bg-white dark:bg-gray-800 text-emerald-600 dark:text-emerald-400 shadow-sm border border-zinc-200/80 dark:border-gray-700' 
                                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                        ]">
                    <span class="material-symbols-outlined text-lg">assignment</span>
                    <span>Consumos por Consumidor</span>
                </button>

                <button @click="switchTab('movements')"
                        :class="[
                            'flex items-center justify-center gap-3 px-4 py-3 rounded-xl font-bold text-xs sm:text-sm transition-all',
                            currentTab === 'movements' 
                                ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm border border-zinc-200/80 dark:border-gray-700' 
                                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                        ]">
                    <span class="material-symbols-outlined text-lg">swap_vert</span>
                    <span>Entradas y Salidas Mensuales</span>
                </button>

                <button @click="switchTab('stock')"
                        :class="[
                            'flex items-center justify-center gap-3 px-4 py-3 rounded-xl font-bold text-xs sm:text-sm transition-all',
                            currentTab === 'stock' 
                                ? 'bg-white dark:bg-gray-800 text-teal-600 dark:text-teal-400 shadow-sm border border-zinc-200/80 dark:border-gray-700' 
                                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                        ]">
                    <span class="material-symbols-outlined text-lg">inventory_2</span>
                    <span>Stock de Insumos</span>
                </button>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-gray-700/60 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-zinc-400 text-lg">filter_list</span>
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Filtros de Búsqueda</span>
                    </div>

                    <button @click="clearAllFilters" 
                            class="text-xs font-semibold text-rose-500 hover:text-rose-600 dark:text-rose-400 flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-sm">restart_alt</span>
                        <span>Limpiar Filtros</span>
                    </button>
                </div>

                <!-- Tab 1 Filters: Consumos -->
                <div v-if="currentTab === 'consumptions'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Consumidor / Solicitante</label>
                        <select v-model="filterForm.user_id" @change="applyFilters"
                                class="w-full text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 py-2.5">
                            <option value="">Todos los Consumidores</option>
                            <option v-for="c in consumers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Almacén</label>
                        <select v-model="filterForm.warehouse_id" @change="applyFilters"
                                class="w-full text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 py-2.5">
                            <option value="">Todos los Almacenes</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Estado Solicitud</label>
                        <select v-model="filterForm.status" @change="applyFilters"
                                class="w-full text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 py-2.5">
                            <option value="">Todos los Estados</option>
                            <option value="pending">Pendiente</option>
                            <option value="approved">Aprobado</option>
                            <option value="dispatched">Despachado</option>
                            <option value="received">Recibido</option>
                            <option value="observed">Observado</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Buscar Insumo / Código</label>
                        <div class="relative">
                            <input v-model="filterForm.search" @input="debouncedApply"
                                   type="text" placeholder="Ej: Jabón, MAT-001..."
                                   class="w-full text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-9 py-2.5 focus:ring-emerald-500 focus:border-emerald-500" />
                            <span class="material-symbols-outlined absolute left-3 top-2.5 text-zinc-400 text-sm">search</span>
                        </div>
                    </div>
                </div>

                <!-- Tab 2 Filters: Entradas y Salidas -->
                <div v-else-if="currentTab === 'movements'" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Almacén</label>
                        <select v-model="filterForm.warehouse_id" @change="applyFilters"
                                class="w-full text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 py-2.5">
                            <option value="">Todos los Almacenes</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Tipo de Movimiento</label>
                        <select v-model="filterForm.movement_type" @change="applyFilters"
                                class="w-full text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 py-2.5">
                            <option value="">Todos los Movimientos</option>
                            <option value="ENTRADA">Solo ENTRADAS (Compras / Ajustes +)</option>
                            <option value="SALIDA">Solo SALIDAS (Consumos / Despachos / Mermas)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Buscar Insumo / Código</label>
                        <div class="relative">
                            <input v-model="filterForm.search" @input="debouncedApply"
                                   type="text" placeholder="Ej: Cloro, INSUMO-002..."
                                   class="w-full text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-9 py-2.5 focus:ring-blue-500 focus:border-blue-500" />
                            <span class="material-symbols-outlined absolute left-3 top-2.5 text-zinc-400 text-sm">search</span>
                        </div>
                    </div>
                </div>

                <!-- Tab 3 Filters: Stock de Insumos -->
                <div v-else-if="currentTab === 'stock'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Condición de Stock</label>
                        <select v-model="filterForm.stock_condition" @change="applyFilters"
                                class="w-full text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500 py-2.5">
                            <option value="with_stock">Stock Mayor a Cero (Stock > 0)</option>
                            <option value="all">Todos los Productos (incluye Stock 0)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Almacén</label>
                        <select v-model="filterForm.warehouse_id" @change="applyFilters"
                                class="w-full text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500 py-2.5">
                            <option value="">Todos los Almacenes</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Categoría</label>
                        <select v-model="filterForm.category_id" @change="applyFilters"
                                class="w-full text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500 py-2.5">
                            <option value="">Todas las Categorías</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Buscar Insumo / Código</label>
                        <div class="relative">
                            <input v-model="filterForm.search" @input="debouncedApply"
                                   type="text" placeholder="Ej: Papel, PROD-010..."
                                   class="w-full text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-9 py-2.5 focus:ring-teal-500 focus:border-teal-500" />
                            <span class="material-symbols-outlined absolute left-3 top-2.5 text-zinc-400 text-sm">search</span>
                        </div>
                    </div>
                </div>

                <!-- Date Range Row (For Consumos and Movements) -->
                <div v-if="currentTab === 'consumptions' || currentTab === 'movements'" 
                     class="pt-3 border-t border-zinc-100 dark:border-gray-700/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-zinc-600 dark:text-zinc-400">Desde:</label>
                            <input v-model="filterForm.date_from" @change="applyFilters" type="date"
                                   class="text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white py-1.5 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-zinc-600 dark:text-zinc-400">Hasta:</label>
                            <input v-model="filterForm.date_to" @change="applyFilters" type="date"
                                   class="text-xs font-medium rounded-xl border-zinc-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white py-1.5 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>
                    </div>

                    <!-- Quick Date Pills -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider mr-1">Rápido:</span>
                        <button @click="setQuickDate('this_month')" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-zinc-100 dark:bg-gray-700 hover:bg-zinc-200 dark:hover:bg-gray-600 text-zinc-700 dark:text-zinc-300 transition-colors">Este Mes</button>
                        <button @click="setQuickDate('last_month')" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-zinc-100 dark:bg-gray-700 hover:bg-zinc-200 dark:hover:bg-gray-600 text-zinc-700 dark:text-zinc-300 transition-colors">Mes Anterior</button>
                        <button @click="setQuickDate('last_30_days')" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-zinc-100 dark:bg-gray-700 hover:bg-zinc-200 dark:hover:bg-gray-600 text-zinc-700 dark:text-zinc-300 transition-colors">Últimos 30 días</button>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Consumos KPIs -->
                <template v-if="currentTab === 'consumptions'">
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">fact_check</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Solicitudes</p>
                            <h3 class="text-xl font-black text-zinc-900 dark:text-white">{{ formatNumber(reportData.summary?.total_requests) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">list_alt</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Total Ítems</p>
                            <h3 class="text-xl font-black text-zinc-900 dark:text-white">{{ formatNumber(reportData.summary?.total_items) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">shopping_cart</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Cant. Solicitada</p>
                            <h3 class="text-xl font-black text-zinc-900 dark:text-white">{{ formatNumber(reportData.summary?.total_requested) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">inventory</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Cant. Entregada</p>
                            <h3 class="text-xl font-black text-teal-600 dark:text-teal-400">{{ formatNumber(reportData.summary?.total_delivered) }}</h3>
                        </div>
                    </div>
                </template>

                <!-- Movements KPIs -->
                <template v-else-if="currentTab === 'movements'">
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">arrow_downward</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Entradas (Cant.)</p>
                            <h3 class="text-xl font-black text-emerald-600 dark:text-emerald-400">+{{ formatNumber(reportData.summary?.total_entradas_qty) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">arrow_upward</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Salidas (Cant.)</p>
                            <h3 class="text-xl font-black text-rose-600 dark:text-rose-400">-{{ formatNumber(reportData.summary?.total_salidas_qty) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Valor Entradas</p>
                            <h3 class="text-xl font-black text-zinc-900 dark:text-white">{{ formatCurrency(reportData.summary?.total_entradas_val) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">payments</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Valor Salidas</p>
                            <h3 class="text-xl font-black text-zinc-900 dark:text-white">{{ formatCurrency(reportData.summary?.total_salidas_val) }}</h3>
                        </div>
                    </div>
                </template>

                <!-- Stock KPIs -->
                <template v-else-if="currentTab === 'stock'">
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">category</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Total Productos</p>
                            <h3 class="text-xl font-black text-zinc-900 dark:text-white">{{ formatNumber(reportData.summary?.total_items) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">inventory</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Stock Total</p>
                            <h3 class="text-xl font-black text-zinc-900 dark:text-white">{{ formatNumber(reportData.summary?.total_quantity) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">monetization_on</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Valorización Total</p>
                            <h3 class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(reportData.summary?.total_value) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="p-3 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-xl">
                            <span class="material-symbols-outlined text-2xl">warning</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Stock Bajo / Agotado</p>
                            <h3 class="text-xl font-black text-rose-600 dark:text-rose-400">{{ formatNumber(reportData.summary?.low_stock_count) }}</h3>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-zinc-200/80 dark:border-gray-700 shadow-sm overflow-hidden">
                
                <!-- Tab 1: Consumos Table -->
                <div v-if="currentTab === 'consumptions'" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-gray-700">
                        <thead class="bg-zinc-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">N° Solicitud</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Fecha</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Consumidor</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Producto / Insumo</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">U.M.</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Solicitado</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Entregado</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                            <tr v-for="item in reportData.paginated?.data" :key="item.id" class="hover:bg-zinc-50/80 dark:hover:bg-gray-700/40 transition-colors">
                                <td class="px-4 py-3 text-xs font-bold text-zinc-900 dark:text-white whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-lg bg-zinc-100 dark:bg-gray-700 border border-zinc-200 dark:border-gray-600">
                                        SOL-{{ item.consumption_request?.formatted_number ?? item.consumption_request?.number ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300 whitespace-nowrap">
                                    {{ formatDate(item.consumption_request?.date) }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ item.consumption_request?.requested_by ?? item.consumption_request?.user?.name ?? '—' }}</div>
                                    <div class="text-[11px] text-zinc-400">{{ item.consumption_request?.warehouse?.name }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ item.product?.name ?? 'Producto Eliminado' }}</div>
                                    <div class="text-[11px] text-zinc-400 font-mono">{{ item.product?.code ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-center font-medium text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                    {{ item.product?.unit_of_measure?.abbreviation ?? item.product?.unit_of_measure?.name ?? 'UND' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-right font-bold text-zinc-900 dark:text-white whitespace-nowrap">
                                    {{ formatNumber(item.quantity_requested) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-right font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    {{ formatNumber(item.quantity_delivered ?? item.quantity_requested) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-center whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider', getStatusBadge(item.consumption_request?.status).class]">
                                        {{ getStatusBadge(item.consumption_request?.status).label }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!reportData.paginated?.data?.length">
                                <td colspan="8" class="px-4 py-12 text-center text-zinc-400 dark:text-zinc-500">
                                    <span class="material-symbols-outlined text-4xl mb-2">content_paste_off</span>
                                    <p class="text-sm font-semibold">No se encontraron registros de consumo con los filtros seleccionados.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 2: Movements Table -->
                <div v-else-if="currentTab === 'movements'" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-gray-700">
                        <thead class="bg-zinc-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Fecha / Hora</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tipo Movimiento</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Producto / Insumo</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Almacén</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Entrada</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Salida</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Costo Unit.</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                            <tr v-for="k in reportData.paginated?.data" :key="k.id" class="hover:bg-zinc-50/80 dark:hover:bg-gray-700/40 transition-colors">
                                <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300 whitespace-nowrap">
                                    <div class="font-bold">{{ formatDate(k.created_at ? k.created_at.substring(0, 10) : '') }}</div>
                                    <div class="text-[10px] text-zinc-400">{{ k.created_at ? k.created_at.substring(11, 16) : '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap">
                                    <span v-if="isIngresoMovement(k.type)" 
                                          class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 uppercase">
                                        ENTRADA
                                    </span>
                                    <span v-else-if="isSalidaMovement(k.type)" 
                                          class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800 uppercase">
                                        SALIDA
                                    </span>
                                    <span v-else 
                                          class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-gray-700 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-gray-600 uppercase">
                                        {{ k.type }}
                                    </span>
                                    <div class="text-[10px] text-zinc-400 mt-0.5">{{ String(k.type).replace(/_/g, ' ') }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ k.product?.name ?? 'Producto Eliminado' }}</div>
                                    <div class="text-[11px] text-zinc-400 font-mono">{{ k.product?.code ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300 whitespace-nowrap">
                                    {{ k.warehouse?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-right font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    {{ isIngresoMovement(k.type) ? formatNumber(k.quantity) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-right font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                    {{ isSalidaMovement(k.type) ? formatNumber(k.quantity) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-right text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                    {{ formatCurrency(k.unit_cost) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-right font-bold text-zinc-900 dark:text-white whitespace-nowrap">
                                    {{ formatNumber(k.balance_quantity) }}
                                </td>
                            </tr>
                            <tr v-if="!reportData.paginated?.data?.length">
                                <td colspan="8" class="px-4 py-12 text-center text-zinc-400 dark:text-zinc-500">
                                    <span class="material-symbols-outlined text-4xl mb-2">swap_driving_apps_wheel</span>
                                    <p class="text-sm font-semibold">No se encontraron movimientos registrados para los filtros seleccionados.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 3: Stock Table -->
                <div v-else-if="currentTab === 'stock'" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-gray-700">
                        <thead class="bg-zinc-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Código</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Producto / Insumo</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Categoría</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Almacén</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">U.M.</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Stock Actual</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Stock Mín.</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Costo Prom.</th>
                                <th class="px-4 py-3.5 text-right text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Valor Total</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                            <tr v-for="stock in reportData.paginated?.data" :key="stock.id" class="hover:bg-zinc-50/80 dark:hover:bg-gray-700/40 transition-colors">
                                <td class="px-4 py-3 text-xs font-mono font-bold text-zinc-900 dark:text-white whitespace-nowrap">
                                    {{ stock.product?.code ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ stock.product?.name ?? 'Producto Eliminado' }}</div>
                                    <div class="text-[10px] text-zinc-400">{{ stock.product?.type === 'supply' ? 'Insumo' : 'Materia Prima' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">
                                    {{ stock.product?.categories?.map(c => c.name).join(', ') || '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300 whitespace-nowrap">
                                    {{ stock.warehouse?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-center font-medium text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                    {{ stock.product?.unit_of_measure?.abbreviation ?? stock.product?.unit_of_measure?.name ?? 'UND' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-right font-black whitespace-nowrap"
                                    :class="[
                                        Number(stock.quantity) <= 0 ? 'text-rose-600 dark:text-rose-400' :
                                        Number(stock.quantity) <= Number(stock.product?.min_stock || 0) ? 'text-amber-600 dark:text-amber-400' :
                                        'text-emerald-600 dark:text-emerald-400'
                                    ]">
                                    {{ formatNumber(stock.quantity) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-right text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                    {{ formatNumber(stock.product?.min_stock) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-right text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                    {{ formatCurrency(stock.average_cost) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-right font-bold text-zinc-900 dark:text-white whitespace-nowrap">
                                    {{ formatCurrency(stock.inventory_value) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-center whitespace-nowrap">
                                    <span v-if="Number(stock.quantity) <= 0" 
                                          class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800 uppercase">
                                        AGOTADO
                                    </span>
                                    <span v-else-if="Number(stock.quantity) <= Number(stock.product?.min_stock || 0)" 
                                          class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800 uppercase">
                                        BAJO
                                    </span>
                                    <span v-else 
                                          class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 uppercase">
                                        NORMAL
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!reportData.paginated?.data?.length">
                                <td colspan="10" class="px-4 py-12 text-center text-zinc-400 dark:text-zinc-500">
                                    <span class="material-symbols-outlined text-4xl mb-2">inventory_2</span>
                                    <p class="text-sm font-semibold">No se encontraron productos o insumos con las condiciones solicitadas.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="reportData.paginated?.links?.length > 3" 
                     class="px-6 py-4 bg-zinc-50/50 dark:bg-gray-900/30 border-t border-zinc-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                        Mostrando del <span class="font-bold text-zinc-700 dark:text-zinc-200">{{ reportData.paginated.from || 0 }}</span> al <span class="font-bold text-zinc-700 dark:text-zinc-200">{{ reportData.paginated.to || 0 }}</span> de <span class="font-bold text-zinc-700 dark:text-zinc-200">{{ reportData.paginated.total || 0 }}</span> resultados
                    </div>

                    <div class="flex flex-wrap items-center gap-1">
                        <Link v-for="(link, i) in reportData.paginated.links" :key="i"
                              :href="link.url || '#'"
                              v-html="link.label"
                              :class="[
                                  'px-3 py-1.5 text-xs font-bold rounded-lg transition-all',
                                  link.active ? 'bg-emerald-600 text-white shadow-sm' :
                                  link.url ? 'bg-white dark:bg-gray-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-gray-700 border border-zinc-200/80 dark:border-gray-700' :
                                  'text-zinc-300 dark:text-zinc-600 cursor-not-allowed bg-transparent'
                              ]"
                        />
                    </div>
                </div>

            </div>

        </div>
    </AdminLayout>
</template>
