<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    warehouses: Array,
    providers: Array,
});

const emit = defineEmits(['close']);

const step = ref(1); // 1: Upload, 2: Preview
const previewData = ref(null);
const fileName = ref('');
const isUploading = ref(false);

const form = useForm({
    warehouse_id: '',
    provider_id: '',
    override_prices: false,
    valid_rows: [],
});

const fileInput = ref(null);

const handleFileUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    isUploading.value = true;
    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await axios.post(route('admin.products.import-preview'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        previewData.value = response.data.preview;
        fileName.value = response.data.file_name;
        form.valid_rows = response.data.preview.valid_rows;
        step.value = 2;
    } catch (error) {
        alert(error.response?.data?.error || 'Error al procesar el archivo');
    } finally {
        isUploading.value = false;
    }
};

const isImporting = ref(false);
const isFinished = ref(false);
const progress = ref(0);
const importMetrics = ref(null);

const submitImport = async () => {
    isImporting.value = true;
    isFinished.value = false;
    progress.value = 0;
    
    try {
        // 1. Inicializar Importación
        const initResponse = await axios.post(route('admin.products.import'), {
            file_name: fileName.value,
            total_rows: form.valid_rows.length
        });
        
        const logId = initResponse.data.log_id;
        const rows = [...form.valid_rows];
        const chunkSize = 50; 
        const totalChunks = Math.ceil(rows.length / chunkSize);
        
        // 2. Procesar Chunks
        for (let i = 0; i < totalChunks; i++) {
            const chunk = rows.slice(i * chunkSize, (i + 1) * chunkSize);
            await axios.post(route('admin.products.import-chunk'), {
                log_id: logId,
                rows: chunk,
                override_prices: form.override_prices
            });
            
            progress.value = Math.round(((i + 1) / totalChunks) * 90);
        }
        
        // 3. Finalizar
        const finishResponse = await axios.post(route('admin.products.import-finalize'), {
            log_id: logId,
            warehouse_id: form.warehouse_id,
            provider_id: form.provider_id
        });
        
        progress.value = 100;
        importMetrics.value = finishResponse.data.metrics;
        isFinished.value = true;
        step.value = 3; // Mostrar resumen

    } catch (error) {
        console.error('Error en la importación por bloques:', error);
        alert(error.response?.data?.error || 'Ocurrió un error durante la importación.');
    } finally {
        isImporting.value = false;
    }
};

const closeModal = () => {
    if (isImporting.value) return;
    
    const wasFinished = isFinished.value;
    
    step.value = 1;
    previewData.value = null;
    fileName.value = '';
    progress.value = 0;
    isImporting.value = false;
    isFinished.value = false;
    importMetrics.value = null;
    form.reset();
    
    emit('close');

    if (wasFinished) {
        router.reload({ preserveScroll: true });
    }
};

