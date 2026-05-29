<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    branch: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    address: '',
    phone: '',
    whatsapp_number: '',
    is_main: false,
    is_active: true,
});

watch(() => props.show, (show) => {
    if (show) {
        if (props.branch) {
            form.name = props.branch.name;
            form.address = props.branch.address;
            form.phone = props.branch.phone || '';
            form.whatsapp_number = props.branch.whatsapp_number || '';
            form.is_main = !!props.branch.is_main;
            form.is_active = !!props.branch.is_active;
        } else {
            form.reset();
        }
    }
});

const submit = () => {
    if (props.branch) {
        form.put(route('admin.branches.update', props.branch.id), {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        form.post(route('admin.branches.store'), {
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const closeModal = () => {
    emit('close');
    form.reset();
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-0 sm:p-6">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-zinc-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

        <!-- Modal Content -->
        <div class="relative bg-white dark:bg-gray-800 w-full h-full sm:h-auto sm:max-h-[90vh] sm:max-w-2xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden transition-all transform border-t sm:border border-zinc-200 dark:border-gray-700 mt-auto sm:mt-0">
            <!-- Header (Fixed) -->
            <div class="shrink-0 px-6 py-4 border-b border-zinc-100 dark:border-gray-700 bg-white dark:bg-gray-800 z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-gray-700 text-zinc-900 dark:text-white flex items-center justify-center">
                            <span class="material-symbols-outlined">corporate_fare</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">
                                {{ branch ? 'Editar Sucursal' : 'Nueva Sucursal' }}
                            </h2>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium uppercase tracking-wider">Información de la sede</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto no-scrollbar p-6 space-y-5">
                <form id="branchForm" @submit.prevent="submit" class="space-y-5 pb-4">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-xs font-black text-zinc-400 uppercase tracking-widest mb-1.5 ml-1">Nombre de la sucursal *</label>
                        <input v-model="form.name" type="text" placeholder="Ej. Sucursal Norte"
                               class="w-full px-4 py-3 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none text-zinc-900 dark:text-white"
                                :class="{'border-rose-500 ring-rose-500/10': form.errors.name}">
                        <p v-if="form.errors.name" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- Dirección -->
                    <div>
                        <label class="block text-xs font-black text-zinc-400 uppercase tracking-widest mb-1.5 ml-1">Dirección Exacta *</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-3 text-zinc-400 text-[18px]">location_on</span>
                            <input v-model="form.address" type="text" placeholder="Calle, Av, Ciudad..."
                                   class="w-full pl-10 pr-4 py-3 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none text-zinc-900 dark:text-white"
                                   :class="{'border-rose-500 ring-rose-500/10': form.errors.address}">
                        </div>
                        <p v-if="form.errors.address" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.address }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <!-- Teléfono -->
                        <div>
                            <label class="block text-xs font-black text-zinc-400 uppercase tracking-widest mb-1.5 ml-1">Teléfono</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-3 text-zinc-400 text-[18px]">call</span>
                                <input v-model="form.phone" type="text" placeholder="70000000"
                                       class="w-full pl-10 pr-4 py-3 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none text-zinc-900 dark:text-white">
                            </div>
                        </div>

                        <!-- WhatsApp -->
                        <div>
                            <label class="block text-xs font-black text-zinc-400 uppercase tracking-widest mb-1.5 ml-1">WhatsApp pedidos</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-3 text-zinc-400 text-[18px]">brand_awareness</span>
                                <input v-model="form.whatsapp_number" type="text" placeholder="59170000000"
                                       class="w-full pl-10 pr-4 py-3 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none text-zinc-900 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Toggle Main -->
                        <div @click="form.is_main = !form.is_main" class="p-4 bg-zinc-50 dark:bg-gray-900/50 rounded-2xl border border-zinc-200 dark:border-gray-700 flex items-center justify-between cursor-pointer hover:border-indigo-500/50 transition-colors group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                     :class="form.is_main ? 'bg-indigo-600 text-white' : 'bg-zinc-200 dark:bg-gray-700 text-zinc-500'">
                                    <span class="material-symbols-outlined text-[18px]">verified</span>
                                </div>
                                <div class="text-left">
                                    <p class="text-[11px] font-black text-zinc-900 dark:text-white uppercase tracking-widest">Sede Central</p>
                                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-tighter">Principal</p>
                                </div>
                            </div>
                            <div class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                 :class="form.is_main ? 'bg-indigo-600' : 'bg-zinc-300 dark:bg-zinc-600'">
                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="form.is_main ? 'translate-x-4' : 'translate-x-0'"></span>
                            </div>
                        </div>

                        <!-- Toggle Active -->
                        <div @click="form.is_active = !form.is_active" class="p-4 bg-zinc-50 dark:bg-gray-900/50 rounded-2xl border border-zinc-200 dark:border-gray-700 flex items-center justify-between cursor-pointer hover:border-emerald-500/50 transition-colors group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                     :class="form.is_active ? 'bg-emerald-600 text-white' : 'bg-zinc-200 dark:bg-gray-700 text-zinc-500'">
                                    <span class="material-symbols-outlined text-[18px]">bolt</span>
                                </div>
                                <div class="text-left">
                                    <p class="text-[11px] font-black text-zinc-900 dark:text-white uppercase tracking-widest">Estado Activo</p>
                                    <p class="text-[9px] text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-tighter">Operacional</p>
                                </div>
                            </div>
                            <div class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                 :class="form.is_active ? 'bg-emerald-600' : 'bg-zinc-300 dark:bg-zinc-600'">
                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                      :class="form.is_active ? 'translate-x-4' : 'translate-x-0'"></span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer (Fixed) -->
            <div class="shrink-0 p-6 border-t border-zinc-100 dark:border-gray-700 bg-zinc-50 dark:bg-gray-900/30">
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 w-full">
                    <button type="button" @click="closeModal"
                            class="w-full sm:w-auto px-6 py-3 text-xs font-black text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 uppercase tracking-widest transition-all">
                        Cancelar Registro
                    </button>
                    <button form="branchForm" type="submit" :disabled="form.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-zinc-900 dark:bg-indigo-600 text-white text-xs font-black rounded-2xl hover:bg-zinc-800 dark:hover:bg-indigo-700 transition-all shadow-xl shadow-zinc-900/20 dark:shadow-indigo-600/20 active:scale-95 disabled:opacity-50 uppercase tracking-widest">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                        {{ branch ? 'Guardar Cambios' : 'Crear Sucursal' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
