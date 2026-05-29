<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import Swal from 'sweetalert2';
import axios from 'axios';
import debounce from 'lodash/debounce';

const props = defineProps({
    subscriptions: Object,
    filters: Object,
    tenants: Array,
    plans: Array,
    statusOptions: Array
});

// Filtros
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const handleSearch = debounce(() => {
    router.get(route('superadmin.subscriptions.index'), {
        search: search.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch([search, statusFilter], () => {
    handleSearch();
});

// Modal Asignar
const showAssignModal = ref(false);
const assignForm = useForm({
    tenant_id: '',
    plan_id: '',
});

const openAssign = () => {
    assignForm.reset();
    assignForm.clearErrors();
    showAssignModal.value = true;
};

const submitAssign = () => {
    assignForm.post(route('superadmin.subscriptions.assign'), {
        onSuccess: () => {
            showAssignModal.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: 'success',
                title: 'Suscripción asignada correctamente.'
            });
        }
    });
};

// Modal Extender
const showExtendModal = ref(false);
const extendForm = useForm({
    id: null,
    days: 30,
    type: 'GIFT',
    reason: '',
});

const openExtend = (sub) => {
    extendForm.id = sub.id;
    extendForm.days = 30;
    extendForm.type = 'GIFT';
    extendForm.reason = '';
    extendForm.clearErrors();
    showExtendModal.value = true;
};

const submitExtend = () => {
    extendForm.post(route('superadmin.subscriptions.extend', extendForm.id), {
        onSuccess: () => {
            showExtendModal.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: 'success',
                title: `Suscripción extendida ${extendForm.days} días.`
            });
        }
    });
};

// Modal Cambio de Plan
const showChangePlanModal = ref(false);
const prorationData = ref(null);
const changePlanForm = useForm({
    id: null,
    new_plan_id: '',
});

const openChangePlan = (sub) => {
    changePlanForm.id = sub.id;
    changePlanForm.new_plan_id = '';
    prorationData.value = null;
    changePlanForm.clearErrors();
    showChangePlanModal.value = true;
};

const calculateProration = async () => {
    if (!changePlanForm.new_plan_id) {
        prorationData.value = null;
        return;
    }

    try {
        const response = await axios.post(route('superadmin.subscriptions.calculate-proration', changePlanForm.id), {
            new_plan_id: changePlanForm.new_plan_id
        });
        prorationData.value = response.data;
    } catch (error) {
        console.error('Error calculating proration', error);
    }
};

watch(() => changePlanForm.new_plan_id, () => {
    calculateProration();
});

const submitChangePlan = () => {
    changePlanForm.post(route('superadmin.subscriptions.change-plan', changePlanForm.id), {
        onSuccess: () => {
            showChangePlanModal.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: 'success',
                title: 'Plan actualizado correctamente.'
            });
        }
    });
};

// Acciones Directas
const confirmCancel = (sub) => {
    Swal.fire({
        title: '¿Cancelar esta suscripción?',
        text: 'La empresa perderá acceso al sistema.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#71717a',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('superadmin.subscriptions.cancel', sub.id), {}, {
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        icon: 'success',
                        title: 'Suscripción cancelada.'
                    });
                }
            });
        }
    });
};

const renew = (sub) => {
    router.post(route('superadmin.subscriptions.renew', sub.id), {}, {
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: 'success',
                title: 'Suscripción renovada.'
            });
        }
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '∞';
    return dateString;
};
</script>

