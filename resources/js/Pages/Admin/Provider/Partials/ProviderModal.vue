<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    provider: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    id: null,
    name: '',
    contact_name: '',
    document_type: 'NIT',
    document_number: '',
    email: '',
    phone: '',
    address: '',
    is_active: true,
});

watch(() => props.provider, (newProvider) => {
    if (newProvider) {
        form.id = newProvider.id;
        form.name = newProvider.name;
        form.contact_name = newProvider.contact_name || '';
        form.document_type = newProvider.document_type || 'NIT';
        form.document_number = newProvider.document_number || '';
        form.email = newProvider.email || '';
        form.phone = newProvider.phone || '';
        form.address = newProvider.address || '';
        form.is_active = Boolean(newProvider.is_active);
    } else {
        form.reset();
        form.id = null;
        form.document_type = 'NIT';
        form.is_active = true;
    }
}, { immediate: true });

const closeModal = () => {
    emit('close');
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (form.id) {
        form.put(route('admin.providers.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.providers.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const documentTypes = [
    { label: 'NIT - Número de Identificación Tributaria', value: 'NIT' },
    { label: 'CI - Cédula de Identidad', value: 'CI' },
    { label: 'Otro', value: 'OTRO' },
];

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/40 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="bg-white dark:bg-secondary-900 w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden border border-zinc-100 dark:border-secondary-700 animate-in zoom-in duration-300">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-secondary-800 flex items-center justify-between bg-zinc-50/50 dark:bg-secondary-800/50">
                <h3 class="text-lg font-black text-zinc-900 dark:text-white uppercase tracking-tight">
                    {{ form.id ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
                </h3>
                <button @click="closeModal" class="p-2 text-zinc-400 hover:text-rose-500 transition-colors hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <div class="space-y-1.5">
                    <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Razón Social / Nombre Comercial</label>
                    <input v-model="form.name" type="text" required placeholder="Nombre de la empresa..."
                           :class="['w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border rounded-xl text-sm transition-all dark:text-white shadow-sm', 
                                    form.errors.name ? 'border-rose-500 focus:ring-rose-500/10' : 'border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10']">
                    <p v-if="form.errors.name" class="text-[10px] text-rose-500 font-bold ml-1">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Nombre de Contacto</label>
                        <input v-model="form.contact_name" type="text" placeholder="Persona de contacto..."
                               :class="['w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border rounded-xl text-sm transition-all dark:text-white shadow-sm', 
                                        form.errors.contact_name ? 'border-rose-500 focus:ring-rose-500/10' : 'border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10']">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Estado</label>
                        <div class="flex items-center gap-4 py-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-secondary-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-secondary-600 peer-checked:bg-emerald-500"></div>
                                <span class="ml-3 text-sm font-bold" :class="form.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-secondary-500'">
                                    {{ form.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Tipo de Documento</label>
                        <select v-model="form.document_type"
                                class="w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm">
                            <option value="">Ninguno</option>
                            <option v-for="type in documentTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Número de Documento</label>
                        <input v-model="form.document_number" type="text" placeholder="1234567..."
                               :class="['w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border rounded-xl text-sm transition-all dark:text-white shadow-sm', 
                                        form.errors.document_number ? 'border-rose-500 focus:ring-rose-500/10' : 'border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10']">
                        <p v-if="form.errors.document_number" class="text-[10px] text-rose-500 font-bold ml-1">{{ form.errors.document_number }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Correo Electrónico</label>
                        <input v-model="form.email" type="email" placeholder="ventas@proveedor.com"
                               :class="['w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border rounded-xl text-sm transition-all dark:text-white shadow-sm', 
                                        form.errors.email ? 'border-rose-500 focus:ring-rose-500/10' : 'border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10']">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Teléfono</label>
                        <input v-model="form.phone" type="text" placeholder="+591..."
                               :class="['w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border rounded-xl text-sm transition-all dark:text-white shadow-sm', 
                                        form.errors.phone ? 'border-rose-500 focus:ring-rose-500/10' : 'border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10']">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Dirección Física</label>
                    <textarea v-model="form.address" rows="2" placeholder="Calle, número, zona..."
                              class="w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm resize-none"></textarea>
                </div>

                <!-- Footer Actions -->
                <div class="pt-4 flex flex-col sm:flex-row justify-end items-center gap-3">
                    <button type="button" @click="closeModal"
                            class="w-full sm:w-auto px-6 py-2.5 text-sm font-bold text-zinc-500 dark:text-secondary-400 hover:bg-zinc-100 dark:hover:bg-secondary-800 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="form.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-black uppercase tracking-widest rounded-xl shadow-lg shadow-indigo-500/20 transition-all active:scale-95 disabled:opacity-50">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin text-lg">sync</span>
                        {{ form.id ? 'Actualizar Proveedor' : 'Guardar Proveedor' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Overlay to close -->
        <div @click="closeModal" class="fixed inset-0 z-[-1]"></div>
    </div>
</template>
