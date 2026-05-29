<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    transfers: Object,
    originWarehouses: Array,
    destinationWarehouses: Array,
    statuses: Array,
    filters: Object,
});

const approve = (id) => {
    Swal.fire({
        title: '¿Aprobar Transferencia?',
        text: "La transferencia pasará a estado 'Aprobada' para su despacho.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.transfers.approve', id), {}, {
                onSuccess: () => {
                    Swal.fire('Éxito', 'Transferencia aprobada.', 'success');
                }
            });
        }
    });
};

const dispatch = (id) => {
    Swal.fire({
        title: '¿Confirmar Despacho?',
        text: "Se descontará el stock del origen y pasará a 'En Tránsito'.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Sí, despachar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.transfers.dispatch', id), {}, {
                onSuccess: () => {
                    Swal.fire('¡Despachado!', 'La transferencia ha sido enviada.', 'success');
                }
            });
        }
    });
};

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const origin_warehouse_id = ref(props.filters.origin_warehouse_id || '');
const destination_warehouse_id = ref(props.filters.destination_warehouse_id || '');

const updateFilters = debounce(() => {
    router.get(route('admin.transfers.index'), {
        search: search.value,
        status: status.value,
        origin_warehouse_id: origin_warehouse_id.value,
        destination_warehouse_id: destination_warehouse_id.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, status, origin_warehouse_id, destination_warehouse_id], () => {
    updateFilters();
});
</script>

<template>
    <Head title="Gestión de Transferencias" />

    <AdminLayout>
        <template #breadcrumbs>
            <div class="flex items-center gap-2 text-sm text-zinc-500">
                <Link :href="route('admin.dashboard')" class="hover:text-violet-600 transition-colors">Dashboard</Link>
                <span>/</span>
                <span class="text-zinc-900 dark:text-zinc-100 font-medium">Transferencias</span>
            </div>
        </template>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Transferencias de Inventario</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Flujo profesional entre almacenes y sucursales</p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.transfers.create')" 
                      class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-violet-500/25">
                    <span class="material-symbols-outlined text-[20px]">add</span>
                    Nueva Transferencia
                </Link>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm mb-6 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-zinc-400 text-[20px]">search</span>
                    </div>
                    <input v-model="search" type="text" placeholder="Número o Almacén..." 
                           class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all outline-none" />
                </div>
                
                <select v-model="status" 
                        class="block w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all outline-none">
                    <option value="">Todos los estados</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>

                <select v-model="origin_warehouse_id" 
                        class="block w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all outline-none">
                    <option value="">Almacén Origen</option>
                    <option v-for="w in originWarehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>

                <select v-model="destination_warehouse_id" 
                        class="block w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all outline-none">
                    <option value="">Almacén Destino</option>
                    <option v-for="w in destinationWarehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800">
                            <th class="px-6 py-4 font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider text-[11px]">Número / Fecha</th>
                            <th class="px-6 py-4 font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider text-[11px]">Origen</th>
                            <th class="px-6 py-4 font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider text-[11px]">Destino</th>
                            <th class="px-6 py-4 font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider text-[11px] text-center">Estado</th>
                            <th class="px-6 py-4 font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider text-[11px] text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <tr v-for="tr in transfers.data" :key="tr.id" 
                            class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-zinc-900 dark:text-white">{{ tr.number || 'DRAFT' }}</span>
                                    <span class="text-[10px] text-zinc-500 dark:text-zinc-400 capitalize italic">{{ new Date(tr.created_at).toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ tr.origin_warehouse.name }}</span>
                                    <span class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ tr.origin_branch?.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ tr.destination_warehouse.name }}</span>
                                    <span class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ tr.destination_branch?.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span :class="[
                                    'px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider',
                                    tr.status === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                    tr.status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' :
                                    tr.status === 'approved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                    tr.status === 'draft' ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400' :
                                    tr.status === 'in_transit' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400' :
                                    'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                                ]">
                                    {{ tr.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="route('admin.transfers.show', tr.id)" 
                                          title="Ver Detalle"
                                          class="p-2 text-zinc-400 hover:text-violet-600 dark:hover:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-lg transition-all">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </Link>

                                    <template v-if="tr.status === 'pending' && tr.can_approve">
                                        <button @click="approve(tr.id)" 
                                                title="Aprobar"
                                                class="p-2 text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-all">
                                            <span class="material-symbols-outlined text-[20px]">check_circle</span>
                                        </button>
                                    </template>

                                    <template v-if="tr.status === 'approved'">
                                        <Link :href="route('admin.transfers.show', tr.id)" 
                                              title="Despachar (Ingresar cantidades)"
                                              class="p-2 text-violet-500 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-lg transition-all">
                                            <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                                        </Link>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="transfers.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-zinc-500 italic">No se encontraron transferencias.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="transfers.meta && transfers.meta.last_page > 1" class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50 flex justify-center">
                <!-- Paginación -->
            </div>
        </div>
    </AdminLayout>
</template>
