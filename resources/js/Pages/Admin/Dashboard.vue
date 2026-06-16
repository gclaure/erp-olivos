<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    filters: Object,
    stats: Object,
    planUsage: Object,
    salesChartData: Object, // Propiedad que contiene datos del gráfico anual de solicitudes
    topProducts: Array, // Propiedad que contiene insumos más solicitados
    categoryDistribution: Array,
    inventoryMovements: Object,
    lowStockProducts: Array,
    stockByWarehouse: Array,
    isWarehouse: Boolean,
    assignedWarehouses: Array,
});

const month = ref(props.filters.month);
const year = ref(props.filters.year);

const months = [
    { name: 'Enero', id: 1 }, { name: 'Febrero', id: 2 }, { name: 'Marzo', id: 3 },
    { name: 'Abril', id: 4 }, { name: 'Mayo', id: 5 }, { name: 'Junio', id: 6 },
    { name: 'Julio', id: 7 }, { name: 'Agosto', id: 8 }, { name: 'Septiembre', id: 9 },
    { name: 'Octubre', id: 10 }, { name: 'Noviembre', id: 11 }, { name: 'Diciembre', id: 12 },
];

const years = [2024, 2025, 2026, 2027];

const applyFilters = () => {
    router.get(route('admin.dashboard'), {
        month: month.value,
        year: year.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch([month, year], () => {
    applyFilters();
});

// Chart Refs
const salesChartRef = ref(null);
const topProductsChartRef = ref(null);
const categoryChartRef = ref(null);
const movementChartRef = ref(null);
const warehouseChartRef = ref(null);

let chartInstances = {};

const destroyCharts = () => {
    Object.values(chartInstances).forEach(chart => {
        if (chart) {
            chart.destroy();
        }
    });
    chartInstances = {};
};

const renderCharts = () => {
    destroyCharts();

    const isDarkMode = document.documentElement.classList.contains('dark');
    const chartTextColor = isDarkMode ? '#a1a1aa' : '#71717a';
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';

    Chart.defaults.color = chartTextColor;
    Chart.defaults.borderColor = gridColor;

    // 1. Requisitions Trend Chart
    if (salesChartRef.value) {
        chartInstances.sales = new Chart(salesChartRef.value, {
            type: 'line',
            data: props.salesChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: chartTextColor, precision: 0 } },
                    x: { grid: { display: false }, ticks: { color: chartTextColor } }
                }
            }
        });
    }

    // 2. Top Requested Insumos
    if (topProductsChartRef.value) {
        chartInstances.topProducts = new Chart(topProductsChartRef.value, {
            type: 'bar',
            data: {
                labels: props.topProducts.map(p => p.name),
                datasets: [{
                    label: 'Cant. Solicitada',
                    data: props.topProducts.map(p => p.total),
                    backgroundColor: '#4f46e5',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: chartTextColor } },
                    y: { grid: { display: false }, ticks: { color: chartTextColor } }
                }
            }
        });
    }

    // 3. Category Distribution
    if (categoryChartRef.value) {
        chartInstances.category = new Chart(categoryChartRef.value, {
            type: 'doughnut',
            data: {
                labels: props.categoryDistribution.map(c => c.name),
                datasets: [{
                    data: props.categoryDistribution.map(c => c.total),
                    backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
                    borderWidth: isDarkMode ? 2 : 0,
                    borderColor: isDarkMode ? '#1f2937' : '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, color: chartTextColor } }
                }
            }
        });
    }

    // 4. Movements
    if (movementChartRef.value) {
        chartInstances.movement = new Chart(movementChartRef.value, {
            type: 'bar',
            data: {
                labels: ['Movimientos de Stock'],
                datasets: [
                    { label: 'Entradas', data: [props.inventoryMovements.entries], backgroundColor: '#10b981' },
                    { label: 'Salidas', data: [props.inventoryMovements.exits], backgroundColor: '#ef4444' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: chartTextColor } },
                    x: { grid: { display: false }, ticks: { color: chartTextColor } }
                }
            }
        });
    }

    // 5. Stock by Warehouse
    if (warehouseChartRef.value) {
        chartInstances.warehouse = new Chart(warehouseChartRef.value, {
            type: 'bar',
            data: {
                labels: props.stockByWarehouse.map(w => w.name),
                datasets: [{
                    label: 'Stock Total',
                    data: props.stockByWarehouse.map(w => w.total),
                    backgroundColor: '#6366f1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: chartTextColor } },
                    x: { grid: { display: false }, ticks: { color: chartTextColor } }
                }
            }
        });
    }
};

onMounted(() => {
    renderCharts();
});

onUnmounted(() => {
    destroyCharts();
});

watch(() => props, () => {
    renderCharts();
}, { deep: true });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(value);
};

