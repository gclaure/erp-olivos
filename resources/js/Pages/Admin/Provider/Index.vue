<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProviderModal from './Partials/ProviderModal.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    providers: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const selectedProvider = ref(null);

const updateFilters = debounce(() => {
    router.get(route('admin.providers.index'), {
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
    selectedProvider.value = null;
    showModal.value = true;
};

const openEditModal = (provider) => {
    selectedProvider.value = provider;
    showModal.value = true;
};

const confirmDelete = (provider) => {
    Swal.fire({
        title: '¿Eliminar proveedor?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#f43f5e',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        background: document.documentElement.classList.contains('dark') ? '#18181b' : '#fff',
        color: document.documentElement.classList.contains('dark') ? '#fff' : '#18181b',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.providers.destroy', provider.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Eliminado',
                        text: 'El proveedor ha sido eliminado correctamente.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        background: document.documentElement.classList.contains('dark') ? '#18181b' : '#fff',
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#18181b',
                    });
                },
                onError: (errors) => {
                    Swal.fire({
                        title: 'Error',
                        text: errors.error || 'No se pudo eliminar el proveedor.',
                        icon: 'error',
                        background: document.documentElement.classList.contains('dark') ? '#18181b' : '#fff',
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#18181b',
                    });
                }
            });
        }
    });
};

</script>

