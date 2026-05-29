<script setup>
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    user: Object,
    roles: Array,
    branches: Array,
    warehouses: Array,
});

const emit = defineEmits(['close']);

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
    is_super_admin: false,
    branch_id: '',
    warehouse_id: '',
    point_of_sale_ids: [],
});

const isOpenWarehouse = ref(false);
const isOpenPOS = ref(false);
const warehouseDropdownRef = ref(null);
const posDropdownRef = ref(null);
const showPassword = ref(false);
const warehouseSearch = ref('');
const isInitializing = ref(false);

const selectWarehouse = (warehouse) => {
    form.warehouse_id = warehouse.id;
    isOpenWarehouse.value = false;
    warehouseSearch.value = '';
};

const getSelectedWarehouse = computed(() => {
    return props.warehouses.find(w => w.id === form.warehouse_id);
});

const filteredWarehouses = computed(() => {
    if (!warehouseSearch.value) return props.warehouses;
    const search = warehouseSearch.value.toLowerCase();
    return props.warehouses.filter(w => 
        w.name.toLowerCase().includes(search) || 
        w.branch_name.toLowerCase().includes(search)
    );
});

const handleClickOutside = (event) => {
    if (warehouseDropdownRef.value && !warehouseDropdownRef.value.contains(event.target)) {
        isOpenWarehouse.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.user) {
            isInitializing.value = true;
            form.name = props.user.name;
            form.email = props.user.email;
            form.password = '';
            form.role = props.user.roles[0]?.name || '';
            form.is_super_admin = props.user.is_super_admin;
            form.branch_id = props.user.branch_id || '';
            
            form.warehouse_id = '';
            form.point_of_sale_ids = [];
            
            // Re-enable watch after next tick
            nextTick(() => {
                isInitializing.value = false;
            });
        } else {
            form.reset();
            if (props.branches.length === 1) {
                form.branch_id = props.branches[0].id;
            } else if (!currentUser.value.is_super_admin) {
                form.branch_id = currentUser.value.branch_id;
            }
        }
    }
});

watch(() => form.warehouse_id, (newVal, oldVal) => {
    if (isInitializing.value) return; // Skip if modal is loading data

    if (newVal) {
        const selectedWarehouse = props.warehouses.find(w => w.id === newVal);
        if (selectedWarehouse) {
            form.branch_id = selectedWarehouse.branch_id;
        }
    }
});

