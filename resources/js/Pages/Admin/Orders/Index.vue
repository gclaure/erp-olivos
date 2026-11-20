<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import OrderDetailModal from './Partials/OrderDetailModal.vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    selectedCustomer: {
        type: Object,
        default: null,
    },
    stats: {
        type: Object,
        default: () => ({
            total_orders: 0,
            active_orders: 0,
            cancelled_orders: 0,
            today_deliveries: 0,
        }),
    },
    deliveryModes: {
        type: Array,
        default: () => [],
    },
});

const localFilters = reactive({
    search: props.filters.search || '',
    registered_from: props.filters.registered_from || '',
    registered_to: props.filters.registered_to || '',
    delivery_from: props.filters.delivery_from || '',
    delivery_to: props.filters.delivery_to || '',
    delivery_mode: props.filters.delivery_mode || '',
    customer_id: props.filters.customer_id || '',
    status: props.filters.status || '',
    per_page: Number(props.filters.per_page || 10),
});

const perPageOptions = [10, 25, 50, 100];
let searchTimer = null;

const hasActiveFilters = computed(() => {
    return Boolean(
        localFilters.search ||
            localFilters.registered_from ||
            localFilters.registered_to ||
            localFilters.delivery_from ||
            localFilters.delivery_to ||
            localFilters.delivery_mode ||
            localFilters.customer_id ||
            localFilters.status
    );
});

const buildFilterParams = (extra = {}) => {
    const payload = {
        ...localFilters,
        ...extra,
    };

    const clean = {};
    Object.entries(payload).forEach(([key, val]) => {
        if (val !== null && val !== undefined && String(val).trim() !== '') {
            clean[key] = val;
        }
    });
    return clean;
};

const applyFilters = (extra = {}) => {
    router.get(route('admin.orders.index'), buildFilterParams(extra), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const handleSearchInput = () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters({ page: 1 });
    }, 300);
};

const clearAllFilters = () => {
    localFilters.search = '';
    localFilters.registered_from = '';
    localFilters.registered_to = '';
    localFilters.delivery_from = '';
    localFilters.delivery_to = '';
    localFilters.delivery_mode = '';
    localFilters.customer_id = '';
    localFilters.status = '';
    applyFilters({ page: 1 });
};

const filterByCustomer = (customer) => {
    showDetailModal.value = false;
    localFilters.customer_id = customer.id;
    applyFilters({ page: 1 });
};

const clearCustomerFilter = () => {
    localFilters.customer_id = '';
    applyFilters({ page: 1 });
};

// Paginador interactivo completo
const goToPage = (pageNumber) => {
    const current = props.orders.current_page || 1;
    const last = props.orders.last_page || 1;
    if (pageNumber < 1 || pageNumber > last || pageNumber === current) {
        return;
    }
    applyFilters({ page: pageNumber });
};

const changePerPage = () => {
    applyFilters({ page: 1 });
};

const visiblePages = computed(() => {
    const current = props.orders.current_page || 1;
    const last = props.orders.last_page || 1;
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }
    if (current <= 4) {
        return [1, 2, 3, 4, 5, '...', last];
    }
    if (current >= last - 3) {
        return [1, '...', last - 4, last - 3, last - 2, last - 1, last];
    }
    return [1, '...', current - 1, current, current + 1, '...', last];
});

// Modal de detalle completo
const showDetailModal = ref(false);
const loadingDetail = ref(false);
const activeOrderDetail = ref(null);
const activePreviousOrders = ref([]);

const openOrderDetail = async (orderId) => {
    showDetailModal.value = true;
    loadingDetail.value = true;

    try {
        const { data } = await axios.get(route('admin.orders.show', orderId));
        activeOrderDetail.value = data.order;
        activePreviousOrders.value = data.previous_orders || [];
    } catch {
        showDetailModal.value = false;
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cargar el detalle del pedido.',
        });
    } finally {
        loadingDetail.value = false;
    }
};

