<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UnitOfMeasureModal from './Partials/UnitOfMeasureModal.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    unitOfMeasures: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingUnit = ref(null);

const updateFilters = debounce(() => {
    router.get(route('admin.unit-of-measures.index'), {
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
    editingUnit.value = null;
    showModal.value = true;
};

const openEditModal = (unit) => {
    editingUnit.value = unit;
    showModal.value = true;
};

const deleteUnit = (unit) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Esta acción eliminará la unidad "${unit.name}" de forma permanente.`,
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
            router.delete(route('admin.unit-of-measures.destroy', unit.id), {
                onError: (errors) => {
                    if (errors.error) {
                        Swal.fire('Error', errors.error, 'error');
                    }
                }
            });
        }
    });
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    // Podríamos añadir una notificación aquí si el layout lo soporta
};

</script>

<template>
    <Head title="Unidades de Medida" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-8">
            <div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">Unidades de Medida</h1>
                <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">Gestión técnica de magnitudes y abreviaturas</p>
            </div>
            <button @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20 active:scale-95 group w-full sm:w-auto">
                <span class="material-symbols-outlined text-lg transition-transform group-hover:rotate-90">add</span>
                <span>Nueva Unidad</span>
            </button>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-zinc-200 dark:border-gray-700 shadow-sm mb-6 overflow-hidden transition-colors">
            <div class="p-4 sm:p-5">
                <div class="relative max-w-md group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors">search</span>
                    </div>
                    <input v-model="search"
                           type="text"
                           placeholder="Buscar por nombre o abreviatura..."
                           class="w-full pl-11 pr-4 py-2.5 bg-zinc-50 dark:bg-gray-700/50 border-transparent focus:border-indigo-500 focus:bg-white dark:focus:bg-gray-700 rounded-xl text-sm transition-all dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 ring-1 ring-zinc-200 dark:ring-gray-600 focus:ring-2 focus:ring-indigo-500/20 shadow-sm">
                </div>
            </div>
        </div>

        <!-- Main Content / Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-zinc-200 dark:border-gray-700 shadow-sm overflow-hidden transition-all">
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-100 dark:border-zinc-700">
                            <th class="hidden md:table-cell px-6 py-4 text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest text-center w-16">#</th>
                            <th class="px-6 py-4 text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">Información de Unidad</th>
                            <th class="hidden sm:table-cell px-6 py-4 text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest text-center w-32">Abreviatura</th>
                            <th class="hidden lg:table-cell px-6 py-4 text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest text-center w-32">Estado</th>
                            <th class="hidden xl:table-cell px-6 py-4 text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest text-center w-32">Productos</th>
                            <th class="px-6 py-4 text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest text-right w-32">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50 dark:divide-secondary-700/50">
                        <tr v-for="(unit, index) in unitOfMeasures.data" :key="unit.id"
                            class="group transition-all duration-200 hover:bg-zinc-50 dark:hover:bg-secondary-700/50 border-b border-zinc-100 dark:border-secondary-700/50">
                            <!-- Index -->
                            <td class="hidden md:table-cell px-6 py-4 text-center">
                                <span class="text-xs font-bold text-zinc-400 dark:text-secondary-100 bg-zinc-100 dark:bg-secondary-700 px-2.5 py-1 rounded-lg">
                                    {{ String(index + 1).padStart(2, '0') }}
                                </span>
                            </td>

                            <!-- Info -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-zinc-900 dark:text-secondary-50 truncate text-sm sm:text-base">{{ unit.name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5 sm:mt-1">
                                        <span class="sm:hidden px-1.5 py-0.5 bg-zinc-100 dark:bg-gray-700 text-zinc-600 dark:text-zinc-400 text-[10px] font-bold rounded">{{ unit.abbreviation }}</span>
                                        <span class="text-[10px] sm:text-xs text-zinc-400 dark:text-zinc-500 font-medium">Creado: {{ unit.created_at }}</span>
                                        <span class="lg:hidden">
                                            <span v-if="unit.is_active" class="w-2 h-2 rounded-full bg-emerald-500 inline-block shadow-sm shadow-emerald-500/20"></span>
                                            <span v-else class="w-2 h-2 rounded-full bg-zinc-300 dark:bg-zinc-600 inline-block"></span>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Abbreviation (Desktop) -->
                            <td class="hidden sm:table-cell px-6 py-4 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 text-zinc-700 dark:text-zinc-300 shadow-sm transition-colors group-hover:border-indigo-200 dark:group-hover:border-indigo-800">
                                    {{ unit.abbreviation }}
                                </span>
                            </td>

                            <!-- Status (Desktop) -->
                            <td class="hidden lg:table-cell px-6 py-4 text-center">
                                <span v-if="unit.is_active" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Activo</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 border border-zinc-100 dark:border-zinc-700">
                                    <span>Inactivo</span>
                                </span>
                            </td>

                            <!-- Products -->
                            <td class="hidden xl:table-cell px-6 py-4 text-center">
                                <div class="inline-flex items-center gap-2 text-zinc-600 dark:text-zinc-300 font-bold bg-zinc-50 dark:bg-zinc-800 px-3 py-1 rounded-full">
                                    <span class="material-symbols-outlined text-sm opacity-40">inventory_2</span>
                                    <span class="dark:text-white text-xs">{{ unit.products_count }}</span>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openEditModal(unit)"
                                            class="inline-flex items-center justify-center p-2.5 text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all active:scale-90 group/btn">
                                        <span class="material-symbols-outlined text-lg group-hover/btn:rotate-12 transition-transform">edit_square</span>
                                    </button>
                                    <button @click="deleteUnit(unit)"
                                            class="inline-flex items-center justify-center p-2.5 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-all active:scale-90 group/btn">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="unitOfMeasures.data.length === 0">
                            <td colspan="6" class="px-6 py-20 text-center bg-zinc-50/30 dark:bg-gray-800/30">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 bg-zinc-100 dark:bg-gray-700 rounded-3xl flex items-center justify-center mb-4 border-2 border-dashed border-zinc-200 dark:border-gray-600">
                                        <span class="material-symbols-outlined text-4xl text-zinc-300 dark:text-zinc-500">inventory_2</span>
                                    </div>
                                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Sin Unidades de Medida</h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 max-w-xs mx-auto">Comienza agregando las métricas técnicas (UND, KG, LT) para tus productos.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-gray-700">
                <div v-for="(unit, index) in unitOfMeasures.data" :key="unit.id" 
                     class="p-4 space-y-4 bg-white dark:bg-gray-800/50">
                    
                    <!-- Basic Info -->
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">#{{ String(index + 1).padStart(2, '0') }}</span>
                            <h4 class="font-bold text-zinc-900 dark:text-white text-base mt-0.5">{{ unit.name }}</h4>
                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase tracking-tighter">Creado: {{ unit.created_at }}</span>
                        </div>
                        <span v-if="unit.is_active" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                            Activo
                        </span>
                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-gray-600">
                            Inactivo
                        </span>
                    </div>

                    <!-- Details Grid -->
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Abreviatura</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                {{ unit.abbreviation }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Relación</span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ unit.products_count }}</span>
                                <span class="text-[9px] font-black text-zinc-400 uppercase tracking-widest">Productos</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Row -->
                    <div class="flex justify-end items-center gap-3 pt-2">
                        <button @click="openEditModal(unit)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 active:scale-90 transition-all border border-amber-100 dark:border-amber-800/20"
                                title="Editar">
                            <span class="material-symbols-outlined text-lg">edit</span>
                        </button>
                        <button @click="deleteUnit(unit)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 active:scale-90 transition-all border border-rose-100 dark:border-rose-900/20"
                                title="Eliminar">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State for Mobile -->
                <div v-if="unitOfMeasures.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800">
                    <div class="flex flex-col items-center py-10">
                        <div class="w-20 h-20 bg-zinc-50 dark:bg-gray-700/50 rounded-2xl flex items-center justify-center mb-6 border-2 border-dashed border-zinc-200 dark:border-gray-700">
                            <span class="material-symbols-outlined text-4xl text-zinc-300 dark:text-zinc-600">inventory_2</span>
                        </div>
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Sin Unidades</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2 max-w-xs mx-auto">Comienza agregando las métricas técnicas (UND, KG, LT) para tus productos.</p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="unitOfMeasures.meta && unitOfMeasures.meta.links && unitOfMeasures.meta.last_page > 1" class="px-6 py-5 border-t border-zinc-100 dark:border-gray-700 bg-zinc-50/50 dark:bg-gray-800/50 flex justify-center">
                <nav class="flex gap-1">
                    <Link v-for="(link, i) in unitOfMeasures.meta.links" :key="i"
                          :href="link.url || '#'"
                          v-html="link.label"
                          :class="[
                              'px-4 py-2 text-xs font-bold rounded-xl transition-all',
                              link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 
                              link.url ? 'bg-white dark:bg-gray-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-gray-700' : 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed border border-zinc-100 dark:border-gray-800'
                          ]"
                    />
                </nav>
            </div>
        </div>

        <!-- Modal -->
        <UnitOfMeasureModal :show="showModal"
                           :unit="editingUnit"
                           @close="showModal = false" />
    </AdminLayout>
</template>
