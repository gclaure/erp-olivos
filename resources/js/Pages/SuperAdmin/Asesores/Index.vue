<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import Swal from 'sweetalert2';
import debounce from 'lodash/debounce';

const props = defineProps({
    asesores: Object,
    filters: Object
});

// Filtros
const search = ref(props.filters.search || '');

const handleSearch = debounce(() => {
    router.get(route('superadmin.asesores.index'), {
        search: search.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch(search, () => {
    handleSearch();
});

// Modal Create/Edit
const showModal = ref(false);
const editId = ref(null);
const form = useForm({
    name: '',
    email: '',
    phone: '',
    is_active: true
});

const openCreate = () => {
    editId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (asesor) => {
    editId.value = asesor.id;
    form.name = asesor.name;
    form.email = asesor.email || '';
    form.phone = asesor.phone || '';
    form.is_active = asesor.is_active;
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editId.value) {
        form.put(route('superadmin.asesores.update', editId.value), {
            onSuccess: () => {
                showModal.value = false;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    icon: 'success',
                    title: 'Asesor actualizado correctamente.'
                });
            }
        });
    } else {
        form.post(route('superadmin.asesores.store'), {
            onSuccess: () => {
                showModal.value = false;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    icon: 'success',
                    title: 'Asesor registrado correctamente.'
                });
            }
        });
    }
};

const confirmDelete = (asesor) => {
    Swal.fire({
        title: '¿Eliminar este asesor?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#71717a',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('superadmin.asesores.destroy', asesor.id), {
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        icon: 'success',
                        title: 'Asesor eliminado.'
                    });
                }
            });
        }
    });
};
</script>

<template>
    <SuperAdminLayout>
        <Head title="Gestión de Asesores" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">Gestión de Asesores</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Registra y administra los asesores externos del sistema</p>
            </div>
            <button @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m3 2.25a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
                Nuevo Asesor
            </button>
        </div>

        <!-- Filtros -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 mb-6">
            <div class="p-4">
                <div class="relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input v-model="search"
                           type="text"
                           placeholder="Buscar por nombre o email..."
                           class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-gray-500 rounded-lg text-sm bg-white dark:bg-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors">
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                            <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Contacto</th>
                            <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider text-center">Estado</th>
                            <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                        <tr v-for="asesor in asesores.data" :key="asesor.id" class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                        {{ asesor.initials }}
                                    </div>
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ asesor.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ asesor.email || 'Sin email' }}</span>
                                    <span class="text-xs text-zinc-400">{{ asesor.phone || 'Sin teléfono' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="asesor.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400">
                                    Activo
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                                    Inactivo
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openEdit(asesor)"
                                            class="p-2 text-zinc-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/30 rounded-lg transition-colors" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </button>
                                    <button @click="confirmDelete(asesor)"
                                            class="p-2 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="asesores.data.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-zinc-500">
                                No se encontraron asesores registrados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="asesores.meta && asesores.meta.last_page > 1" class="px-6 py-4 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50 dark:bg-gray-600">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Mostrando {{ asesores.meta.from }} a {{ asesores.meta.to }} de {{ asesores.meta.total }} resultados
                    </p>
                    <div class="flex gap-2">
                        <Link v-for="link in asesores.meta.links" :key="link.label"
                              :href="link.url || '#'"
                              v-html="link.label"
                              :class="['px-3 py-1 text-xs rounded-md border transition-colors', 
                                link.active ? 'bg-violet-600 text-white border-violet-600' : 'bg-white dark:bg-gray-700 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-gray-600 hover:bg-zinc-50 dark:hover:bg-gray-600',
                                !link.url ? 'opacity-50 cursor-not-allowed' : '']"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Create/Edit -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200 text-left">
                <div class="p-6 border-b border-zinc-100 dark:border-gray-600">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">{{ editId ? 'Editar Asesor' : 'Nuevo Asesor' }}</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-normal mt-1">{{ editId ? 'Actualiza los datos del asesor' : 'Registra un asesor para seguimiento' }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nombre Completo *</label>
                        <input v-model="form.name" type="text" placeholder="Ej. Carlos Mendoza" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500 transition-all" />
                        <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Email (Opcional)</label>
                        <input v-model="form.email" type="email" placeholder="email@contacto.com" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500 transition-all" />
                        <p v-if="form.errors.email" class="text-xs text-rose-500 mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Teléfono (Opcional)</label>
                        <input v-model="form.phone" type="text" placeholder="+591 ..." class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500 transition-all" />
                        <p v-if="form.errors.phone" class="text-xs text-rose-500 mt-1">{{ form.errors.phone }}</p>
                    </div>
                    <div class="p-4 bg-zinc-50 dark:bg-gray-700 rounded-xl border border-zinc-200 dark:border-gray-600 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">Asesor Activo</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Determina si aparecerá en la lista de selección.</p>
                        </div>
                        <button @click="form.is_active = !form.is_active" type="button" 
                                :class="['relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none', form.is_active ? 'bg-violet-600' : 'bg-zinc-200 dark:bg-zinc-600']">
                            <span :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', form.is_active ? 'translate-x-5' : 'translate-x-0']" />
                        </button>
                    </div>
                </div>
                <div class="p-6 bg-zinc-50/50 dark:bg-gray-700/50 border-t border-zinc-100 dark:border-gray-600 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                    <button @click="showModal = false" class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-zinc-500 hover:bg-zinc-100 dark:hover:bg-gray-600 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button @click="submit" 
                            :disabled="form.processing"
                            class="w-full sm:w-auto px-8 py-2.5 bg-violet-600 text-white text-sm font-bold rounded-xl hover:bg-violet-700 shadow-lg shadow-violet-500/20 transition-all disabled:opacity-50">
                        {{ editId ? 'Guardar Cambios' : 'Registrar Asesor' }}
                    </button>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
