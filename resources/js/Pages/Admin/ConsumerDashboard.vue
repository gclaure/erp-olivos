<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    filters: Object,
    stats: Object,
    salesChartData: Object, // Historial anual de solicitudes de consumo
    topProducts: Array, // Insumos más solicitados
    categoryDistribution: Array, // Distribución por categoría
    recentRequests: Array, // Últimas 5 solicitudes del usuario
});

const month = ref(props.filters.month);
const year = ref(props.filters.year);

const page = usePage();
const user = computed(() => page.props.auth.user);
const userArea = computed(() => user.value?.area || 'Sin Área Asignada');

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

// Referencias de los gráficos
const trendChartRef = ref(null);
const topProductsChartRef = ref(null);
const categoryChartRef = ref(null);

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

    // 1. Tendencia de Solicitudes
    if (trendChartRef.value) {
        chartInstances.trend = new Chart(trendChartRef.value, {
            type: 'line',
            data: props.salesChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: chartTextColor, precision: 0 } },
                    x: { grid: { display: false }, ticks: { color: chartTextColor } }
                }
            }
        });
    }

    // 2. Ranking de Insumos más Pedidos
    if (topProductsChartRef.value) {
        chartInstances.topProducts = new Chart(topProductsChartRef.value, {
            type: 'bar',
            data: {
                labels: props.topProducts.map(p => p.name),
                datasets: [{
                    label: 'Cant. Pedida',
                    data: props.topProducts.map(p => p.total),
                    backgroundColor: '#6366f1',
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

    // 3. Distribución por Categorías
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

const formatInteger = (value) => {
    return new Intl.NumberFormat('es-BO', { maximumFractionDigits: 0 }).format(value);
};

const currentMonthName = computed(() => {
    const m = months.find(item => item.id === month.value);
    return m ? m.name : '';
});

// Estilos de los estados de solicitud
const getStatusClasses = (status) => {
    switch (status) {
        case 'pendiente':
            return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300';
        case 'aprobado':
            return 'bg-sky-50 text-sky-700 dark:bg-sky-950/30 dark:text-sky-400 border border-sky-200/50 dark:border-sky-800/30';
        case 'despachado':
        case 'despachado_parcial':
            return 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/30';
        case 'recibido':
            return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/30';
        case 'observado':
            return 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200/50 dark:border-rose-800/30 animate-pulse';
        case 'cancelado':
            return 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700/50 dark:text-zinc-400';
        default:
            return 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'pendiente':
            return 'Pendiente';
        case 'aprobado':
            return 'Aprobado';
        case 'despachado':
            return 'Despachado (Listo)';
        case 'despachado_parcial':
            return 'Despacho Parcial';
        case 'recibido':
            return 'Recibido';
        case 'observado':
            return 'Observado (Revisar)';
        case 'cancelado':
            return 'Cancelado';
        default:
            return status;
    }
};
</script>

<template>
    <Head title="Panel de Consumidor" />

    <AdminLayout>
        <!-- Encabezado con información del Área Operativa -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight">Mi Panel de Consumo</h1>
                <p class="text-indigo-600 dark:text-indigo-400 font-bold mt-1 uppercase text-xs tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">restaurant</span>
                    Área Operativa: {{ userArea }}
                </p>
            </div>
            
            <div class="flex items-center gap-3 bg-surface p-2 rounded-xl border border-zinc-200 dark:border-gray-600 shadow-sm">
                <div class="w-40">
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase mb-1">Mes</label>
                    <select v-model="month" class="w-full bg-zinc-50 dark:bg-gray-800 border-zinc-200 dark:border-gray-700 rounded-lg text-sm focus:ring-indigo-500 dark:text-white">
                        <option v-for="m in months" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
                <div class="w-32">
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase mb-1">Año</label>
                    <select v-model="year" class="w-full bg-zinc-50 dark:bg-gray-800 border-zinc-200 dark:border-gray-700 rounded-lg text-sm focus:ring-indigo-500 dark:text-white">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Alerta Importante si hay Solicitudes Observadas -->
        <div v-if="stats.observed_requests > 0" class="mb-8 p-4 bg-rose-50 dark:bg-rose-950/20 border-l-4 border-rose-500 rounded-r-xl flex items-start gap-3 shadow-sm">
            <span class="material-symbols-outlined text-rose-500 mt-0.5 animate-bounce">warning</span>
            <div>
                <h4 class="font-bold text-rose-900 dark:text-rose-300 text-sm">Solicitudes Observadas</h4>
                <p class="text-xs text-rose-700 dark:text-rose-400 mt-0.5">Tienes {{ stats.observed_requests }} solicitud(es) de consumo con observaciones que requieren tu revisión. Por favor, corrígelas para que el almacén pueda proceder con el despacho.</p>
                <Link :href="route('admin.consumption-requests.index', { status: 'observado' })" class="inline-block text-xs font-bold text-rose-600 dark:text-rose-400 underline mt-2 hover:text-rose-800">
                    Ver mis solicitudes observadas &rarr;
                </Link>
            </div>
        </div>

        <!-- KPIs de Consumidor -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Solicitudes del Mes -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm hover:shadow-md transition-all group overflow-hidden relative">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50 dark:bg-indigo-900/30 rounded-full opacity-50 group-hover:scale-110 transition-transform"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-100 font-bold">
                        <span class="material-symbols-outlined">assignment</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-300 uppercase tracking-widest">Mis Solicitudes</p>
                        <h3 class="text-2xl font-black text-zinc-900 dark:text-white mt-0.5">{{ formatInteger(stats.month_requests) }}</h3>
                        <p class="text-[9px] text-zinc-400 dark:text-zinc-500 mt-1 italic">Solicitudes en {{ currentMonthName }}</p>
                    </div>
                </div>
            </div>

            <!-- Solicitudes Pendientes / En Proceso -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm hover:shadow-md transition-all group overflow-hidden relative">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-50 dark:bg-amber-900/30 rounded-full opacity-50 group-hover:scale-110 transition-transform"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center text-white shadow-lg shadow-amber-100">
                        <span class="material-symbols-outlined">pending_actions</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-300 uppercase tracking-widest">Pendientes</p>
                        <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-0.5">{{ formatInteger(stats.pending_requests) }}</h3>
                        <p class="text-[9px] text-zinc-400 dark:text-zinc-500 mt-1">Esperando despacho o recepción</p>
                    </div>
                </div>
            </div>

            <!-- Solicitudes Observadas -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm hover:shadow-md transition-all group overflow-hidden relative">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-50 dark:bg-rose-900/30 rounded-full opacity-50 group-hover:scale-110 transition-transform"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-500 flex items-center justify-center text-white shadow-lg shadow-rose-100">
                        <span class="material-symbols-outlined">feedback</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-300 uppercase tracking-widest">Observadas</p>
                        <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-0.5">{{ stats.observed_requests }}</h3>
                        <p class="text-[9px] text-zinc-400 dark:text-zinc-500 mt-1">Requieren tu atención inmediata</p>
                    </div>
                </div>
            </div>

            <!-- Insumos Recibidos y Finalizados -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm hover:shadow-md transition-all group overflow-hidden relative">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 dark:bg-emerald-900/30 rounded-full opacity-50 group-hover:scale-110 transition-transform"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center text-white shadow-lg shadow-emerald-100">
                        <span class="material-symbols-outlined">check_circle</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-300 uppercase tracking-widest">Finalizadas</p>
                        <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ formatInteger(stats.received_requests) }}</h3>
                        <p class="text-[9px] text-zinc-400 dark:text-zinc-500 mt-1">Solicitudes recibidas con éxito</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos Principales -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Tendencia de Solicitudes del Consumidor -->
            <div class="lg:col-span-2 bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-zinc-900 dark:text-white tracking-tight flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        Mi Tendencia de Pedidos
                    </h3>
                    <span class="text-[10px] bg-indigo-50 dark:bg-indigo-950/30 px-2 py-1 rounded-md font-bold text-indigo-600 dark:text-indigo-400">HISTORIAL ANUAL</span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6">Muestra el volumen mensual de solicitudes de consumo que has creado durante este año.</p>
                <div class="h-[300px]">
                    <canvas ref="trendChartRef"></canvas>
                </div>
            </div>

            <!-- Distribución por Categorías de Insumos pedidos -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="font-bold text-zinc-900 dark:text-white tracking-tight flex items-center gap-2 mb-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        Pedidos por Categoría
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6">Categorías de materias primas que más has solicitado en el periodo.</p>
                </div>
                <div class="h-[230px] relative">
                    <canvas ref="categoryChartRef"></canvas>
                    <div v-if="categoryDistribution.length === 0" class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50">
                        <p class="text-xs text-zinc-400 italic">Sin registros en este mes</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Top Insumos Solicitados -->
            <div class="bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm">
                <h3 class="font-bold text-zinc-900 dark:text-white tracking-tight flex items-center gap-2 mb-2">
                    <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                    Mis Insumos más Pedidos
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6">Ranking personal de los 5 insumos con mayor cantidad solicitada en el periodo.</p>
                <div class="h-[250px] relative">
                    <canvas ref="topProductsChartRef"></canvas>
                    <div v-if="topProducts.length === 0" class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50">
                        <p class="text-xs text-zinc-400 italic">Sin registros de pedidos</p>
                    </div>
                </div>
            </div>

            <!-- Últimas 5 Solicitudes -->
            <div class="lg:col-span-2 bg-surface rounded-2xl border border-zinc-200 dark:border-gray-600 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-zinc-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-500">history</span>
                        Solicitudes Recientes
                    </h3>
                    <Link :href="route('admin.consumption-requests.index')" class="text-xs font-bold text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 hover:underline">
                        Ver todas
                    </Link>
                </div>
                
                <div class="overflow-x-auto rounded-xl border border-zinc-150 dark:border-gray-700">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-zinc-50 dark:bg-gray-800 text-[10px] font-black uppercase text-zinc-500 dark:text-zinc-400 tracking-widest border-b border-zinc-150 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3">Código / Número</th>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Almacén Destino</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-50 dark:divide-gray-850">
                            <tr v-for="r in recentRequests" :key="r.id" class="hover:bg-zinc-50/50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3">
                                    <span class="font-bold text-zinc-900 dark:text-white">#{{ r.formatted_number }}</span>
                                </td>
                                <td class="px-4 py-3 text-zinc-500 font-mono text-xs">{{ r.date }}</td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 font-medium">{{ r.warehouse_name }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="getStatusClasses(r.status)" class="px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md">
                                        {{ getStatusLabel(r.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('admin.consumption-requests.show', r.id)" class="px-3 py-1 bg-zinc-100 hover:bg-indigo-600 dark:bg-gray-800 dark:hover:bg-indigo-600 text-zinc-700 dark:text-zinc-300 hover:text-white rounded-lg font-bold text-xs transition-colors inline-block">
                                        VER DETALLES
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="recentRequests.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-zinc-400 dark:text-zinc-500 italic">No tienes solicitudes registradas todavía.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Estilos y transiciones */
.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}
</style>