const confirmCancelOrder = async (order) => {
    const { value: reason, isConfirmed } = await Swal.fire({
        title: `¿Anular Reserva #${order.formatted_number}?`,
        text: `Esta acción marcará la reserva como anulada en el historial.`,
        icon: 'warning',
        input: 'text',
        inputLabel: 'Motivo de anulación (opcional)',
        inputPlaceholder: 'Ej: Cliente canceló el evento, cambio de fecha...',
        showCancelButton: true,
        confirmButtonText: 'Sí, anular pedido',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#71717a',
        reverseButtons: true,
    });

    if (!isConfirmed) return;

    try {
        await axios.post(route('admin.orders.cancel', order.id), {
            reason: reason || null,
        });

        await Swal.fire({
            icon: 'success',
            title: 'Reserva Anulada',
            text: `La reserva #${order.formatted_number} fue anulada correctamente.`,
            timer: 1800,
            showConfirmButton: false,
        });

        if (showDetailModal.value && activeOrderDetail.value?.id === order.id) {
            showDetailModal.value = false;
        }

        applyFilters();
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo anular la reserva.',
        });
    }
};

const summarizeProducts = (order) => {
    const items = order.items || [];
    if (!items.length) return '—';
    return items
        .map((it) => `${Number(it.quantity)} ${it.product_name}${it.portions_or_size ? ` (${it.portions_or_size})` : ''}`)
        .join(', ');
};
</script>

