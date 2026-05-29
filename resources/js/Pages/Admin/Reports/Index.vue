<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Chart from 'chart.js/auto';
import debounce from 'lodash/debounce';

const props = defineProps({
    filters: Object,
    warehouses: Array,
    metrics: Object,
    salesTrend: Object,
    salesByCategory: Object,
    topProducts: Object,
    stockValuationTrend: Object,
    inventoryFlow: Object,
    abcAnalysis: Object
});

const activeTab = ref('sales');
const dateRange = ref(props.filters.dateRange || 'last_30_days');
const warehouseId = ref(props.filters.warehouseId || '');
const productSearch = ref(props.filters.productName || '');
const productId = ref(props.filters.productId || '');
const products = ref([]);
const isSearching = ref(false);
const showProductList = ref(false);

// Referencias de los objetos Chart
const charts = {
    salesTrend: null,
    categoryPie: null,
    topProducts: null,
    stockValuation: null,
    movements: null
};

const destroyCharts = () => {
    Object.keys(charts).forEach(key => {
        if (charts[key]) {
            charts[key].destroy();
            charts[key] = null;
        }
    });
};

const initCharts = () => {
    destroyCharts();

    // Sales Trend Chart
    const trendCtx = document.getElementById('salesTrendChart');
    if (trendCtx && props.salesTrend?.labels?.length > 0) {
        charts.salesTrend = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: props.salesTrend.labels,
                datasets: [
                    {
                        label: 'Ingresos',
                        data: props.salesTrend.revenue,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Utilidad',
                        data: props.salesTrend.profit,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Category Pie Chart
    const pieCtx = document.getElementById('categoryPieChart');
    if (pieCtx && props.salesByCategory?.labels?.length > 0) {
        charts.categoryPie = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: props.salesByCategory.labels,
                datasets: [{
                    data: props.salesByCategory.values,
                    backgroundColor: [
                        '#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                        '#ec4899', '#06b6d4', '#f97316'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    }

    // Top Products Chart
    const topCtx = document.getElementById('topProductsChart');
    if (topCtx && props.topProducts?.labels?.length > 0) {
        charts.topProducts = new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: props.topProducts.labels,
                datasets: [{
                    label: 'Ingresos por Producto',
                    data: props.topProducts.revenue,
                    backgroundColor: '#4f46e5',
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // Stock Valuation Chart
    const stockCtx = document.getElementById('stockValuationChart');
    if (stockCtx && props.stockValuationTrend?.labels?.length > 0) {
        charts.stockValuation = new Chart(stockCtx, {
            type: 'line',
            data: {
                labels: props.stockValuationTrend.labels,
                datasets: [{
                    label: 'Valor de Inventario',
                    data: props.stockValuationTrend.values,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Movements Chart
    const movCtx = document.getElementById('movementsChart');
    if (movCtx && props.inventoryFlow?.labels?.length > 0) {
        charts.movements = new Chart(movCtx, {
            type: 'bar',
            data: {
                labels: props.inventoryFlow.labels,
                datasets: [
                    {
                        label: 'Entradas',
                        data: props.inventoryFlow.inputs,
                        backgroundColor: '#10b981'
                    },
                    {
                        label: 'Salidas',
                        data: props.inventoryFlow.outputs,
                        backgroundColor: '#ef4444'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
};

const applyFilters = () => {
    router.get(route('admin.reports.index'), {
        dateRange: dateRange.value,
        warehouseId: warehouseId.value,
        productId: productId.value
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            initCharts();
        }
    });
};

watch([dateRange, warehouseId, productId], () => {
    applyFilters();
});

watch(activeTab, () => {
    setTimeout(initCharts, 50); // Pequeño delay para asegurar que el DOM se actualizó tras el cambio de pestaña
});

const searchProducts = debounce(async (e) => {
    const query = e.target.value;
    if (query.length < 2) {
        products.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const response = await axios.get(route('admin.products.search'), {
            params: { query }
        });
        products.value = response.data;
    } catch (error) {
        console.error('Error searching products:', error);
    } finally {
        isSearching.value = false;
    }
}, 300);

const handleClickOutside = (event) => {
    const searchContainer = document.getElementById('product-search-container');
    if (searchContainer && !searchContainer.contains(event.target)) {
        showProductList.value = false;
    }
};

onMounted(() => {
    initCharts();
    window.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    destroyCharts();
    window.removeEventListener('click', handleClickOutside);
});

const formatNumber = (val) => {
    try {
        const num = parseFloat(val);
        if (isNaN(num)) return '0,00';
        return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2 }).format(num);
    } catch (e) {
        return '0,00';
    }
};

const margin = computed(() => {
    const revenue = props.metrics?.revenue || 0;
    const profit = props.metrics?.profit || 0;
    return revenue > 0 ? (profit / revenue) * 100 : 0;
});

const selectProduct = (product) => {
    productId.value = product.id;
    productSearch.value = product.name;
    showProductList.value = false;
};

const clearProduct = () => {
    productId.value = '';
    productSearch.value = '';
    showProductList.value = false;
};
</script>

<template>
    <Head title="Reports BI" />

    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Header & Global Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 transition-colors text-zinc-900 dark:text-white">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-2xl font-black leading-tight">Dashboard de Business Intelligence</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium tracking-wide gap-2 flex items-center mt-1">
                            <span class="material-symbols-outlined text-[18px] text-indigo-500">analytics</span>
                            Análisis Estratégico Multidimensional
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 flex-1 lg:max-w-3xl">
                        <!-- Período Filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Período</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">calendar_today</span>
                                <select v-model="dateRange" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl pl-10 pr-4 py-2.5 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="last_30_days">Últimos 30 días</option>
                                    <option value="this_month">Este mes</option>
                                    <option value="last_month">Mes pasado</option>
                                    <option value="this_year">Este año</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] pointer-events-none">expand_more</span>
                            </div>
                        </div>

                        <!-- Almacén Filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Almacén</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">home</span>
                                <select v-model="warehouseId" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl pl-10 pr-4 py-2.5 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">Todos los almacenes</option>
                                    <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] pointer-events-none">expand_more</span>
                            </div>
                        </div>

                        <!-- Producto Filter -->
                        <div class="relative" id="product-search-container">
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Producto</label>
                            <div class="relative">
                                <input 
                                    v-model="productSearch" 
                                    @input="searchProducts"
                                    @focus="showProductList = true"
                                    type="text" 
                                    placeholder="Filtrar por producto" 
                                    class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                                >
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                                    <div v-if="isSearching" class="w-4 h-4 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                                    <span v-else class="material-symbols-outlined text-gray-400 text-[18px] pointer-events-none">unfold_more</span>
                                    
                                    <button v-if="productId" @click="clearProduct" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                        <span class="material-symbols-outlined text-[16px]">close</span>
                                    </button>
                                </div>
                            </div>
                            <!-- Autocomplete list -->
                            <div v-if="showProductList && (products.length > 0 || isSearching || (productSearch.length > 0 && !isSearching))" 
                                 class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                                
                                <div v-if="isSearching" class="p-4 text-center text-sm text-gray-500">
                                    Buscando productos...
                                </div>
                                
                                <template v-else-if="products.length > 0">
                                    <div v-for="p in products" 
                                         :key="p.id" 
                                         @click="selectProduct(p)" 
                                         class="px-4 py-3 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer dark:text-white border-b border-gray-100 dark:border-gray-700 last:border-0 flex flex-col">
                                        <span class="font-bold">{{ p.name }}</span>
                                        <span class="text-[10px] text-gray-400 uppercase tracking-widest">{{ p.code }}</span>
                                    </div>
                                </template>
                                
                                <div v-else-if="productSearch.length > 0" class="p-4 text-center text-sm text-gray-500">
                                    No se encontraron productos.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="overflow-x-auto no-scrollbar -mx-6 px-6 sm:mx-0 sm:px-0">
                <nav class="flex space-x-1 p-1 rounded-xl w-fit shadow-sm border transition-colors bg-zinc-100 border-zinc-200 dark:bg-zinc-900 dark:border-zinc-800" aria-label="Tabs">
                    <button @click="activeTab = 'sales'" 
                            class="px-4 sm:px-6 py-2 text-[11px] sm:text-sm font-bold rounded-lg transition-all duration-200 whitespace-nowrap flex-shrink-0"
                            :class="activeTab === 'sales' ? 'bg-white text-indigo-600 shadow-md dark:bg-indigo-600 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'">
                        Ventas y Rentabilidad
                    </button>

                    <button @click="activeTab = 'stock'" 
                            class="px-4 sm:px-6 py-2 text-[11px] sm:text-sm font-bold rounded-lg transition-all duration-200 whitespace-nowrap flex-shrink-0"
                            :class="activeTab === 'stock' ? 'bg-white text-indigo-600 shadow-md dark:bg-indigo-600 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'">
                        Inventario y Rotación
                    </button>

                    <button @click="activeTab = 'abc'" 
                            class="px-4 sm:px-6 py-2 text-[11px] sm:text-sm font-bold rounded-lg transition-all duration-200 whitespace-nowrap flex-shrink-0"
                            :class="activeTab === 'abc' ? 'bg-white text-indigo-600 shadow-md dark:bg-indigo-600 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'">
                        Análisis ABC
                    </button>
                </nav>
            </div>

            <!-- Content Area -->
            <div class="animate-fade-in">
                <div v-if="activeTab === 'sales'">
                    <!-- Metrics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-indigo-600 rounded-2xl p-6 text-white shadow-xl shadow-indigo-100 dark:shadow-none relative overflow-hidden group">
                            <p class="text-indigo-100 text-xs font-black uppercase tracking-widest mb-1">Ingresos Totales</p>
                            <h3 class="text-3xl font-black">Bs. {{ formatNumber(metrics.revenue) }}</h3>
                            <div class="flex items-center mt-4 text-xs font-bold text-indigo-200">
                                <span class="material-symbols-outlined text-[16px] mr-1">trending_up</span>
                                <span>{{ formatNumber(metrics.quantity) }} unidades vendidas</span>
                            </div>
                        </div>

                        <div class="bg-emerald-600 rounded-2xl p-6 text-white shadow-xl shadow-emerald-100 dark:shadow-none relative overflow-hidden group">
                            <p class="text-emerald-100 text-xs font-black uppercase tracking-widest mb-1">Utilidad Bruta</p>
                            <h3 class="text-3xl font-black">Bs. {{ formatNumber(metrics.profit) }}</h3>
                            <div class="flex items-center mt-4 text-xs font-bold text-emerald-200">
                                <span class="material-symbols-outlined text-[16px] mr-1">pie_chart</span>
                                <span>Margen: {{ formatNumber(margin) }}% de rentabilidad</span>
                            </div>
                        </div>

                        <div class="bg-amber-500 rounded-2xl p-6 text-white shadow-xl shadow-amber-100 dark:shadow-none relative overflow-hidden group">
                            <p class="text-amber-100 text-xs font-black uppercase tracking-widest mb-1">Costo de Ventas</p>
                            <h3 class="text-3xl font-black">Bs. {{ formatNumber(metrics.cost) }}</h3>
                            <div class="flex items-center mt-4 text-xs font-bold text-amber-100">
                                <span class="material-symbols-outlined text-[16px] mr-1">account_balance_wallet</span>
                                <span>Capital Re-invertible</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 transition-colors">
                            <h5 class="text-sm font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Tendencia de Ingresos y Utilidad</h5>
                            <div class="h-80">
                                <canvas id="salesTrendChart"></canvas>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 transition-colors">
                            <h5 class="text-sm font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Ingresos por Categoría</h5>
                            <div class="h-80">
                                <canvas id="categoryPieChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 mb-8 transition-colors">
                        <h5 class="text-sm font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Top 10 Productos por Ventas</h5>
                        <div class="h-96">
                            <canvas id="topProductsChart"></canvas>
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'stock'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 transition-colors">
                            <h5 class="text-sm font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Evolución de Valor en Stock</h5>
                            <div class="h-80">
                                <canvas id="stockValuationChart"></canvas>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 transition-colors">
                            <h5 class="text-sm font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Flujo de Mercadería (Entradas vs Salidas)</h5>
                            <div class="h-80">
                                <canvas id="movementsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'abc'">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/30 rounded-2xl p-6 transition-colors shadow-sm">
                            <span class="bg-indigo-600 text-white text-xs font-black px-3 py-1 rounded-full mb-4 inline-block shadow-sm shadow-indigo-200 dark:shadow-none italic tracking-tighter">CLASE A</span>
                            <h4 class="text-4xl font-black text-indigo-900 dark:text-indigo-300 tracking-tighter">{{ abcAnalysis.A }}</h4>
                            <p class="text-[10px] font-bold text-indigo-700 dark:text-indigo-400 mt-2 uppercase tracking-widest opacity-90">80% de rentabilidad acumulada</p>
                        </div>

                        <div class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/30 rounded-2xl p-6 transition-colors shadow-sm">
                            <span class="bg-emerald-600 text-white text-xs font-black px-3 py-1 rounded-full mb-4 inline-block shadow-sm shadow-emerald-200 dark:shadow-none italic tracking-tighter">CLASE B</span>
                            <h4 class="text-4xl font-black text-emerald-900 dark:text-emerald-300 tracking-tighter">{{ abcAnalysis.B }}</h4>
                            <p class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 mt-2 uppercase tracking-widest opacity-90">15% de rentabilidad acumulada</p>
                        </div>

                        <div class="bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-6 transition-colors shadow-sm">
                            <span class="bg-zinc-600 text-white text-xs font-black px-3 py-1 rounded-full mb-4 inline-block shadow-sm shadow-zinc-200 dark:shadow-none italic tracking-tighter">CLASE C</span>
                            <h4 class="text-4xl font-black text-zinc-900 dark:text-zinc-300 tracking-tighter">{{ abcAnalysis.C }}</h4>
                            <p class="text-[10px] font-bold text-zinc-700 dark:text-zinc-400 mt-2 uppercase tracking-widest opacity-90">5% de rentabilidad acumulada</p>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-8 text-center max-w-2xl mx-auto shadow-sm transition-colors">
                        <h5 class="text-xl font-black text-gray-900 dark:text-white mb-4 tracking-tight">Análisis de Pareto Automatizado</h5>
                        <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed mb-6 font-medium">
                            Nuestro sistema clasifica automáticamente sus productos basándose en su aportación económica histórico acumulada. Esto le permite priorizar el tiempo y los recursos financieros en los productos que realmente mueven su negocio.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Custom Select styling to match screenshot */
select {
    background-image: none;
}

/* Hide scrollbar for Chrome, Safari and Opera */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.no-scrollbar {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
</style>
