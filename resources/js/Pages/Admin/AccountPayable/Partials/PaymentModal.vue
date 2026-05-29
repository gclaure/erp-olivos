<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    record: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'EFECTIVO',
    reference: '',
    notes: '',
    receipt: null,
});

const fileInput = ref(null);

watch(() => props.record, (newRecord) => {
    if (newRecord) {
        form.amount = newRecord.balance.toFixed(2);
        form.payment_date = new Date().toISOString().split('T')[0];
    }
}, { immediate: true });

const closeModal = () => {
    emit('close');
    form.reset();
    form.clearErrors();
    if (fileInput.value) fileInput.value.value = '';
};

const handleFileChange = (e) => {
    form.receipt = e.target.files[0];
};

const submit = () => {
    form.post(route('admin.accounts-payable.payment', props.record.id), {
        onSuccess: () => closeModal(),
        forceFormData: true,
    });
};

const paymentMethods = ['EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'CHEQUE', 'OTRO'];

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/40 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="bg-white dark:bg-gray-700 w-full max-w-lg rounded-xl shadow-xl overflow-hidden border border-zinc-100 dark:border-gray-600 animate-in zoom-in duration-300">
            <div class="h-1.5 w-full bg-emerald-500"></div>
            
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Registrar Pago</h3>
                    <button @click="closeModal" class="text-zinc-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 p-2 rounded-lg transition-all">
                        <span class="material-symbols-outlined text-2xl leading-none">close</span>
                    </button>
                </div>

                <div v-if="record" class="mb-6 p-4 bg-zinc-50 dark:bg-gray-600 rounded-lg border border-zinc-100 dark:border-gray-500 flex items-center justify-between">
                    <div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wider font-bold">Compra</div>
                        <div class="font-bold text-zinc-900 dark:text-white">{{ record.purchase?.purchase_number }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-zinc-500 uppercase tracking-wider font-bold">Saldo Pendiente</div>
                        <div class="font-black text-rose-600 text-lg">Bs. {{ record.balance.toLocaleString('es-BO', {minimumFractionDigits: 2}) }}</div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Amount -->
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Monto a Pagar *</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-zinc-500 sm:text-sm">Bs.</span>
                            </div>
                            <input v-model="form.amount" type="number" step="0.01" required
                                   class="block w-full pl-10 pr-4 py-2 border-zinc-300 dark:border-gray-500 dark:bg-gray-700 dark:text-white rounded-md focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                   placeholder="0.00">
                        </div>
                        <p v-if="form.errors.amount" class="text-xs text-rose-500 mt-1">{{ form.errors.amount }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Date -->
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Fecha de Pago *</label>
                            <input v-model="form.payment_date" type="date" required
                                   class="block w-full border-zinc-300 dark:border-gray-500 dark:bg-gray-700 dark:text-white rounded-md focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                        </div>

                        <!-- Method -->
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Método de Pago</label>
                            <select v-model="form.payment_method" required
                                    class="block w-full border-zinc-300 dark:border-gray-500 dark:bg-gray-700 dark:text-white rounded-md focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                                <option v-for="method in paymentMethods" :key="method" :value="method">{{ method }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Reference -->
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Referencia (Nro. Operación)</label>
                        <input v-model="form.reference" type="text" placeholder="Ej: TX-12345"
                               class="block w-full border-zinc-300 dark:border-gray-500 dark:bg-gray-700 dark:text-white rounded-md focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                    </div>
                    
                    <!-- Receipt -->
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Adjuntar Comprobante (Imagen)</label>
                        <input type="file" ref="fileInput" @change="handleFileChange" accept="image/*"
                               class="block w-full text-sm text-zinc-500 dark:text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-600 dark:file:text-zinc-300">
                        <div v-if="form.progress" class="w-full bg-zinc-200 dark:bg-gray-500 rounded-full h-1.5 mt-2">
                            <div class="bg-indigo-600 h-1.5 rounded-full" :style="`width: ${form.progress.percentage}%`"></div>
                        </div>
                        <p v-if="form.errors.receipt" class="text-xs text-rose-500 mt-1">{{ form.errors.receipt }}</p>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Notas (Opcional)</label>
                        <textarea v-model="form.notes" rows="2" placeholder="..."
                                  class="block w-full border-zinc-300 dark:border-gray-500 dark:bg-gray-700 dark:text-white rounded-md focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm resize-none"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col-reverse sm:flex-row gap-2 justify-end mt-8">
                        <button type="button" @click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-gray-600 rounded-md transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-md transition-all active:scale-95 disabled:opacity-50 shadow-sm">
                            <span v-if="form.processing" class="material-symbols-outlined animate-spin text-lg">sync</span>
                            Confirmar Pago
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Overlay -->
        <div @click="closeModal" class="fixed inset-0 z-[-1]"></div>
    </div>
</template>
