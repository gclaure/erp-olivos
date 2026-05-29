<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BranchModal from './Partials/BranchModal.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    branches: Object,
    filters: Object,
    canCreateBranch: Boolean,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const selectedBranch = ref(null);

const updateFilters = debounce(() => {
    router.get(route('admin.branches.index'), {
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(search, () => {
    updateFilters();
});

const openCreateModal = () => {
    selectedBranch.value = null;
    showModal.value = true;
};

const openEditModal = (branch) => {
    selectedBranch.value = branch;
    showModal.value = true;
};

const confirmDelete = (branch) => {
    Swal.fire({
        title: '¿Desactivar sucursal?',
        text: 'Las sucursales no se pueden eliminar, solo desactivar.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48', // rose-600
        cancelButtonColor: '#52525b', // zinc-600
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        customClass: {
            popup: 'dark:bg-secondary-800 dark:border dark:border-gray-700 rounded-2xl',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.branches.destroy', branch.id), {
                onSuccess: () => {
                    // Success message is handled by AdminLayout flash watcher
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Gestión de Sucursales" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center gap-2 text-sm text-zinc-500 mb-2">
                <span class="material-symbols-outlined text-sm">home</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="font-bold text-zinc-900 dark:text-white">Sucursales</span>
            </div>
        </template>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Sucursales</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Gestiona las sedes físicas de tu empresa</p>
            </div>
            <button @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-zinc-900 dark:bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 dark:hover:bg-indigo-700 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Nueva Sucursal
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 mb-6">
            <div class="p-4">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400">search</span>
                    <input v-model="search"
                           type="text"
                           placeholder="Buscar sucursales..."
                           class="w-full sm:w-80 pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-zinc-900 dark:focus:ring-indigo-500 focus:border-zinc-900 dark:focus:border-indigo-500 transition-colors outline-none">
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="space-y-6">
            <!-- Desktop View -->
            <div class="hidden md:block bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                                <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Dirección</th>
                                <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Teléfono</th>
                                <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">WhatsApp</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider w-32">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                            <tr v-for="branch in branches.data" :key="branch.id" class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ branch.name }}</span>
                                </td>
                                <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">{{ branch.address }}</td>
                                <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">{{ branch.phone ?? '—' }}</td>
                                <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                    <div class="flex items-center gap-2">
                                        <span v-if="branch.whatsapp_number" class="material-symbols-outlined text-[16px] text-emerald-500">brand_awareness</span>
                                        {{ branch.whatsapp_number ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="branch.is_main" 
                                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-900 text-white uppercase tracking-wider">
                                        Central
                                    </span>
                                    <span v-else 
                                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-gray-700 text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">
                                        Sucursal
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="branch.is_active" 
                                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 uppercase tracking-wider">
                                        Activo
                                    </span>
                                    <span v-else 
                                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400 uppercase tracking-wider">
                                        Inactivo
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="openEditModal(branch)"
                                                class="p-2 text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                                title="Editar">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </button>
                                        <button @click="confirmDelete(branch)"
                                                class="p-2 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                                title="Desactivar">
                                            <span class="material-symbols-outlined text-[20px]">block</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="branches.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="material-symbols-outlined text-4xl text-zinc-300 dark:text-zinc-600 mb-3">corporate_fare</span>
                                        <p class="text-zinc-500 dark:text-zinc-400 font-medium">No se encontraron sucursales</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Desktop Pagination -->
                <div v-if="branches.links.length > 3" class="px-6 py-4 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50 dark:bg-gray-600 flex justify-center">
                    <nav class="flex gap-1">
                        <Link v-for="(link, i) in branches.links" :key="i"
                              :href="link.url || '#'"
                              v-html="link.label"
                              :class="[
                                  'px-4 py-2 text-sm font-bold rounded-xl transition-all',
                                  link.active ? 'bg-zinc-900 dark:bg-indigo-600 text-white shadow-lg shadow-zinc-900/20 dark:shadow-indigo-600/20' : 
                                  link.url ? 'bg-white dark:bg-gray-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' : 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'
                              ]"
                        />
                    </nav>
                </div>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden space-y-4">
                <div v-for="branch in branches.data" :key="branch.id" 
                     class="bg-white dark:bg-gray-800/50 rounded-2xl border border-zinc-200 dark:border-gray-700 p-4 shadow-sm space-y-4">
                    <!-- Header -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-gray-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-zinc-500 dark:text-zinc-400">corporate_fare</span>
                            </div>
                            <div class="flex flex-col text-left">
                                <span class="text-xs font-black text-zinc-900 dark:text-white">{{ branch.name }}</span>
                                <span v-if="branch.is_main" class="text-[9px] font-black uppercase text-indigo-600 dark:text-indigo-400 tracking-widest">Sede Central</span>
                                <span v-else class="text-[9px] font-black uppercase text-zinc-400 tracking-widest">Sucursal Secundaria</span>
                            </div>
                        </div>
                        <span v-if="branch.is_active" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400">Activo</span>
                        <span v-else class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400">Inactivo</span>
                    </div>

                    <!-- Details -->
                    <div class="space-y-2.5">
                        <div class="flex flex-col gap-1 text-left">
                            <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Dirección</span>
                            <span class="text-xs text-zinc-600 dark:text-zinc-300 leading-relaxed">{{ branch.address }}</span>
                        </div>
                        <div class="flex justify-between items-center bg-zinc-50 dark:bg-gray-700/50 p-2.5 rounded-xl border border-zinc-100 dark:border-gray-600">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm text-zinc-400">call</span>
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Teléfono</span>
                            </div>
                            <span class="text-xs font-black text-zinc-900 dark:text-white">{{ branch.phone ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center bg-zinc-50 dark:bg-gray-700/50 p-2.5 rounded-xl border border-zinc-100 dark:border-gray-600">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm text-emerald-500">brand_awareness</span>
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">WhatsApp</span>
                            </div>
                            <span class="text-xs font-black text-zinc-900 dark:text-white">{{ branch.whatsapp_number ?? '—' }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end items-center gap-3 pt-2 border-t border-zinc-100 dark:border-gray-700/50">
                        <button @click="openEditModal(branch)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 active:scale-90 transition-all border border-amber-100 dark:border-amber-800/20"
                                title="Editar">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </button>
                        <button @click="confirmDelete(branch)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 active:scale-90 transition-all border border-rose-100 dark:border-rose-800/20"
                                title="Desactivar">
                            <span class="material-symbols-outlined text-[20px]">block</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State Mobile -->
                <div v-if="branches.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800 rounded-2xl border border-zinc-200 dark:border-gray-700 shadow-sm">
                    <div class="flex flex-col items-center opacity-40">
                        <span class="material-symbols-outlined text-4xl mb-4 text-zinc-300 dark:text-zinc-600">corporate_fare</span>
                        <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-xs">Sin sucursales registradas</p>
                    </div>
                </div>

                <!-- Mobile Pagination -->
                <div v-if="branches.links.length > 3" class="flex justify-center pt-2">
                    <nav class="flex gap-2">
                        <Link v-for="(link, i) in branches.links" :key="i"
                              :href="link.url || '#'"
                              v-html="link.label"
                              :class="[
                                  'px-4 py-2 text-sm font-black rounded-xl border transition-all shadow-sm',
                                  link.active ? 'bg-zinc-900 dark:bg-indigo-600 text-white border-zinc-900 dark:border-indigo-600' : 
                                  link.url ? 'bg-white dark:bg-gray-800 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-gray-700 active:scale-95' : 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed border-zinc-100 dark:border-gray-700'
                              ]"
                        />
                    </nav>
                </div>
            </div>
        </div>

        <BranchModal :show="showModal" 
                     :branch="selectedBranch"
                     @close="showModal = false" />
    </AdminLayout>
</template>
