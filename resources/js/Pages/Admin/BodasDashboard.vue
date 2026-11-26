<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({ month: new Date().getMonth() + 1, year: new Date().getFullYear() }),
    },
    stats: {
        type: Object,
        default: () => ({
            registered_today: 0,
            total_orders: 0,
            active_orders: 0,
            cancelled_orders: 0,
        }),
    },
    recentOrders: {
        type: Array,
        default: () => [],
    },
});

const month = ref(props.filters.month);
const year = ref(props.filters.year);

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userName = computed(() => user.value?.name ? user.value.name.split(' ')[0] : 'Operador');

const months = [
    { name: 'Enero', id: 1 }, { name: 'Febrero', id: 2 }, { name: 'Marzo', id: 3 },
    { name: 'Abril', id: 4 }, { name: 'Mayo', id: 5 }, { name: 'Junio', id: 6 },
    { name: 'Julio', id: 7 }, { name: 'Agosto', id: 8 }, { name: 'Septiembre', id: 9 },
    { name: 'Octubre', id: 10 }, { name: 'Noviembre', id: 11 }, { name: 'Diciembre', id: 12 },
];

const currentYear = new Date().getFullYear();
const years = [currentYear - 1, currentYear, currentYear + 1];

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
</script>

<template>
    <Head title="Registro de Pedidos - Bodas" />

    <AdminLayout>
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- 1. Header con Saludo y Acciones Directas -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-secondary-800 p-5 sm:p-6 rounded-2xl shadow-sm border border-zinc-200 dark:border-secondary-700">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/40">
                            Rol Bodas & Reservas
                        </span>
                        <span class="text-xs text-zinc-400 dark:text-secondary-400">• Módulo de Registro y Consulta</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white tracking-tight">
                        ¡Hola, {{ userName }}! 👋
                    </h1>
                    <p class="text-xs sm:text-sm text-zinc-500 dark:text-secondary-400">
                        Registra nuevas reservas de pedidos y consulta el historial de clientes.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Selector de período -->
                    <div class="flex items-center gap-1.5 bg-zinc-50 dark:bg-secondary-900/60 p-1.5 rounded-xl border border-zinc-200 dark:border-secondary-700">
                        <select v-model="month"
                                class="bg-transparent text-xs font-bold text-zinc-700 dark:text-secondary-200 border-none focus:ring-0 py-1 pl-2 pr-6 rounded-lg cursor-pointer">
                            <option v-for="m in months" :key="m.id" :value="m.id" class="dark:bg-secondary-800 text-zinc-900 dark:text-white">
                                {{ m.name }}
                            </option>
                        </select>
                        <select v-model="year"
                                class="bg-transparent text-xs font-bold text-zinc-700 dark:text-secondary-200 border-none focus:ring-0 py-1 pl-2 pr-6 rounded-lg cursor-pointer">
                            <option v-for="y in years" :key="y" :value="y" class="dark:bg-secondary-800 text-zinc-900 dark:text-white">
                                {{ y }}
                            </option>
                        </select>
                    </div>

                    <!-- Botón Destacado: Registrar Pedido -->
                    <Link :href="route('admin.orders.create')"
                          class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-wider transition-all shadow-sm shadow-indigo-600/20">
                        <span class="material-symbols-outlined text-base">add_circle</span>
                        Registrar Pedido
                    </Link>

                    <!-- Botón Historial -->
                    <Link :href="route('admin.orders.index')"
                          class="inline-flex items-center justify-center gap-1.5 h-10 px-3.5 rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-secondary-700 dark:hover:bg-secondary-600 text-zinc-700 dark:text-secondary-200 text-xs font-bold transition-all">
                        <span class="material-symbols-outlined text-base">receipt_long</span>
                        Historial de Pedidos
                    </Link>
                </div>
            </div>

            <!-- 2. Tarjetas KPI de Registro (100% enfocadas en toma de pedidos) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Registrados Hoy -->
                <div class="p-5 rounded-2xl bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                            Registrados Hoy
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <span class="material-symbols-outlined">today</span>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight">
                            {{ stats.registered_today }}
                        </span>
                        <span class="text-xs font-bold text-zinc-400 dark:text-secondary-400">
                            pedidos
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-zinc-500 dark:text-secondary-400 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-indigo-500">add_task</span>
                        Tomados en el día
                    </div>
                </div>

                <!-- Total Pedidos del Mes -->
                <div class="p-5 rounded-2xl bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-zinc-600 dark:text-secondary-300">
                            Total del Mes
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-secondary-700 text-zinc-600 dark:text-secondary-300 flex items-center justify-center">
                            <span class="material-symbols-outlined">receipt_long</span>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight">
                            {{ stats.total_orders }}
                        </span>
                        <span class="text-xs font-bold text-zinc-400 dark:text-secondary-400">
                            registrados
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-zinc-500 dark:text-secondary-400 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-zinc-400">calendar_month</span>
                        En el período seleccionado
                    </div>
                </div>

                <!-- Reservas Activas (Vigentes) -->
                <div class="p-5 rounded-2xl bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                            Reservas Activas
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <span class="material-symbols-outlined">check_circle</span>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight">
                            {{ stats.active_orders }}
                        </span>
                        <span class="text-xs font-bold text-zinc-400 dark:text-secondary-400">
                            vigentes
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-zinc-500 dark:text-secondary-400 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-emerald-500">task_alt</span>
                        Listas para cocina / pastelería
                    </div>
                </div>

                <!-- Pedidos Anulados -->
                <div class="p-5 rounded-2xl bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">
                            Anuladas
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                            <span class="material-symbols-outlined">block</span>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight">
                            {{ stats.cancelled_orders }}
                        </span>
                        <span class="text-xs font-bold text-zinc-400 dark:text-secondary-400">
                            en el mes
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-zinc-500 dark:text-secondary-400 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-rose-500">cancel</span>
                        Canceladas con motivo
                    </div>
                </div>

            </div>

            <!-- 3. Tabla Principal: Últimos Pedidos Registrados -->
            <div class="bg-white dark:bg-secondary-800 rounded-2xl border border-zinc-200 dark:border-secondary-700 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-zinc-100 dark:border-secondary-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl">list_alt</span>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-zinc-900 dark:text-white">
                                Últimos Pedidos Registrados
                            </h2>
                            <p class="text-xs text-zinc-500 dark:text-secondary-400">
                                Lista de pedidos tomados recientemente con acceso rápido a comanda técnica
                            </p>
                        </div>
                    </div>

                    <Link :href="route('admin.orders.index')"
                          class="inline-flex items-center gap-1 text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400 hover:underline">
                        Ver Historial Completo
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </Link>
                </div>

                <!-- Tabla Responsiva -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-secondary-700 text-left">
                        <thead class="bg-zinc-50 dark:bg-secondary-900/50 text-[10px] font-black uppercase tracking-wider text-zinc-400 dark:text-secondary-400">
                            <tr>
                                <th class="px-5 py-3">Folio</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Fecha Registro</th>
                                <th class="px-4 py-3">Fecha Solicitada</th>
                                <th class="px-4 py-3 hidden md:table-cell">Productos</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-5 py-3 text-right">Comanda</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/60 text-xs">
                            <tr v-for="order in recentOrders" :key="order.id"
                                class="hover:bg-zinc-50/80 dark:hover:bg-secondary-900/30 transition-colors">
                                
                                <!-- Folio -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-black text-xs border border-indigo-200/60 dark:border-indigo-800/40">
                                        #{{ order.formatted_number }}
                                    </span>
                                </td>

                                <!-- Cliente -->
                                <td class="px-4 py-3.5">
                                    <div class="font-black text-zinc-900 dark:text-white">
                                        {{ order.customer_name }}
                                    </div>
                                    <div v-if="order.customer_phone" class="text-[11px] text-zinc-500 dark:text-secondary-400 flex items-center gap-1 mt-0.5 font-semibold">
                                        <span class="material-symbols-outlined text-xs text-indigo-500">call</span>
                                        {{ order.customer_phone }}
                                    </div>
                                </td>

                                <!-- Fecha Registro -->
                                <td class="px-4 py-3.5 whitespace-nowrap hidden sm:table-cell text-zinc-600 dark:text-secondary-300 font-semibold">
                                    <div>{{ order.registered_at }}</div>
                                    <div v-if="order.user_name" class="text-[10px] text-zinc-400 dark:text-secondary-400 font-medium flex items-center gap-1 mt-0.5">
                                        <span class="material-symbols-outlined text-[13px] text-zinc-400">person</span>
                                        <span>{{ order.user_name }}</span>
                                    </div>
                                </td>

                                <!-- Fecha Solicitada / Modalidad -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-bold text-zinc-900 dark:text-white">
                                        {{ order.delivery_date }}
                                    </div>
                                    <div class="text-[11px] text-zinc-400 dark:text-secondary-400 mt-0.5">
                                        {{ order.delivery_mode_label }} · {{ order.delivery_time || '-' }}
                                    </div>
                                </td>

                                <!-- Resumen de Productos -->
                                <td class="px-4 py-3.5 hidden md:table-cell text-zinc-600 dark:text-secondary-300 max-w-xs truncate font-medium">
                                    {{ order.items_summary || (order.items_count + ' producto(s)') }}
                                </td>

                                <!-- Estado -->
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <span :class="order.is_cancelled
                                            ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/40'
                                            : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/40'"
                                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border">
                                        {{ order.status }}
                                    </span>
                                </td>

                                <!-- Acción: Imprimir Comanda -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                    <a :href="route('admin.orders.receipt-pdf', order.id)"
                                       target="_blank"
                                       class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-100 hover:bg-zinc-800 hover:text-white dark:bg-secondary-700 dark:hover:bg-secondary-600 text-zinc-700 dark:text-secondary-200 font-bold text-xs transition-all"
                                       title="Imprimir Comanda Técnica en PDF">
                                        <span class="material-symbols-outlined text-sm">print</span>
                                        <span class="hidden sm:inline">Comanda</span>
                                    </a>
                                </td>

                            </tr>

                            <!-- Empty state si no hay pedidos -->
                            <tr v-if="recentOrders.length === 0">
                                <td colspan="7" class="py-12 text-center">
                                    <div class="w-12 h-12 rounded-2xl bg-zinc-100 dark:bg-secondary-700/60 text-zinc-400 flex items-center justify-center mx-auto mb-2">
                                        <span class="material-symbols-outlined text-2xl">receipt_long</span>
                                    </div>
                                    <p class="text-sm font-bold text-zinc-700 dark:text-secondary-300">
                                        No hay pedidos registrados en este período
                                    </p>
                                    <p class="text-xs text-zinc-400 dark:text-secondary-400 max-w-sm mx-auto mt-1 mb-4">
                                        Comienza registrando la primera reserva del evento.
                                    </p>
                                    <Link :href="route('admin.orders.create')"
                                          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-black uppercase tracking-wider hover:bg-indigo-700 transition-colors">
                                        <span class="material-symbols-outlined text-sm">add_circle</span>
                                        Registrar Pedido Ahora
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
