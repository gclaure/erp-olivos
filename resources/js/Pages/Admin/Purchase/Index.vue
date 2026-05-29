<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DetailModal from './Partials/DetailModal.vue';
import CancelModal from './Partials/CancelModal.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    records: Object,
    warehouses: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const payment_type = ref(props.filters.payment_type || '');
const only_with_debt = ref(props.filters.only_with_debt || false);

const showDetailModal = ref(false);
const showCancelModal = ref(false);
const selectedRecord = ref(null);

const updateFilters = debounce(() => {
    router.get(route('admin.purchases.index'), {
        search: search.value,
        payment_type: payment_type.value,
        only_with_debt: only_with_debt.value ? 1 : 0,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, payment_type, only_with_debt], () => {
    updateFilters();
});

const openDetail = (record) => {
    selectedRecord.value = record;
    showDetailModal.value = true;
};

const openCancel = (record) => {
    selectedRecord.value = record;
    showCancelModal.value = true;
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

</script>

<template>
    <Head title="Compras" />

    <AdminLayout>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Compras</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Registro de compras realizadas</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <Link :href="route('admin.purchases.create')" 
                      class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-lg">add</span> Nueva Compra
                </Link>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 shadow-sm mb-6 overflow-hidden">
            <div class="p-4 flex flex-col md:flex-row gap-4 items-center">
                <div class="flex-1 w-full relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors text-xl">search</span>
                    </div>
                    <input v-model="search" type="text" 
                        placeholder="Buscar compras por proveedor o Nro. PO..." 
                        class="w-full pl-11 pr-4 py-2.5 bg-zinc-50 dark:bg-gray-600 border-zinc-200 dark:border-gray-500 rounded-xl text-sm dark:text-white focus:bg-white dark:focus:bg-gray-500 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>
                
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <select v-model="payment_type"
                            class="w-full sm:w-56 px-4 py-2.5 bg-zinc-50 dark:bg-gray-600 border-zinc-200 dark:border-gray-500 rounded-xl text-sm dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium">
                        <option value="">Mostrar todos los pagos</option>
                        <option value="contado">💸 Compras al Contado</option>
                        <option value="credito">💳 Compras al Crédito</option>
                    </select>

                    <label class="flex items-center gap-3 px-4 py-2.5 border border-zinc-200 dark:border-gray-500 rounded-xl bg-zinc-50/50 dark:bg-gray-600/50 hover:bg-zinc-100 dark:hover:bg-gray-600 transition-colors w-full sm:w-auto text-zinc-600 dark:text-zinc-300 cursor-pointer">
                        <input type="checkbox" v-model="only_with_debt" class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-medium">Solo con deuda</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden shadow-sm">
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                        <tr class="text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <th class="px-6 py-4">#</th>
                            <th class="px-6 py-4">Nro. PO</th>
                            <th class="px-6 py-4">Fecha</th>
                            <th class="px-6 py-4">Proveedor</th>
                            <th class="px-6 py-4">Almacén</th>
                            <th class="px-6 py-4">Usuario</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Total</th>
                            <th class="px-6 py-4 text-right">Saldo</th>
                            <th class="px-6 py-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                        <tr v-for="(purchase, i) in records.data" :key="purchase.id"
                            class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors group">
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                {{ records.meta.from + i }}
                            </td>
                            <td class="px-6 py-4 font-bold text-indigo-600 tracking-wide">
                                {{ purchase.purchase_number }}
                            </td>
                            <td class="px-6 py-4 text-zinc-900 dark:text-white capitalize">
                                {{ purchase.date_formatted }}
                            </td>
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                                {{ purchase.provider?.name || '—' }}
                            </td>
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                {{ purchase.warehouse?.name || '—' }}
                            </td>
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                {{ purchase.user?.name || '—' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="purchase.status === 'cancelado'"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400 border border-rose-200 dark:border-rose-800/30">
                                    Cancelado
                                </span>
                                <span v-else-if="purchase.status === 'pendiente'"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 border border-amber-200 dark:border-amber-800/30">
                                    Pendiente
                                </span>
                                <span v-else
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/30">
                                    Completada
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-white">
                                Bs. {{ formatNumber(purchase.total) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span v-if="purchase.payment_type === 'credito'"
                                      :class="['font-bold', purchase.balance > 0 ? 'text-rose-600' : 'text-emerald-600']">
                                    Bs. {{ formatNumber(purchase.balance) }}
                                </span>
                                <span v-else class="text-zinc-300 dark:text-zinc-600">--</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button @click="openDetail(purchase)"
                                            class="w-9 h-9 flex items-center justify-center text-blue-600 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-600 hover:text-white border border-blue-200 dark:border-blue-800 rounded-lg transition-all shadow-sm"
                                            title="Ver Detalle">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </button>

                                    <template v-if="purchase.status !== 'cancelado'">
                                        <button @click="openCancel(purchase)"
                                                class="w-9 h-9 flex items-center justify-center text-rose-600 bg-rose-50 dark:bg-rose-900/30 hover:bg-rose-600 hover:text-white border border-rose-200 dark:border-rose-800 rounded-lg transition-all shadow-sm"
                                                title="Cancelar Compra">
                                            <span class="material-symbols-outlined text-lg">block</span>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="records.data.length === 0">
                            <td colspan="10" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center opacity-40">
                                    <span class="material-symbols-outlined text-6xl mb-4 text-zinc-300 dark:text-zinc-600">shopping_cart</span>
                                    <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-sm">Sin compras registradas</p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-2">Las compras aparecerán aquí cuando se registren</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-gray-700">
                <div v-for="(purchase, i) in records.data" :key="purchase.id" 
                     class="p-4 space-y-4 bg-white dark:bg-gray-800/50">
                    
                    <!-- Header Info -->
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">PO: {{ purchase.purchase_number }}</span>
                            <span class="text-xs font-bold text-zinc-900 dark:text-white capitalize mt-0.5">{{ purchase.date_formatted }}</span>
                        </div>
                        <span v-if="purchase.status === 'cancelado'"
                              class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/20">
                            Cancelado
                        </span>
                        <span v-else-if="purchase.status === 'pendiente'"
                              class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-800/20">
                            Pendiente
                        </span>
                        <span v-else
                              class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                            Completada
                        </span>
                    </div>

                    <!-- Details Grid -->
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Proveedor</span>
                            <span class="text-xs font-bold text-zinc-900 dark:text-white">{{ purchase.provider?.name || '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Almacén</span>
                            <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ purchase.warehouse?.name || '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Total</span>
                            <span class="text-sm font-black text-zinc-900 dark:text-white">Bs. {{ formatNumber(purchase.total) }}</span>
                        </div>
                        <div v-if="purchase.payment_type === 'credito'" class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Saldo Pendiente</span>
                            <span :class="['text-xs font-black', purchase.balance > 0 ? 'text-rose-600' : 'text-emerald-600']">
                                Bs. {{ formatNumber(purchase.balance) }}
                            </span>
                        </div>
                    </div>

                    <!-- Action Row -->
                    <div class="flex justify-end items-center gap-3 pt-2">
                        <button @click="openDetail(purchase)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 active:scale-90 transition-all border border-emerald-100 dark:border-emerald-800/20"
                                title="Ver Detalle">
                            <span class="material-symbols-outlined text-lg">visibility</span>
                        </button>
                        <template v-if="purchase.status !== 'cancelado'">
                            <button @click="openCancel(purchase)"
                                    class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 active:scale-90 transition-all border border-rose-100 dark:border-rose-800/20"
                                    title="Anular Compra">
                                <span class="material-symbols-outlined text-lg">block</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Empty State for Mobile -->
                <div v-if="records.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800">
                    <div class="flex flex-col items-center opacity-40 py-10">
                        <span class="material-symbols-outlined text-4xl mb-4 text-zinc-300 dark:text-zinc-600">shopping_cart</span>
                        <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-sm">Sin compras registradas</p>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="records.meta && records.meta.links" class="px-6 py-6 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50/50 dark:bg-gray-600 flex justify-center">
                <nav class="flex gap-1.5">
                    <template v-for="(link, i) in records.meta.links" :key="i">
                        <button v-if="link.url"
                                @click="router.get(link.url, { search, payment_type, only_with_debt: only_with_debt ? 1 : 0 }, { preserveState: true })"
                                v-html="link.label"
                                :class="[
                                    'px-4 py-2 text-[11px] font-black rounded-xl transition-all duration-200',
                                    link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30 scale-110' : 
                                    'bg-white dark:bg-gray-700 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-gray-600 shadow-sm border border-zinc-200 dark:border-gray-600'
                                ]"
                        />
                        <span v-else v-html="link.label" class="px-4 py-2 text-[11px] font-black text-zinc-300 dark:text-zinc-800" />
                    </template>
                </nav>
            </div>
        </div>

        <!-- Modals -->
        <DetailModal :show="showDetailModal" :record="selectedRecord" @close="showDetailModal = false" />
        <CancelModal :show="showCancelModal" :record="selectedRecord" @close="showCancelModal = false" />
    </AdminLayout>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
