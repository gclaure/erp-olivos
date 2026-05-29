<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import WarehouseModal from './Partials/WarehouseModal.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    warehouses: Object,
    branches: Array,
    filters: Object,
    activeBranchId: [String, Number],
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingWarehouse = ref(null);

const updateFilters = debounce(() => {
    router.get(route('admin.warehouses.index'), {
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
    editingWarehouse.value = null;
    showModal.value = true;
};

const openEditModal = (warehouse) => {
    editingWarehouse.value = warehouse;
    showModal.value = true;
};

const deleteWarehouse = (warehouse) => {
    Swal.fire({
        title: '¿Desactivar almacén?',
        text: 'Los almacenes no se pueden eliminar de forma permanente si tienen registros asociados, se procederá a desactivarlo.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#f43f5e',
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar',
        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#000000',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.warehouses.destroy', warehouse.id), {
                onSuccess: () => {
                    // El éxito se maneja vía flash o podemos disparar un Swal manual
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
    <Head title="Almacenes" />

    <AdminLayout>
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">Almacenes</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Administración de puntos de almacenamiento y stock</p>
            </div>
            <button @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2.5 px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 active:scale-95 group w-full sm:w-auto">
                <span class="material-symbols-outlined text-lg transition-transform group-hover:rotate-90">add</span>
                <span>Nuevo Almacén</span>
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
                       placeholder="Buscar por nombre..."
                       class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-800 border-zinc-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white placeholder-zinc-400 shadow-sm">
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-surface dark:bg-gray-800 rounded-3xl border border-zinc-200 dark:border-gray-700 shadow-xl overflow-hidden mb-8 transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-100 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center w-16">#</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Información Almacén</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest hidden lg:table-cell">Ubicación / Sucursal</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center">Estado</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-right w-32">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/30">
                        <tr v-for="(warehouse, index) in warehouses.data" :key="warehouse.id" 
                            class="bg-white dark:bg-secondary-800/40 transition-all duration-200 hover:bg-zinc-50 dark:hover:bg-secondary-700/50 group">
                            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
                                <span class="text-xs font-bold text-zinc-400 dark:text-secondary-400 bg-zinc-100 dark:bg-secondary-700 px-2.5 py-1.5 rounded-lg">
                                    {{ String(index + warehouses.meta.from).padStart(2, '0') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="flex flex-col">
                                    <span class="font-bold text-zinc-900 dark:text-zinc-100 tracking-tight text-sm uppercase">{{ warehouse.name }}</span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase tracking-tight italic">{{ warehouse.address || 'Sin dirección' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle hidden lg:table-cell">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/20">
                                            {{ warehouse.branch?.name || 'Sucursal desconocida' }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-zinc-400 dark:text-zinc-500 italic max-w-xs truncate ml-0.5">
                                        — Almacén Técnico —
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center align-middle">
                                <span v-if="warehouse.is_active" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Activo</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-gray-600">
                                    <span>Inactivo</span>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openEditModal(warehouse)"
                                            class="w-9 h-9 flex items-center justify-center text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all active:scale-90"
                                            title="Editar">
                                        <span class="material-symbols-outlined text-lg">edit_square</span>
                                    </button>
                                    <button @click="deleteWarehouse(warehouse)"
                                            :disabled="!warehouse.is_active"
                                            class="w-9 h-9 flex items-center justify-center text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-all active:scale-90 disabled:opacity-0 disabled:pointer-events-none"
                                            title="Desactivar">
                                        <span class="material-symbols-outlined text-lg">block</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="warehouses.data.length === 0">
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center py-10">
                                    <div class="w-20 h-20 bg-zinc-50 dark:bg-secondary-800/50 rounded-3xl flex items-center justify-center mb-6 border-2 border-dashed border-zinc-200 dark:border-secondary-700">
                                        <span class="material-symbols-outlined text-4xl text-zinc-300 dark:text-secondary-600">home_modern</span>
                                    </div>
                                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white uppercase tracking-tight">No hay almacenes</h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2 max-w-xs mx-auto">Administra tus puntos de almacenamiento para gestionar correctamente el inventario técnico.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Section -->
            <div v-if="warehouses.meta && warehouses.meta.links" class="px-6 py-5 border-t border-zinc-100 dark:border-secondary-700 bg-zinc-50 dark:bg-zinc-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">
                    Mostrando {{ warehouses.meta.from }} - {{ warehouses.meta.to }} de {{ warehouses.meta.total }} almacenes
                </p>
                <nav class="flex gap-1.5">
                    <Link v-for="(link, i) in warehouses.meta.links" :key="i"
                          :href="link.url || '#'"
                          v-html="link.label"
                          :class="[
                              'px-3.5 py-2 text-xs font-bold rounded-xl transition-all border shadow-sm',
                              link.active ? 'bg-indigo-600 border-indigo-600 text-white shadow-indigo-500/20' : 
                              link.url ? 'bg-white dark:bg-secondary-800 border-zinc-200 dark:border-secondary-700 text-zinc-500 dark:text-zinc-400 hover:border-indigo-500 dark:hover:border-indigo-500 hover:text-indigo-600' : 'bg-zinc-50 dark:bg-secondary-900/50 border-zinc-100 dark:border-secondary-800 text-zinc-300 dark:text-zinc-600 cursor-not-allowed'
                          ]"
                    />
                </nav>
            </div>
        </div>

        <!-- Warehouse Modal -->
        <WarehouseModal :show="showModal" 
                        :warehouse="editingWarehouse" 
                        :branches="branches"
                        :activeBranchId="activeBranchId"
                        @close="showModal = false" />

    </AdminLayout>
</template>
