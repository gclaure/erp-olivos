<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import debounce from 'lodash/debounce';
import TenantFormModal from './Partials/TenantFormModal.vue';
import QuickPaymentModal from './Partials/QuickPaymentModal.vue';
import AddUserModal from './Partials/AddUserModal.vue';
import AddRoleModal from './Partials/AddRoleModal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    tenants: Object,
    filters: Object,
    plans: Array,
    vendedores: Array,
    roles: Array,
    all_permissions: Array,
});

const search = ref(props.filters.search || '');
const filterStatus = ref(props.filters.status || '');
const filterAsesor = ref(props.filters.vendedor_id || '');

const showModal = ref(false);
const showPaymentModal = ref(false);
const showUserModal = ref(false);
const showRoleModal = ref(false);
const selectedTenantId = ref(null);

const selectedTenant = computed(() => {
    if (!selectedTenantId.value) return null;
    return props.tenants.data.find(t => t.id === selectedTenantId.value);
});

const openAddRole = (tenant) => {
    selectedTenantId.value = tenant.id;
    showRoleModal.value = true;
};

// Debounced search
watch([search, filterStatus, filterAsesor], debounce(() => {
    router.get(route('superadmin.tenants.index'), {
        search: search.value,
        status: filterStatus.value,
        vendedor_id: filterAsesor.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300));

const openCreate = () => {
    selectedTenantId.value = null;
    showModal.value = true;
};

const openEdit = (tenant) => {
    selectedTenantId.value = tenant.id;
    showModal.value = true;
};

const openPayment = (tenant) => {
    selectedTenantId.value = tenant.id;
    showPaymentModal.value = true;
};

const toggleStatus = (tenant) => {
    router.post(route('superadmin.tenants.toggle-status', tenant.id), {}, {
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: 'success',
                title: 'Estado actualizado'
            });
        }
    });
};

const confirmDelete = (tenant) => {
    Swal.fire({
        title: '¿Eliminar esta empresa?',
        text: "Se eliminarán todos los datos asociados. Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#71717a',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('superadmin.tenants.destroy', tenant.id));
        }
    });
};


defineOptions({ layout: SuperAdminLayout });
</script>

