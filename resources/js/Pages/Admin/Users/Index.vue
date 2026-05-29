<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UserModal from './Partials/UserModal.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array,
    branches: Array,
    warehouses: Array,
    allPointsOfSale: Array,
    canCreateUser: Boolean,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const selectedUser = ref(null);

const updateFilters = debounce(() => {
    router.get(route('admin.users.index'), {
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
    selectedUser.value = null;
    showModal.value = true;
};

const openEditModal = (user) => {
    selectedUser.value = user;
    showModal.value = true;
};

const confirmDelete = (user) => {
    Swal.fire({
        title: '¿Eliminar usuario?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48', // rose-600
        cancelButtonColor: '#52525b', // zinc-600
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        customClass: {
            container: 'dark:text-white',
            popup: 'dark:bg-secondary-800 dark:border dark:border-gray-700 rounded-2xl',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.users.destroy', user.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Eliminado',
                        text: 'Usuario eliminado correctamente.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'dark:bg-secondary-800 dark:border dark:border-gray-700 rounded-2xl',
                        }
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Usuarios" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center gap-2 text-sm text-zinc-500 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
                <span class="font-bold text-zinc-900 dark:text-white">Usuarios</span>
            </div>
        </template>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Usuarios</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Administra los usuarios del sistema</p>
            </div>
            <button 
                @click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo Usuario
            </button>
        </div>

        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 mb-6">
            <div class="p-4">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Buscar por nombre o email..." 
                        class="w-full sm:w-80 pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none"
                    >
                </div>
            </div>
        </div>

        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden shadow-sm">
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider w-16">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Sucursal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Rol</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider w-32">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                        <tr v-for="(user, index) in users.data" :key="user.id" class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                {{ (users.meta.current_page - 1) * users.meta.per_page + index + 1 }}
                            </td>
                             <td class="px-6 py-4 whitespace-nowrap">
                                 <div class="flex items-center">
                                     <div class="flex-shrink-0 h-8 w-8 rounded-full bg-zinc-100 dark:bg-gray-700 flex items-center justify-center text-zinc-500 dark:text-zinc-400 font-bold text-xs uppercase">
                                         {{ user.initials }}
                                     </div>
                                     <div class="ml-4">
                                         <div class="text-sm font-medium text-zinc-900 dark:text-white flex items-center gap-2">
                                             {{ user.name }}
                                             <span v-if="user.is_super_admin" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800 border border-amber-200 uppercase">Super Admin</span>
                                         </div>
                                     </div>
                                 </div>
                             </td>
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">{{ user.email }}</td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ user.branch?.name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="role in user.roles" :key="role.id" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-400">
                                        {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button 
                                        @click="openEditModal(user)" 
                                        class="p-2 text-zinc-400 dark:text-zinc-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-gray-700 rounded-lg transition-colors" 
                                        title="Editar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                    <button 
                                        v-if="user.id !== $page.props.auth.user.id"
                                        @click="confirmDelete(user)" 
                                        class="p-2 text-zinc-400 dark:text-zinc-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-gray-700 rounded-lg transition-colors" 
                                        title="Eliminar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                                <p class="text-zinc-500 dark:text-zinc-400 font-medium">No se encontraron usuarios</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-gray-700">
                <div v-for="(user, index) in users.data" :key="user.id" 
                     class="p-4 space-y-4 bg-white dark:bg-gray-800/50">
                    
                    <!-- Header Info -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black text-xs border border-indigo-100 dark:border-indigo-800/20">
                                {{ user.initials }}
                            </div>
                            <div class="ml-3">
                                <span class="text-xs font-black text-zinc-900 dark:text-white flex items-center gap-2">
                                    {{ user.name }}
                                    <span v-if="user.is_super_admin" class="text-[8px] bg-amber-50 text-amber-600 border border-amber-100 px-1 rounded uppercase font-black">SA</span>
                                </span>
                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ user.email }}</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold text-zinc-400">#{{ (users.meta.current_page - 1) * users.meta.per_page + index + 1 }}</span>
                    </div>

                    <!-- Details Grid -->
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Sucursal</span>
                            <span class="text-xs font-bold text-zinc-900 dark:text-white">{{ user.branch?.name || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest mt-1">Roles</span>
                            <div class="flex flex-wrap gap-1 justify-end max-w-[180px]">
                                <span v-for="role in user.roles" :key="role.id" class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/20">
                                    {{ role.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Row -->
                    <div class="flex justify-end items-center gap-3 pt-2">
                        <button @click="openEditModal(user)" 
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 active:scale-90 transition-all border border-amber-100 dark:border-amber-800/20" 
                                title="Editar">
                            <span class="material-symbols-outlined text-lg">edit</span>
                        </button>
                        <button v-if="user.id !== $page.props.auth.user.id"
                                @click="confirmDelete(user)" 
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 active:scale-90 transition-all border border-rose-100 dark:border-rose-800/20" 
                                title="Eliminar">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State for Mobile -->
                <div v-if="users.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800">
                    <div class="flex flex-col items-center opacity-40 py-10">
                        <span class="material-symbols-outlined text-4xl mb-4 text-zinc-300 dark:text-zinc-600">group_off</span>
                        <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-sm">Sin usuarios registrados</p>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="users.meta.last_page > 1" class="px-6 py-4 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50 dark:bg-gray-600 flex justify-center">
                <nav class="flex gap-1">
                    <Link v-for="(link, i) in users.meta.links" :key="i"
                          :href="link.url || '#'"
                          v-html="link.label"
                          :class="[
                              'px-4 py-2 text-sm font-bold rounded-xl transition-all',
                              link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 
                              link.url ? 'bg-white dark:bg-gray-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' : 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'
                          ]"
                    />
                </nav>
            </div>
        </div>

        <UserModal 
            :show="showModal" 
            :user="selectedUser"
            :roles="roles"
            :branches="branches"
            :warehouses="warehouses"
            :all-points-of-sale="allPointsOfSale"
            @close="showModal = false"
        />
    </AdminLayout>
</template>

<style scoped>
/* Hide scrollbar for Chrome, Safari and Opera */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.no-scrollbar {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}

.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
}

@keyframes fade-in {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out forwards;
}
</style>
