<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CategoryModal from './Partials/CategoryModal.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    categories: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingCategory = ref(null);

const updateFilters = debounce(() => {
    router.get(route('admin.categories.index'), {
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 500);

watch(search, () => {
    updateFilters();
});

const openCreateModal = () => {
    editingCategory.value = null;
    showModal.value = true;
};

const openEditModal = (category) => {
    editingCategory.value = category;
    showModal.value = true;
};

const deleteCategory = (category) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Esta acción eliminará la categoría "${category.name}" de forma permanente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#f43f5e',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#000000',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.categories.destroy', category.id), {
                onSuccess: () => {
                    // El éxito se maneja vía flash en el layout si existe, 
                    // o podemos añadir un Swal.fire de éxito aquí.
                },
                onError: (errors) => {
                    if (errors.error) {
                        Swal.fire('Error', errors.error, 'error');
                    }
                }
            });
        }
    });
};

</script>

<template>
    <Head title="Categorías" />

    <AdminLayout>
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">Categorías</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Clasificación y organización del catálogo de productos</p>
            </div>
            <button @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2.5 px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 active:scale-95 group w-full sm:w-auto">
                <span class="material-symbols-outlined text-lg transition-transform group-hover:rotate-90">add</span>
                <span>Nueva Categoría</span>
            </button>
        </div>

        <!-- Filters Section -->
        <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 mb-8 items-center">
            <div class="lg:col-span-3 relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors">search</span>
                </div>
                <input v-model="search"
                       type="text"
                       placeholder="Buscar categorías por nombre..."
                       class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-800 border-zinc-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white placeholder-zinc-400 shadow-sm">
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-surface dark:bg-gray-800 rounded-3xl border border-zinc-200 dark:border-gray-700 shadow-xl overflow-hidden mb-8">
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-100 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center w-16">#</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Información</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest hidden lg:table-cell">Descripción</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center">Estado</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center">Relación</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-right w-32">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/30">
                        <tr v-for="(category, index) in categories.data" :key="category.id" 
                            class="bg-white dark:bg-secondary-800/40 transition-all duration-200 hover:bg-zinc-50 dark:hover:bg-secondary-700/50 group">
                            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
                                <span class="text-xs font-bold text-zinc-400 dark:text-secondary-400 bg-zinc-100 dark:bg-secondary-700 px-2.5 py-1.5 rounded-lg">
                                    {{ String(index + 1).padStart(2, '0') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="flex flex-col">
                                    <span class="font-bold text-zinc-900 dark:text-zinc-100 tracking-tight text-sm">{{ category.name }}</span>
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5 uppercase tracking-tighter">Creado el {{ category.created_at }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle hidden lg:table-cell">
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-sm line-clamp-1 italic">
                                    {{ category.description || '— Sin descripción detallada —' }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-center align-middle">
                                <span v-if="category.is_active" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Activo</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-gray-600">
                                    <span>Inactivo</span>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center align-middle">
                                <div class="inline-flex flex-col items-center">
                                    <span class="text-sm font-bold text-zinc-800 dark:text-white">{{ category.products_count }}</span>
                                    <span class="text-[9px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-tighter leading-none">Productos</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openEditModal(category)"
                                            class="w-9 h-9 flex items-center justify-center text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all active:scale-90">
                                        <span class="material-symbols-outlined text-lg">edit_square</span>
                                    </button>
                                    <button @click="deleteCategory(category)"
                                            class="w-9 h-9 flex items-center justify-center text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-all active:scale-90">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="categories.data.length === 0">
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center py-10">
                                    <div class="w-20 h-20 bg-zinc-50 dark:bg-gray-700/50 rounded-2xl flex items-center justify-center mb-6 border-2 border-dashed border-zinc-200 dark:border-gray-700">
                                        <span class="material-symbols-outlined text-4xl text-zinc-300 dark:text-zinc-600">folder_open</span>
                                    </div>
                                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white">No hay categorías</h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2 max-w-xs mx-auto">Comienza agregando las categorías base para organizar tu inventario técnico.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-gray-700">
                <div v-for="(category, index) in categories.data" :key="category.id" 
                     class="p-4 space-y-4 bg-white dark:bg-gray-800/50">
                    
                    <!-- Basic Info -->
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">#{{ String(index + 1).padStart(2, '0') }}</span>
                            <h4 class="font-bold text-zinc-900 dark:text-white text-base mt-0.5">{{ category.name }}</h4>
                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase tracking-tighter">Creado: {{ category.created_at }}</span>
                        </div>
                        <span v-if="category.is_active" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                            Activo
                        </span>
                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-gray-600">
                            Inactivo
                        </span>
                    </div>

                    <!-- Details Grid -->
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Descripción</span>
                            <span class="text-xs text-zinc-700 dark:text-zinc-300 text-right italic max-w-[200px] line-clamp-2">{{ category.description || '— Sin descripción —' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Relación</span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ category.products_count }}</span>
                                <span class="text-[9px] font-black text-zinc-400 uppercase tracking-widest">Productos</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Row -->
                    <div class="flex justify-end items-center gap-3 pt-2">
                        <button @click="openEditModal(category)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 active:scale-90 transition-all border border-amber-100 dark:border-amber-800/20"
                                title="Editar">
                            <span class="material-symbols-outlined text-lg">edit</span>
                        </button>
                        <button @click="deleteCategory(category)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 active:scale-90 transition-all border border-rose-100 dark:border-rose-900/20"
                                title="Eliminar">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State for Mobile -->
                <div v-if="categories.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800">
                    <div class="flex flex-col items-center py-10">
                        <div class="w-20 h-20 bg-zinc-50 dark:bg-gray-700/50 rounded-2xl flex items-center justify-center mb-6 border-2 border-dashed border-zinc-200 dark:border-gray-700">
                            <span class="material-symbols-outlined text-4xl text-zinc-300 dark:text-zinc-600">folder_open</span>
                        </div>
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">No hay categorías</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2 max-w-xs mx-auto">Comienza agregando las categorías base para organizar tu inventario técnico.</p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="categories.meta && categories.meta.links" class="px-6 py-5 border-t border-zinc-100 dark:border-gray-700 bg-zinc-50 dark:bg-zinc-800 flex justify-center">
                <nav class="flex gap-1">
                    <Link v-for="(link, i) in categories.meta.links" :key="i"
                          :href="link.url || '#'"
                          v-html="link.label"
                          :class="[
                              'px-4 py-2 text-sm font-bold rounded-xl transition-all',
                              link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 
                              link.url ? 'bg-white dark:bg-gray-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' : 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'
                          ]"
                    />
                </nav>
            </div>
        </div>

        <!-- Category Modal -->
        <CategoryModal :show="showModal" 
                      :category="editingCategory" 
                      @close="showModal = false" />

    </AdminLayout>
</template>
