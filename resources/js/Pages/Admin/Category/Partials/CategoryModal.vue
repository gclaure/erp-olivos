<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    category: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    id: null,
    name: '',
    description: '',
    is_active: true,
});

watch(() => props.category, (newCategory) => {
    if (newCategory) {
        form.id = newCategory.id;
        form.name = newCategory.name;
        form.description = newCategory.description || '';
        form.is_active = Boolean(newCategory.is_active);
    } else {
        form.reset();
    }
}, { immediate: true });

const submit = () => {
    if (form.id) {
        form.put(route('admin.categories.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.categories.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    form.reset();
    emit('close');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-zinc-950/40 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-3xl shadow-2xl shadow-zinc-950/20 border border-zinc-200 dark:border-gray-700 overflow-hidden transform transition-all animate-in zoom-in-95 duration-200">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-zinc-100 dark:border-gray-700 flex items-center justify-between bg-zinc-50/50 dark:bg-gray-800/50">
                <div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">
                        {{ form.id ? 'Editar Categoría' : 'Nueva Categoría' }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Organiza tus productos técnicos</p>
                </div>
                <button @click="closeModal" class="w-10 h-10 flex items-center justify-center rounded-xl text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-gray-700 transition-all">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submit" class="p-8 space-y-6">
                <!-- Name -->
                <div class="space-y-2">
                    <label class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest ml-1">Nombre de la Categoría</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors text-lg">folder</span>
                        </div>
                        <input v-model="form.name"
                               type="text"
                               required
                               placeholder="Ej. Repuestos, Herramientas..."
                               class="w-full pl-11 pr-4 py-3 bg-zinc-50 dark:bg-gray-900 border-zinc-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white placeholder-zinc-400">
                    </div>
                    <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1 ml-1 font-medium">{{ form.errors.name }}</p>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <label class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest ml-1">Descripción (Opcional)</label>
                    <textarea v-model="form.description"
                              rows="3"
                              placeholder="Breve descripción de la categoría..."
                              class="w-full px-4 py-3 bg-zinc-50 dark:bg-gray-900 border-zinc-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white placeholder-zinc-400 resize-none"></textarea>
                    <p v-if="form.errors.description" class="text-xs text-rose-500 mt-1 ml-1 font-medium">{{ form.errors.description }}</p>
                </div>

                <!-- Active Toggle -->
                <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-gray-900/50 rounded-2xl border border-zinc-100 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-zinc-100 dark:border-gray-700">
                            <span class="material-symbols-outlined text-indigo-500" v-if="form.is_active">toggle_on</span>
                            <span class="material-symbols-outlined text-zinc-400" v-else>toggle_off</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-zinc-900 dark:text-white">Estado de la Categoría</p>
                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400">Determina si será visible en el catálogo</p>
                        </div>
                    </div>
                    <button type="button" 
                            @click="form.is_active = !form.is_active"
                            :class="[
                                'relative w-12 h-6 rounded-full transition-all duration-300',
                                form.is_active ? 'bg-indigo-600' : 'bg-zinc-300 dark:bg-gray-700'
                            ]">
                        <div :class="[
                            'absolute top-1 w-4 h-4 rounded-full bg-white transition-all duration-300 shadow-md',
                            form.is_active ? 'left-7' : 'left-1'
                        ]"></div>
                    </button>
                </div>

                <!-- Footer / Actions -->
                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="button" @click="closeModal"
                            class="flex-1 px-6 py-3 bg-zinc-100 dark:bg-gray-700 text-zinc-700 dark:text-zinc-200 text-sm font-bold rounded-2xl hover:bg-zinc-200 dark:hover:bg-gray-600 transition-all active:scale-95 border border-zinc-200 dark:border-gray-600">
                        Cancelar
                    </button>
                    <button type="submit" 
                            :disabled="form.processing"
                            class="flex-1 px-6 py-3 bg-indigo-600 text-white text-sm font-bold rounded-2xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 active:scale-95 disabled:opacity-50 disabled:scale-100 flex items-center justify-center gap-2">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin text-lg">sync</span>
                        <span>{{ form.id ? 'Guardar Cambios' : 'Crear Categoría' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