const totalValid = computed(() => previewData.value?.success_count || 0);
const totalErrors = computed(() => previewData.value?.error_count || 0);

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
        <div class="bg-white dark:bg-zinc-900 w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden my-auto border border-zinc-200 dark:border-zinc-800 relative">
            
            <!-- Loader Overlay (Replicating Wire:loading) -->
            <div v-if="isUploading || isImporting" class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 backdrop-blur-[2px] flex items-center justify-center z-[50] rounded-xl transition-all">
                <div class="flex flex-col items-center bg-white dark:bg-gray-700 p-6 rounded-2xl shadow-xl border border-zinc-100 dark:border-gray-600 max-w-xs w-full text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-indigo-600 dark:text-indigo-400 animate-spin">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <p class="mt-4 text-sm font-black text-zinc-700 dark:text-zinc-200 uppercase tracking-widest">
                        {{ isUploading ? 'Procesando Archivo...' : 'Importando...' }}
                    </p>
                    
                    <!-- Progress Bar for Chunks -->
                    <div v-if="isImporting" class="w-full mt-4 bg-zinc-100 dark:bg-gray-600 rounded-full h-2 overflow-hidden">
                        <div class="bg-indigo-600 h-full transition-all duration-300 shadow-sm" :style="`width: ${progress}%`"></div>
                    </div>
                    <p v-if="isImporting" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 mt-2">{{ progress }}% completado</p>

                    <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-2 leading-tight">
                        {{ isUploading ? 'Estamos validando tus datos. Por favor, no cierres la ventana.' : 'Estamos procesando los productos en bloques de 50. Por favor, espera a que termine.' }}
                    </p>
                </div>
            </div>

            <!-- Header -->
            <div class="px-8 py-6 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">cloud_upload</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Importación Masiva</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Carga tu catálogo técnico vía Excel</p>
                    </div>
                </div>
                <button @click="closeModal" class="p-2 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="p-8 space-y-6">
                <template v-if="step === 1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="space-y-1">
                            <label class="block text-[11px] font-black text-zinc-400 uppercase tracking-widest mb-1">Almacén Destino *</label>
                            <select v-model="form.warehouse_id" required
                                    class="w-full px-4 py-3 bg-zinc-50 dark:bg-gray-800 border-zinc-200 dark:border-gray-700 rounded-xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all dark:text-white">
                                <option value="">Seleccionar</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-black text-zinc-400 uppercase tracking-widest mb-1">Proveedor *</label>
                            <select v-model="form.provider_id" required
                                    class="w-full px-4 py-3 bg-zinc-50 dark:bg-gray-800 border-zinc-200 dark:border-gray-700 rounded-xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all dark:text-white">
                                <option value="">Seleccionar</option>
                                <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800 rounded-xl p-4 flex gap-3">
                        <span class="material-symbols-outlined text-blue-500 text-lg">info</span>
                        <p class="text-xs text-blue-700 dark:text-blue-400 leading-relaxed font-medium">La importación registrará el stock inicial en el Kardex.</p>
                    </div>

                    <div class="relative group" 
                         :class="(!form.warehouse_id || !form.provider_id) ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
                         @click="(!form.warehouse_id || !form.provider_id) ? null : fileInput.click()">
                        <input type="file" ref="fileInput" @change="handleFileUpload" class="hidden" accept=".xlsx,.xls,.csv" :disabled="!form.warehouse_id || !form.provider_id">
                        <div class="border-2 border-dashed rounded-2xl p-10 flex flex-col items-center justify-center transition-all"
                             :class="(!form.warehouse_id || !form.provider_id) 
                                ? 'border-zinc-100 dark:border-gray-800 bg-zinc-50/50 dark:bg-gray-900/20' 
                                : 'border-zinc-200 dark:border-gray-700 bg-zinc-50 dark:bg-gray-800/50 group-hover:border-indigo-500'">
                            <span class="material-symbols-outlined text-4xl mb-4 transition-transform"
                                  :class="(!form.warehouse_id || !form.provider_id) ? 'text-zinc-200 dark:text-gray-800' : 'text-zinc-300 group-hover:scale-110'">
                                description
                            </span>
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-sm font-bold text-center"
                                      :class="(!form.warehouse_id || !form.provider_id) ? 'text-zinc-400' : 'text-zinc-600 dark:text-zinc-300'">
                                    {{ form.file ? 'Archivo Listo' : 'Click o Arrastra tu Excel' }}
                                </span>
                                <span v-if="!form.warehouse_id || !form.provider_id" class="text-[10px] font-black text-rose-500/70 uppercase tracking-widest animate-pulse">
                                    Selecciona Almacén y Proveedor primero
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else-if="step === 2">
                    <!-- Exact Preview Summary from Livewire -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-xl text-center">
                            <p class="text-xs font-bold text-emerald-800 dark:text-emerald-400 uppercase tracking-widest">Válidos</p>
                            <p class="text-2xl font-black text-emerald-600 mt-1">{{ totalValid }}</p>
                        </div>
                        <div class="p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 rounded-xl text-center">
                            <p class="text-xs font-bold text-rose-800 dark:text-rose-400 uppercase tracking-widest">Errores</p>
                            <p class="text-2xl font-black text-rose-600 mt-1">{{ totalErrors }}</p>
                        </div>
                    </div>

                    <!-- Detalle de Errores -->
                    <div v-if="previewData?.errors?.length > 0" class="mt-6">
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-rose-500 text-sm">warning</span>
                            Detalle de Errores
                        </h4>
                        <div class="max-h-60 overflow-y-auto space-y-2 border border-zinc-100 dark:border-gray-700 rounded-xl p-2 bg-zinc-50 dark:bg-gray-800/50 custom-scrollbar">
                            <div v-for="(err, i) in previewData.errors" :key="i" class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-rose-100 dark:border-rose-900/30">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-black text-zinc-800 dark:text-white uppercase">{{ err.descripcion || 'Fila sin descripción' }}</p>
                                        <ul class="mt-1 list-disc list-inside text-[11px] text-rose-600 dark:text-rose-400">
                                            <li v-for="(msg, j) in err.messages" :key="j">{{ msg }}</li>
                                        </ul>
                                    </div>
                                    <span class="shrink-0 px-2 py-1 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 text-[10px] font-black rounded-md">
                                        Fila {{ err.row }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else-if="step === 3">
                    <div class="text-center space-y-6 py-4">
                        <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
                            <span class="material-symbols-outlined text-4xl">check_circle</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-zinc-900 dark:text-white uppercase tracking-tight">¡Importación Exitosa!</h3>
                            <p class="text-sm text-zinc-500 mt-1">El catálogo técnico se ha actualizado correctamente.</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 max-w-md mx-auto">
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700">
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-widest">Creados</p>
                                <p class="text-xl font-black text-emerald-600">{{ importMetrics?.created_count || 0 }}</p>
                            </div>
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700">
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-widest">Actualizados</p>
                                <p class="text-xl font-black text-blue-600">{{ importMetrics?.updated_count || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Footer Actions -->
                <div class="flex flex-col gap-3 pt-4 border-t border-zinc-100 dark:border-gray-700 mt-4">
                    <div v-if="Object.keys(form.errors).length > 0" class="p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 rounded-xl">
                        <ul class="list-disc list-inside text-[10px] text-rose-600 dark:text-rose-400 font-bold uppercase tracking-tighter">
                            <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                        </ul>
                    </div>
                    <div class="flex items-center justify-end gap-3 w-full">
                        <button v-if="step !== 3" type="button" @click="closeModal" class="px-6 py-2.5 text-sm font-bold text-zinc-500 hover:text-zinc-700 transition-colors">
                            Cancelar
                        </button>
                        
                        <template v-if="step === 1">
                            <button type="button" @click="fileInput.click()" 
                                    :disabled="!form.warehouse_id || !form.provider_id"
                                    class="px-8 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
                                Previsualizar
                            </button>
                        </template>
                        <template v-else-if="step === 2">
                            <button type="button" @click="submitImport" :disabled="isImporting || totalValid === 0"
                                    class="px-8 py-2.5 bg-emerald-600 text-white text-sm font-black rounded-xl hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-500/20 active:scale-95 disabled:opacity-50">
                                {{ isImporting ? 'Importando...' : 'Importar Ahora' }}
                            </button>
                        </template>
                        <template v-else-if="step === 3">
                            <button type="button" @click="closeModal" 
                                    class="w-full sm:w-auto px-10 py-3 bg-zinc-900 text-white text-sm font-black rounded-xl hover:bg-black transition-all shadow-xl active:scale-95">
                                Cerrar y Ver Productos
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
