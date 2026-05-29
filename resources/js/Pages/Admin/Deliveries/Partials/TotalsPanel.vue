<script setup>
import { computed } from 'vue';

const props = defineProps({
    sale: Object,
    actualDeliveryDate: String,
});

const emit = defineEmits(['update:actualDeliveryDate']);

const formatMoney = (val) => {
    return parseFloat(val).toFixed(2);
};

const displaySubtotal = computed(() => {
    return parseFloat(props.sale.subtotal) > 0 
        ? props.sale.subtotal 
        : (parseFloat(props.sale.total) - parseFloat(props.sale.delivery_cost) + parseFloat(props.sale.global_discount));
});
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
        <div v-if="sale.is_active && !sale.is_delivered" class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-zinc-500 dark:text-secondary-400 uppercase tracking-widest px-1">Confirmar Fecha de Salida</label>
            <input 
                type="datetime-local" 
                :value="actualDeliveryDate"
                @input="e => $emit('update:actualDeliveryDate', e.target.value)"
                class="w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 rounded-xl text-sm font-bold text-zinc-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 transition-all shadow-sm"
            >
        </div>
        <div v-else class="flex flex-col gap-1">
            <span class="text-[10px] font-bold text-zinc-500 dark:text-secondary-400 uppercase tracking-widest px-1">
                {{ !sale.is_active ? 'Fecha de Anulación' : 'Fecha de Entrega Confirmada' }}
            </span>
            <div class="flex items-center gap-2 px-4 py-2.5 bg-zinc-100 dark:bg-secondary-900 rounded-xl border border-zinc-200 dark:border-secondary-700 text-sm font-bold text-zinc-900 dark:text-white shadow-inner">
                <svg v-if="!sale.is_active" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-rose-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-emerald-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.745 3.745 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                </svg>
                {{ sale.formatted_delivered_at || sale.formatted_cancelled_at }}
            </div>
        </div>

        <div class="p-4 bg-zinc-50 dark:bg-secondary-800 rounded-xl shadow-lg border border-zinc-200 dark:border-secondary-700 space-y-2">
            <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-zinc-500 dark:text-secondary-400">
                <span>Subtotal</span>
                <span class="text-xs text-zinc-400 dark:text-secondary-400">Bs. {{ formatMoney(displaySubtotal) }}</span>
            </div>
            <div class="flex justify-between items-center text-rose-500 font-bold italic border-t border-zinc-100 dark:border-secondary-700/50 pt-1 mt-1">
                <span class="text-[10px] uppercase tracking-widest text-zinc-500 dark:text-secondary-400">Descuento Global</span>
                <span class="text-xs">Bs. {{ formatMoney(sale.global_discount) }}</span>
            </div>
            <div class="flex justify-between items-center border-t border-zinc-800/60 dark:border-secondary-700 pt-2 text-left">
                <span class="text-[11px] font-black text-zinc-900 dark:text-white uppercase tracking-widest leading-none">Total Venta</span>
                <span class="text-xl font-black text-zinc-900 dark:text-white leading-none tracking-tight">Bs. {{ formatMoney(sale.total) }}</span>
            </div>
        </div>
    </div>
</template>
