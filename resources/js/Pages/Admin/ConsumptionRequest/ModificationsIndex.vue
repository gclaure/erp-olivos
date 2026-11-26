<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import ResolveModificationModal from '@/Components/ConsumptionModification/ResolveModificationModal.vue';
import RecordReturnModal from '@/Components/ConsumptionModification/RecordReturnModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    modifications: {
        type: Object,
        required: true,
    },
    counts: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const statusFilter = ref(props.filters.status || 'pendiente');
const search = ref(props.filters.search || '');

const selectedToResolve = ref(null);
const showResolveModal = ref(false);

const selectedForReturn = ref(null);
const showReturnModal = ref(false);

const items = computed(() => props.modifications?.data || []);
const links = computed(() => props.modifications?.meta?.links || props.modifications?.links || []);

const applyFilters = debounce(() => {
    router.get(
        route('admin.consumption-modifications.index'),
        {
            status: statusFilter.value,
            search: search.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}, 250);

const selectTab = (tabStatus) => {
    statusFilter.value = tabStatus;
    applyFilters();
};

watch(search, () => applyFilters());

const openResolve = (mod) => {
    selectedToResolve.value = mod;
    showResolveModal.value = true;
};

const openReturn = (mod) => {
    selectedForReturn.value = mod;
    showReturnModal.value = true;
};

const reloadTray = () => {
    router.reload({ only: ['modifications', 'counts'] });
};

// Tiempo real Reverb en canal de sucursal
const activeBranchId = computed(() => page.props.activeBranch?.id);
let subscribedBranch = null;

const subscribeBranch = (branchId) => {
    if (subscribedBranch && window.Echo) {
        window.Echo.leave(`sucursal.${subscribedBranch}`);
        subscribedBranch = null;
    }
    if (window.Echo && branchId) {
        subscribedBranch = branchId;
        window.Echo.private(`sucursal.${branchId}`)
            .listen('.consumption-modification.requested', reloadTray)
            .listen('.consumption-modification.resolved', reloadTray)
            .listen('.consumption-modification.return-recorded', reloadTray)
            .listen('.consumption-modification.applied', reloadTray);
    }
};

watch(activeBranchId, (id) => subscribeBranch(id), { immediate: true });

onUnmounted(() => {
    if (subscribedBranch && window.Echo) {
        window.Echo.leave(`sucursal.${subscribedBranch}`);
    }
});

const badgeClass = (status) => {
    switch (status) {
        case 'pendiente':
            return 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50';
        case 'aprobada':
            return 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/50';
        case 'aplicada':
            return 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50';
        default:
            return 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50';
    }
};
</script>

<template>
    <Head title="Solicitudes de Modificación de Consumo" />

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-6">
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-zinc-900 dark:text-white uppercase tracking-tight">
                    Solicitudes de Modificación
                </h1>
                <p class="text-xs sm:text-sm font-semibold text-zinc-500 dark:text-secondary-400 mt-1">
                    Bandeja de autorización administrativa para sustituciones de productos y control de devoluciones físicas
                </p>
            </div>
            <Link
                :href="route('admin.consumption-requests.index')"
                class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-xl bg-white hover:bg-zinc-100 dark:bg-secondary-800 dark:hover:bg-secondary-700 text-zinc-700 dark:text-secondary-100 border border-zinc-200 dark:border-secondary-700 text-xs font-black uppercase tracking-wider gap-2 transition-colors"
            >
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                <span>Volver a Consumos</span>
            </Link>
        </div>

        <!-- Tabs y Buscador -->
        <div class="bg-white dark:bg-secondary-800 border border-zinc-200/80 dark:border-secondary-700 rounded-2xl p-4 sm:p-5 space-y-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="selectTab('pendiente')"
                    class="min-h-[40px] px-3.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all inline-flex items-center gap-2"
                    :class="
                        statusFilter === 'pendiente'
                            ? 'bg-primary-600 text-white shadow-sm'
                            : 'bg-zinc-50 dark:bg-secondary-900 text-zinc-700 dark:text-secondary-200 border border-zinc-200 dark:border-secondary-700'
                    "
                >
                    <span>Pendientes</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-black/15">{{ counts.pendiente || 0 }}</span>
                </button>

                <button
                    type="button"
                    @click="selectTab('aprobada')"
                    class="min-h-[40px] px-3.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all inline-flex items-center gap-2"
                    :class="
                        statusFilter === 'aprobada'
                            ? 'bg-primary-600 text-white shadow-sm'
                            : 'bg-zinc-50 dark:bg-secondary-900 text-zinc-700 dark:text-secondary-200 border border-zinc-200 dark:border-secondary-700'
                    "
                >
                    <span>En Devolución Física</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-black/15">{{ counts.aprobada || 0 }}</span>
                </button>

                <button
                    type="button"
                    @click="selectTab('aplicada')"
                    class="min-h-[40px] px-3.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all inline-flex items-center gap-2"
                    :class="
                        statusFilter === 'aplicada'
                            ? 'bg-primary-600 text-white shadow-sm'
                            : 'bg-zinc-50 dark:bg-secondary-900 text-zinc-700 dark:text-secondary-200 border border-zinc-200 dark:border-secondary-700'
                    "
                >
                    <span>Aplicadas</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-black/15">{{ counts.aplicada || 0 }}</span>
                </button>

                <button
                    type="button"
                    @click="selectTab('todas')"
                    class="min-h-[40px] px-3.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    :class="
                        statusFilter === 'todas'
                            ? 'bg-primary-600 text-white shadow-sm'
                            : 'bg-zinc-50 dark:bg-secondary-900 text-zinc-700 dark:text-secondary-200 border border-zinc-200 dark:border-secondary-700'
                    "
                >
                    Todas
                </button>
            </div>

            <div class="relative">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por número de solicitud, área, producto original/nuevo o motivo..."
                    class="w-full h-11 pl-10 pr-4 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-900 dark:text-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none"
                />
                <span class="material-symbols-outlined text-lg text-zinc-400 absolute left-3 top-2.5">search</span>
            </div>
        </div>

        <!-- Tabla Responsiva Desktop + Cards Móviles -->
        <div class="bg-white dark:bg-secondary-800 border border-zinc-200/80 dark:border-secondary-700 rounded-2xl shadow-sm overflow-hidden">
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-secondary-700">
                    <thead class="bg-zinc-50 dark:bg-secondary-900/50 text-zinc-500 dark:text-secondary-400">
                        <tr>
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider">Solicitud / Área</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider">Sustitución Propuesta</th>
                            <th class="hidden lg:table-cell px-4 py-3 text-center text-[10px] font-black uppercase tracking-wider">Situación Física</th>
                            <th class="hidden xl:table-cell px-4 py-3 text-center text-[10px] font-black uppercase tracking-wider">Compras</th>
                            <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/60">
                        <tr
                            v-for="mod in items"
                            :key="mod.id"
                            class="hover:bg-zinc-50/80 dark:hover:bg-secondary-700/60 transition-colors"
                        >
                            <td class="px-4 py-4">
                                <Link
                                    :href="route('admin.consumption-requests.show', { consumption_request: mod.consumption_request_id })"
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 font-black text-xs border border-primary-200/60 dark:border-primary-800/40 hover:underline"
                                >
                                    #{{ mod.consumption_request?.formatted_number || 'S/N' }}
                                </Link>
                                <p class="text-xs font-black text-zinc-800 dark:text-secondary-100 uppercase mt-1">
                                    {{ mod.consumption_request?.requested_by }}
                                </p>
                                <p class="text-[10px] font-semibold text-zinc-500 dark:text-secondary-400">
                                    Almacén: {{ mod.requested_by_user?.name }} · {{ mod.created_at_formatted }}
                                </p>
                            </td>

                            <td class="px-4 py-4 max-w-sm">
                                <div class="text-xs font-black text-zinc-700 dark:text-secondary-200 uppercase">
                                    {{ mod.from_product?.name }} ({{ mod.original_quantity }} {{ mod.from_product?.unit_of_measure }})
                                </div>
                                <div class="text-xs font-black text-primary-700 dark:text-primary-300 uppercase flex items-center gap-1 mt-0.5">
                                    <span class="material-symbols-outlined text-sm">subdirectory_arrow_right</span>
                                    <span>{{ mod.to_product?.name }} ({{ mod.requested_quantity }} {{ mod.to_product?.unit_of_measure }})</span>
                                </div>
                                <p class="text-[11px] text-zinc-500 dark:text-secondary-400 italic truncate mt-1" :title="mod.reason">
                                    "{{ mod.reason }}"
                                </p>
                            </td>

                            <td class="hidden lg:table-cell px-4 py-4 text-center text-xs font-mono">
                                <span v-if="mod.quantity_dispatched_before_modification === 0" class="text-zinc-400 dark:text-secondary-500 font-sans font-semibold">
                                    Sin despacho previo
                                </span>
                                <div v-else class="space-y-0.5">
                                    <div class="text-zinc-700 dark:text-secondary-200 font-bold">Almacén: {{ mod.quantity_prepared }}</div>
                                    <div :class="mod.quantity_physically_delivered > 0 ? 'text-amber-600 dark:text-amber-400 font-black' : 'text-zinc-400'">
                                        Entregado: {{ mod.quantity_physically_delivered }}
                                    </div>
                                    <div v-if="mod.status === 'aprobada'" class="text-[10px] text-sky-600 dark:text-sky-400 font-black">
                                        Pend. devolver: {{ mod.pending_return_quantity }}
                                    </div>
                                </div>
                            </td>

                            <td class="hidden xl:table-cell px-4 py-4 text-center">
                                <span
                                    v-if="mod.open_purchase_lines?.length > 0"
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50"
                                >
                                    {{ mod.open_purchase_lines.length }} línea(s) por resolver
                                </span>
                                <span v-else class="text-[11px] font-semibold text-zinc-400">—</span>
                            </td>

                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider" :class="badgeClass(mod.status)">
                                    {{ mod.status_label }}
                                </span>
                            </td>

                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        v-if="mod.status === 'pendiente'"
                                        type="button"
                                        @click="openResolve(mod)"
                                        class="min-h-[38px] px-3.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-black uppercase tracking-wider transition-all shadow-sm"
                                    >
                                        Revisar
                                    </button>
                                    <button
                                        v-if="mod.status === 'aprobada'"
                                        type="button"
                                        @click="openReturn(mod)"
                                        class="min-h-[38px] px-3.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black uppercase tracking-wider transition-all shadow-sm"
                                    >
                                        Devolución
                                    </button>
                                    <Link
                                        :href="route('admin.consumption-requests.show', { consumption_request: mod.consumption_request_id })"
                                        class="min-h-[38px] px-3 rounded-xl bg-white hover:bg-zinc-100 dark:bg-secondary-900 dark:hover:bg-secondary-700 text-zinc-700 dark:text-secondary-200 border border-zinc-200 dark:border-secondary-700 text-xs font-black uppercase inline-flex items-center"
                                    >
                                        Ver Solicitud
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="items.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-xs font-bold text-zinc-500 dark:text-secondary-400 uppercase">
                                No hay solicitudes de modificación para este filtro.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Cards Móviles (< 768px) -->
            <div class="md:hidden divide-y divide-zinc-100 dark:divide-secondary-700/60">
                <div v-for="mod in items" :key="'mob-' + mod.id" class="p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <Link
                            :href="route('admin.consumption-requests.show', { consumption_request: mod.consumption_request_id })"
                            class="px-2.5 py-1 rounded-lg bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 font-black text-xs border border-primary-200/60 dark:border-primary-800/40"
                        >
                            #{{ mod.consumption_request?.formatted_number || 'S/N' }} · {{ mod.consumption_request?.requested_by }}
                        </Link>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase" :class="badgeClass(mod.status)">
                            {{ mod.status_label }}
                        </span>
                    </div>

                    <div class="text-xs space-y-1">
                        <p class="font-bold text-zinc-600 dark:text-secondary-300">
                            De: <strong class="text-zinc-900 dark:text-white">{{ mod.from_product?.name }} ({{ mod.original_quantity }})</strong>
                        </p>
                        <p class="font-bold text-primary-700 dark:text-primary-300">
                            A: <strong>{{ mod.to_product?.name }} ({{ mod.requested_quantity }} {{ mod.to_product?.unit_of_measure }})</strong>
                        </p>
                        <p class="text-[11px] italic text-zinc-500">"{{ mod.reason }}"</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 pt-2">
                        <button
                            v-if="mod.status === 'pendiente'"
                            type="button"
                            @click="openResolve(mod)"
                            class="min-h-[44px] w-full rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-black uppercase tracking-wider"
                        >
                            Revisar y Resolver
                        </button>
                        <button
                            v-if="mod.status === 'aprobada'"
                            type="button"
                            @click="openReturn(mod)"
                            class="min-h-[44px] w-full rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black uppercase tracking-wider"
                        >
                            Gestionar Devolución ({{ mod.pending_return_quantity }} pend.)
                        </button>
                    </div>
                </div>
                <div v-if="items.length === 0" class="p-8 text-center text-xs font-bold text-zinc-500 dark:text-secondary-400 uppercase">
                    No hay solicitudes de modificación para este filtro.
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="links.length > 3" class="px-4 py-3 border-t border-zinc-100 dark:border-secondary-700 flex flex-wrap items-center justify-center gap-1">
                <template v-for="(link, idx) in links" :key="idx">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
                        :class="
                            link.active
                                ? 'bg-primary-600 text-white'
                                : 'bg-zinc-50 dark:bg-secondary-900 text-zinc-700 dark:text-secondary-300 hover:bg-zinc-100'
                        "
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <ResolveModificationModal
            :show="showResolveModal"
            :modification="selectedToResolve"
            @close="showResolveModal = false"
            @resolved="reloadTray"
        />

        <RecordReturnModal
            :show="showReturnModal"
            :modification="selectedForReturn"
            :can-request-return="true"
            :can-admin-cancel="true"
            @close="showReturnModal = false"
            @updated="reloadTray"
        />
    </div>
</template>
