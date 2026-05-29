<script setup>
import { computed, watch, ref } from 'vue';

const props = defineProps({
    form: Object,
    operationType: String,
    isFixedDiscount: Boolean,
    globalDiscount: [Number, String],
    finalTotal: Number,
    selectedClient: Object,
    clients: Array,
    defaultClient: Object
});

const emit = defineEmits(['update:global-discount']);

const paymentType = ref('efectivo');
const creditDays = ref(0);
const dueDate = ref(null);
const downPayment = ref(0);

// Watchers to sync with form (two-way)
watch(paymentType, (val) => props.form.payment_type = val);
watch(() => props.form.payment_type, (val) => {
    if (val && val !== paymentType.value) paymentType.value = val;
});

watch(creditDays, (val) => {
    props.form.credit_days = val;
    if (val > 0) {
        const date = new Date();
        date.setDate(date.getDate() + parseInt(val));
        dueDate.value = date.toISOString().split('T')[0];
    } else {
        dueDate.value = null;
    }
});
watch(() => props.form.credit_days, (val) => {
    if (val !== undefined && val !== creditDays.value) creditDays.value = val;
});

watch(dueDate, (val) => props.form.due_date = val);
watch(() => props.form.due_date, (val) => {
    if (val !== undefined && val !== dueDate.value) dueDate.value = val;
});

watch(downPayment, (val) => props.form.down_payment = val);
watch(() => props.form.down_payment, (val) => {
    if (val !== undefined && val !== downPayment.value) downPayment.value = val;
});

const selectedClientObj = computed(() => {
    if (props.selectedClient) return props.selectedClient;
    return props.clients.find(c => c.id === props.form.client_id);
});

const creditAnalysis = computed(() => {
    const client = selectedClientObj.value;
    if (!client) return { limit: 0, available: 0, exceeded: false, unlimited: true };

    const limit = parseFloat(client.credit_limit) || 0;
    const balance = parseFloat(client.current_balance) || 0;
    const creditUsed = Math.max(0, props.finalTotal - (parseFloat(downPayment.value) || 0));
    const newBalance = balance + creditUsed;
    
    const unlimited = limit === 0;
    const available = unlimited ? 0 : limit - newBalance;
    const exceeded = !unlimited && available < 0;

    return { limit, available, newBalance, exceeded, unlimited };
});

const formatMoney = (val) => parseFloat(val || 0).toFixed(2);
</script>

