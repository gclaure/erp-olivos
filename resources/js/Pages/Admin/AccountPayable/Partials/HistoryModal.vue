<script setup>

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

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/40 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="bg-white dark:bg-gray-700 w-full max-w-2xl rounded-xl shadow-xl overflow-hidden border border-zinc-100 dark:border-gray-600 animate-in zoom-in duration-300 flex flex-col max-h-[90vh]">
            <div class="h-1.5 w-full bg-zinc-500"></div>
            
            <div class="p-6 overflow-y-auto">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Historial de Pagos</h3>
                        <p v-if="record" class="text-sm text-zinc-500 mt-1">
                            Compra: <span class="font-bold">{{ record.purchase?.purchase_number }}</span> - {{ record.provider?.name }}
                        </p>
                    </div>
                    <button @click="closeModal" class="text-zinc-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 p-2 rounded-lg transition-all">
                        <span class="material-symbols-outlined text-2xl leading-none">close</span>
                    </button>
                </div>

                <div v-if="record">
                    <!-- Metrics Summary -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                        <div class="p-3 bg-zinc-50 dark:bg-gray-600 rounded-lg border border-zinc-100 dark:border-gray-500">
                            <div class="text-[10px] text-zinc-400 uppercase tracking-widest font-black">Total Deuda</div>
                            <div class="text-lg font-bold text-zinc-900 dark:text-white">Bs. {{ formatNumber(record.total_amount) }}</div>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-100 dark:border-emerald-800/50">
                            <div class="text-[10px] text-emerald-500 uppercase tracking-widest font-black">Total Pagado</div>
                            <div class="text-lg font-bold text-emerald-700">Bs. {{ formatNumber(record.paid_amount) }}</div>
                        </div>
                        <div class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded-lg border border-rose-100 dark:border-rose-800/50">
                            <div class="text-[10px] text-rose-500 uppercase tracking-widest font-black">Saldo Actual</div>
                            <div class="text-lg font-bold text-rose-700">Bs. {{ formatNumber(record.balance) }}</div>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div class="overflow-hidden border border-zinc-200 dark:border-gray-600 rounded-lg">
                        <table class="w-full text-sm text-left whitespace-nowrap">
                            <thead class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                                <tr class="text-zinc-700 dark:text-zinc-300 font-semibold uppercase text-[11px] tracking-wider">
                                    <th class="px-4 py-3">Fecha / Usuario</th>
                                    <th class="px-4 py-3">Método</th>
                                    <th class="px-4 py-3">Ref.</th>
                                    <th class="px-4 py-3 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                                <template v-for="payment in record.payments" :key="payment.id">
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-zinc-900 dark:text-white capitalize text-xs italic">
                                                {{ payment.payment_date_formatted }}
                                            </div>
                                            <div class="text-[10px] text-zinc-400 dark:text-zinc-300 uppercase tracking-tighter">
                                                {{ payment.user_name }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-[10px] px-2 py-0.5 bg-zinc-100 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300 rounded-full font-bold uppercase tracking-widest">
                                                {{ payment.payment_method }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 font-mono text-xs">
                                            {{ payment.reference || '--' }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-emerald-600">
                                            <div class="flex flex-col items-end">
                                                <span class="text-sm">Bs. {{ formatNumber(payment.amount) }}</span>
                                                <a v-if="payment.receipt_url" :href="payment.receipt_url" target="_blank" 
                                                   class="text-[10px] text-indigo-500 hover:underline flex items-center mt-1 gap-1 font-bold uppercase tracking-tight">
                                                    <span class="material-symbols-outlined text-xs">image</span> Ver Comprobante
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="payment.notes" class="bg-zinc-50/30 dark:bg-gray-600/30 border-t border-zinc-50 dark:border-gray-600">
                                        <td colspan="4" class="px-4 py-2 text-[11px] text-zinc-400 italic flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[14px] opacity-50">chat_bubble</span>
                                            {{ payment.notes }}
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="record.payments?.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-zinc-400 italic">
                                        No se han registrado pagos aún.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button @click="closeModal"
                                class="px-6 py-2 text-sm font-bold text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-gray-600 rounded-lg transition-colors border border-transparent hover:border-zinc-200 dark:hover:border-gray-500 uppercase tracking-widest">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overlay -->
        <div @click="closeModal" class="fixed inset-0 z-[-1]"></div>
    </div>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
