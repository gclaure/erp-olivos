<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: Boolean,
    record: Object,
});

const emit = defineEmits(['close']);

const closeModal = () => {
    emit('close');
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
        month: 'long',
        year: 'numeric'
    }).format(date);
};

const getMethodIcon = (method) => {
    switch (method) {
        case 'efectivo': return 'payments';
        case 'transferencia': return 'account_balance';
        case 'tarjeta_qr': return 'qr_code_2';
        case 'deposito': return 'savings';
        case 'cheque': return 'history_edu';
        default: return 'credit_card';
    }
};

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/40 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="bg-white dark:bg-secondary-900 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden border border-zinc-100 dark:border-secondary-700 animate-in zoom-in duration-300 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-zinc-100 dark:border-secondary-800 flex items-center justify-between bg-zinc-50/50 dark:bg-secondary-800/50 shrink-0">
                <div class="flex flex-col">
                    <h3 class="text-xl font-black text-zinc-900 dark:text-white uppercase tracking-tight">Historial de Pagos</h3>
                    <div class="flex items-center gap-3 mt-1.5">
                        <span class="text-[10px] font-black bg-indigo-600 text-white px-2 py-0.5 rounded-lg tracking-widest uppercase shadow-sm shadow-indigo-500/20">Crédito #{{ record?.sale?.number }}</span>
                        <span class="text-xs font-bold text-zinc-500 dark:text-secondary-400 truncate max-w-[250px]">{{ record?.client?.name }}</span>
                    </div>
                </div>
                <button @click="closeModal" class="w-10 h-10 flex items-center justify-center text-zinc-400 hover:text-rose-500 transition-colors hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-2xl border border-zinc-100 dark:border-secondary-700">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Content -->
            <div class="p-0 overflow-y-auto flex-1">
                <!-- Metrics Summary -->
                <div class="grid grid-cols-2 gap-4 p-8 bg-zinc-50/30 dark:bg-secondary-800/20 border-b border-zinc-100 dark:border-secondary-800">
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Monto Original</p>
                        <p class="text-2xl font-black text-zinc-800 dark:text-secondary-200 tracking-tighter">Bs. {{ formatNumber(record?.total_amount) }}</p>
                    </div>
                    <div class="space-y-1 text-right">
                        <p class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Saldo Pendiente</p>
                        <p :class="['text-2xl font-black tracking-tighter', record?.balance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400']">
                            Bs. {{ formatNumber(record?.balance) }}
                        </p>
                    </div>
                </div>

                <!-- Payments Table -->
                <div v-if="record?.payments?.length > 0" class="min-w-full">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead>
                            <tr class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest border-b border-zinc-50 dark:border-secondary-800/50">
                                <th class="px-8 py-4">Fecha / Usuario</th>
                                <th class="px-8 py-4">Método / Ref</th>
                                <th class="px-8 py-4 text-right">Monto Pagado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-50 dark:divide-secondary-800/50">
                            <tr v-for="payment in record.payments" :key="payment.id" class="hover:bg-zinc-50/30 dark:hover:bg-secondary-800/20 transition-colors">
                                <td class="px-8 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-black text-zinc-900 dark:text-white tracking-tight">{{ formatDate(payment.payment_date) }}</span>
                                        <span class="text-[10px] font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mt-0.5 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[12px]">person</span>
                                            {{ payment.user_name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-8 py-4">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5 text-zinc-700 dark:text-secondary-300 font-bold capitalize">
                                            <span class="material-symbols-outlined text-base text-indigo-500">{{ getMethodIcon(payment.payment_method) }}</span>
                                            {{ payment.payment_method.replace('_', ' ') }}
                                        </div>
                                        <span v-if="payment.reference" class="text-[10px] font-mono text-zinc-400 dark:text-secondary-500 mt-0.5">{{ payment.reference }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <span class="text-base font-black text-emerald-600 dark:text-emerald-400 tracking-tighter">Bs. {{ formatNumber(payment.amount) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="px-8 py-20 text-center">
                    <div class="flex flex-col items-center opacity-30">
                        <span class="material-symbols-outlined text-6xl mb-4">history</span>
                        <p class="text-zinc-500 dark:text-secondary-400 font-black uppercase tracking-widest text-sm">Sin pagos registrados</p>
                        <p class="text-xs text-zinc-400 dark:text-secondary-500 mt-2">Aún no se han realizado abonos a este crédito.</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-8 py-6 border-t border-zinc-100 dark:border-secondary-800 bg-zinc-50/50 dark:bg-secondary-800/50 flex justify-end shrink-0">
                <button @click="closeModal"
                        class="px-8 py-2.5 text-sm font-black text-zinc-500 dark:text-secondary-400 hover:bg-zinc-100 dark:hover:bg-secondary-800 rounded-2xl transition-colors border border-transparent hover:border-zinc-200 dark:hover:border-secondary-700 uppercase tracking-widest">
                    Cerrar Historial
                </button>
            </div>
        </div>

        <!-- Overlay to close -->
        <div @click="closeModal" class="fixed inset-0 z-[-1]"></div>
    </div>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