<template>
    <Head title="Gestión de Empresas" />

    <div>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">Gestión de Empresas</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Administra las empresas del sistema</p>
            </div>
            <button @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nueva Empresa
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
                               placeholder="Buscar por nombre, NIT o email..."
                               class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-gray-500 rounded-lg text-sm bg-white dark:bg-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors">
                    </div>
                    <div class="w-full sm:w-64">
                        <select v-model="filterAsesor" class="w-full rounded-lg border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500 text-sm py-2.5">
                            <option value="">Filtrar por Asesor</option>
                            <option v-for="v in vendedores" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                    </div>
                    <div class="w-full sm:w-64">
                        <select v-model="filterStatus" class="w-full rounded-lg border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500 text-sm py-2.5">
                            <option value="">Todos los Estados</option>
                            <option value="ACTIVE">Activo</option>
                            <option value="TRIAL">Prueba</option>
                            <option value="SUSPENDED">Suspendido</option>
                            <option value="CANCELLED">Cancelado</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Empresa</th>
                            <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Razón Social</th>
                            <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Contacto / Dir.</th>
                            <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Dueño / Admin</th>
                            <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Vendedor</th>
                            <th class="hidden xl:table-cell px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Suscripción</th>
                            <th class="hidden xl:table-cell px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Métricas</th>
                            <th class="px-4 sm:px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Estado</th>
                            <th class="px-4 sm:px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                        <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                            <td class="px-4 sm:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-xs font-bold text-violet-600 dark:text-violet-400 flex-shrink-0">
                                        {{ tenant.name.substring(0, 2).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ tenant.name }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">NIT: {{ tenant.nit }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ tenant.business_name }}</span>
                            </td>
                            <td class="hidden md:table-cell px-6 py-4">
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 space-y-1">
                                    <div v-if="tenant.email" class="flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                                        {{ tenant.email }}
                                    </div>
                                    <div v-if="tenant.phone" class="flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                                        {{ tenant.phone }}
                                    </div>
                                    <div v-if="tenant.address" class="flex items-start gap-1.5 truncate max-w-[180px]">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400 mt-0.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                                        <span class="truncate text-violet-600 dark:text-violet-400 font-medium">Sucurs:</span> <span class="truncate">{{ tenant.address }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden lg:table-cell px-6 py-4">
                                <div v-if="tenant.owner" class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300 flex-shrink-0">
                                        {{ tenant.owner.initials }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm text-zinc-900 dark:text-white truncate">{{ tenant.owner.name }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ tenant.owner.email }}</p>
                                    </div>
                                </div>
                                <span v-else class="text-xs text-zinc-400 italic">Sin asignar</span>
                            </td>
                            <td class="hidden md:table-cell px-6 py-4">
                                <div v-if="tenant.vendedor_name !== 'Sin asesor'" class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center text-[10px] font-bold flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm text-zinc-900 dark:text-white truncate">{{ tenant.vendedor_name }}</p>
                                    </div>
                                </div>
                                <span v-else class="text-xs text-zinc-400 italic">Sin asesor</span>
                            </td>
                            <td class="hidden xl:table-cell px-6 py-4">
                                <div v-if="tenant.subscription" class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ tenant.subscription.plan_name }}</span>
                                    </div>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">Renueva: {{ tenant.subscription.ends_at }}</p>
                                </div>
                                <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                    Sin Suscripción
                                </span>
                            </td>
                            <td class="hidden xl:table-cell px-6 py-4">
                                <div class="flex items-center justify-center gap-8">
                                    <div class="flex flex-col items-center gap-1">
                                        <div class="flex items-center gap-1.5 opacity-60">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-zinc-400 w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-9h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                            <span class="text-[9px] text-zinc-500 uppercase font-black tracking-tight">Sucursales</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs font-black text-zinc-700 dark:text-zinc-300">{{ tenant.metrics.branches_count }}</span>
                                            <span class="text-[10px] text-zinc-400 font-bold italic">/ {{ tenant.metrics.branches_limit }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-center gap-1">
                                        <div class="flex items-center gap-1.5 opacity-60">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-zinc-400 w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-3.833-6.247 4.103 4.103 0 0 0-1.463.266 11.077 11.077 0 0 0-5.451 5.451 11.185 11.185 0 0 1 3.501 1.11Zm-11.72.372a9.337 9.337 0 0 1 4.121-.952 4.125 4.125 0 0 1-3.833 6.247 4.103 4.103 0 0 1-1.463-.266 11.077 11.077 0 0 1-5.451-5.451 11.185 11.185 0 0 1 3.501 1.11Zm3.501-1.11a11.186 11.186 0 0 1 3.501-1.11 11.078 11.078 0 0 0-5.451-5.451 4.103 4.103 0 0 0-1.463-.266 4.125 4.125 0 0 0-3.833 6.247 9.337 9.337 0 0 0 4.121.952 9.38 9.38 0 0 0 2.625-.372Zm11.72-.372a9.337 9.337 0 0 0-4.121.952 4.125 4.125 0 0 0 3.833 6.247 4.103 4.103 0 0 0 1.463-.266 11.077 11.077 0 0 0 5.451-5.451 11.185 11.185 0 0 1-3.501-1.11ZM12 4.5c2.105 0 3.812 1.707 3.812 3.812A3.813 3.813 0 0 1 12 12.125 3.813 3.813 0 0 1 8.188 8.312 3.812 3.812 0 0 1 12 4.5ZM12 15a9 9 0 0 0-9 9h18a9 9 0 0 0-9-9Z" /></svg>
                                            <span class="text-[9px] text-zinc-500 uppercase font-black tracking-tight">Usuarios</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs font-black text-zinc-700 dark:text-zinc-300">{{ tenant.metrics.users_count }}</span>
                                            <span class="text-[10px] text-zinc-400 font-bold italic">/ {{ tenant.metrics.users_limit }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="tenant.status_color === 'emerald' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400' : 
                                            tenant.status_color === 'amber' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400' :
                                            tenant.status_color === 'red' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400' :
                                            'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300'">
                                    {{ tenant.status_label }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openPayment(tenant)" class="p-2 text-zinc-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors" title="Registrar Pago">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                                    </button>
                                    <button @click="openEdit(tenant)" class="p-2 text-zinc-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/30 rounded-lg transition-colors" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </button>
                                    <button @click="toggleStatus(tenant)" class="p-2 text-zinc-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors" title="Suspender/Activar">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                    </button>
                                    <button @click="openAddRole(tenant)" class="p-2 text-zinc-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="Añadir Nuevo Rol">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.333 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                    </button>
                                    <button @click="confirmDelete(tenant)" class="p-2 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="tenants.data.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mb-3"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-9h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                    <p class="text-zinc-500 dark:text-zinc-400 font-medium">No se encontraron empresas</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="tenants.meta" class="px-6 py-4 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50 dark:bg-gray-600">
                <nav class="flex items-center justify-between">
                    <p class="text-xs text-zinc-500">Mostrando {{ tenants.meta.from }} a {{ tenants.meta.to }} de {{ tenants.meta.total }} resultados</p>
                    <div class="flex items-center gap-1">
                        <Link v-if="tenants.links.prev" :href="tenants.links.prev" class="p-2 border border-zinc-200 rounded-lg text-zinc-400 hover:bg-zinc-50">« Anterior</Link>
                        <div class="w-8 h-8 flex items-center justify-center bg-violet-600 text-white text-xs font-bold rounded-lg shadow-sm">{{ tenants.meta.current_page }}</div>
                        <Link v-if="tenants.links.next" :href="tenants.links.next" class="p-2 border border-zinc-200 rounded-lg text-zinc-400 hover:bg-zinc-50">Siguiente »</Link>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Modals -->
        <TenantFormModal 
            :show="showModal" 
            :tenant="selectedTenant" 
            :plans="plans" 
            :vendedores="vendedores"
            @close="showModal = false"
        />

        <QuickPaymentModal 
            :show="showPaymentModal" 
            :tenant="selectedTenant" 
            @close="showPaymentModal = false" 
        />

        <AddUserModal 
            :show="showUserModal" 
            :tenant="selectedTenant" 
            :roles="roles"
            @close="showUserModal = false" 
        />
        <AddRoleModal 
            :show="showRoleModal" 
            :tenant="selectedTenant" 
            :permissions="all_permissions"
            @close="showRoleModal = false" 
        />
    </div>
</template>