<template>
    <div class="space-y-6">
        <!-- TIPO DE VENTA -->
        <div v-if="operationType === 'sale'" class="pt-2">
            <p class="text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-blue-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75-3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V14.1" />
                </svg>
                TIPO DE VENTA *
            </p>
            <div class="grid grid-cols-2 gap-3">
                <button 
                    type="button"
                    @click="paymentType = 'efectivo'"
                    class="flex flex-col items-center justify-center p-3 rounded-2xl border-2 transition-all gap-2"
                    :class="paymentType === 'efectivo' 
                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-4 ring-emerald-500/10' 
                        : 'border-zinc-100 dark:border-secondary-700 bg-white dark:bg-secondary-900 text-zinc-400 dark:text-secondary-500 hover:border-emerald-200 dark:hover:border-emerald-500/30'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                    </svg>
                    <span class="text-[10px] font-black uppercase tracking-tight">Al Contado</span>
                </button>

                <button 
                    v-if="!defaultClient || form.client_id !== defaultClient.id"
                    type="button"
                    @click="paymentType = 'credito'"
                    class="flex flex-col items-center justify-center p-3 rounded-2xl border-2 transition-all gap-2"
                    :class="paymentType === 'credito' 
                        ? 'border-amber-500 bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-4 ring-amber-500/10' 
                        : 'border-zinc-100 dark:border-secondary-700 bg-white dark:bg-secondary-900 text-zinc-400 dark:text-secondary-500 hover:border-amber-200 dark:hover:border-amber-500/30'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span class="text-[10px] font-black uppercase tracking-tight">Al Crédito</span>
                </button>
            </div>
        </div>

        <!-- DETALLES DE PAGO Y DESCUENTO -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Descuento Global -->
            <div>
                <p class="text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-widest mb-2 flex justify-between">
                    <span>DESCUENTO GLOBAL</span>
                    <span class="text-rose-500 dark:text-rose-400 text-[9px]">(OPCIONAL)</span>
                </p>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-black text-rose-500" v-text="isFixedDiscount ? 'Bs' : '%'"></span>
                    <input 
                        type="number" 
                        :value="globalDiscount"
                        @input="e => $emit('update:global-discount', e.target.value)"
                        class="w-full pl-8 pr-3 py-3 border border-zinc-200 dark:border-secondary-700 rounded-xl text-sm font-black text-zinc-800 dark:text-white bg-rose-500/5 dark:bg-rose-500/10 focus:ring-4 focus:ring-rose-500/10 focus:border-rose-400 text-right placeholder:text-zinc-300 dark:placeholder:text-secondary-600"
                        :placeholder="isFixedDiscount ? '0.00' : '0'"
                    >
                </div>
            </div>

            <!-- Plazo Crédito -->
            <div v-if="paymentType === 'credito'">
                <p class="text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-widest mb-2">PLAZO (DÍAS) *</p>
                <input 
                    type="number" 
                    v-model="creditDays"
                    class="w-full border border-zinc-200 dark:border-secondary-700 rounded-xl px-3 py-3 text-sm font-black text-zinc-800 dark:text-white bg-white dark:bg-secondary-900 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-400 text-center" 
                    placeholder="0"
                >
            </div>
        </div>

        <!-- Campos Adicionales Crédito -->
        <div v-if="paymentType === 'credito'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-widest mb-2">FECHA VENCIMIENTO</p>
                <input 
                    type="date"
                    v-model="dueDate"
                    class="w-full border border-zinc-200 dark:border-secondary-700 rounded-xl px-3 py-3 text-sm font-bold text-zinc-800 dark:text-white bg-white dark:bg-secondary-900 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-400"
                >
            </div>
            <div>
                <p class="text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-widest mb-2 flex justify-between">
                    <span>ADELANTO (CUOTA INICIAL)</span>
                    <span class="text-emerald-500 dark:text-emerald-400 text-[9px]">OPCIONAL</span>
                </p>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-zinc-400">Bs</span>
                    <input 
                        type="number" 
                        v-model="downPayment"
                        class="w-full pl-8 pr-3 py-3 border border-zinc-200 dark:border-secondary-700 rounded-xl text-sm font-black text-zinc-800 dark:text-white bg-white dark:bg-secondary-900 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-400 text-right"
                        placeholder="0.00"
                    >
                </div>
            </div>
        </div>

        <!-- Análisis de Crédito -->
        <div v-if="paymentType === 'credito'" class="grid grid-cols-1 gap-4">
            <div v-if="creditAnalysis.unlimited" class="bg-blue-500/5 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 rounded-2xl p-4">
                <div class="flex items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-blue-500"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" /></svg>
                    <p class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">CRÉDITO SIN LÍMITE</p>
                </div>
                <p class="text-xs font-black text-blue-700 dark:text-blue-200">Saldo Nuevo: Bs. {{ formatMoney(creditAnalysis.newBalance) }}</p>
            </div>

            <div v-else :class="creditAnalysis.exceeded ? 'bg-rose-500/5 border-rose-200 dark:border-rose-500/20' : 'bg-emerald-500/5 border-emerald-200 dark:border-emerald-500/20'" class="border rounded-2xl p-4">
                <p class="text-[10px] font-black uppercase mb-1" :class="creditAnalysis.exceeded ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'">
                    {{ creditAnalysis.exceeded ? 'Límite Excedido' : 'Crédito Disponible (Post-Venta)' }}
                </p>
                <p class="text-xl font-black" :class="creditAnalysis.exceeded ? 'text-rose-700 dark:text-rose-200' : 'text-emerald-700 dark:text-emerald-100'">
                    Bs. {{ formatMoney(creditAnalysis.available) }}
                </p>
                <p class="text-[9px] font-bold mt-1 opacity-60">Límite Total: Bs. {{ formatMoney(creditAnalysis.limit) }}</p>
                
                <div v-if="creditAnalysis.exceeded" class="mt-2 flex items-center gap-1.5 text-rose-600 dark:text-rose-400 text-[10px] font-black uppercase">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" /></svg>
                    No es posible confirmar.
                </div>
            </div>
        </div>
    </div>
</template>
