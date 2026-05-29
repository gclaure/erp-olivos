<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaymentModal from './Partials/PaymentModal.vue';
import HistoryModal from './Partials/HistoryModal.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    records: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const showPaymentModal = ref(false);
const showHistoryModal = ref(false);
const selectedRecord = ref(null);

const updateFilters = debounce(() => {
    router.get(route('admin.accounts-receivable.index'), {
        search: search.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, status], () => {
    updateFilters();
});

const openPaymentModal = (record) => {
    selectedRecord.value = record;
    showPaymentModal.value = true;
};

const openHistoryModal = (record) => {
    selectedRecord.value = record;
    showHistoryModal.value = true;
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-BO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(date);
};

const getStatusClass = (status, isOverdue) => {
    if (isOverdue) return 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400 border-rose-100 dark:border-rose-800/30';
    
    switch (status) {
        case 'pendiente': return 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 border-amber-100 dark:border-amber-800/30';
        case 'parcial': return 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 border-blue-100 dark:border-blue-800/30';
        case 'pagado': return 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 border-emerald-100 dark:border-emerald-800/30';
        default: return 'bg-zinc-50 text-zinc-600 dark:bg-secondary-700 dark:text-secondary-300 border-zinc-100 dark:border-secondary-600/30';
    }
};

</script>

<template>
    <Head title="Cuentas por Cobrar" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div class="text-left">
                <h1 class="text-2xl font-black text-zinc-900 dark:text-white leading-tight tracking-tight uppercase">Cuentas por Cobrar</h1>
                <p class="text-sm text-zinc-500 dark:text-secondary-400 mt-1">Control de créditos, saldos pendientes y cobranzas.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex flex-col items-end px-4 border-r border-zinc-200 dark:border-secondary-700">
                    <span class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Total Registros</span>
                    <span class="text-lg font-black text-zinc-800 dark:text-secondary-200 tracking-tighter">{{ records.meta.total }}</span>
                </div>
                <Link :href="route('admin.sales.index')" 
                      class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-white dark:bg-secondary-800 text-zinc-600 dark:text-secondary-300 text-xs font-black uppercase tracking-widest rounded-2xl border border-zinc-200 dark:border-secondary-700 hover:bg-zinc-50 dark:hover:bg-secondary-700 transition-all shadow-sm active:scale-95">
                    <span class="material-symbols-outlined text-lg">shopping_cart</span>
                    Ventas
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 shadow-sm mb-6 p-5">
            <div class="flex flex-col lg:flex-row gap-5">
                <div class="flex-1">
                    <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Buscar Cliente</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors text-xl">person_search</span>
                        </div>
                        <input v-model="search"
                               type="text"
                               placeholder="Nombre o número de documento..."
                               class="w-full pl-12 pr-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm placeholder-zinc-400 font-medium">
                    </div>
                </div>
                
                <div class="w-full lg:w-64">
                    <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Estado de Cuenta</label>
                    <select v-model="status"
                            class="w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm font-black uppercase tracking-widest text-[11px]">
                        <option value="">TODOS LOS ESTADOS</option>
                        <option value="pendiente">PENDIENTE</option>
                        <option value="parcial">PARCIAL</option>
                        <option value="pagado">PAGADO</option>
                        <option value="vencido">VENCIDO</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-secondary-700 border-b border-zinc-200 dark:border-secondary-600 text-zinc-500 dark:text-secondary-400 font-black uppercase tracking-widest text-[10px]">
                            <th class="px-6 py-4">Cliente / Cr&eacute;dito</th>
                            <th class="px-6 py-4">Vencimiento</th>
                            <th class="px-6 py-4 text-right">Monto Total</th>
                            <th class="px-6 py-4 text-right">Saldo</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50">
                        <tr v-for="ar in records.data" :key="ar.id"
                            class="hover:bg-zinc-50/80 dark:hover:bg-secondary-700/50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black text-sm border border-indigo-100 dark:border-indigo-800/30">
                                        {{ ar.client?.name?.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-zinc-900 dark:text-white font-black tracking-tight text-base leading-tight">{{ ar.client?.name }}</span>
                                        <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mt-0.5 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[12px]">receipt_long</span>
                                            Venta #{{ ar.sale?.number }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-zinc-700 dark:text-secondary-300 font-bold tracking-tight">{{ formatDate(ar.due_date) }}</span>
                                    <span v-if="ar.is_overdue" class="text-[9px] font-black text-rose-500 uppercase tracking-widest mt-0.5">¡Atrasado!</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-zinc-500 dark:text-secondary-400 tracking-tighter">Bs. {{ formatNumber(ar.total_amount) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span :class="['text-base font-black tracking-tighter', ar.balance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400']">
                                    Bs. {{ formatNumber(ar.balance) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span :class="[
                                    'px-3 py-1 inline-flex text-[10px] font-black uppercase tracking-widest rounded-full shadow-sm border',
                                    getStatusClass(ar.status, ar.is_overdue)
                                ]">
                                    {{ ar.is_overdue ? 'vencido' : ar.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openPaymentModal(ar)"
                                            v-if="ar.balance > 0"
                                            class="w-9 h-9 inline-flex items-center justify-center rounded-xl text-zinc-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-all active:scale-90 border border-transparent hover:border-emerald-100 dark:hover:border-emerald-800"
                                            title="Registrar Pago">
                                        <span class="material-symbols-outlined text-xl">add_card</span>
                                    </button>
                                    <button @click="openHistoryModal(ar)"
                                            class="w-9 h-9 inline-flex items-center justify-center rounded-xl text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-all active:scale-90 border border-transparent hover:border-indigo-100 dark:hover:border-indigo-800"
                                            title="Ver Historial">
                                        <span class="material-symbols-outlined text-xl">history</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="records.data.length === 0">
                            <td colspan="6" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center opacity-40">
                                    <div class="w-20 h-20 bg-zinc-50 dark:bg-secondary-700 rounded-3xl flex items-center justify-center mb-4 border-2 border-dashed border-zinc-200 dark:border-secondary-600">
                                        <span class="material-symbols-outlined text-5xl text-zinc-300 dark:text-secondary-600">account_balance_wallet</span>
                                    </div>
                                    <p class="text-zinc-500 dark:text-secondary-400 font-black uppercase tracking-widest text-sm">No se encontraron créditos</p>
                                    <p class="text-xs text-zinc-400 dark:text-secondary-500 mt-2">Prueba ajustando los filtros o busca un cliente específico.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="records.meta && records.meta.links" class="px-6 py-6 border-t border-zinc-100 dark:border-secondary-700 bg-zinc-50/50 dark:bg-secondary-800/50 flex justify-center">
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

        <!-- Modals -->
        <PaymentModal :show="showPaymentModal" :record="selectedRecord" @close="showPaymentModal = false" />
        <HistoryModal :show="showHistoryModal" :record="selectedRecord" @close="showHistoryModal = false" />
    </AdminLayout>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