<template>
    <Head title="Proveedores" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="text-left">
                <h1 class="text-2xl font-black text-zinc-900 dark:text-white leading-tight tracking-tight uppercase">Proveedores</h1>
                <p class="text-sm text-zinc-500 dark:text-secondary-400 mt-1">Administra los proveedores de productos y suministros.</p>
            </div>
            <button @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 text-white text-sm font-black uppercase tracking-widest rounded-2xl hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-500/20 active:scale-95 group">
                <span class="material-symbols-outlined text-xl group-hover:rotate-12 transition-transform">add_business</span>
                Nuevo Proveedor
            </button>
        </div>

        <!-- Search -->
        <div class="bg-surface dark:bg-secondary-800 rounded-2xl border border-zinc-200 dark:border-secondary-700 shadow-sm mb-6 p-4">
            <div class="relative group max-w-md">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors text-xl">search</span>
                </div>
                <input v-model="search"
                       type="text"
                       placeholder="Buscar por nombre, contacto o documento..."
                       class="w-full pl-12 pr-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm placeholder-zinc-400">
            </div>
        </div>

        <!-- Table -->
        <div class="bg-surface dark:bg-secondary-800 rounded-2xl border border-zinc-200 dark:border-secondary-700 shadow-sm overflow-hidden">
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-secondary-700 border-b border-zinc-200 dark:border-secondary-600 text-zinc-500 dark:text-secondary-300 font-black uppercase tracking-widest text-[10px]">
                            <th class="px-6 py-4">Empresa / Razón Social</th>
                            <th class="px-6 py-4">Contacto</th>
                            <th class="px-6 py-4">Email / Teléfono</th>
                            <th class="px-6 py-4">Documento</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50">
                        <tr v-for="provider in providers.data" :key="provider.id"
                            class="hover:bg-zinc-50/80 dark:hover:bg-secondary-700/50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black text-sm border border-indigo-100 dark:border-indigo-800/30">
                                        {{ provider.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-zinc-900 dark:text-white font-black tracking-tight text-base leading-tight">{{ provider.name }}</span>
                                        <span class="text-[10px] text-zinc-400 dark:text-secondary-500 uppercase font-black tracking-widest mt-0.5 truncate max-w-[200px]">{{ provider.address || 'Sin dirección' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-zinc-600 dark:text-secondary-300 font-bold tracking-tight">{{ provider.contact_name || '—' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-1.5 text-zinc-500 dark:text-secondary-400 text-xs">
                                        <span class="material-symbols-outlined text-sm">mail</span>
                                        <span class="truncate max-w-[150px]">{{ provider.email || '—' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-zinc-500 dark:text-secondary-400 text-xs">
                                        <span class="material-symbols-outlined text-sm text-emerald-500">call</span>
                                        <span class="font-black tracking-tighter">{{ provider.phone || '—' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="provider.document_number" class="flex flex-col gap-0.5">
                                    <span class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">{{ provider.document_type || 'NIT' }}</span>
                                    <span class="text-zinc-600 dark:text-secondary-300 font-bold tracking-tight">{{ provider.document_number }}</span>
                                </div>
                                <span v-else class="text-zinc-400">——</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="provider.is_active" 
                                      class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/30 shadow-sm">
                                    Activo
                                </span>
                                <span v-else 
                                      class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/30 shadow-sm">
                                    Inactivo
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openEditModal(provider)"
                                            class="w-9 h-9 inline-flex items-center justify-center rounded-xl text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-all active:scale-90 border border-transparent hover:border-indigo-100 dark:hover:border-indigo-800"
                                            title="Editar">
                                        <span class="material-symbols-outlined text-xl">edit</span>
                                    </button>
                                    <button @click="confirmDelete(provider)"
                                            class="w-9 h-9 inline-flex items-center justify-center rounded-xl text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-all active:scale-90 border border-transparent hover:border-rose-100 dark:hover:border-rose-800"
                                            title="Eliminar">
                                        <span class="material-symbols-outlined text-xl">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="providers.data.length === 0">
                            <td colspan="6" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center opacity-40">
                                    <div class="w-20 h-20 bg-zinc-50 dark:bg-secondary-700 rounded-3xl flex items-center justify-center mb-4 border-2 border-dashed border-zinc-200 dark:border-secondary-600">
                                        <span class="material-symbols-outlined text-5xl text-zinc-300 dark:text-secondary-600">store</span>
                                    </div>
                                    <p class="text-zinc-500 dark:text-secondary-400 font-black uppercase tracking-widest text-sm">No se encontraron proveedores</p>
                                    <p class="text-xs text-zinc-400 dark:text-secondary-500 mt-2">Prueba ajustando los filtros o registra uno nuevo.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-gray-700">
                <div v-for="provider in providers.data" :key="provider.id" 
                     class="p-4 space-y-4 bg-white dark:bg-gray-800/50">
                    
                    <!-- Basic Info -->
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex-shrink-0 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black text-base border border-indigo-100 dark:border-indigo-800/30">
                            {{ provider.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="font-black text-zinc-900 dark:text-white text-base leading-tight truncate">{{ provider.name }}</h4>
                                <span v-if="provider.is_active" class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                                    Activo
                                </span>
                                <span v-else class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/20">
                                    Inactivo
                                </span>
                            </div>
                            <span class="text-[10px] text-zinc-400 dark:text-secondary-500 uppercase font-black tracking-widest mt-0.5 truncate block">{{ provider.address || 'Sin dirección' }}</span>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Contacto</span>
                            <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">{{ provider.contact_name || '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Documento</span>
                            <div class="flex flex-col items-end">
                                <span class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase leading-none">{{ provider.document_type || 'NIT' }}</span>
                                <span class="text-xs font-bold text-zinc-900 dark:text-white">{{ provider.document_number || '—' }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Email</span>
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 truncate max-w-[180px]">{{ provider.email || '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Teléfono</span>
                            <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 tracking-tighter">{{ provider.phone || '—' }}</span>
                        </div>
                    </div>

                    <!-- Action Row -->
                    <div class="flex justify-end items-center gap-3 pt-2">
                        <button @click="openEditModal(provider)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 active:scale-90 transition-all border border-amber-100 dark:border-amber-800/20"
                                title="Editar">
                            <span class="material-symbols-outlined text-lg">edit</span>
                        </button>
                        <button @click="confirmDelete(provider)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 active:scale-90 transition-all border border-rose-100 dark:border-rose-800/20"
                                title="Eliminar">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State for Mobile -->
                <div v-if="providers.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800">
                    <div class="flex flex-col items-center opacity-40 py-10">
                        <div class="w-16 h-16 bg-zinc-50 dark:bg-secondary-700 rounded-2xl flex items-center justify-center mb-4 border-2 border-dashed border-zinc-200 dark:border-secondary-600">
                            <span class="material-symbols-outlined text-4xl text-zinc-300 dark:text-secondary-600">store</span>
                        </div>
                        <p class="text-zinc-500 dark:text-secondary-400 font-black uppercase tracking-widest text-sm">No hay proveedores</p>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="providers.meta && providers.meta.links" class="px-6 py-5 border-t border-zinc-100 dark:border-secondary-700 bg-zinc-50/50 dark:bg-secondary-800/50 flex justify-center">
                <nav class="flex gap-1.5">
                    <Link v-for="(link, i) in providers.meta.links" :key="i"
                          :href="link.url || '#'"
                          v-html="link.label"
                          :class="[
                              'px-4 py-2 text-[11px] font-black rounded-xl transition-all duration-200',
                              link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30 scale-110' : 
                              link.url ? 'bg-white dark:bg-secondary-700 text-zinc-500 dark:text-secondary-300 hover:bg-zinc-100 dark:hover:bg-secondary-600 shadow-sm' : 'text-zinc-300 dark:text-secondary-800 cursor-not-allowed'
                          ]"
                    />
                </nav>
            </div>
        </div>

        <!-- Modals -->
        <ProviderModal :show="showModal" :provider="selectedProvider" @close="showModal = false" />
    </AdminLayout>
</template>