const formatInteger = (value) => {
    return new Intl.NumberFormat('es-BO', { maximumFractionDigits: 0 }).format(value);
};

const currentMonthName = computed(() => {
    const m = months.find(item => item.id === month.value);
    return m ? m.name : '';
});

</script>

<template>
    <Head title="Panel de Control" />

    <AdminLayout>
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight">Panel de Control</h1>
                <p class="text-zinc-500 dark:text-zinc-400 font-medium mt-1 uppercase text-[10px] tracking-widest">Gestión Logística y Abastecimiento de Áreas</p>
                <div v-if="isWarehouse && assignedWarehouses && assignedWarehouses.length > 0" class="mt-2 flex items-center gap-1.5 flex-wrap">
                    <span class="text-[9px] font-black uppercase tracking-widest bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-950/40 dark:text-fuchsia-300 px-2 py-0.5 rounded-md border border-fuchsia-200/50 dark:border-fuchsia-900/30 shadow-sm">
                        Encargado de Almacén
                    </span>
                    <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-bold">
                        Almacenes a su cargo: <span class="text-zinc-800 dark:text-zinc-200 font-extrabold">{{ assignedWarehouses.join(', ') }}</span>
                    </span>
                </div>
            </div>
            
            <div class="flex items-center gap-3 bg-surface p-2 rounded-xl border border-zinc-200 dark:border-gray-600 shadow-sm">
                <div class="w-40">
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase mb-1">Mes</label>
                    <select v-model="month" class="w-full bg-zinc-50 dark:bg-gray-800 border-zinc-200 dark:border-gray-700 rounded-lg text-sm focus:ring-indigo-500 dark:text-white animate-none">
                        <option v-for="m in months" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
                <div class="w-32">
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase mb-1">Año</label>
                    <select v-model="year" class="w-full bg-zinc-50 dark:bg-gray-800 border-zinc-200 dark:border-gray-700 rounded-lg text-sm focus:ring-indigo-500 dark:text-white animate-none">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- KPIs de Logística -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Solicitudes del Mes -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm hover:shadow-md transition-all group overflow-hidden relative">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50 dark:bg-indigo-900/30 rounded-full opacity-50 group-hover:scale-110 transition-transform"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-100 font-bold">
                        <span class="material-symbols-outlined">assignment</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-1">
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-300 uppercase tracking-widest">Solicitudes del Mes</p>
                        </div>
                        <h3 class="text-2xl font-black text-zinc-900 dark:text-white mt-0.5">{{ formatInteger(stats.month_requests) }}</h3>
                        <p class="text-[9px] text-zinc-400 dark:text-zinc-500 mt-1 italic">Solicitudes en {{ currentMonthName }} {{ year }}</p>
                    </div>
                </div>
            </div>

            <!-- Valorización de Stock -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm hover:shadow-md transition-all group overflow-hidden relative">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 dark:bg-emerald-900/30 rounded-full opacity-50 group-hover:scale-110 transition-transform"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center text-white shadow-lg shadow-emerald-100">
                        <span class="material-symbols-outlined">payments</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-300 uppercase tracking-widest">Valorización Stock</p>
                        <h3 class="text-2xl font-black text-zinc-900 dark:text-white mt-0.5">{{ formatCurrency(stats.inventory_value) }}</h3>
                        <p class="text-[9px] text-zinc-400 dark:text-zinc-500 mt-1">Capital en almacenes (Costo)</p>
                    </div>
                </div>
            </div>

            <!-- Artículos en Stock Mínimo -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm hover:shadow-md transition-all group overflow-hidden relative">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-50 dark:bg-rose-900/30 rounded-full opacity-50 group-hover:scale-110 transition-transform"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-500 flex items-center justify-center text-white shadow-lg shadow-rose-100">
                        <span class="material-symbols-outlined">warning</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-300 uppercase tracking-widest">Stock Crítico</p>
                        <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-0.5">{{ stats.low_stock }} <span class="text-zinc-400 dark:text-zinc-500 text-sm font-bold">Items</span></h3>
                        <p class="text-[9px] text-zinc-400 dark:text-zinc-500 mt-1">Bajo el mínimo establecido</p>
                    </div>
                </div>
            </div>

            <!-- Insumos Registrados -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm hover:shadow-md transition-all group overflow-hidden relative">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-zinc-50 dark:bg-zinc-800/30 rounded-full opacity-50 group-hover:scale-110 transition-transform"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-zinc-800 flex items-center justify-center text-white shadow-lg shadow-zinc-200">
                        <span class="material-symbols-outlined">inventory_2</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-300 uppercase tracking-widest">Total Insumos</p>
                        <h3 class="text-2xl font-black text-zinc-900 dark:text-white mt-0.5">{{ stats.total_products }}</h3>
                        <p class="text-[9px] text-zinc-400 dark:text-zinc-500 mt-1">Catálogo de materias primas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Gráficos Principales -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Tendencia de Solicitudes (Main) -->
            <div class="lg:col-span-2 bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-zinc-900 dark:text-white tracking-tight flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        Tendencia de Solicitudes
                    </h3>
                    <span class="text-[10px] bg-zinc-100 dark:bg-gray-700 px-2 py-1 rounded-md font-bold text-zinc-500 dark:text-zinc-200">HISTORIAL ANUAL</span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6">Muestra la cantidad mensual de solicitudes de consumo interno gestionadas en el sistema.</p>
                <div class="h-[300px]">
                    <canvas ref="salesChartRef"></canvas>
                </div>
            </div>

            <!-- Distribution / Today Summary -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="font-bold text-zinc-900 dark:text-white tracking-tight flex items-center gap-2 mb-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        Resumen Logístico Diario
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6">Flujo operativo en la jornada actual.</p>
                </div>
                
                <div class="flex-1 flex flex-col gap-4 justify-center">
                    <div class="p-4 bg-zinc-50 dark:bg-gray-800 rounded-xl border border-zinc-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Solicitudes creadas Hoy</p>
                        <h4 class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ formatInteger(stats.today_requests_count) }}</h4>
                    </div>

                    <div class="p-4 bg-zinc-50 dark:bg-gray-800 rounded-xl border border-zinc-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Solicitudes Pendientes (Almacén)</p>
                        <h4 class="text-2xl font-black text-amber-600 dark:text-amber-500">{{ formatInteger(stats.pending_requests) }} <span class="text-xs font-bold text-zinc-400 uppercase">por despachar</span></h4>
                    </div>

                    <div class="p-4 bg-zinc-50 dark:bg-gray-800 rounded-xl border border-zinc-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Órdenes de Compra del Mes</p>
                        <h4 class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                            {{ formatInteger(stats.purchase_orders_count) }}
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Top Products -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm">
                <h3 class="font-bold text-zinc-900 dark:text-white tracking-tight flex items-center gap-2 mb-2">
                    <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                    Insumos más Solicitados por las Áreas
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6">Ranking de mayor volumen solicitado en el periodo actual.</p>
                <div class="h-[250px]">
                    <canvas ref="topProductsChartRef"></canvas>
                </div>
            </div>

            <!-- Movements -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm">
                <h3 class="font-bold text-zinc-900 dark:text-white tracking-tight flex items-center gap-2 mb-2">
                    <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                    Tráfico Total de Inventario (Kardex)
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6">Comparativa de Entradas vs Salidas físicas acumuladas en el mes.</p>
                <div class="h-[250px]">
                    <canvas ref="movementChartRef"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Stock by Warehouse -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm">
                <h3 class="font-bold text-zinc-900 dark:text-white tracking-tight flex items-center gap-2 mb-2">
                    <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                    Stock Físico por Almacén
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">Cantidad total de unidades agrupadas por almacén.</p>
                <div class="h-[200px]">
                    <canvas ref="warehouseChartRef"></canvas>
                </div>
            </div>

            <!-- Low Stock Alerts -->
            <div class="lg:col-span-2 bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm">
                <h3 class="font-bold text-zinc-900 dark:text-white tracking-tight flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-rose-500">error</span>
                    Insumos Críticos (Alerta de Reposición)
                </h3>
                
                <div class="overflow-x-auto rounded-xl border border-zinc-100 dark:border-gray-700">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-zinc-50 dark:bg-gray-800 text-[10px] font-black uppercase text-zinc-500 dark:text-zinc-400 tracking-widest border-b border-zinc-100 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3">Insumo</th>
                                <th class="px-4 py-3 text-center">Stock Mínimo</th>
                                <th class="px-4 py-3 text-right">Existencia Actual</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-50 dark:divide-gray-800">
                            <tr v-for="p in lowStockProducts" :key="p.id" class="hover:bg-zinc-50/50 dark:hover:bg-gray-800/50 animate-none">
                                <td class="px-4 py-3">
                                    <span class="font-bold text-zinc-900 dark:text-white">{{ p.name }}</span>
                                    <p class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase tracking-tighter">{{ p.code }}</p>
                                </td>
                                <td class="px-4 py-3 text-center text-zinc-500 font-mono">{{ p.min_stock }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="px-2 py-1 bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 rounded-md font-bold font-mono">
                                        {{ p.total_stock }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('admin.products.index', { search: p.code })" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs underline">VER</Link>
                                </td>
                            </tr>
                            <tr v-if="lowStockProducts.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-zinc-400 dark:text-zinc-500 italic">No hay alertas activas de stock. Todo bajo control.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Transiciones suaves para los KPIs */
.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}
</style>
