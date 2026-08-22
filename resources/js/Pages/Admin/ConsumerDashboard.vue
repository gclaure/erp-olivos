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
const userName = computed(() => user.value?.name ? user.value.name.split(' ')[0] : 'Usuario');

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
    const chartTextColor = isDarkMode ? '#94a3b8' : '#64748b';
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.04)' : 'rgba(0, 0, 0, 0.04)';

    Chart.defaults.color = chartTextColor;
    Chart.defaults.borderColor = gridColor;
    Chart.defaults.font.family = 'inherit';

    // 1. Tendencia de Solicitudes (Line Chart con Degradado Suave)
    if (trendChartRef.value && props.salesChartData) {
        const ctx = trendChartRef.value.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, isDarkMode ? 'rgba(99, 102, 241, 0.4)' : 'rgba(79, 70, 229, 0.25)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

        const enhancedData = {
            ...props.salesChartData,
            datasets: (props.salesChartData.datasets || []).map(ds => ({
                ...ds,
                borderColor: '#6366f1',
                borderWidth: 3,
                backgroundColor: gradient,
                fill: true,
                tension: 0.38,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: isDarkMode ? '#1e293b' : '#ffffff',
                pointBorderWidth: 2.5,
                pointRadius: 4,
                pointHoverRadius: 6.5,
            }))
        };

        chartInstances.trend = new Chart(trendChartRef.value, {
            type: 'line',
            data: enhancedData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDarkMode ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: isDarkMode ? '#f8fafc' : '#0f172a',
                        bodyColor: isDarkMode ? '#cbd5e1' : '#334155',
                        borderColor: isDarkMode ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 12,
                        displayColors: false,
                        titleFont: { weight: 'bold', size: 12 },
                        bodyFont: { weight: 'bold', size: 13 },
                        callbacks: {
                            label: (ctx) => `Solicitudes: ${ctx.parsed.y}`
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: gridColor }, 
                        ticks: { color: chartTextColor, precision: 0, font: { weight: 'bold', size: 10 } },
                        border: { dash: [4, 4] }
                    },
                    x: { 
                        grid: { display: false }, 
                        ticks: { color: chartTextColor, font: { weight: 'bold', size: 10 } } 
                    }
                }
            }
        });
    }

    // 2. Ranking de Insumos más Pedidos (Horizontal Bar Chart)
    if (topProductsChartRef.value && props.topProducts?.length) {
        chartInstances.topProducts = new Chart(topProductsChartRef.value, {
            type: 'bar',
            data: {
                labels: props.topProducts.map(p => p.name.length > 20 ? p.name.substring(0, 20) + '...' : p.name),
                datasets: [{
                    label: 'Cant. Pedida',
                    data: props.topProducts.map(p => p.total),
                    backgroundColor: isDarkMode ? 'rgba(99, 102, 241, 0.85)' : 'rgba(79, 70, 229, 0.85)',
                    borderRadius: 8,
                    barThickness: 16,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDarkMode ? '#0f172a' : '#ffffff',
                        titleColor: isDarkMode ? '#f8fafc' : '#0f172a',
                        bodyColor: isDarkMode ? '#cbd5e1' : '#334155',
                        borderColor: isDarkMode ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 10,
                    }
                },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: chartTextColor, precision: 0, font: { weight: 'bold', size: 10 } } },
                    y: { grid: { display: false }, ticks: { color: chartTextColor, font: { weight: 'bold', size: 10 } } }
                }
            }
        });
    }

    // 3. Distribución por Categorías (Doughnut Chart)
    if (categoryChartRef.value && props.categoryDistribution?.length) {
        chartInstances.category = new Chart(categoryChartRef.value, {
            type: 'doughnut',
            data: {
                labels: props.categoryDistribution.map(c => c.name),
                datasets: [{
                    data: props.categoryDistribution.map(c => c.total),
                    backgroundColor: ['#4f46e5', '#059669', '#d97706', '#e11d48', '#8b5cf6', '#06b6d4'],
                    borderWidth: isDarkMode ? 3 : 2,
                    borderColor: isDarkMode ? '#1e293b' : '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDarkMode ? '#0f172a' : '#ffffff',
                        titleColor: isDarkMode ? '#f8fafc' : '#0f172a',
                        bodyColor: isDarkMode ? '#cbd5e1' : '#334155',
                        borderColor: isDarkMode ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => ` ${ctx.label}: ${ctx.parsed} pedidos`
                        }
                    }
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
    return new Intl.NumberFormat('es-BO', { maximumFractionDigits: 0 }).format(value || 0);
};

const currentMonthName = computed(() => {
    const m = months.find(item => item.id === month.value);
    return m ? m.name : '';
});

// Estilos y badges de estado
const getStatusBadge = (status) => {
    switch (status) {
        case 'pendiente':
            return {
                bg: 'bg-zinc-100 dark:bg-secondary-800 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-secondary-700',
                dot: 'bg-zinc-400',
                label: 'Pendiente'
            };
        case 'aprobado':
            return {
                bg: 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-800/40',
                dot: 'bg-sky-500',
                label: 'Aprobado'
            };
        case 'despachado':
            return {
                bg: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800/40',
                dot: 'bg-amber-500 animate-pulse',
                label: 'Despachado'
            };
        case 'despachado_parcial':
            return {
                bg: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800/40',
                dot: 'bg-amber-500',
                label: 'Despacho Parcial'
            };
        case 'entregado':
        case 'recibido':
            return {
                bg: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/40',
                dot: 'bg-emerald-500',
                label: 'Recibido'
            };
        case 'observado':
            return {
                bg: 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800/40 animate-pulse',
                dot: 'bg-rose-500',
                label: 'Observado'
            };
        case 'cancelado':
            return {
                bg: 'bg-zinc-200/70 dark:bg-secondary-700/50 text-zinc-600 dark:text-secondary-400 border-zinc-300 dark:border-secondary-600',
                dot: 'bg-zinc-400',
                label: 'Cancelado'
            };
        default:
            return {
                bg: 'bg-zinc-100 dark:bg-secondary-800 text-zinc-800 dark:text-zinc-200 border-zinc-200',
                dot: 'bg-zinc-400',
                label: status
            };
    }
};
</script>

<template>
    <Head title="Mi Panel de Consumo" />

    <AdminLayout>
        <div class="space-y-6 pb-12">
            <!-- HERO HEADER EJECUTIVO -->
            <div class="relative overflow-hidden bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 p-5 sm:p-7 shadow-sm transition-all duration-300">
                <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                    <!-- Bienvenida y Área Operativa -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-700 dark:text-emerald-400 text-[10px] font-black uppercase tracking-wider rounded-full shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Área Operativa: {{ userArea }}
                            </span>
                            <span class="text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest hidden sm:inline">
                                Portal de Consumo
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight flex items-center gap-2">
                            Hola, {{ userName }} <span class="text-2xl">👋</span>
                        </h1>
                        <p class="text-xs sm:text-sm font-medium text-zinc-500 dark:text-secondary-400 max-w-xl">
                            Gestiona y monitorea tus solicitudes de insumos y materias primas para <span class="font-black text-zinc-700 dark:text-secondary-200">{{ userArea }}</span>.
                        </p>
                    </div>

                    <!-- Controles Rápidos: Filtro de Periodo & Botón Nueva Solicitud -->
                    <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
                        <!-- Selector de Período Estilizado -->
                        <div class="flex items-center gap-1.5 bg-zinc-100 dark:bg-secondary-900 p-1.5 rounded-2xl border border-zinc-200 dark:border-secondary-700 shadow-sm">
                            <div class="relative">
                                <select 
                                    v-model="month" 
                                    class="appearance-none bg-surface dark:bg-secondary-800 text-zinc-800 dark:text-secondary-100 text-xs font-black py-2 pl-3 pr-7 rounded-xl border border-zinc-200 dark:border-secondary-700 focus:ring-1 focus:ring-emerald-500 cursor-pointer uppercase tracking-tight"
                                >
                                    <option v-for="m in months" :key="m.id" :value="m.id">{{ m.name }}</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-1.5 top-1/2 -translate-y-1/2 text-[16px] text-zinc-400 pointer-events-none">expand_more</span>
                            </div>

                            <div class="relative">
                                <select 
                                    v-model="year" 
                                    class="appearance-none bg-surface dark:bg-secondary-800 text-zinc-800 dark:text-secondary-100 text-xs font-black py-2 pl-3 pr-7 rounded-xl border border-zinc-200 dark:border-secondary-700 focus:ring-1 focus:ring-emerald-500 cursor-pointer tracking-tight"
                                >
                                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-1.5 top-1/2 -translate-y-1/2 text-[16px] text-zinc-400 pointer-events-none">expand_more</span>
                            </div>
                        </div>

                        <!-- Botón Insignia: Nueva Solicitud -->
                        <Link 
                            :href="route('admin.consumption-requests.create')" 
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/20 active:scale-95 transition-all flex-shrink-0"
                        >
                            <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                            <span>Nueva Solicitud</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- BANNER DE ALERTA: SOLICITUDES OBSERVADAS (Solo si > 0) -->
            <div 
                v-if="stats.observed_requests > 0" 
                class="p-4 sm:p-5 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/60 rounded-3xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm animate-pulse"
            >
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-2xl bg-rose-500 text-white flex items-center justify-center shadow-md shadow-rose-500/20 flex-shrink-0">
                        <span class="material-symbols-outlined text-[20px]">error</span>
                    </div>
                    <div>
                        <h4 class="font-black text-rose-900 dark:text-rose-200 text-xs sm:text-sm uppercase tracking-wide">
                            {{ stats.observed_requests }} Solicitud(es) con Observaciones
                        </h4>
                        <p class="text-xs text-rose-700 dark:text-rose-300 font-medium">
                            El almacén requiere aclaraciones para proceder con el despacho.
                        </p>
                    </div>
                </div>
                <Link 
                    :href="route('admin.consumption-requests.index', { status: 'observado' })" 
                    class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-md transition-all self-start sm:self-auto flex items-center gap-1"
                >
                    <span>Revisar Ahora</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </Link>
            </div>

            <!-- BENTO KPI GRID (4 Tarjetas Ejecutivas) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <!-- 1. MIS SOLICITUDES -->
                <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 p-5 shadow-sm hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-500/40 transition-all group relative overflow-hidden flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[9px] font-black text-zinc-400 dark:text-secondary-400 uppercase tracking-widest">Total del Período</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black">
                            <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight">
                            {{ formatInteger(stats.month_requests) }}
                        </div>
                        <div class="mt-1 flex items-center gap-1.5 text-[11px] font-bold text-zinc-500 dark:text-secondary-400">
                            <span class="text-indigo-600 dark:text-indigo-400">●</span>
                            <span>Pedidos en {{ currentMonthName }}</span>
                        </div>
                    </div>
                </div>

                <!-- 2. PENDIENTES DE DESPACHO -->
                <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 p-5 shadow-sm hover:shadow-md hover:border-amber-300 dark:hover:border-amber-500/40 transition-all group relative overflow-hidden flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[9px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest">En Proceso</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center font-black">
                            <span class="material-symbols-outlined text-[18px]">schedule</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-3xl font-black text-amber-600 dark:text-amber-400 tracking-tight">
                            {{ formatInteger(stats.pending_requests) }}
                        </div>
                        <div class="mt-1 flex items-center gap-1.5 text-[11px] font-bold text-zinc-500 dark:text-secondary-400">
                            <span class="w-2 h-2 rounded-full bg-amber-500" :class="stats.pending_requests > 0 ? 'animate-ping' : ''"></span>
                            <span>Esperando despacho / recepción</span>
                        </div>
                    </div>
                </div>

                <!-- 3. OBSERVADAS -->
                <div 
                    class="rounded-3xl border p-5 shadow-sm transition-all group relative overflow-hidden flex flex-col justify-between"
                    :class="stats.observed_requests > 0 ? 'bg-rose-50/50 dark:bg-rose-950/20 border-rose-200 dark:border-rose-800/50' : 'bg-surface dark:bg-secondary-800 border-zinc-200 dark:border-secondary-700'"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[9px] font-black uppercase tracking-widest" :class="stats.observed_requests > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-400 dark:text-secondary-400'">
                            Atención
                        </span>
                        <div 
                            class="w-8 h-8 rounded-xl flex items-center justify-center font-black"
                            :class="stats.observed_requests > 0 ? 'bg-rose-500 text-white shadow-sm' : 'bg-zinc-100 dark:bg-secondary-700 text-zinc-400 dark:text-secondary-400'"
                        >
                            <span class="material-symbols-outlined text-[18px]">flag</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-3xl font-black tracking-tight" :class="stats.observed_requests > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-900 dark:text-white'">
                            {{ stats.observed_requests }}
                        </div>
                        <div class="mt-1 text-[11px] font-bold" :class="stats.observed_requests > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-400 dark:text-secondary-500'">
                            {{ stats.observed_requests > 0 ? 'Requieren tu respuesta' : 'Sin observaciones activas' }}
                        </div>
                    </div>
                </div>

                <!-- 4. FINALIZADAS -->
                <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 p-5 shadow-sm hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-500/40 transition-all group relative overflow-hidden flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Recibidas</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-black">
                            <span class="material-symbols-outlined text-[18px]">verified</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                            {{ formatInteger(stats.received_requests) }}
                        </div>
                        <div class="mt-1 flex items-center gap-1.5 text-[11px] font-bold text-zinc-500 dark:text-secondary-400">
                            <span class="text-emerald-600 dark:text-emerald-400">✓</span>
                            <span>Entregas completadas</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILA DE GRÁFICOS: TENDENCIA & CATEGORÍAS (2 Columnas: 65% / 35%) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- TENDENCIA ANUAL DE PEDIDOS -->
                <div class="lg:col-span-2 bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-black text-zinc-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <span class="material-symbols-outlined text-indigo-500 text-[18px]">trending_up</span>
                                Mi Tendencia Anual de Pedidos
                            </h3>
                            <span class="px-2.5 py-0.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/40 text-[9px] font-black uppercase rounded-full tracking-wider">
                                Historial {{ year }}
                            </span>
                        </div>
                        <p class="text-xs text-zinc-400 dark:text-secondary-400 mb-4">
                            Volumen mensual de solicitudes creadas durante el año {{ year }}.
                        </p>
                    </div>

                    <div class="h-[260px] sm:h-[280px] w-full relative">
                        <canvas ref="trendChartRef"></canvas>
                    </div>
                </div>

                <!-- PEDIDOS POR CATEGORÍA -->
                <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-black text-zinc-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <span class="material-symbols-outlined text-emerald-500 text-[18px]">pie_chart</span>
                                Pedidos por Categoría
                            </h3>
                        </div>
                        <p class="text-xs text-zinc-400 dark:text-secondary-400 mb-4">
                            Distribución de materias primas e insumos pedidos en {{ currentMonthName }}.
                        </p>
                    </div>

                    <div class="h-[180px] relative my-auto">
                        <canvas ref="categoryChartRef"></canvas>
                        <div v-if="!categoryDistribution || categoryDistribution.length === 0" class="absolute inset-0 flex items-center justify-center">
                            <span class="text-xs font-bold text-zinc-400 dark:text-secondary-500 italic">Sin pedidos en este mes</span>
                        </div>
                    </div>

                    <!-- Leyenda interactiva con porcentajes -->
                    <div v-if="categoryDistribution && categoryDistribution.length > 0" class="pt-3 border-t border-zinc-100 dark:border-secondary-700/60 flex flex-wrap gap-2">
                        <div 
                            v-for="(cat, idx) in categoryDistribution.slice(0, 4)" 
                            :key="cat.name"
                            class="flex items-center gap-1.5 px-2 py-1 bg-zinc-50 dark:bg-secondary-900 rounded-lg text-[10px] font-bold"
                        >
                            <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: ['#4f46e5', '#059669', '#d97706', '#e11d48'][idx % 4] }"></span>
                            <span class="text-zinc-600 dark:text-secondary-300 uppercase truncate max-w-[90px]">{{ cat.name }}</span>
                            <span class="text-zinc-400 dark:text-secondary-500 font-mono">({{ cat.total }})</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILA SECUNDARIA: TOP INSUMOS & SOLICITUDES RECIENTES -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- TOP INSUMOS MÁS PEDIDOS -->
                <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-black text-zinc-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <span class="material-symbols-outlined text-amber-500 text-[18px]">leaderboard</span>
                                Top Insumos Pedidos
                            </h3>
                        </div>
                        <p class="text-xs text-zinc-400 dark:text-secondary-400 mb-4">
                            Los 5 insumos con mayor cantidad solicitada en {{ currentMonthName }}.
                        </p>
                    </div>

                    <div class="h-[220px] relative my-auto">
                        <canvas ref="topProductsChartRef"></canvas>
                        <div v-if="!topProducts || topProducts.length === 0" class="absolute inset-0 flex items-center justify-center">
                            <span class="text-xs font-bold text-zinc-400 dark:text-secondary-500 italic">Sin registros en el período</span>
                        </div>
                    </div>
                </div>

                <!-- SOLICITUDES RECIENTES -->
                <div class="lg:col-span-2 bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-black text-zinc-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <span class="material-symbols-outlined text-indigo-500 text-[18px]">history</span>
                                Solicitudes Recientes
                            </h3>
                            <Link 
                                :href="route('admin.consumption-requests.index')" 
                                class="text-xs font-black text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 uppercase tracking-wider inline-flex items-center gap-1 hover:underline"
                            >
                                <span>Ver todas</span>
                                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </Link>
                        </div>
                        <p class="text-xs text-zinc-400 dark:text-secondary-400 mb-4">
                            Últimas solicitudes procesadas desde tu área operativa.
                        </p>
                    </div>

                    <!-- Tabla de Alta Densidad -->
                    <div class="overflow-x-auto rounded-2xl border border-zinc-200/70 dark:border-secondary-700/70">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-zinc-50 dark:bg-secondary-900/60 text-[9px] font-black uppercase text-zinc-400 dark:text-secondary-400 tracking-widest border-b border-zinc-200/70 dark:border-secondary-700/70">
                                <tr>
                                    <th class="px-4 py-3">Código</th>
                                    <th class="px-4 py-3">Fecha</th>
                                    <th class="px-4 py-3">Almacén Origen</th>
                                    <th class="px-4 py-3 text-center">Estado</th>
                                    <th class="px-4 py-3 text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/60 font-semibold">
                                <tr 
                                    v-for="r in recentRequests" 
                                    :key="r.id" 
                                    class="hover:bg-zinc-50/80 dark:hover:bg-secondary-700/40 transition-colors"
                                >
                                    <td class="px-4 py-3 font-mono font-black text-zinc-900 dark:text-white">
                                        #{{ r.formatted_number }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 dark:text-secondary-400 font-mono text-[11px]">
                                        {{ r.date }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-secondary-300 font-bold uppercase text-[11px]">
                                        {{ r.warehouse_name }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-full border shadow-2xs"
                                            :class="getStatusBadge(r.status).bg"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full" :class="getStatusBadge(r.status).dot"></span>
                                            {{ getStatusBadge(r.status).label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link 
                                            :href="route('admin.consumption-requests.show', r.id)" 
                                            class="px-2.5 py-1 bg-zinc-100 hover:bg-indigo-600 text-zinc-700 hover:text-white dark:bg-secondary-700 dark:hover:bg-indigo-600 dark:text-secondary-200 dark:hover:text-white text-[10px] font-black uppercase rounded-lg transition-all shadow-2xs inline-flex items-center gap-1"
                                        >
                                            <span>Detalle</span>
                                            <span class="material-symbols-outlined text-[12px]">open_in_new</span>
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!recentRequests || recentRequests.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-zinc-400 dark:text-secondary-500 italic font-bold">
                                        No hay solicitudes registradas todavía.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