<template>
    <Head title="Historial de Pedidos" />

    <AdminLayout>
        <div class="space-y-6 w-full py-2">
            <!-- HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-zinc-900 dark:text-secondary-50 tracking-tight uppercase">
                        Registro e Historial de Pedidos
                    </h1>
                    <p class="text-xs sm:text-sm text-zinc-500 dark:text-secondary-400 mt-1 uppercase font-bold tracking-tight">
                        Consulta centralizada de reservas de clientes, productos personalizados, estado y comprobantes
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full sm:w-auto">
                    <Link
                        :href="route('admin.orders.create')"
                        class="inline-flex items-center justify-center h-11 px-5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 shadow-md gap-2 w-full sm:w-auto"
                    >
                        <span class="material-symbols-outlined text-lg">add</span>
                        Registrar Pedido
                    </Link>
                </div>
            </div>

            <!-- TARJETAS KPI OPERACIONALES -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-secondary-800 p-4 sm:p-5 rounded-2xl border border-zinc-200/60 dark:border-secondary-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="block text-[10px] font-black text-zinc-400 dark:text-secondary-400 uppercase tracking-widest">
                            Total Reservas
                        </span>
                        <div class="text-xl sm:text-2xl lg:text-3xl font-black text-zinc-900 dark:text-white mt-1">
                            {{ stats.total_orders }}
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-symbols-outlined">inventory</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-secondary-800 p-4 sm:p-5 rounded-2xl border border-zinc-200/60 dark:border-secondary-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="block text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">
                            Entregas Para Hoy
                        </span>
                        <div class="text-xl sm:text-2xl lg:text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-1">
                            {{ stats.today_deliveries }}
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-symbols-outlined">event_available</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-secondary-800 p-4 sm:p-5 rounded-2xl border border-zinc-200/60 dark:border-secondary-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="block text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">
                            Reservas Activas
                        </span>
                        <div class="text-xl sm:text-2xl lg:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ stats.active_orders }}
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-symbols-outlined">check_circle</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-secondary-800 p-4 sm:p-5 rounded-2xl border border-zinc-200/60 dark:border-secondary-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="block text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest">
                            Reservas Anuladas
                        </span>
                        <div class="text-xl sm:text-2xl lg:text-3xl font-black text-rose-600 dark:text-rose-400 mt-1">
                            {{ stats.cancelled_orders }}
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <span class="material-symbols-outlined">cancel</span>
                    </div>
                </div>
            </div>

            <!-- BANNER DE HISTORIAL DE UN CLIENTE ESPECÍFICO -->
            <div
                v-if="selectedCustomer"
                class="bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
            >
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-sm">
                        {{ selectedCustomer.full_name?.slice(0, 2).toUpperCase() }}
                    </div>
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">
                            Filtrando pedidos anteriores del cliente
                        </div>
                        <div class="text-sm font-black text-zinc-900 dark:text-white uppercase">
                            {{ selectedCustomer.full_name }} · Cel: {{ selectedCustomer.phone }}
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    @click="clearCustomerFilter"
                    class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl bg-white dark:bg-secondary-800 border border-indigo-200 dark:border-secondary-700 text-xs font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-secondary-700 transition-colors"
                >
                    <span class="material-symbols-outlined text-base">close</span>
                    Ver Todos los Clientes
                </button>
            </div>

            <!-- FILTROS -->
            <div class="bg-white dark:bg-secondary-800 rounded-2xl p-5 border border-zinc-200/60 dark:border-secondary-700 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Buscador -->
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">
                            Buscar (# Reserva, Nombre o Celular del Cliente)
                        </label>
                        <div class="relative">
                            <input
                                v-model="localFilters.search"
                                type="text"
                                placeholder="Ej: #00001, Andrea Longhi o 78631135..."
                                @input="handleSearchInput"
                                class="w-full h-11 pl-10 pr-4 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-200 placeholder-zinc-400 dark:placeholder-secondary-500 focus:border-indigo-500 focus:ring-0 transition-all"
                            />
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-zinc-400 text-lg">search</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modalidad de Entrega -->
                    <div>
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">
                            Modalidad de Entrega
                        </label>
                        <select
                            v-model="localFilters.delivery_mode"
                            @change="applyFilters({ page: 1 })"
                            class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-200 focus:border-indigo-500 focus:ring-0 transition-all uppercase"
                        >
                            <option value="">TODAS LAS MODALIDADES</option>
                            <option v-for="m in deliveryModes" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>

                    <!-- Estado de la Reserva -->
                    <div>
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">
                            Estado de la Reserva
                        </label>
                        <select
                            v-model="localFilters.status"
                            @change="applyFilters({ page: 1 })"
                            class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-200 focus:border-indigo-500 focus:ring-0 transition-all uppercase"
                        >
                            <option value="">TODOS LOS ESTADOS</option>
                            <option value="registrado">REGISTRADOS (ACTIVOS)</option>
                            <option value="anulado">ANULADOS</option>
                        </select>
                    </div>

                    <!-- Fecha de Entrega Desde -->
                    <div>
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">
                            Fecha Entrega (Desde)
                        </label>
                        <input
                            v-model="localFilters.delivery_from"
                            type="date"
                            @change="applyFilters({ page: 1 })"
                            class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-200 focus:border-indigo-500 focus:ring-0 transition-all"
                        />
                    </div>

                    <!-- Fecha de Entrega Hasta -->
                    <div>
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">
                            Fecha Entrega (Hasta)
                        </label>
                        <input
                            v-model="localFilters.delivery_to"
                            type="date"
                            @change="applyFilters({ page: 1 })"
                            class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-200 focus:border-indigo-500 focus:ring-0 transition-all"
                        />
                    </div>

                    <!-- Fecha de Registro Desde -->
                    <div>
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">
                            Fecha Registro (Desde)
                        </label>
                        <input
                            v-model="localFilters.registered_from"
                            type="date"
                            @change="applyFilters({ page: 1 })"
                            class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-200 focus:border-indigo-500 focus:ring-0 transition-all"
                        />
                    </div>

                    <!-- Fecha de Registro Hasta + Limpiar -->
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">
                                Fecha Registro (Hasta)
                            </label>
                            <input
                                v-model="localFilters.registered_to"
                                type="date"
                                @change="applyFilters({ page: 1 })"
                                class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-700 dark:text-secondary-200 focus:border-indigo-500 focus:ring-0 transition-all"
                            />
                        </div>

                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="clearAllFilters"
                            class="h-11 px-3 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 rounded-xl transition-colors border border-rose-100 dark:border-rose-800/40 flex items-center justify-center flex-shrink-0"
                            title="Limpiar filtros"
                        >
                            <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- LISTADO DUAL (Desktop Tabla + Mobile Cards según diseno-responsivo-tailwind) -->
            <div class="bg-white dark:bg-secondary-800 rounded-2xl border border-zinc-200/60 dark:border-secondary-700 shadow-sm overflow-hidden">
                <!-- 1. Vista Desktop (hidden md:block) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-secondary-700">
                        <thead class="bg-zinc-50 dark:bg-secondary-900/50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">
                                    Reserva
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">
                                    Cliente
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">
                                    Fecha y Modalidad de Entrega
                                </th>
                                <th scope="col" class="hidden lg:table-cell px-6 py-4 text-left text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">
                                    Productos Solicitados
                                </th>
                                <th scope="col" class="px-6 py-4 text-center text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th scope="col" class="px-6 py-4 text-right text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50 bg-white dark:bg-secondary-800">
                            <tr
                                v-for="order in orders.data"
                                :key="order.id"
                                class="row-hover-fix hover:bg-zinc-50/60 dark:hover:bg-secondary-700/60 transition-colors"
                            >
                                <!-- Reserva -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openOrderDetail(order.id)"
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-wider shadow-sm transition-colors"
                                    >
                                        #{{ order.formatted_number }}
                                    </button>
                                    <div class="text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest mt-1">
                                        Reg: {{ order.registered_at_formatted }}
                                    </div>
                                </td>

                                <!-- Cliente -->
                                <td class="px-6 py-4">
                                    <div class="text-xs sm:text-sm font-black text-zinc-900 dark:text-white uppercase">
                                        {{ order.customer?.full_name }}
                                    </div>
                                    <div class="text-xs font-bold text-zinc-500 dark:text-secondary-400 mt-0.5">
                                        Cel: {{ order.customer?.phone }}
                                    </div>
                                    <button
                                        v-if="(order.customer?.orders_count || 0) > 1 && localFilters.customer_id !== order.customer?.id"
                                        type="button"
                                        @click="filterByCustomer(order.customer)"
                                        class="mt-1 inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        <span class="material-symbols-outlined text-xs">history</span>
                                        {{ order.customer.orders_count }} pedidos del cliente
                                    </button>
                                </td>

                                <!-- Entrega -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs sm:text-sm font-black text-zinc-900 dark:text-white">
                                        {{ order.delivery_date_short }} · {{ order.delivery_time }}
                                    </div>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest mt-1 border"
                                        :class="order.delivery_mode === 'delivery'
                                            ? 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-800/40'
                                            : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/40'"
                                    >
                                        {{ order.delivery_mode_label }}
                                    </span>
                                </td>

                                <!-- Productos Solicitados -->
                                <td class="hidden lg:table-cell px-6 py-4 max-w-xs">
                                    <div
                                        v-for="(it, idx) in (order.items || []).slice(0, 2)"
                                        :key="idx"
                                        class="text-xs text-zinc-700 dark:text-secondary-200 truncate"
                                    >
                                        <span class="font-black text-zinc-900 dark:text-white">{{ Number(it.quantity) }} {{ it.product_name }}</span>
                                        <span v-if="it.portions_or_size" class="text-zinc-500 dark:text-secondary-400">
                                            ({{ it.portions_or_size }})
                                        </span>
                                    </div>
                                    <div v-if="(order.items || []).length > 2" class="text-[10px] font-black uppercase text-indigo-600 dark:text-indigo-400 mt-0.5">
                                        +{{ order.items.length - 2 }} producto(s) más
                                    </div>
                                </td>

                                <!-- Estado -->
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border"
                                        :class="order.is_cancelled
                                            ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/40'
                                            : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/40'"
                                    >
                                        {{ order.is_cancelled ? 'Anulado' : 'Registrado' }}
                                    </span>
                                </td>

                                <!-- Acciones -->
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <!-- Ver Detalle -->
                                        <button
                                            type="button"
                                            @click="openOrderDetail(order.id)"
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-600 hover:text-white border border-indigo-200/70 dark:border-indigo-800/40 transition-all shadow-sm"
                                            title="Ver detalle completo de la reserva"
                                        >
                                            <span class="material-symbols-outlined text-lg">visibility</span>
                                        </button>

                                        <!-- Anular Reserva -->
                                        <button
                                            v-if="!order.is_cancelled"
                                            type="button"
                                            @click="confirmCancelOrder(order)"
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-600 hover:text-white border border-rose-200/70 dark:border-rose-800/40 transition-all shadow-sm"
                                            title="Anular reserva"
                                        >
                                            <span class="material-symbols-outlined text-lg">block</span>
                                        </button>

                                        <!-- Imprimir / PDF -->
                                        <a
                                            :href="route('admin.orders.receipt-pdf', order.id)"
                                            target="_blank"
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-zinc-100 dark:bg-secondary-700 text-zinc-700 dark:text-secondary-200 hover:bg-zinc-800 hover:text-white border border-zinc-200 dark:border-secondary-600 transition-all shadow-sm"
                                            title="Imprimir o descargar comprobante PDF"
                                        >
                                            <span class="material-symbols-outlined text-lg">print</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State Desktop -->
                            <tr v-if="!orders.data?.length">
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center opacity-50">
                                        <span class="material-symbols-outlined text-5xl mb-3 text-zinc-400 dark:text-secondary-500">receipt_long</span>
                                        <p class="text-zinc-500 dark:text-secondary-400 font-black uppercase tracking-widest text-xs">
                                            No se encontraron pedidos registrados
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 2. Vista Mobile en Tarjetas Apiladas (md:hidden según diseno-responsivo-tailwind) -->
                <div class="md:hidden p-4 space-y-4">
                    <div
                        v-for="order in orders.data"
                        :key="order.id"
                        class="bg-white dark:bg-secondary-800 rounded-2xl border border-zinc-100 dark:border-secondary-700 p-4 space-y-4 shadow-sm"
                    >
                        <!-- Cabecera de la Tarjeta -->
                        <div class="flex items-start justify-between gap-2 pb-2 border-b border-zinc-100 dark:border-secondary-700">
                            <div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-indigo-600 text-white text-xs font-black uppercase tracking-wider">
                                    Reserva #{{ order.formatted_number }}
                                </span>
                                <div class="text-xs font-black text-zinc-900 dark:text-white uppercase mt-1.5">
                                    {{ order.customer?.full_name }}
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest border"
                                :class="order.is_cancelled
                                    ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/40'
                                    : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/40'"
                            >
                                {{ order.is_cancelled ? 'Anulado' : 'Registrado' }}
                            </span>
                        </div>

                        <!-- Cuerpo Etiqueta-Valor (Regla 3.2 diseno-responsivo-tailwind) -->
                        <div class="divide-y divide-zinc-100 dark:divide-secondary-700/60">
                            <div class="flex justify-between items-center py-2">
                                <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Celular</span>
                                <span class="text-xs font-bold text-zinc-900 dark:text-white text-right">{{ order.customer?.phone || '—' }}</span>
                            </div>

                            <div class="flex justify-between items-center py-2">
                                <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Fecha Registro</span>
                                <span class="text-xs font-bold text-zinc-900 dark:text-white text-right">{{ order.registered_at_formatted }}</span>
                            </div>

                            <div class="flex justify-between items-center py-2">
                                <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Entrega</span>
                                <span class="text-xs font-bold text-zinc-900 dark:text-white text-right">
                                    {{ order.delivery_date_short }} · {{ order.delivery_time }}
                                </span>
                            </div>

                            <div class="flex justify-between items-center py-2">
                                <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Modalidad</span>
                                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 text-right">{{ order.delivery_mode_label }}</span>
                            </div>

                            <div class="flex justify-between items-center py-2 gap-4">
                                <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest flex-shrink-0">Productos</span>
                                <span class="text-xs font-bold text-zinc-900 dark:text-white text-right truncate">{{ summarizeProducts(order) }}</span>
                            </div>

                            <div v-if="order.is_cancelled" class="flex justify-between items-center py-2">
                                <span class="text-[10px] font-semibold text-rose-500 uppercase tracking-widest">Motivo Anulación</span>
                                <span class="text-xs font-bold text-rose-600 dark:text-rose-400 text-right">{{ order.cancellation_reason || 'Sin motivo' }}</span>
                            </div>
                        </div>

                        <!-- Acciones Circulares Móviles (Regla 3.3 diseno-responsivo-tailwind: paridad exacta) -->
                        <div class="flex justify-end items-center gap-2.5 pt-2 border-t border-zinc-100 dark:border-secondary-700">
                            <button
                                type="button"
                                @click="openOrderDetail(order.id)"
                                class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center"
                                title="Ver detalle"
                            >
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </button>

                            <button
                                v-if="!order.is_cancelled"
                                type="button"
                                @click="confirmCancelOrder(order)"
                                class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center"
                                title="Anular reserva"
                            >
                                <span class="material-symbols-outlined text-lg">block</span>
                            </button>

                            <a
                                :href="route('admin.orders.receipt-pdf', order.id)"
                                target="_blank"
                                class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-secondary-700 text-zinc-700 dark:text-secondary-200 flex items-center justify-center"
                                title="Imprimir PDF"
                            >
                                <span class="material-symbols-outlined text-lg">print</span>
                            </a>
                        </div>
                    </div>

                    <!-- Empty State Mobile -->
                    <div v-if="!orders.data?.length" class="p-10 text-center">
                        <div class="flex flex-col items-center opacity-50 py-6">
                            <span class="material-symbols-outlined text-4xl mb-3 text-zinc-400 dark:text-secondary-500">receipt_long</span>
                            <p class="text-zinc-500 dark:text-secondary-400 font-black uppercase tracking-widest text-xs">
                                No se encontraron pedidos registrados
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PAGINADOR INTERACTIVO COMPLETO -->
                <div
                    v-if="orders.total > 0"
                    class="px-4 sm:px-6 py-4 bg-zinc-50 dark:bg-secondary-900/80 border-t border-zinc-200 dark:border-secondary-700 flex flex-col sm:flex-row items-center justify-between gap-4"
                >
                    <div class="text-xs sm:text-sm font-medium text-zinc-500 dark:text-secondary-400">
                        Mostrando del <span class="font-bold text-zinc-800 dark:text-secondary-100">{{ orders.from || 0 }}</span>
                        al <span class="font-bold text-zinc-800 dark:text-secondary-100">{{ orders.to || 0 }}</span>
                        total de <span class="font-bold text-zinc-800 dark:text-secondary-100">{{ orders.total || 0 }}</span>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-1.5 sm:gap-2">
                        <!-- Primera página « -->
                        <button
                            type="button"
                            @click="goToPage(1)"
                            :disabled="orders.current_page <= 1"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-bold text-zinc-500 dark:text-secondary-400 hover:bg-zinc-200/70 dark:hover:bg-secondary-700 disabled:opacity-35 disabled:pointer-events-none transition-colors"
                            title="Primera página"
                        >
                            «
                        </button>

                        <!-- Página anterior ‹ -->
                        <button
                            type="button"
                            @click="goToPage(orders.current_page - 1)"
                            :disabled="orders.current_page <= 1"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-bold text-zinc-500 dark:text-secondary-400 hover:bg-zinc-200/70 dark:hover:bg-secondary-700 disabled:opacity-35 disabled:pointer-events-none transition-colors"
                            title="Página anterior"
                        >
                            ‹
                        </button>

                        <!-- Números de página -->
                        <template v-for="(pageItem, idx) in visiblePages" :key="idx">
                            <span
                                v-if="pageItem === '...'"
                                class="w-8 h-8 flex items-center justify-center text-xs font-bold text-zinc-400 dark:text-secondary-500 select-none"
                            >
                                ...
                            </span>
                            <button
                                v-else
                                type="button"
                                @click="goToPage(pageItem)"
                                :class="[
                                    'w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all',
                                    pageItem === orders.current_page
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-zinc-600 dark:text-secondary-300 hover:bg-zinc-200/70 dark:hover:bg-secondary-700'
                                ]"
                            >
                                {{ pageItem }}
                            </button>
                        </template>

                        <!-- Página siguiente › -->
                        <button
                            type="button"
                            @click="goToPage(orders.current_page + 1)"
                            :disabled="orders.current_page >= orders.last_page"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-bold text-zinc-500 dark:text-secondary-400 hover:bg-zinc-200/70 dark:hover:bg-secondary-700 disabled:opacity-35 disabled:pointer-events-none transition-colors"
                            title="Página siguiente"
                        >
                            ›
                        </button>

                        <!-- Última página » -->
                        <button
                            type="button"
                            @click="goToPage(orders.last_page)"
                            :disabled="orders.current_page >= orders.last_page"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-bold text-zinc-500 dark:text-secondary-400 hover:bg-zinc-200/70 dark:hover:bg-secondary-700 disabled:opacity-35 disabled:pointer-events-none transition-colors"
                            title="Última página"
                        >
                            »
                        </button>

                        <!-- Selector de cantidad por página -->
                        <div class="ml-1 sm:ml-2">
                            <select
                                v-model.number="localFilters.per_page"
                                @change="changePerPage"
                                aria-label="Registros por página"
                                class="h-9 pl-3 pr-8 rounded-xl border border-zinc-300 dark:border-secondary-700 bg-white dark:bg-secondary-900 text-xs font-bold text-zinc-800 dark:text-secondary-100 focus:border-indigo-500 focus:ring-0 transition-all cursor-pointer"
                            >
                                <option v-for="opt in perPageOptions" :key="opt" :value="opt">
                                    {{ opt }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Detalle Completo (Estilo Reserva #00001) -->
        <OrderDetailModal
            :show="showDetailModal"
            :loading="loadingDetail"
            :order="activeOrderDetail"
            :previousOrders="activePreviousOrders"
            @close="showDetailModal = false"
            @cancel-order="confirmCancelOrder"
            @select-order="openOrderDetail"
            @filter-by-customer="filterByCustomer"
        />
    </AdminLayout>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.dark .row-hover-fix:hover {
    background-color: rgb(var(--color-secondary-700) / 0.5) !important;
}

.dark input:not([type='checkbox']):not([type='radio']):not([type='file']),
.dark textarea,
.dark select {
    background-color: rgb(var(--color-secondary-900)) !important;
    color: rgb(var(--color-text-primary)) !important;
    border-color: rgb(var(--color-secondary-700));
    color-scheme: dark;
}

.dark input::placeholder,
.dark textarea::placeholder {
    color: rgb(var(--color-secondary-500)) !important;
}
</style>
