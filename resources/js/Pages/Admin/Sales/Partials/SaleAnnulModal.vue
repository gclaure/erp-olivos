<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    saleId: String
});

const emit = defineEmits(['close']);

const form = useForm({
    reason: ''
});

const submit = () => {
    form.post(route('admin.sales.annul', props.saleId), {
        onSuccess: () => {
            form.reset();
            emit('close');
        }
    });
};

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-900/40 backdrop-blur-sm overflow-y-auto custom-scrollbar">
        <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden my-auto border border-zinc-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-gray-600">
                <div class="flex items-center gap-4 text-left">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">Anular Venta</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 font-normal mt-1">Esta acción es irreversible y restaurará el stock.</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="p-6">
                <div class="space-y-4">
                    <div class="p-4 bg-rose-50 dark:bg-rose-900/30 rounded-xl border border-rose-100 dark:border-rose-900/50">
                        <p class="text-xs text-rose-700 dark:text-rose-300 font-medium leading-relaxed">
                            Al anular esta venta, los productos volverán al inventario y los movimientos del Kardex asociados serán eliminados.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-widest ml-1">Motivo de Anulación *</label>
                        <textarea 
                            v-model="form.reason"
                            placeholder="Ej. Error en la facturación, los productos fueron devueltos..." 
                            rows="4"
                            required
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all outline-none"
                        ></textarea>
                        <div v-if="form.errors.reason" class="text-xs text-rose-600 font-bold ml-1">{{ form.errors.reason }}</div>
                        <p class="text-[10px] text-zinc-400">Describa brevemente el por qué se está anulando esta transacción.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6">
                    <button type="button" @click="$emit('close')" class="px-6 py-2 text-sm font-bold text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="form.processing"
                            class="px-6 py-2 text-sm font-bold bg-rose-600 text-white hover:bg-rose-700 rounded-lg transition-all shadow-lg shadow-rose-500/20 active:scale-95 disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Confirmar Anulación
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
