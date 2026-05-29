<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    warehouse: Object,
    branches: Array,
    activeBranchId: [String, Number],
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    branch_id: '',
    address: '',
    is_active: true,
});

watch(() => [props.warehouse, props.show, props.activeBranchId], ([newWarehouse, isShowing, activeId]) => {
    if (newWarehouse) {
        form.name = newWarehouse.name || '';
        form.branch_id = newWarehouse.branch_id || '';
        form.address = newWarehouse.address || '';
        form.is_active = newWarehouse.is_active ?? true;
    } else if (isShowing) {
        form.reset();
        form.branch_id = activeId || '';
        form.is_active = true;
    }
}, { immediate: true });

const submit = () => {
    if (props.warehouse) {
        form.put(route('admin.warehouses.update', props.warehouse.id), {
            onSuccess: () => {
                emit('close');
                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Almacén actualizado correctamente.',
                    icon: 'success',
                    confirmButtonColor: '#4f46e5',
                });
            },
        });
    } else {
        form.post(route('admin.warehouses.store'), {
            onSuccess: () => {
                emit('close');
                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Almacén creado correctamente.',
                    icon: 'success',
                    confirmButtonColor: '#4f46e5',
                });
            },
        });
    }
};

const closeModal = () => {
    emit('close');
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background backdrop -->
            <div @click="closeModal" class="fixed inset-0 bg-zinc-900/60 dark:bg-black/80 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-secondary-800 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-zinc-200 dark:border-secondary-700 animate-in zoom-in duration-200">
                <!-- Header -->
                <div class="px-6 py-6 border-b border-zinc-100 dark:border-secondary-700 flex items-center gap-4 bg-zinc-50 dark:bg-secondary-800/50">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-2xl">warehouse</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">
                            {{ warehouse ? 'Editar Almacén' : 'Nuevo Almacén' }}
                        </h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            {{ warehouse ? 'Actualiza los datos del almacén' : 'Registra un nuevo punto de almacenamiento' }}
                        </p>
                    </div>
                    <button @click="closeModal" class="ml-auto w-8 h-8 flex items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-secondary-700 transition-all">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <!-- Nombre -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest ml-1">Nombre *</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors text-lg">edit</span>
                            </div>
                            <input v-model="form.name"
                                   type="text"
                                   placeholder="Ej. Almacén Central"
                                   class="w-full pl-11 pr-4 py-3 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white placeholder-zinc-400 shadow-sm"
                                   :class="{ 'border-rose-500 focus:ring-rose-500/10': form.errors.name }">
                        </div>
                        <p v-if="form.errors.name" class="text-[10px] font-bold text-rose-500 mt-1 ml-1 uppercase tracking-tight">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Sucursal -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest ml-1">Sucursal *</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors text-lg">storefront</span>
                                </div>
                                <select v-model="form.branch_id"
                                        :disabled="true"
                                        class="w-full pl-11 pr-4 py-3 bg-zinc-50 dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white appearance-none shadow-sm disabled:opacity-70 disabled:cursor-not-allowed"
                                        :class="{ 'border-rose-500 focus:ring-rose-500/10': form.errors.branch_id }">
                                    <option value="" disabled>Seleccionar sucursal</option>
                                    <option v-for="branch in branches" :key="branch.value" :value="branch.value">
                                        {{ branch.label }}
                                    </option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-zinc-400">expand_more</span>
                                </div>
                            </div>
                        </div>

                        <!-- Dirección -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest ml-1">Dirección</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors text-lg">location_on</span>
                                </div>
                                <input v-model="form.address"
                                       type="text"
                                       placeholder="Dirección del almacén"
                                       class="w-full pl-11 pr-4 py-3 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white placeholder-zinc-400 shadow-sm"
                                       :class="{ 'border-rose-500 focus:ring-rose-500/10': form.errors.address }">
                            </div>
                        </div>
                    </div>



                    <!-- Estado -->
                    <div class="p-4 bg-zinc-50 dark:bg-secondary-900/50 rounded-2xl border border-zinc-200/60 dark:border-secondary-700 flex items-center justify-between transition-all hover:border-zinc-300 dark:hover:border-secondary-600 shadow-inner">
                        <div class="pr-2">
                            <p class="text-xs font-bold text-zinc-900 dark:text-white leading-tight uppercase tracking-wide">Estado del almacén</p>
                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-1 uppercase tracking-tighter">¿Este almacén estará activo y disponible?</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-500/20 dark:peer-focus:ring-indigo-500/10 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-600 peer-checked:bg-indigo-600 shadow-sm"></div>
                        </label>
                    </div>

                    <!-- Footer -->
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-secondary-700 mt-4">
                        <button type="button" 
                                @click="closeModal" 
                                class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest hover:bg-zinc-100 dark:hover:bg-secondary-700/50 rounded-xl transition-all">
                            Cancelar
                        </button>
                        <button type="submit" 
                                :disabled="form.processing"
                                class="w-full sm:w-auto px-8 py-2.5 bg-indigo-600 text-white text-xs font-bold uppercase tracking-widest rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 active:scale-95 disabled:opacity-60 flex items-center justify-center gap-2">
                            <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            {{ warehouse ? 'Guardar Cambios' : 'Crear Almacén' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
}
</style>
