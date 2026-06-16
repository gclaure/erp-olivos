<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';
import {
    solicitarPermisoNotificaciones,
    mostrarNotificacionBrowser,
    actualizarBadgeTab,
    actualizarFaviconBadge
} from '@/composables/useNotificacionesBrowser.js';

const page = usePage();
const isWarehouseRole = computed(() => {
    const roles = page.props.auth.user?.roles || [];
    return roles.includes('Almacén') || roles.includes('almacen');
});
const isConsumidor = computed(() => {
    const roles = page.props.auth.user?.roles || [];
    return roles.includes('Consumidor') || roles.includes('consumidor');
});

const props = defineProps({
    requests: Object,
    filters: Object
});

defineOptions({ layout: AdminLayout });

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const order = ref(props.filters.order || 'desc');

const localRequests = ref(props.requests?.data || []);

watch(() => props.requests, (newVal) => {
    localRequests.value = newVal?.data || [];
}, { deep: true });

const activeBranchId = computed(() => page.props.activeBranch?.id);
let currentSubscription = null;

// Contador de nuevas solicitudes llegadas en tiempo real (para badge de pestaña)
const nuevasSolicitudes = ref(0);

const limpiarBadges = () => {
    nuevasSolicitudes.value = 0;
    actualizarBadgeTab(0);
    actualizarFaviconBadge(0);
};

const subscribeToBranch = (branchId) => {
    if (currentSubscription) {
        window.Echo.leave(`sucursal.${currentSubscription}`);
        currentSubscription = null;
    }

    if (window.Echo && branchId) {
        currentSubscription = branchId;
        window.Echo.private(`sucursal.${branchId}`)
            .listen('.consumption-request.created', (e) => {
                const exists = localRequests.value.some(r => r.id === e.request.id);
                if (!exists) {
                    localRequests.value.unshift(e.request);

                    // Badge en pestaña y favicon
                    nuevasSolicitudes.value++;
                    actualizarBadgeTab(nuevasSolicitudes.value);
                    actualizarFaviconBadge(nuevasSolicitudes.value);

                    // Toast en pantalla
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: `Nueva solicitud de consumo #${e.request.formatted_number}`,
                        text: `Solicitado por ${e.request.requested_by}`,
                        showConfirmButton: false,
                        timer: 4500,
                        timerProgressBar: true
                    });

                    // Notificación nativa del sistema operativo con sonido
                    mostrarNotificacionBrowser(
                        `📋 Solicitud #${e.request.formatted_number}`,
                        `Área: ${e.request.requested_by}`,
                        route('admin.consumption-requests.index'),
                        true
                    );
                }
            });
    }
};

watch(activeBranchId, (newBranchId) => {
    subscribeToBranch(newBranchId);
}, { immediate: true });

// Solicitar permiso y limpiar badges cuando el usuario regresa a la pestaña
onMounted(async () => {
    await solicitarPermisoNotificaciones();

    window.addEventListener('focus', limpiarBadges);
    window.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') limpiarBadges();
    });
});

onUnmounted(() => {
    if (window.Echo && currentSubscription) {
        window.Echo.leave(`sucursal.${currentSubscription}`);
    }
    window.removeEventListener('focus', limpiarBadges);
});

const getStatusBadgeClass = (statusVal) => {
    switch (statusVal) {
        case 'pendiente':
            return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/10 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20';
        case 'entregado':
            return 'bg-emerald-600 text-white font-black shadow-md shadow-emerald-500/20 dark:bg-emerald-500';
        case 'parcial':
            return 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/10 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20';
        case 'despachado':
            return 'bg-fuchsia-50 text-fuchsia-700 ring-1 ring-fuchsia-600/10 dark:bg-fuchsia-500/10 dark:text-fuchsia-400 dark:ring-fuchsia-500/20';
        case 'despachado_parcial':
            return 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/10 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/20';
        case 'cancelado':
            return 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20';
        default:
            return 'bg-slate-50 text-slate-700 ring-1 ring-slate-600/10 dark:bg-secondary-500/10 dark:text-secondary-400 dark:ring-secondary-500/20';
    }
};