const submit = () => {
    if (props.user) {
        form.put(route('admin.users.update', props.user.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => emit('close'),
        });
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-0 sm:p-6">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-zinc-900/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <!-- Modal Content -->
        <div class="relative bg-white dark:bg-secondary-800 w-full h-full sm:h-auto sm:max-h-[90vh] sm:max-w-4xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden transition-all duration-300 transform border-t sm:border border-zinc-200 dark:border-gray-700 mt-auto sm:mt-0">
            <!-- Header (Fixed) -->
            <div class="shrink-0 px-6 py-4 border-b border-zinc-100 dark:border-gray-700 bg-white dark:bg-secondary-800 z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <span class="material-symbols-outlined">person_add</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">
                                {{ user ? 'Editar Perfil de Usuario' : 'Nuevo Colaborador' }}
                            </h2>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium uppercase tracking-wider">Gestión de Accesos y Permisos</p>
                        </div>
                    </div>
                    <button @click="$emit('close')" class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto no-scrollbar p-6">
                <form id="userForm" @submit.prevent="submit" class="space-y-8 pb-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Sección de Cuenta -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-1.5 h-6 bg-indigo-500 rounded-full"></span>
                                <h3 class="text-xs font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Información de Cuenta</h3>
                            </div>
                            
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-xs font-black text-zinc-400 uppercase tracking-widest mb-1.5 ml-1">Nombre completo *</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-3 text-zinc-400 text-[18px]">badge</span>
                                        <input v-model="form.name" type="text" placeholder="Ej. Juan Perez" 
                                               class="w-full pl-10 pr-4 py-3 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 transition-all dark:text-white outline-none">
                                    </div>
                                    <p v-if="form.errors.name" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.name }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-black text-zinc-400 uppercase tracking-widest mb-1.5 ml-1">Correo electrónico *</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-3 text-zinc-400 text-[18px]">mail</span>
                                        <input v-model="form.email" type="email" placeholder="juan@ejemplo.com" 
                                               class="w-full pl-10 pr-4 py-3 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 transition-all dark:text-white outline-none">
                                    </div>
                                    <p v-if="form.errors.email" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.email }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-black text-zinc-400 uppercase tracking-widest mb-1.5 ml-1">{{ user ? 'Nueva Contraseña' : 'Contraseña de Acceso *' }}</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-3 text-zinc-400 text-[18px]">lock</span>
                                        <input 
                                            v-model="form.password" 
                                            :type="showPassword ? 'text' : 'password'" 
                                            :placeholder="user ? 'Dejar vacío para mantener' : 'Mínimo 8 caracteres'" 
                                            class="w-full pl-10 pr-12 py-3 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 transition-all dark:text-white outline-none"
                                        >
                                        <button 
                                            type="button" 
                                            @click="showPassword = !showPassword"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors"
                                        >
                                            <span class="material-symbols-outlined text-[20px]">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.password" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.password }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-black text-zinc-400 uppercase tracking-widest mb-1.5 ml-1">Rol Administrativo</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-3 text-zinc-400 text-[18px]">admin_panel_settings</span>
                                        <select v-model="form.role" class="w-full pl-10 pr-10 py-3 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 transition-all dark:text-white outline-none appearance-none cursor-pointer">
                                            <option value="">Seleccionar rol</option>
                                            <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                                        </select>
                                        <span class="material-symbols-outlined absolute right-3 top-3 text-zinc-400 text-[18px] pointer-events-none">expand_more</span>
                                    </div>
                                    <p v-if="form.errors.role" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.role }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Accesos -->
                        <div class="space-y-6">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-6 bg-emerald-500 rounded-full"></span>
                                    <h3 class="text-xs font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Sucursal y Terminales</h3>
                                </div>
                                <div v-if="currentUser.is_super_admin" class="flex items-center gap-3 px-3 py-1.5 bg-zinc-100 dark:bg-gray-700/50 rounded-full border border-zinc-200 dark:border-gray-700">
                                    <span class="text-[10px] font-black text-zinc-500 dark:text-zinc-400 uppercase tracking-tighter">Super Admin</span>
                                    <button 
                                        type="button"
                                        @click="form.is_super_admin = !form.is_super_admin"
                                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                        :class="form.is_super_admin ? 'bg-indigo-600' : 'bg-zinc-300 dark:bg-zinc-600'"
                                    >
                                        <span 
                                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                            :class="form.is_super_admin ? 'translate-x-4' : 'translate-x-0'"
                                        />
                                    </button>
                                </div>
                            </div>
                            
                            <div class="space-y-5">
                                <!-- Custom Warehouse Dropdown -->
                                <div :class="{'opacity-50 pointer-events-none grayscale': form.is_super_admin}">
                                    <label class="block text-xs font-black text-zinc-400 uppercase tracking-widest mb-1.5 ml-1">Almacén de Asignación *</label>
                                    <div class="relative" ref="warehouseDropdownRef">
                                        <div @click="isOpenWarehouse = !isOpenWarehouse"
                                             class="w-full pl-10 pr-10 py-3 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm flex items-center justify-between cursor-pointer focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm"
                                             :class="{'border-indigo-500 ring-2 ring-indigo-500/10': isOpenWarehouse, 'border-rose-500': form.errors.warehouse_id}">
                                            <span class="material-symbols-outlined absolute left-3 top-3 text-zinc-400 text-[20px]">inventory_2</span>
                                            
                                            <div v-if="getSelectedWarehouse" class="flex flex-col truncate">
                                                <span class="text-xs font-bold text-zinc-900 dark:text-white truncate">{{ getSelectedWarehouse.name }}</span>
                                                <span class="text-[9px] font-black text-indigo-500 uppercase tracking-tighter">{{ getSelectedWarehouse.branch_name }}</span>
                                            </div>
                                            <span v-else class="text-zinc-400 italic">Seleccionar almacén</span>
                                            
                                            <span class="material-symbols-outlined text-zinc-400 transition-transform duration-200" :class="{'rotate-180': isOpenWarehouse}">expand_more</span>
                                        </div>

                                        <!-- Dropdown Menu -->
                                        <div v-if="isOpenWarehouse" 
                                             class="absolute z-50 w-full mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-gray-700 rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200 origin-top">
                                            <div class="p-3 border-b border-zinc-100 dark:border-gray-700 bg-zinc-50/50 dark:bg-gray-900/50">
                                                <div class="relative">
                                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">search</span>
                                                    <input v-model="warehouseSearch" 
                                                           type="text" 
                                                           placeholder="Buscar almacén o sucursal..."
                                                           class="w-full pl-9 pr-4 py-2 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-gray-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                                                           @click.stop>
                                                </div>
                                            </div>
                                            <div class="max-h-60 overflow-y-auto custom-scrollbar p-1.5">
                                                <div v-for="warehouse in filteredWarehouses" 
                                                     :key="warehouse.id"
                                                     @click="selectWarehouse(warehouse)"
                                                     class="group flex items-center justify-between p-3 rounded-2xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer transition-all border border-transparent hover:border-indigo-100 dark:hover:border-indigo-800/30 mb-1"
                                                     :class="{'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-100 dark:border-indigo-800/50': form.warehouse_id === warehouse.id}">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-xl bg-white dark:bg-secondary-800 shadow-sm border border-zinc-100 dark:border-gray-700 flex items-center justify-center transition-colors group-hover:scale-110"
                                                             :class="{'text-indigo-600': form.warehouse_id === warehouse.id}">
                                                            <span class="material-symbols-outlined text-lg">warehouse</span>
                                                        </div>
                                                        <div class="flex flex-col">
                                                            <span class="text-xs font-bold" :class="form.warehouse_id === warehouse.id ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-900 dark:text-white'">{{ warehouse.name }}</span>
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="material-symbols-outlined text-[10px] text-zinc-400">storefront</span>
                                                                <span class="text-[9px] font-black text-zinc-400 uppercase tracking-tighter">{{ warehouse.branch_name }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span v-if="form.warehouse_id === warehouse.id" class="material-symbols-outlined text-indigo-600 text-lg">check_circle</span>
                                                </div>
                                                <div v-if="filteredWarehouses.length === 0" class="p-8 text-center opacity-40">
                                                    <span class="material-symbols-outlined text-3xl mb-2">inventory_2</span>
                                                    <p class="text-[10px] font-black uppercase tracking-widest">No hay resultados</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.warehouse_id" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.warehouse_id }}</p>
                                    </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer (Fixed) -->
            <div class="shrink-0 p-6 border-t border-zinc-100 dark:border-gray-700 bg-zinc-50 dark:bg-gray-900/30">
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 w-full">
                    <button type="button" @click="$emit('close')"
                            class="w-full sm:w-auto px-6 py-3 text-xs font-black text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 uppercase tracking-widest transition-all rounded-xl hover:bg-zinc-100 dark:hover:bg-gray-800">
                        Descartar Cambios
                    </button>
                    <button form="userForm" type="submit" :disabled="form.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-zinc-900 dark:bg-indigo-600 text-white text-xs font-black rounded-2xl hover:bg-zinc-800 dark:hover:bg-indigo-700 transition-all shadow-xl shadow-zinc-900/20 dark:shadow-indigo-600/20 active:scale-95 disabled:opacity-50 uppercase tracking-widest">
                        <span v-if="form.processing" class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                        {{ user ? 'Actualizar Perfil' : 'Dar de Alta Usuario' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
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

.custom-scrollbar {
    scrollbar-gutter: stable;
}
</style>
