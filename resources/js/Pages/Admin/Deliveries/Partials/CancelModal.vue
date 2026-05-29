<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'confirm']);

const reason = ref('');

const handleConfirm = () => {
    if (reason.value.trim().length < 5) return;
    emit('confirm', reason.value);
    reason.value = '';
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-900/60 backdrop-blur-sm">
        <div class="w-full max-w-md bg-white dark:bg-secondary-800 rounded-2xl shadow-2xl overflow-hidden border border-zinc-200 dark:border-secondary-700 transition-all duration-300">
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-secondary-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Cancelar Despacho</h3>
                <button @click="$emit('close')" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl flex items-start gap-3 border border-amber-100 dark:border-amber-900/30">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200 leading-tight">Acción Irreversible</h4>
                        <p class="text-xs text-amber-700 dark:text-amber-300/80 mt-1">Al cancelar el despacho, la venta se marcará como anulada. Si el stock no fue entregado, este se mantendrá disponible en el almacén.</p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-zinc-700 dark:text-secondary-300">Motivo de la cancelación</label>
                    <textarea 
                        v-model="reason"
                        placeholder="Ej: El cliente no se presentó a recoger el pedido..."
                        class="w-full px-4 py-3 bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 rounded-xl text-sm focus:ring-rose-500 focus:border-rose-500 min-h-[100px] resize-none transition-all"
                    ></textarea>
                    <p class="text-[10px] text-zinc-400">Mínimo 5 caracteres.</p>
                </div>
            </div>

            <div class="px-6 py-4 bg-zinc-50 dark:bg-secondary-900/50 border-t border-zinc-100 dark:border-secondary-700 flex items-center justify-end gap-3">
                <button @click="$emit('close')" class="px-4 py-2 text-sm font-bold text-zinc-500 hover:text-zinc-700 dark:text-secondary-400 dark:hover:text-secondary-200">
                    Volver
                </button>
                <button 
                    @click="handleConfirm"
                    :disabled="reason.trim().length < 5"
                    class="px-6 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-sm font-bold shadow-md shadow-rose-500/20 transition-all flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Confirmar Anulación
                </button>
            </div>
        </div>
    </div>
</template>
