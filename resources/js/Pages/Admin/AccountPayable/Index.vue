<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaymentModal from './Partials/PaymentModal.vue';
import HistoryModal from './Partials/HistoryModal.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    records: Object,
    providers: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const provider_id = ref(props.filters.provider_id || '');
const status = ref(props.filters.status || '');

const showPaymentModal = ref(false);
const showHistoryModal = ref(false);
const selectedRecord = ref(null);

const updateFilters = debounce(() => {
    router.get(route('admin.accounts-payable.index'), {
        search: search.value,
        provider_id: provider_id.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, provider_id, status], () => {
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

const getStatusDetails = (status) => {
    switch (status) {
        case 'PENDING': return { label: 'Pendiente', color: 'bg-amber-100 text-amber-700 border-amber-200' };
        case 'PARTIAL': return { label: 'Parcial', color: 'bg-blue-100 text-blue-700 border-blue-200' };
        case 'PAID': return { label: 'Pagado', color: 'bg-emerald-100 text-emerald-700 border-emerald-200' };
        case 'OVERDUE': return { label: 'Vencido', color: 'bg-rose-100 text-rose-700 border-rose-200' };
        default: return { label: status, color: 'bg-zinc-100 text-zinc-700 border-zinc-200' };
    }
};

</script>

<template>
    <Head title="Cuentas por Pagar" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Cuentas por Pagar</h1>
                    <p class="text-sm text-zinc-500 mt-1">Gestión de deudas con proveedores y seguimiento de pagos</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-surface p-6 rounded-xl border border-zinc-200 dark:border-gray-600 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Buscar por Nro Compra</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors">search</span>
                            </div>
                            <input v-model="search" type="text" placeholder="PO/..."
                                   class="block w-full pl-10 pr-4 py-2 border-zinc-300 dark:border-gray-500 dark:bg-gray-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                    
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Proveedor</label>
                        <select v-model="provider_id"
                                class="block w-full border-zinc-300 dark:border-gray-500 dark:bg-gray-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">Todos los proveedores</option>
                            <option v-for="provider in providers" :key="provider.id" :value="provider.id">{{ provider.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Estado</label>
                        <select v-model="status"
                                class="block w-full border-zinc-300 dark:border-gray-500 dark:bg-gray-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">Todos</option>
                            <option value="PENDING">Pendiente</option>
                            <option value="PARTIAL">Parcial</option>
                            <option value="PAID">Pagado</option>
                            <option value="OVERDUE">Vencido</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-300 uppercase text-[11px] tracking-wider">Compra</th>
                                <th class="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-300 uppercase text-[11px] tracking-wider">Proveedor</th>
                                <th class="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-300 uppercase text-[11px] tracking-wider">Vencimiento</th>
                                <th class="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-300 uppercase text-[11px] tracking-wider text-right">Total</th>
                                <th class="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-300 uppercase text-[11px] tracking-wider text-right">Pagado</th>
                                <th class="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-300 uppercase text-[11px] tracking-wider text-right">Saldo</th>
                                <th class="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-300 uppercase text-[11px] tracking-wider text-center">Estado</th>
                                <th class="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-300 uppercase text-[11px] tracking-wider text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-gray-600">
                            <tr v-for="debt in records.data" :key="debt.id" class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ debt.purchase?.purchase_number }}</div>
                                    <div class="text-[10px] text-zinc-500 dark:text-zinc-400 capitalize italic">{{ debt.purchase?.date_formatted }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ debt.provider?.name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['capitalize text-xs font-bold tracking-tight', debt.is_overdue ? 'text-rose-600' : 'text-zinc-600 dark:text-zinc-400']">
                                        {{ debt.due_date_formatted }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-white">
                                    Bs. {{ formatNumber(debt.total_amount) }}
                                </td>
                                <td class="px-6 py-4 text-right text-emerald-600 font-bold">
                                    Bs. {{ formatNumber(debt.paid_amount) }}
                                </td>
                                <td class="px-6 py-4 text-right text-rose-600 font-black">
                                    Bs. {{ formatNumber(debt.balance) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="[
                                        'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border',
                                        getStatusDetails(debt.status).color
                                    ]">
                                        {{ getStatusDetails(debt.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button v-if="debt.status !== 'PAID'"
                                                @click="openPaymentModal(debt)"
                                                class="w-9 h-9 inline-flex items-center justify-center rounded-full bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 transition-all active:scale-90"
                                                title="Registrar Pago">
                                            <span class="material-symbols-outlined text-xl">payments</span>
                                        </button>
                                        <button @click="openHistoryModal(debt)"
                                                class="w-9 h-9 inline-flex items-center justify-center rounded-full bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-gray-600 dark:text-zinc-300 transition-all active:scale-90"
                                                title="Ver Historial">
                                            <span class="material-symbols-outlined text-xl">visibility</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="records.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-zinc-500 bg-zinc-50/50 dark:bg-gray-600/50">
                                    <div class="flex flex-col items-center">
                                        <span class="material-symbols-outlined text-5xl mb-3 text-zinc-300 dark:text-zinc-500">receipt</span>
                                        <p class="font-bold uppercase tracking-widest text-xs">No se encontraron cuentas por pagar</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="records.meta && records.meta.links" class="px-6 py-4 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50 dark:bg-gray-600 flex justify-center">
                    <nav class="flex gap-1.5">
                        <template v-for="(link, i) in records.meta.links" :key="i">
                            <button v-if="link.url"
                                    @click="router.get(link.url, { search, provider_id, status }, { preserveState: true })"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 text-[11px] font-black rounded-lg transition-all',
                                        link.active ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white dark:bg-gray-700 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-gray-500 border border-zinc-200 dark:border-gray-600'
                                    ]"
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-[11px] font-black text-zinc-300 dark:text-zinc-500 border border-transparent" />
                        </template>
                    </nav>
                </div>
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