const getStatusLabel = (statusVal) => {
    switch (statusVal) {
        case 'pendiente': return 'Pendiente';
        case 'entregado': return 'Entregado';
        case 'parcial': return 'Despacho Parcial';
        case 'despachado': return 'Despachado';
        case 'despachado_parcial': return 'Despacho Parcial';
        case 'cancelado': return 'Cancelado';
        default: return statusVal;
    }
};

const applyFilters = debounce(() => {
    router.get(route('admin.consumption-requests.index'), {
        search: search.value,
        status: status.value,
        start_date: startDate.value,
        end_date: endDate.value,
        order: order.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch([search, status, startDate, endDate, order], () => {
    applyFilters();
});

const clearFilters = () => {
    search.value = '';
    status.value = '';
    startDate.value = '';
    endDate.value = '';
    order.value = 'desc';
};

// Selección de solicitudes para consolidación de compras
const selectedRequestIds = ref([]);

// Filtrar las solicitudes que son aptas para consolidar (con faltantes y pendientes/parciales)
const eligibleRequests = computed(() => {
    if (!localRequests.value) return [];
    return localRequests.value.filter(req => 
        (req.status === 'pendiente' || req.status === 'parcial') && req.has_missing_stock
    );
});

// Comprobar si todas las solicitudes elegibles están seleccionadas
const areAllEligibleSelected = computed(() => {
    if (eligibleRequests.value.length === 0) return false;
    return eligibleRequests.value.every(req => selectedRequestIds.value.includes(req.id));
});

// Seleccionar/Deseleccionar todas las solicitudes elegibles
const toggleSelectAllEligible = (event) => {
    if (event.target.checked) {
        selectedRequestIds.value = eligibleRequests.value.map(req => req.id);
    } else {
        selectedRequestIds.value = [];
    }
};

// Redirigir al creador de compras consolidando los faltantes de las seleccionadas
const handleConsolidatePurchases = () => {
    if (selectedRequestIds.value.length === 0) return;
    
    router.get(route('admin.purchase-orders.create'), {
        consumption_request_ids: selectedRequestIds.value.join(',')
    });
};
</script>

<template>
    <Head title="Solicitudes de Consumo" />

    <div class="space-y-6 w-full py-2">
        <!-- HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-zinc-900 dark:text-secondary-50 tracking-tight uppercase">
                    Solicitudes de Consumo
                </h1>
                <p class="text-xs sm:text-sm text-zinc-500 dark:text-secondary-400 mt-1 uppercase font-bold tracking-tight">
                    Gestión y control de solicitudes internas de insumos y materiales
                </p>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <button 
                    v-if="selectedRequestIds.length > 0 && !isConsumidor"
                    @click="handleConsolidatePurchases"
                    class="inline-flex items-center justify-center h-11 px-5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 shadow-md hover:shadow-indigo-500/10 gap-2 w-full sm:w-auto"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    Consolidar Faltantes ({{ selectedRequestIds.length }})
                </button>

                <Link 
                    v-if="!isWarehouseRole"
                    :href="route('admin.consumption-requests.create')" 
                    class="inline-flex items-center justify-center h-11 px-5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 shadow-md hover:shadow-blue-500/10 gap-2 w-full sm:w-auto"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nueva Solicitud
                </Link>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="bg-white dark:bg-secondary-800 rounded-2xl p-5 border border-zinc-200/60 dark:border-secondary-700 shadow-sm transition-all duration-300">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Buscador -->
                <div class="col-span-1">
                    <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">Buscar</label>
                    <div class="relative">
                        <input 
                            v-model="search"
                            type="text"
                            placeholder="Nro, área o notas..."
                            class="w-full h-11 pl-10 pr-4 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-300 focus:border-blue-500 focus:ring-0 transition-all uppercase"
                        />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-zinc-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.637 10.637Z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Estado -->
                <div class="col-span-1">
                    <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">Estado</label>
                    <select 
                        v-model="status"
                        class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-300 focus:border-blue-500 focus:ring-0 transition-all uppercase"
                    >
                        <option value="">TODOS LOS ESTADOS</option>
                        <option value="pendiente">PENDIENTE</option>
                        <option value="entregado">ENTREGADO</option>
                        <option value="parcial">DESPACHO PARCIAL</option>
                        <option value="compras_generado">COMPRA SOLICITADA</option>
                        <option value="cancelado">CANCELADO</option>
                    </select>
                </div>

                <!-- Orden -->
                <div class="col-span-1">
                    <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">Orden</label>
                    <select 
                        v-model="order"
                        class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-300 focus:border-blue-500 focus:ring-0 transition-all uppercase"
                    >
                        <option value="desc">⏳ Recientes Primero</option>
                        <option value="asc">⌛ Antiguos Primero</option>
                    </select>
                </div>

                <!-- Fecha Desde -->
                <div class="col-span-1">
                    <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">Desde</label>
                    <input 
                        v-model="startDate"
                        type="date"
                        class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-300 focus:border-blue-500 focus:ring-0 transition-all uppercase"
                    />
                </div>

                <!-- Fecha Hasta -->
                <div class="col-span-1 flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">Hasta</label>
                        <input 
                            v-model="endDate"
                            type="date"
                            class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-300 focus:border-blue-500 focus:ring-0 transition-all uppercase"
                        />
                    </div>
                    
                    <button 
                        v-if="search || status || startDate || endDate || order !== 'desc'"
                        @click="clearFilters"
                        class="h-11 px-3 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition-colors border border-rose-100 flex items-center justify-center flex-shrink-0"
                        title="Limpiar filtros"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- LISTADO -->
        <div class="bg-white dark:bg-secondary-800 rounded-2xl border border-zinc-200/60 dark:border-secondary-700 shadow-sm overflow-hidden transition-all duration-300">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-secondary-700">
                    <thead class="bg-zinc-50 dark:bg-secondary-900/50">
                        <tr>
                            <th v-if="!isConsumidor" scope="col" class="w-12 px-6 py-4 text-center text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">
                                <input 
                                    v-if="eligibleRequests.length > 0"
                                    type="checkbox"
                                    :checked="areAllEligibleSelected"
                                    @change="toggleSelectAllEligible"
                                    class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-secondary-700 dark:bg-secondary-900 w-4 h-4 cursor-pointer"
                                    title="Seleccionar todas las que faltan stock"
                                />
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Número</th>
                            <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Fecha</th>
                            <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Área Solicitante</th>
                            <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Almacén Origen</th>
                            <th scope="col" class="hidden sm:table-cell px-6 py-4 text-left text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Solicitado Por</th>
                            <th scope="col" class="px-6 py-4 text-center text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Estado</th>
                            <th scope="col" class="px-6 py-4 text-right text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50 bg-white dark:bg-secondary-800">
                        <tr 
                            v-for="req in localRequests" 
                            :key="req.id"
                            :class="[
                                selectedRequestIds.includes(req.id) ? 'bg-indigo-50/20 dark:bg-indigo-950/10' : '',
                                'hover:bg-zinc-50/50 dark:hover:bg-secondary-700/10 transition-colors'
                            ]"
                        >
                            <!-- Checkbox Selección -->
                            <td v-if="!isConsumidor" class="px-6 py-4 text-center">
                                <input 
                                    v-if="(req.status === 'pendiente' || req.status === 'parcial') && req.has_missing_stock"
                                    type="checkbox"
                                    v-model="selectedRequestIds"
                                    :value="req.id"
                                    class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-secondary-700 dark:bg-secondary-900 w-4 h-4 cursor-pointer"
                                />
                                <span v-else class="text-zinc-300 dark:text-secondary-700">-</span>
                            </td>
                            <!-- Número -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-black text-zinc-800 dark:text-secondary-100 uppercase tracking-tight">
                                    #{{ req.formatted_number || 'S/N' }}
                                </span>
                            </td>

                            <!-- Fecha -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-secondary-900 flex items-center justify-center text-slate-600 dark:text-secondary-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-zinc-700 dark:text-secondary-300 tracking-tight">{{ req.date_formatted }}</p>
                                        <p class="text-[9px] text-zinc-400 dark:text-secondary-500 font-bold uppercase tracking-wider mt-0.5">{{ req.created_at_time }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Área Solicitante -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-black text-zinc-800 dark:text-secondary-100 uppercase tracking-tight">
                                    {{ req.requested_by }}
                                </span>
                            </td>

                            <!-- Almacén -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-semibold text-zinc-600 dark:text-secondary-400 uppercase tracking-tight">
                                    {{ req.warehouse_name }}
                                </span>
                            </td>

                            <!-- Solicitado Por -->
                            <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-semibold text-zinc-600 dark:text-secondary-400 uppercase tracking-tight">
                                    {{ req.user_name }}
                                </span>
                            </td>

                            <!-- Estado -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-col">
                                    <span 
                                        v-if="req.status === 'entregado'"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-[11px] font-black uppercase tracking-widest bg-emerald-600 text-white shadow-md shadow-emerald-500/20 dark:bg-emerald-500"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4.12-5.671Z" clip-rule="evenodd" />
                                        </svg>
                                        ENTREGADO
                                    </span>
                                    <span 
                                        v-else
                                        :class="['inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider', getStatusBadgeClass(req.status)]"
                                    >
                                        {{ getStatusLabel(req.status) }}
                                    </span>
                                    <span 
                                        v-if="(req.status === 'pendiente' || req.status === 'parcial') && req.has_missing_stock"
                                        class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-500 bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 rounded-full ring-1 ring-amber-500/30"
                                        title="Falta stock en almacén para cumplir la solicitud"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Falta Stock
                                    </span>
                                </div>
                            </td>

                            <!-- Acciones -->
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <Link 
                                    :href="route('admin.consumption-requests.show', { consumption_request: req.id })"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-secondary-900 dark:hover:bg-secondary-950 text-slate-700 dark:text-secondary-300 rounded-lg text-[10px] font-black uppercase tracking-wider transition-colors border border-slate-200/50 dark:border-secondary-800"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    Ver Detalle
                                </Link>
                            </td>
                        </tr>

                        <!-- ESTADO VACÍO -->
                        <tr v-if="localRequests.length === 0">
                            <td :colspan="isConsumidor ? 7 : 8" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-slate-50 dark:bg-secondary-900 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-zinc-100 dark:border-secondary-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-slate-300 dark:text-secondary-700">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>
                                <p class="text-xs font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-tight">No se encontraron solicitudes</p>
                                <p class="text-[10px] text-zinc-400 dark:text-secondary-500 mt-1 max-w-[250px] mx-auto uppercase font-bold tracking-tight">Pruebe ajustando los filtros de búsqueda o registre una nueva solicitud.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div 
                v-if="requests.meta && requests.meta.last_page > 1"
                class="px-6 py-4 bg-zinc-50 dark:bg-secondary-900/30 border-t border-zinc-100 dark:border-secondary-700 flex items-center justify-between"
            >
                <div class="text-xs font-semibold text-zinc-500 dark:text-secondary-400 uppercase tracking-tight">
                    Mostrando página {{ requests.meta.current_page }} de {{ requests.meta.last_page }}
                </div>
                <div class="flex gap-2">
                    <Link 
                        v-if="requests.links.prev"
                        :href="requests.links.prev"
                        class="px-3 py-1.5 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-lg text-[10px] font-black uppercase text-zinc-700 dark:text-secondary-300 hover:bg-zinc-50 dark:hover:bg-secondary-700/50 transition-colors"
                    >
                        Anterior
                    </Link>
                    <Link 
                        v-if="requests.links.next"
                        :href="requests.links.next"
                        class="px-3 py-1.5 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-lg text-[10px] font-black uppercase text-zinc-700 dark:text-secondary-300 hover:bg-zinc-50 dark:hover:bg-secondary-700/50 transition-colors"
                    >
                        Siguiente
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