<template>
    <SuperAdminLayout>
        <Head title="Suscripciones" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">Suscripciones</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Gestiona las suscripciones de las empresas</p>
            </div>
            <button @click="openAssign"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Asignar Suscripción
            </button>
        </div>

        <!-- Filtros -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 mb-6">
            <div class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="relative sm:col-span-1 lg:col-span-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        <input v-model="search"
                               type="text"
                               placeholder="Buscar empresa..."
                               class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-gray-500 rounded-lg text-sm bg-white dark:bg-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors">
                    </div>
                    <select v-model="statusFilter" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-gray-500 rounded-lg text-sm bg-white dark:bg-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors">
                        <option value="">Todos los Estados</option>
                        <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                            <th class="px-4 sm:px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Empresa</th>
                            <th class="hidden sm:table-cell px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Plan</th>
                            <th class="hidden md:table-cell px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Período</th>
                            <th class="hidden lg:table-cell px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Ancla</th>
                            <th class="px-4 sm:px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Estado</th>
                            <th class="px-4 sm:px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                        <tr v-for="sub in subscriptions.data" :key="sub.id" class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                            <td class="px-4 sm:px-6 py-4">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ sub.tenant.name }}</p>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 sm:hidden">{{ sub.plan.name }}</p>
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 w-fit">
                                        {{ sub.plan.name }}
                                    </span>
                                    <p v-if="sub.next_plan" class="text-[10px] text-amber-600 dark:text-amber-400 font-medium flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg> Próximo: {{ sub.next_plan.name }}
                                    </p>
                                </div>
                            </td>
                            <td class="hidden md:table-cell px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                <p class="text-sm">{{ sub.starts_at }} — {{ sub.ends_at }}</p>
                            </td>
                            <td class="hidden lg:table-cell px-6 py-4 text-center text-center">
                                <div class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-bold">
                                    {{ sub.billing_anchor_day }}
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-center text-center">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', 
                                    sub.status_color === 'emerald' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400' :
                                    sub.status_color === 'red' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400' :
                                    'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300']">
                                    {{ sub.status_label }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1 flex-wrap">
                                    <template v-if="sub.status === 'ACTIVE'">
                                        <button @click="openChangePlan(sub)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                            <span class="hidden sm:inline">Cambiar Plan</span>
                                        </button>
                                        <button @click="openExtend(sub)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/30 rounded-lg transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" /></svg>
                                            <span class="hidden sm:inline">Extender</span>
                                        </button>
                                        <button @click="confirmCancel(sub)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                            <span class="hidden sm:inline">Cancelar</span>
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button @click="renew(sub)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                            <span class="hidden sm:inline">Renovar</span>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="subscriptions.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-center">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mb-3"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                                    <p class="text-zinc-500 dark:text-zinc-400 font-medium">No hay suscripciones</p>
                                    <p class="text-sm text-zinc-400 dark:text-zinc-500 mt-1">Asigna una suscripción a una empresa</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="subscriptions.meta && subscriptions.meta.last_page > 1" class="px-6 py-4 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50 dark:bg-gray-600">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Mostrando {{ subscriptions.meta.from }} a {{ subscriptions.meta.to }} de {{ subscriptions.meta.total }} resultados
                    </p>
                    <div class="flex gap-2">
                        <Link v-for="link in subscriptions.meta.links" :key="link.label"
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

        <!-- Modales -->
        <!-- Modal Asignar -->
        <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200 text-left">
                <div class="p-6 border-b border-zinc-100 dark:border-gray-600">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">Asignar Suscripción</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-normal mt-1">Selecciona empresa, plan y duración</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Empresa *</label>
                        <select v-model="assignForm.tenant_id" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500 transition-all">
                            <option value="">Seleccionar empresa...</option>
                            <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-if="assignForm.errors.tenant_id" class="text-xs text-rose-500 mt-1">{{ assignForm.errors.tenant_id }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Plan *</label>
                        <select v-model="assignForm.plan_id" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500 transition-all">
                            <option value="">Seleccionar plan...</option>
                            <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }} ({{ p.billing_period === 'MONTHLY' ? 'Mensual' : 'Anual' }}) — Bs. {{ parseFloat(p.price).toFixed(2) }}</option>
                        </select>
                        <p v-if="assignForm.errors.plan_id" class="text-xs text-rose-500 mt-1">{{ assignForm.errors.plan_id }}</p>
                    </div>
                    <div class="p-3 bg-violet-50 dark:bg-violet-900/10 rounded-lg border border-violet-100 dark:border-violet-900/30">
                        <p class="text-xs text-violet-700 dark:text-violet-400 italic flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                            La duración y el ancla de cobro se calculan automáticamente según el plan.
                        </p>
                    </div>
                </div>
                <div class="p-6 bg-zinc-50/50 dark:bg-gray-700/50 border-t border-zinc-100 dark:border-gray-600 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                    <button @click="showAssignModal = false" class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-zinc-500 hover:bg-zinc-100 dark:hover:bg-gray-600 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button @click="submitAssign" 
                            :disabled="assignForm.processing"
                            class="w-full sm:w-auto px-8 py-2.5 bg-violet-600 text-white text-sm font-bold rounded-xl hover:bg-violet-700 shadow-lg shadow-violet-500/20 transition-all disabled:opacity-50">
                        Asignar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Extender -->
        <div v-if="showExtendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200 text-left">
                <div class="p-6 border-b border-zinc-100 dark:border-gray-600">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">Extender Suscripción</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-normal mt-1">Agrega días adicionales a la suscripción</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Días a Agregar *</label>
                        <input v-model="extendForm.days" type="number" min="1" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                        <p v-if="extendForm.errors.days" class="text-xs text-rose-500 mt-1">{{ extendForm.errors.days }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tipo de Extensión *</label>
                        <select v-model="extendForm.type" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="GIFT">Regalo</option>
                            <option value="PROMO">Promoción</option>
                            <option value="SUPPORT">Soporte</option>
                            <option value="COMPENSATION">Compensación</option>
                        </select>
                        <p v-if="extendForm.errors.type" class="text-xs text-rose-500 mt-1">{{ extendForm.errors.type }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Motivo (opcional)</label>
                        <textarea v-model="extendForm.reason" rows="3" placeholder="Razón de la extensión..." class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>
                </div>
                <div class="p-6 bg-zinc-50/50 dark:bg-gray-700/50 border-t border-zinc-100 dark:border-gray-600 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                    <button @click="showExtendModal = false" class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-zinc-500 hover:bg-zinc-100 dark:hover:bg-gray-600 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button @click="submitExtend" 
                            :disabled="extendForm.processing"
                            class="w-full sm:w-auto px-8 py-2.5 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 transition-all disabled:opacity-50">
                        Extender
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Cambio de Plan -->
        <div v-if="showChangePlanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200 text-left">
                <div class="p-6 border-b border-zinc-100 dark:border-gray-600">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">Cambiar Plan</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-normal mt-1">Upgrade inmediato o Downgrade programado</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nuevo Plan *</label>
                        <select v-model="changePlanForm.new_plan_id" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-amber-500 focus:border-amber-500 transition-all">
                            <option value="">Seleccionar nuevo plan...</option>
                            <option v-for="p in plans" :key="'new-plan-'+p.id" :value="p.id">{{ p.name }} — Bs. {{ parseFloat(p.price).toFixed(2) }}</option>
                        </select>
                        <p v-if="changePlanForm.errors.new_plan_id" class="text-xs text-rose-500 mt-1">{{ changePlanForm.errors.new_plan_id }}</p>
                    </div>

                    <div v-if="prorationData" 
                         :class="['p-4 rounded-xl border', prorationData.is_upgrade ? 'bg-emerald-50 border-emerald-100 dark:bg-emerald-900/10 dark:border-emerald-900/30' : 'bg-blue-50 border-blue-100 dark:bg-blue-900/10 dark:border-blue-900/30']">
                        <template v-if="prorationData.is_upgrade">
                            <h4 class="text-sm font-bold text-emerald-800 dark:text-emerald-400 mb-2">Resumen de Upgrade</h4>
                            <div class="space-y-1.5 text-xs text-emerald-700 dark:text-emerald-300">
                                <div class="flex justify-between">
                                    <span>Crédito (días no usados):</span>
                                    <span class="font-semibold">Bs. {{ prorationData.unused_value }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Costo Nuevo Plan:</span>
                                    <span class="font-semibold">Bs. {{ prorationData.new_price.toFixed(2) }}</span>
                                </div>
                                <div class="border-t border-emerald-200 dark:border-emerald-800 my-1 pt-1 flex justify-between text-sm font-black">
                                    <span>Saldo a Cobrar Hoy:</span>
                                    <span>Bs. {{ prorationData.balance_due.toFixed(2) }}</span>
                                </div>
                            </div>
                            <p class="text-[10px] mt-3 italic text-emerald-600 dark:text-emerald-500">
                                * El nuevo ciclo comenzará hoy y durará un periodo completo (mes/año).
                            </p>
                        </template>
                        <template v-else>
                            <h4 class="text-sm font-bold text-blue-800 dark:text-blue-400 mb-2">Resumen de Downgrade</h4>
                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                El cambio de plan NO es inmediato. Se aplicará automáticamente al finalizar el periodo actual para respetar el tiempo ya pagado.
                            </p>
                            <div class="mt-2 text-xs font-semibold text-blue-800 dark:text-blue-400">
                                Fecha de cambio: {{ formatDate(new Date(Date.now() + prorationData.remaining_days * 24 * 60 * 60 * 1000).toLocaleDateString('es-BO')) }}
                            </div>
                        </template>
                    </div>
                </div>
                <div class="p-6 bg-zinc-50/50 dark:bg-gray-700/50 border-t border-zinc-100 dark:border-gray-600 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                    <button @click="showChangePlanModal = false" class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-zinc-500 hover:bg-zinc-100 dark:hover:bg-gray-600 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button @click="submitChangePlan" 
                            :disabled="changePlanForm.processing || !changePlanForm.new_plan_id"
                            class="w-full sm:w-auto px-8 py-2.5 bg-amber-600 text-white text-sm font-bold rounded-xl hover:bg-amber-700 shadow-lg shadow-amber-500/20 transition-all disabled:opacity-50">
                        Confirmar Cambio
                    </button>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
