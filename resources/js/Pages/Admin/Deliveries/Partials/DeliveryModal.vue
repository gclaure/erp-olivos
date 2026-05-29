<script setup>
import { ref, watch } from 'vue';
import RecipientCard from './RecipientCard.vue';
import LogisticsPanels from './LogisticsPanels.vue';
import ItemsTable from './ItemsTable.vue';
import TotalsPanel from './TotalsPanel.vue';

const props = defineProps({
    show: Boolean,
    sale: Object,
});

const emit = defineEmits(['close', 'process']);

const actualDeliveryDate = ref('');

watch(() => props.show, (newVal) => {
    if (newVal) {
        // Set default date to now in YYYY-MM-DDTHH:mm format for datetime-local
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        actualDeliveryDate.value = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
});

const handleProcess = () => {
    emit('process', actualDeliveryDate.value);
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-0 overflow-hidden bg-zinc-900/60 backdrop-blur-sm">
        <div class="w-full h-full bg-white dark:bg-secondary-800 flex flex-col transition-all duration-300">
            <!-- Header -->
            <div class="flex-shrink-0 px-6 py-4 bg-zinc-50 dark:bg-secondary-900 border-b border-zinc-200 dark:border-secondary-700 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-primary-500/10 dark:bg-primary-500/20 flex items-center justify-center border border-primary-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-primary-600 dark:text-primary-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a.75.75 0 0 1 .75.75 6.75 6.75 0 0 1-13.5 0 .75.75 0 0 1 .75-.75h12Z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-black text-zinc-900 dark:text-white uppercase tracking-tighter">Protocolo de Despacho</h2>
                            <span class="px-2.5 py-0.5 rounded-full bg-zinc-200 dark:bg-secondary-700 text-zinc-600 dark:text-secondary-400 text-[10px] font-black uppercase">Venta #{{ sale?.formatted_number }}</span>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-secondary-500 font-medium">Verifique los detalles logísticos antes de confirmar la salida física.</p>
                    </div>
                </div>
                <button @click="$emit('close')" class="p-2.5 rounded-xl bg-white dark:bg-secondary-800 text-zinc-400 hover:text-rose-500 dark:hover:text-rose-400 border border-zinc-200 dark:border-secondary-700 hover:border-rose-200 transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 custom-scrollbar bg-zinc-50/30 dark:bg-secondary-900/10">
                <div v-if="sale" class="max-w-6xl mx-auto space-y-6">
                    <RecipientCard :sale="sale" />
                    <LogisticsPanels :sale="sale" />
                    <ItemsTable :details="sale.details" />
                    <TotalsPanel :sale="sale" v-model:actualDeliveryDate="actualDeliveryDate" />
                </div>
                <div v-else class="flex flex-col items-center justify-center h-full gap-4">
                    <div class="w-12 h-12 border-4 border-primary-500/20 border-t-primary-500 rounded-full animate-spin"></div>
                    <span class="text-sm font-medium text-zinc-500 dark:text-secondary-400">Cargando protocolo de despacho...</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex-shrink-0 px-6 py-4 bg-zinc-50 dark:bg-secondary-900 border-t border-zinc-200 dark:border-secondary-700">
                <div class="max-w-6xl mx-auto flex items-center justify-between">
                    <div class="hidden sm:flex items-center gap-4 text-[10px] font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div> Stock Actualizado
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full bg-primary-500"></div> Registro en Kardex
                        </div>
                    </div>
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <button @click="$emit('close')" class="flex-1 sm:flex-none px-6 py-3 rounded-xl bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 text-zinc-700 dark:text-secondary-300 font-bold text-xs uppercase hover:bg-zinc-100 dark:hover:bg-secondary-700 transition-all">
                            Volver
                        </button>
                        <button 
                            v-if="sale?.is_active && !sale?.is_delivered"
                            @click="handleProcess" 
                            class="flex-1 sm:flex-none px-8 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-2 active:scale-95"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.745 3.745 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                            </svg>
                            Confirmar y Finalizar Entrega
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e4e4e7; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #3f3f46; }
</style>
