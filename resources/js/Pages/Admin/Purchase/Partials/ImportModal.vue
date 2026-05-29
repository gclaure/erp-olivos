<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    warehouses: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    import_file: null,
    warehouse_id: '',
});

const fileInput = ref(null);
const isDragging = ref(false);

watch(() => props.show, (newVal) => {
    if (newVal) {
        form.reset();
        form.clearErrors();
        if (fileInput.value) fileInput.value.value = '';
        
        // Auto-select if only one warehouse
        if (props.warehouses?.length === 1) {
            form.warehouse_id = props.warehouses[0].id;
        }
    }
});

const closeModal = () => {
    emit('close');
};

const handleFileChange = (e) => {
    form.import_file = e.target.files[0];
};

const handleDrop = (e) => {
    isDragging.value = false;
    form.import_file = e.dataTransfer.files[0];
};

const submit = () => {
    form.post(route('admin.purchases.import'), {
        onSuccess: () => closeModal(),
        forceFormData: true,
    });
};

const downloadTemplate = () => {
    window.location.href = route('admin.purchases.template');
};

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/40 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="bg-white dark:bg-zinc-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-zinc-100 dark:border-zinc-700 animate-in zoom-in duration-300">
            <div class="h-1.5 w-full bg-indigo-500"></div>
            
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-black text-zinc-900 dark:text-white tracking-tight uppercase">Importar Compras</h3>
                    <button @click="closeModal" class="text-zinc-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 p-2 rounded-xl transition-all">
                        <span class="material-symbols-outlined text-2xl leading-none">close</span>
                    </button>
                </div>

                <div class="space-y-6 relative">
                    <!-- Progress Overlay -->
                    <div v-if="form.processing" class="absolute inset-0 bg-white/70 dark:bg-zinc-800/70 backdrop-blur-sm z-50 flex flex-col items-center justify-center rounded-xl">
                        <span class="material-symbols-outlined text-5xl text-indigo-600 animate-spin mb-4">sync</span>
                        <p class="text-xs font-black text-zinc-700 dark:text-white uppercase tracking-widest">Procesando Archivo...</p>
                        <p class="text-[10px] text-zinc-400 mt-1 italic">Sincronizando existencias e inventarios</p>
                    </div>

                    <!-- Info Alert -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400 p-4 rounded-2xl text-xs leading-relaxed shadow-sm">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-xl mt-0.5 text-blue-500">info</span>
                            <div>
                                <p class="font-black uppercase tracking-widest mb-1">Guía de Importación</p>
                                <p class="font-medium">Utiliza la plantilla oficial. Las compras se asignarán al Proveedor General y se procesarán agrupándolas por fecha automáticamente.</p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Warehouse Select -->
                        <div v-if="warehouses.length > 1">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-2 ml-1">Almacén Destino *</label>
                            <select v-model="form.warehouse_id" required
                                    class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900/50 border-zinc-200 dark:border-zinc-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm font-medium">
                                <option value="">Seleccione un almacén...</option>
                                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                            </select>
                            <p v-if="form.errors.warehouse_id" class="text-xs text-rose-500 mt-2 ml-1 font-bold">{{ form.errors.warehouse_id }}</p>
                        </div>
                        <div v-else-if="warehouses.length === 1" class="bg-zinc-50 dark:bg-zinc-900/30 p-3 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-700 flex items-center gap-3">
                            <span class="material-symbols-outlined text-zinc-400">inventory_2</span>
                            <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400">Destino: <span class="text-zinc-900 dark:text-white">{{ warehouses[0].name }}</span></span>
                        </div>

                        <!-- Drop Zone -->
                        <div>
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-2 ml-1">Archivo Excel / CSV *</label>
                            <div @dragover.prevent="isDragging = true"
                                 @dragleave.prevent="isDragging = false"
                                 @drop.prevent="handleDrop"
                                 @click="fileInput.click()"
                                 :class="[
                                     'relative flex flex-col items-center justify-center p-8 border-2 border-dashed rounded-3xl transition-all cursor-pointer group',
                                     isDragging ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:border-indigo-400 hover:bg-zinc-50 dark:hover:bg-zinc-900/30'
                                 ]">
                                <input type="file" ref="fileInput" @change="handleFileChange" accept=".xlsx,.xls,.csv" class="hidden">
                                
                                <template v-if="form.import_file">
                                    <span class="material-symbols-outlined text-5xl text-emerald-500 mb-3">description</span>
                                    <p class="text-sm font-black text-zinc-900 dark:text-white tracking-tight text-center break-all px-4">{{ form.import_file.name }}</p>
                                    <p class="text-[10px] text-emerald-600 font-bold uppercase mt-2">Archivo listo para procesar</p>
                                </template>
                                <template v-else>
                                    <span class="material-symbols-outlined text-5xl text-zinc-300 dark:text-zinc-600 mb-3 group-hover:text-indigo-400 transition-colors">cloud_upload</span>
                                    <p class="text-sm font-bold text-zinc-500 dark:text-zinc-400">Haz clic o arrastra el archivo</p>
                                    <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1 uppercase tracking-widest">Excel o CSV hasta 10MB</p>
                                </template>
                            </div>
                            <p v-if="form.errors.import_file" class="text-xs text-rose-500 mt-2 ml-1 font-bold">{{ form.errors.import_file }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-3 mt-8">
                            <button type="submit" :disabled="form.processing || !form.import_file"
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all active:scale-95 disabled:opacity-50 shadow-lg shadow-indigo-500/30">
                                <span class="material-symbols-outlined text-xl">settings_suggest</span>
                                Procesar Importación
                            </button>
                            <div class="flex gap-2">
                                <button type="button" @click="downloadTemplate"
                                        class="flex-1 px-4 py-2.5 text-[10px] font-black text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all uppercase tracking-widest flex items-center justify-center gap-2 border border-transparent hover:border-indigo-100 dark:hover:border-indigo-800">
                                    <span class="material-symbols-outlined text-lg">download</span> Plantilla
                                </button>
                                <button type="button" @click="closeModal"
                                        class="flex-1 px-4 py-2.5 text-[10px] font-black text-zinc-400 dark:text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-900/30 rounded-xl transition-all uppercase tracking-widest flex items-center justify-center gap-2">
                                    Cerrar
                                </button>
                            </div>
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
