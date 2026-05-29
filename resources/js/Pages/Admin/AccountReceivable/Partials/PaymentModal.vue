<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    record: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    amount: 0,
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'efectivo',
    reference: '',
    notes: '',
});

watch(() => props.record, (newRecord) => {
    if (newRecord) {
        form.amount = parseFloat(newRecord.balance);
        form.payment_date = new Date().toISOString().split('T')[0];
    }
}, { immediate: true });

const closeModal = () => {
    emit('close');
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post(route('admin.accounts-receivable.payment', props.record.id), {
        onSuccess: () => closeModal(),
    });
};

const paymentMethods = [
    { label: 'Efectivo', value: 'efectivo' },
    { label: 'Transferencia', value: 'transferencia' },
    { label: 'Tarjeta QR', value: 'tarjeta_qr' },
    { label: 'Depósito', value: 'deposito' },
    { label: 'Cheque', value: 'cheque' },
];

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/40 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="bg-white dark:bg-secondary-900 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden border border-zinc-100 dark:border-secondary-700 animate-in zoom-in duration-300">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-secondary-800 flex items-center justify-between bg-zinc-50/50 dark:bg-secondary-800/50">
                <div class="flex flex-col">
                    <h3 class="text-lg font-black text-zinc-900 dark:text-white uppercase tracking-tight">Registrar Pago</h3>
                    <p class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mt-0.5">
                        Crédito #{{ record?.sale?.number }} — {{ record?.client?.name }}
                    </p>
                </div>
                <button @click="closeModal" class="p-2 text-zinc-400 hover:text-rose-500 transition-colors hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <div class="p-4 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-2xl border border-indigo-100 dark:border-indigo-900/20 mb-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-zinc-500 dark:text-secondary-400 uppercase tracking-widest">Saldo Pendiente</span>
                        <span class="text-xl font-black text-indigo-600 dark:text-indigo-400 tracking-tighter">Bs. {{ record?.balance }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Monto a Pagar</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400 font-bold text-xs">Bs.</span>
                            <input v-model="form.amount" type="number" step="0.01" required
                                   :class="['w-full pl-10 pr-4 py-2.5 bg-white dark:bg-secondary-900 border rounded-xl text-sm font-black transition-all dark:text-white shadow-sm', 
                                            form.errors.amount ? 'border-rose-500 focus:ring-rose-500/10' : 'border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10']">
                        </div>
                        <p v-if="form.errors.amount" class="text-[10px] text-rose-500 font-bold ml-1">{{ form.errors.amount }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Fecha de Pago</label>
                        <input v-model="form.payment_date" type="date" required
                               class="w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm font-medium">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Método de Pago</label>
                        <select v-model="form.payment_method" required
                                class="w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm font-bold">
                            <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ method.label }}</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Referencia / N° Doc</label>
                        <input v-model="form.reference" type="text" placeholder="Opcional..."
                               class="w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Notas / Observaciones</label>
                    <textarea v-model="form.notes" rows="2" placeholder="Información adicional sobre el pago..."
                              class="w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm resize-none"></textarea>
                </div>

                <!-- Footer Actions -->
                <div class="pt-4 flex flex-col sm:flex-row justify-end items-center gap-3">
                    <button type="button" @click="closeModal"
                            class="w-full sm:w-auto px-6 py-2.5 text-sm font-bold text-zinc-500 dark:text-secondary-400 hover:bg-zinc-100 dark:hover:bg-secondary-800 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="form.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-black uppercase tracking-widest rounded-xl shadow-lg shadow-indigo-500/20 transition-all active:scale-95 disabled:opacity-50">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin text-lg">sync</span>
                        Registrar Pago
                    </button>
                </div>
            </form>
        </div>

        <!-- Overlay to close -->
        <div @click="closeModal" class="fixed inset-0 z-[-1]"></div>
    </div>
</template>
