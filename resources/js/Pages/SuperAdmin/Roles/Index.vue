<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import Swal from 'sweetalert2';
import debounce from 'lodash/debounce';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    roles: Object,
    permissions: Object,
    allPermissions: Array,
    filters: Object,
    translations: Object
});

const page = usePage();
const isSuperAdminPath = computed(() => page.url.startsWith('/superadmin'));
const Layout = computed(() => isSuperAdminPath.value ? SuperAdminLayout : AdminLayout);

const activeTab = ref(props.filters.tab || 'roles');
const search = ref(props.filters.search || '');

const handleSearch = debounce(() => {
    const routeName = isSuperAdminPath.value ? 'superadmin.roles.index' : 'admin.roles.index';
    router.get(route(routeName), {
        search: search.value,
        tab: activeTab.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch([search, activeTab], () => {
    handleSearch();
});

// Role Modal
const showModal = ref(false);
const editId = ref(null);
const roleForm = useForm({
    name: '',
    permissions: []
});

watch(() => roleForm.name, (newName) => {
    if (newName && (newName.toLowerCase() === 'admin' || newName.toLowerCase() === 'administrador')) {
        roleForm.permissions = props.allPermissions.map(p => p.name);
    }
});

const openCreateRole = () => {
    editId.value = null;
    roleForm.reset();
    roleForm.clearErrors();
    showModal.value = true;
};

const openEditRole = (role) => {
    editId.value = role.id;
    roleForm.name = role.name;
    if (role.name.toLowerCase() === 'admin' || role.name.toLowerCase() === 'administrador') {
        roleForm.permissions = props.allPermissions.map(p => p.name);
    } else {
        roleForm.permissions = role.permissions.map(p => p.name);
    }
    roleForm.clearErrors();
    showModal.value = true;
};

const submitRole = () => {
    const storeRoute = isSuperAdminPath.value ? 'superadmin.roles.store' : 'admin.roles.store';
    const updateRoute = isSuperAdminPath.value ? 'superadmin.roles.update' : 'admin.roles.update';

    if (editId.value) {
        roleForm.put(route(updateRoute, editId.value), {
            onSuccess: () => {
                showModal.value = false;
                Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Rol actualizado correctamente.' });
            }
        });
    } else {
        roleForm.post(route(storeRoute), {
            onSuccess: () => {
                showModal.value = false;
                Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Rol creado correctamente.' });
            }
        });
    }
};

// Permission Modal
const showPermissionModal = ref(false);
const editPermissionId = ref(null);
const permissionForm = useForm({
    name: ''
});

const openCreatePermission = () => {
    editPermissionId.value = null;
    permissionForm.reset();
    permissionForm.clearErrors();
    showPermissionModal.value = true;
};

const openEditPermission = (perm) => {
    editPermissionId.value = perm.id;
    permissionForm.name = perm.name;
    permissionForm.clearErrors();
    showPermissionModal.value = true;
};

const submitPermission = () => {
    const storeRoute = isSuperAdminPath.value ? 'superadmin.permissions.store' : 'admin.permissions.store';
    const updateRoute = isSuperAdminPath.value ? 'superadmin.permissions.update' : 'admin.permissions.update';

    if (editPermissionId.value) {
        permissionForm.put(route(updateRoute, editPermissionId.value), {
            onSuccess: () => {
                showPermissionModal.value = false;
                Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Permiso actualizado correctamente.' });
            }
        });
    } else {
        permissionForm.post(route(storeRoute), {
            onSuccess: () => {
                showPermissionModal.value = false;
                Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Permiso creado correctamente.' });
            }
        });
    }
};

const confirmDelete = (id, type) => {
    Swal.fire({
        title: type === 'role' ? '¿Eliminar rol?' : '¿Eliminar permiso?',
        text: type === 'role' ? 'Se removerá este rol de todos los usuarios asignados.' : 'Se removerá este permiso de todos los roles asignados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#71717a',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let url;
            if (type === 'role') {
                url = route(isSuperAdminPath.value ? 'superadmin.roles.destroy' : 'admin.roles.destroy', id);
            } else {
                url = route(isSuperAdminPath.value ? 'superadmin.permissions.destroy' : 'admin.permissions.destroy', id);
            }
            router.delete(url, {
                onSuccess: () => Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Eliminado correctamente.' })
            });
        }
    });
};

const permissionDescriptions = {
    'create-purchases': 'Registra nuevas facturas de compra de mercancía.',
    'create-sales': 'Realiza ventas directas desde el Punto de Venta (POS).',
    'manage-categories': 'Organiza y clasifica productos en grupos lógicos.',
    'manage-clients': 'Administra la base de datos de compradores y sus créditos.',
    'manage-inventory': 'Controla stock, ajustes manuales y traslados (Kardex).',
    'manage-products': 'Crea y edita el catálogo de artículos y sus precios.',
    'manage-providers': 'Administra la información de los abastecedores.',
    'manage-purchases': 'Ver historial de compras y gestionar estados de pago.',
    'manage-roles': 'Define perfiles de seguridad y asigna permisos.',
    'manage-sales': 'Revisa facturación, cotizaciones y anulaciones.',
    'manage-deliveries': 'Administra la logística y despacho de ventas realizadas.',
    'manage-settings': 'Ajusta el perfil del usuario y preferencias del sistema.',
    'manage-users': 'Administra las cuentas de acceso de los empleados.',
    'manage-warehouses': 'Organiza los depósitos físicos de mercancía.',
    'view-reports': 'Accede a analíticas de ventas y rendimiento del negocio.',
    'manage-branches': 'Gestiona las sedes físicas y puntos de venta.',
    'pos-access': 'Habilita la interfaz táctil de ventas rápidas (Puntos de Venta).',
    'manage-pos': 'Administra los puntos de venta de la empresa.',
    'manage-transfers': 'Gestiona traslados de mercancía entre almacenes.',
    'manage-company': 'Modifica logo y configuración legal de la compañía.',
};

const translatePermission = (name) => {
    return props.translations[name] || name.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};
</script>

<template>
    <component :is="Layout">
        <Head title="Roles y Permisos" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 text-left">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">Roles y Permisos</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Configura los roles y gestiona permisos del sistema</p>
            </div>
            <div class="flex items-center gap-2">
                <button v-if="isSuperAdminPath" @click="activeTab = 'roles'" :class="['px-4 py-2.5 text-sm font-medium rounded-lg transition-colors', activeTab === 'roles' ? 'bg-zinc-800 text-white shadow-sm' : 'bg-white dark:bg-gray-600 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-gray-500 hover:bg-zinc-50 dark:hover:bg-gray-500']">Roles</button>
                <button v-if="isSuperAdminPath" @click="activeTab = 'permissions'" :class="['px-4 py-2.5 text-sm font-medium rounded-lg transition-colors', activeTab === 'permissions' ? 'bg-zinc-800 text-white shadow-sm' : 'bg-white dark:bg-gray-600 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-gray-500 hover:bg-zinc-50 dark:hover:bg-gray-500']">Permisos</button>
                <button v-if="page.props.auth.user.is_super_admin" @click="activeTab === 'roles' ? openCreateRole() : openCreatePermission()" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    {{ activeTab === 'roles' ? 'Nuevo Rol' : 'Nuevo Permiso' }}
                </button>
            </div>
        </div>

        <!-- Search -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 mb-6">
            <div class="p-4">
                <div class="relative w-full sm:w-80">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input v-model="search" type="text" :placeholder="activeTab === 'roles' ? 'Buscar rol...' : 'Buscar permiso...'" 
                           class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
            </div>
        </div>

        <!-- Tab: Roles -->
        <div v-if="activeTab === 'roles'">
            <!-- Desktop View -->
            <div class="hidden md:block bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                                <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Permisos</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Usuarios</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                            <tr v-for="(role, index) in roles.data" :key="role.id" class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                                <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">{{ (roles.meta.from || 0) + index }}</td>
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                                    <span class="inline-flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-indigo-400"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                        {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="role.name.toLowerCase() === 'admin' || role.name.toLowerCase() === 'administrador'" class="flex flex-wrap gap-1">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase bg-gradient-to-r from-emerald-500/10 to-teal-500/10 dark:from-emerald-500/20 dark:to-teal-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/30 shadow-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Todos los Accesos
                                        </span>
                                    </div>
                                    <div v-else class="flex flex-wrap gap-1 max-w-md">
                                        <span v-for="perm in role.permissions.slice(0, 4)" :key="perm.id"
                                              class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-zinc-100 dark:bg-gray-700 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-gray-500">
                                            {{ perm.label }}
                                        </span>
                                        <span v-if="role.permissions.length > 4" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            +{{ role.permissions.length - 4 }} más
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 dark:bg-gray-700 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-gray-500">
                                        {{ role.users_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="openEditRole(role)" class="p-2 text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                        </button>
                                        <button v-if="page.props.auth.user.is_super_admin && role.name !== 'admin'" @click="confirmDelete(role.id, 'role')" class="p-2 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Eliminar">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="roles.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                    <p class="text-zinc-500 dark:text-zinc-400 font-medium">No se encontraron roles</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Desktop Pagination -->
                <div v-if="roles.meta && roles.meta.last_page > 1" class="px-6 py-4 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50 dark:bg-gray-600">
                    <div class="flex items-center justify-between">
                        <div class="flex gap-2">
                            <Link v-for="link in roles.meta.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                                   :class="['px-3 py-1 text-xs rounded-md border transition-colors', link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-700 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-gray-600 hover:bg-zinc-50 dark:hover:bg-gray-500', !link.url ? 'opacity-50 cursor-not-allowed' : '']" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden space-y-4">
                <div v-for="(role, index) in roles.data" :key="role.id" 
                     class="bg-white dark:bg-gray-800/50 rounded-2xl border border-zinc-200 dark:border-gray-700 p-4 shadow-sm space-y-4">
                    <!-- Card Header -->
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Rol</span>
                            <span class="text-xs font-black text-zinc-900 dark:text-white capitalize mt-0.5 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                {{ role.name }}
                            </span>
                        </div>
                        <span class="text-[10px] font-bold text-zinc-400">#{{ (roles.meta.from || 0) + index }}</span>
                    </div>

                    <!-- Card Details -->
                    <div class="space-y-3">
                        <div class="flex justify-between items-start gap-4">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest mt-1">Permisos</span>
                            <div v-if="role.name.toLowerCase() === 'admin' || role.name.toLowerCase() === 'administrador'" class="flex justify-end">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase bg-gradient-to-r from-emerald-500/10 to-teal-500/10 dark:from-emerald-500/20 dark:to-teal-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/30">
                                    <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Todos los Accesos
                                </span>
                            </div>
                            <div v-else class="flex flex-wrap gap-1 justify-end max-w-[180px]">
                                <span v-for="perm in role.permissions.slice(0, 3)" :key="perm.id" class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-zinc-100 dark:bg-gray-700 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-gray-600">
                                    {{ perm.label }}
                                </span>
                                <span v-if="role.permissions.length > 3" class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-indigo-50 text-indigo-600 border border-indigo-100">
                                    +{{ role.permissions.length - 3 }}
                                </span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Usuarios</span>
                            <span class="text-xs font-black text-zinc-900 dark:text-white bg-zinc-50 dark:bg-gray-700 px-2 rounded-lg border border-zinc-100 dark:border-gray-600">{{ role.users_count }}</span>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="flex justify-end items-center gap-3 pt-2 border-t border-zinc-100 dark:border-gray-700/50">
                        <button @click="openEditRole(role)" class="w-10 h-10 flex items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 active:scale-90 transition-all border border-amber-100 dark:border-amber-800/20" title="Editar">
                            <span class="material-symbols-outlined text-lg">edit</span>
                        </button>
                        <button v-if="page.props.auth.user.is_super_admin && role.name !== 'admin'" @click="confirmDelete(role.id, 'role')" class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 active:scale-90 transition-all border border-rose-100 dark:border-rose-800/20" title="Eliminar">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State Mobile -->
                <div v-if="roles.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800 rounded-2xl border border-zinc-200 dark:border-gray-700 shadow-sm">
                    <div class="flex flex-col items-center opacity-40">
                        <span class="material-symbols-outlined text-4xl mb-4 text-zinc-300 dark:text-zinc-600">shield_person</span>
                        <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-xs">Sin roles registrados</p>
                    </div>
                </div>

                <!-- Mobile Pagination -->
                <div v-if="roles.meta && roles.meta.last_page > 1" class="flex justify-center pt-2">
                    <div class="flex gap-2">
                        <Link v-for="link in roles.meta.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                               :class="['px-4 py-2 text-sm rounded-xl border transition-all shadow-sm font-black', link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-gray-700', !link.url ? 'opacity-50 cursor-not-allowed' : 'active:scale-95']" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Permissions -->
        <div v-if="activeTab === 'permissions'">
            <!-- Desktop View -->
            <div class="hidden md:block bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden text-left shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                                <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Nombre del Permiso</th>
                                <th class="px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Llave Interna</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                            <tr v-for="(perm, index) in permissions.data" :key="perm.id" class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                                <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">{{ (permissions.meta.from || 0) + index }}</td>
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ translatePermission(perm.name) }}</td>
                                <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400 font-mono text-xs italic">{{ perm.name }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="openEditPermission(perm)" class="p-2 text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                        </button>
                                        <button @click="confirmDelete(perm.id, 'permission')" class="p-2 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Eliminar">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="permissions.data.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-zinc-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a3 3 0 0 1-3 3m0 0a3 3 0 0 1-3 3m0 0a3 3 0 0 1 3-3Zm-10.5 0a3 3 0 0 1 3 3m3 0a3 3 0 0 1-3 3m0 0a3 3 0 0 1-3-3m0 0a3 3 0 0 1 3-3Zm0 10.5a3 3 0 0 1 3 3m3 0a3 3 0 0 1-3 3m0 0a3 3 0 0 1-3-3m0 0a3 3 0 0 1 3-3Z" /></svg>
                                    No se encontraron permisos registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Desktop Pagination -->
                <div v-if="permissions.meta && permissions.meta.last_page > 1" class="px-6 py-4 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50 dark:bg-gray-600">
                    <div class="flex items-center justify-between">
                        <div class="flex gap-2">
                            <Link v-for="link in permissions.meta.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                                   :class="['px-3 py-1 text-xs rounded-md border transition-colors', link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-700 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-gray-600 hover:bg-zinc-50 dark:hover:bg-gray-500', !link.url ? 'opacity-50 cursor-not-allowed' : '']" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden space-y-4">
                <div v-for="(perm, index) in permissions.data" :key="perm.id" 
                     class="bg-white dark:bg-gray-800/50 rounded-2xl border border-zinc-200 dark:border-gray-700 p-4 shadow-sm space-y-4">
                    <!-- Card Header -->
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Permiso</span>
                            <span class="text-xs font-black text-zinc-900 dark:text-white capitalize mt-0.5">
                                {{ translatePermission(perm.name) }}
                            </span>
                        </div>
                        <span class="text-[10px] font-bold text-zinc-400">#{{ (permissions.meta.from || 0) + index }}</span>
                    </div>

                    <!-- Card Details -->
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Llave Interna</span>
                            <span class="text-[10px] font-mono font-bold text-zinc-500 dark:text-zinc-400 italic bg-zinc-50 dark:bg-gray-700 px-1.5 py-0.5 rounded border border-zinc-100 dark:border-gray-600">{{ perm.name }}</span>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="flex justify-end items-center gap-3 pt-2 border-t border-zinc-100 dark:border-gray-700/50">
                        <button @click="openEditPermission(perm)" class="w-10 h-10 flex items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 active:scale-90 transition-all border border-amber-100 dark:border-amber-800/20" title="Editar">
                            <span class="material-symbols-outlined text-lg">edit</span>
                        </button>
                        <button @click="confirmDelete(perm.id, 'permission')" class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 active:scale-90 transition-all border border-rose-100 dark:border-rose-800/20" title="Eliminar">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State Mobile -->
                <div v-if="permissions.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800 rounded-2xl border border-zinc-200 dark:border-gray-700 shadow-sm">
                    <div class="flex flex-col items-center opacity-40">
                        <span class="material-symbols-outlined text-4xl mb-4 text-zinc-300 dark:text-zinc-600">lock_open</span>
                        <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-xs">Sin permisos registrados</p>
                    </div>
                </div>

                <!-- Mobile Pagination -->
                <div v-if="permissions.meta && permissions.meta.last_page > 1" class="flex justify-center pt-2">
                    <div class="flex gap-2">
                        <Link v-for="link in permissions.meta.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                               :class="['px-4 py-2 text-sm rounded-xl border transition-all shadow-sm font-black', link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-gray-700', !link.url ? 'opacity-50 cursor-not-allowed' : 'active:scale-95']" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Role -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200 text-left">
                <div class="p-6 border-b border-zinc-100 dark:border-gray-600">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ editId ? 'Editar Rol' : 'Nuevo Rol' }}</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nombre del Rol *</label>
                        <input v-model="roleForm.name" type="text" placeholder="Ej: manager, vendedor" 
                               :disabled="!page.props.auth.user.is_super_admin"
                               class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-all disabled:opacity-75 disabled:bg-zinc-50" />
                        <p v-if="roleForm.errors.name" class="text-xs text-rose-500 mt-1">{{ roleForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3 flex items-center justify-between">
                            <span>Permisos</span>
                            <span v-if="roleForm.name.toLowerCase() === 'admin' || roleForm.name.toLowerCase() === 'administrador'" class="text-xs text-emerald-600 dark:text-emerald-450 font-bold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Acceso Total Permanente
                            </span>
                        </label>
                        <div v-if="roleForm.name.toLowerCase() === 'admin' || roleForm.name.toLowerCase() === 'administrador'" class="mb-3 p-3 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 rounded-xl flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-[18px] mt-0.5">info</span>
                            <div class="text-xs text-emerald-700 dark:text-emerald-350 leading-normal">
                                El rol <strong class="font-bold">Administrador</strong> tiene otorgado por defecto el acceso total a todos los módulos y configuraciones del sistema. No es posible remover accesos a este rol.
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 bg-zinc-50 dark:bg-gray-700 rounded-xl border border-zinc-200 dark:border-gray-500 overflow-y-auto max-h-80">
                            <label v-for="perm in allPermissions" :key="perm.id" 
                                   class="flex items-start gap-3 p-3 rounded-xl hover:bg-white dark:hover:bg-gray-600 transition-all cursor-pointer group border border-transparent hover:border-zinc-200 dark:hover:border-gray-500">
                                <input type="checkbox" v-model="roleForm.permissions" :value="perm.name" 
                                       :disabled="roleForm.name.toLowerCase() === 'admin' || roleForm.name.toLowerCase() === 'administrador'"
                                       class="mt-1 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 disabled:opacity-50">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-bold text-zinc-700 dark:text-zinc-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ perm.label }}
                                    </span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400 leading-tight">
                                        {{ permissionDescriptions[perm.name] || 'Sin descripción disponible.' }}
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-zinc-50/50 dark:bg-gray-700/50 border-t border-zinc-100 dark:border-gray-600 flex justify-end gap-3">
                    <button @click="showModal = false" class="px-6 py-2.5 text-sm font-medium text-zinc-500 hover:bg-zinc-100 dark:hover:bg-gray-600 rounded-xl transition-colors">Cancelar</button>
                    <button @click="submitRole" :disabled="roleForm.processing" class="px-8 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-500/20 transition-all disabled:opacity-50">
                        {{ editId ? 'Actualizar' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal: Permission -->
        <div v-if="showPermissionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200 text-left">
                <div class="p-6 border-b border-zinc-100 dark:border-gray-600">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ editPermissionId ? 'Editar Permiso' : 'Nuevo Permiso' }}</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nombre Interno (Llave) *</label>
                        <input v-model="permissionForm.name" type="text" placeholder="Ej: reportes-avanzados" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-all" />
                        <p class="text-[10px] text-zinc-400 mt-1">Es recomendable usar palabras en minúsculas separadas por guiones</p>
                        <p v-if="permissionForm.errors.name" class="text-xs text-rose-500 mt-1">{{ permissionForm.errors.name }}</p>
                    </div>
                </div>
                <div class="p-6 bg-zinc-50/50 dark:bg-gray-700/50 border-t border-zinc-100 dark:border-gray-600 flex justify-end gap-3">
                    <button @click="showPermissionModal = false" class="px-6 py-2.5 text-sm font-medium text-zinc-500 hover:bg-zinc-100 dark:hover:bg-gray-600 rounded-xl transition-colors">Cancelar</button>
                    <button @click="submitPermission" :disabled="permissionForm.processing" class="px-8 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-500/20 transition-all disabled:opacity-50">
                        {{ editPermissionId ? 'Actualizar' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </component>
</template>
