<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    form: Object,
    finalTotal: Number,
    operationType: String
});

const emit = defineEmits(['update:amount-received']);

const amountReceived = ref(0);

// Sync with parent via emit
watch(amountReceived, (val) => emit('update:amount-received', val), { immediate: true });


const pendingBalance = computed(() => {
    return Math.max(0, props.finalTotal - (parseFloat(props.form.down_payment) || 0));
});

const formatMoney = (val) => parseFloat(val || 0).toFixed(2);
</script>

<template>
    <div v-if="operationType === 'sale'" class="space-y-4 pt-4">

        <div v-if="form.payment_type === 'credito'" class="p-4 bg-amber-500/5 border border-amber-500/20 rounded-2xl flex justify-between items-center">
            <span class="text-xs font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest">Saldo a Deber:</span>
            <span class="text-2xl font-black text-amber-600 dark:text-amber-400">Bs. {{ formatMoney(pendingBalance) }}</span>
        </div>
    </div>
</template>
