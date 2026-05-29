<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

defineProps({
    show: Boolean,
    warehouses: Array,
    providers: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    warehouse_id: '',
    provider_id: '',
    file: null,
});

const submit = () => {
    // Implement import logic
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden">
            <div class="p-6 border-b border-zinc-100 dark:border-gray-700 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <span class="material-symbols-outlined">cloud_upload</span>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Importación Masiva</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Carga tu catálogo técnico vía Excel</p>
                </div>
                <button @click="emit('close')" class="ml-auto text-zinc-400 hover:text-zinc-600 dark:hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Almacén Destino *</label>
                        <select v-model="form.warehouse_id" class="w-full bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm">
                            <option value="">Seleccionar</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Proveedor *</label>
                        <select v-model="form.provider_id" class="w-full bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm">
                            <option value="">Seleccionar</option>
                            <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800 rounded-xl p-4 flex gap-3">
                    <span class="material-symbols-outlined text-blue-500">info</span>
                    <p class="text-xs text-blue-700 dark:text-blue-400 leading-relaxed font-medium">
                        La importación registrará el stock inicial en el Kardex.
                    </p>
                </div>

                <div :class="[
                    'relative group border-2 border-dashed rounded-2xl p-10 flex flex-col items-center justify-center transition-all',
                    (!form.warehouse_id || !form.provider_id) 
                        ? 'border-zinc-100 dark:border-gray-800 bg-zinc-50/50 dark:bg-gray-900/20 cursor-not-allowed opacity-50' 
                        : 'border-zinc-200 dark:border-gray-700 bg-zinc-50 dark:bg-gray-800/50 hover:border-indigo-500 cursor-pointer'
                ]">
                    <input type="file" 
                           :disabled="!form.warehouse_id || !form.provider_id"
                           @input="form.file = $event.target.files[0]" 
                           class="absolute inset-0 opacity-0 disabled:cursor-not-allowed cursor-pointer" 
                           accept=".xlsx,.xls,.csv">
                    <span class="material-symbols-outlined text-4xl mb-4 transition-transform"
                          :class="[
                              (!form.warehouse_id || !form.provider_id) ? 'text-zinc-200 dark:text-gray-800' : 'text-zinc-300 group-hover:scale-110'
                          ]">
                        description
                    </span>
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-sm font-bold text-center"
                              :class="(!form.warehouse_id || !form.provider_id) ? 'text-zinc-400' : 'text-zinc-600 dark:text-zinc-300'">
                            {{ form.file ? 'Archivo: ' + form.file.name : 'Click o Arrastra tu Excel' }}
                        </span>
                        <span v-if="!form.warehouse_id || !form.provider_id" class="text-[10px] font-black text-rose-500/70 uppercase tracking-widest animate-pulse">
                            Selecciona Almacén y Proveedor primero
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-100 dark:border-gray-700 flex justify-end gap-3">
                <button @click="emit('close')" class="px-4 py-2 text-sm font-bold text-zinc-500 hover:text-zinc-700">Cancelar</button>
                <button @click="submit" class="px-6 py-2 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 active:scale-95">
                    Previsualizar
                </button>
            </div>
        </div>
    </div>
</template>
