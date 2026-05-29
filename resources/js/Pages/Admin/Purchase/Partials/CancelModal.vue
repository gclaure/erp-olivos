<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    record: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    reason: '',
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        form.reason = '';
        form.clearErrors();
    }
});

const closeModal = () => {
    emit('close');
};

const submit = () => {
    form.post(route('admin.purchases.cancel', props.record.id), {
        onSuccess: () => closeModal(),
        preserveScroll: true,
    });
};

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/40 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="bg-white dark:bg-zinc-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-zinc-100 dark:border-zinc-700 animate-in zoom-in duration-300">
            <div class="h-1.5 w-full bg-rose-500"></div>
            
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-black text-zinc-900 dark:text-white tracking-tight uppercase">Confirmar Cancelación</h3>
                    <button @click="closeModal" class="text-zinc-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 p-2 rounded-xl transition-all">
                        <span class="material-symbols-outlined text-2xl leading-none">close</span>
                    </button>
                </div>

                <div class="space-y-6">
                    <!-- Warning Alert -->
                    <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 p-4 rounded-2xl text-xs leading-relaxed shadow-sm">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-xl mt-0.5">warning</span>
                            <div>
                                <p class="font-black uppercase tracking-widest mb-1">¡Acción Irreversible!</p>
                                <p class="font-medium">Esta acción revertirá el stock de los productos e iterará anulando existencias, generando salidas tipo reversión en el Kardex de manera permanente.</p>
                            </div>
                        </div>
                    </div>
                    
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-2 ml-1">
                                Motivo de la cancelación <span class="text-rose-500">*</span>
                            </label>
                            <textarea v-model="form.reason" 
                                      placeholder="Especifique detalladamente la razón (Mínimo 5 caracteres)..." 
                                      rows="4"
                                      class="w-full px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50 border-zinc-200 dark:border-zinc-700 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm placeholder-zinc-400 font-medium resize-none"></textarea>
                            <p v-if="form.errors.reason" class="text-xs text-rose-500 mt-2 ml-1 font-bold">{{ form.errors.reason }}</p>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end mt-8">
                            <button type="button" @click="closeModal"
                                    class="px-6 py-2.5 text-xs font-black text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition-all border border-transparent hover:border-zinc-200 dark:hover:border-zinc-600 uppercase tracking-widest">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing"
                                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all active:scale-95 disabled:opacity-50 shadow-lg shadow-rose-500/30">
                                <span v-if="form.processing" class="material-symbols-outlined animate-spin text-lg">sync</span>
                                <span v-else class="material-symbols-outlined text-lg">block</span>
                                Confirmar Anulación
                            </button>
                        </div>
                    </form>
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
