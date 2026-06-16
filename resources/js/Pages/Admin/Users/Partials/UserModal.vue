<script setup>
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    user: Object,
    roles: Array,
    branches: Array,
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
    point_of_sale_ids: [],
    area: '',
});

const isOpenBranch = ref(false);
const branchDropdownRef = ref(null);
const branchSearch = ref('');

const isOpenRole = ref(false);
const roleDropdownRef = ref(null);

const isOpenArea = ref(false);
const areaDropdownRef = ref(null);

const areas = [
    { label: 'Cocina', value: 'Cocina' },
    { label: 'Pastelería', value: 'Pastelería' },
    { label: 'Eventos', value: 'Eventos' }
];

const showPassword = ref(false);
const isInitializing = ref(false);

const selectBranch = (branch) => {
    form.branch_id = branch.id;
    isOpenBranch.value = false;
    branchSearch.value = '';
};

const getSelectedBranch = computed(() => {
    return props.branches.find(b => b.id === form.branch_id);
});

const filteredBranches = computed(() => {
    if (!branchSearch.value) return props.branches;
    const search = branchSearch.value.toLowerCase();
    return props.branches.filter(b => 
        b.name.toLowerCase().includes(search)
    );
});

const selectRole = (roleValue) => {
    form.role = roleValue;
    isOpenRole.value = false;
};

const getSelectedRole = computed(() => {
    return props.roles.find(r => r.value === form.role);
});

const selectArea = (areaValue) => {
    form.area = areaValue;
    isOpenArea.value = false;
};

const getSelectedArea = computed(() => {
    return areas.find(a => a.value === form.area);
});

const handleClickOutside = (event) => {
    if (branchDropdownRef.value && !branchDropdownRef.value.contains(event.target)) {
        isOpenBranch.value = false;
    }
    if (roleDropdownRef.value && !roleDropdownRef.value.contains(event.target)) {
        isOpenRole.value = false;
    }
    if (areaDropdownRef.value && !areaDropdownRef.value.contains(event.target)) {
        isOpenArea.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

watch(() => props.show, (newVal) => {
    form.clearErrors();
    if (newVal) {
        isOpenBranch.value = false;
        isOpenRole.value = false;
        isOpenArea.value = false;
        branchSearch.value = '';
        if (props.user) {
            isInitializing.value = true;
            form.name = props.user.name;
            form.email = props.user.email;
            form.password = '';
            form.role = props.user.roles[0]?.name || '';
            form.is_super_admin = props.user.is_super_admin;
            form.branch_id = props.user.branch_id || '';
            form.area = props.user.area || '';
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
    } else {
        form.reset();
        isOpenBranch.value = false;
        isOpenRole.value = false;
        isOpenArea.value = false;
        branchSearch.value = '';
    }
});

watch(() => form.role, (newRole) => {
    if (newRole?.toLowerCase() !== 'consumidor') {
        form.area = '';
    }
});

const submit = () => {
    if (props.user) {
        form.put(route('admin.users.update', props.user.id), {
            onSuccess: () => {
                form.reset();
                form.clearErrors();
                emit('close');
            },
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => {
                form.reset();
                form.clearErrors();
                emit('close');
            },
        });
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-0 sm:p-6 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-zinc-950/40 dark:bg-black/60 backdrop-blur-md transition-opacity" @click="$emit('close')"></div>

        <!-- Modal Content -->
        <div class="relative bg-white dark:bg-secondary-800 w-full h-full sm:h-auto sm:max-w-4xl sm:rounded-3xl shadow-[0_25px_60px_-15px_rgba(0,0,0,0.3)] flex flex-col overflow-visible transition-all duration-300 transform border-t-4 border-indigo-600 dark:border-indigo-500 mt-auto sm:mt-0">
            <!-- Header -->
            <div class="shrink-0 px-6 py-5 border-b border-zinc-100 dark:border-gray-700/60 bg-white/80 dark:bg-secondary-800/80 backdrop-blur z-10 sm:rounded-t-3xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-50 to-indigo-100/50 dark:from-indigo-950/40 dark:to-indigo-900/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-100/50 dark:border-indigo-800/30 shadow-sm animate-pulse">
                            <span class="material-symbols-outlined text-[22px]">{{ user ? 'edit_note' : 'person_add' }}</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-zinc-900 dark:text-white leading-tight">
                                {{ user ? 'Editar Perfil de Colaborador' : 'Nuevo Colaborador' }}
                            </h2>
                            <p class="text-[10px] text-zinc-400 dark:text-zinc-500 font-black uppercase tracking-widest mt-0.5">Gestión de Accesos y Permisos</p>
                        </div>
                    </div>
                    <button @click="$emit('close')" class="w-9 h-9 rounded-xl flex items-center justify-center text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-gray-700/50 transition-all">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
            </div>

            <!-- Content Body (No scroll internal) -->
            <div class="p-5 sm:p-6 overflow-visible">
                <form id="userForm" @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-4 items-start">
                        <!-- Sección de Cuenta -->
                        <div class="space-y-3.5">
                            <div class="flex items-center gap-2.5 mb-0.5 ml-1">
                                <span class="w-1.5 h-4 bg-indigo-500 rounded-full"></span>
                                <h3 class="text-xs font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Información de Cuenta</h3>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5 ml-1">Nombre completo *</label>
                                    <div class="relative flex items-center w-full">
                                        <span class="material-symbols-outlined absolute left-3.5 inset-y-0 flex items-center text-zinc-400 text-[18px] pointer-events-none">badge</span>
                                        <input v-model="form.name" type="text" placeholder="Ej. Juan Perez" 
                                               class="w-full pl-11 pr-4 py-2.5 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all dark:text-white outline-none">
                                    </div>
                                    <p v-if="form.errors.name" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.name }}</p>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5 ml-1">Correo electrónico *</label>
                                    <div class="relative flex items-center w-full">
                                        <span class="material-symbols-outlined absolute left-3.5 inset-y-0 flex items-center text-zinc-400 text-[18px] pointer-events-none">mail</span>
                                        <input v-model="form.email" type="email" placeholder="juan@ejemplo.com" 
                                               class="w-full pl-11 pr-4 py-2.5 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all dark:text-white outline-none">
                                    </div>
                                    <p v-if="form.errors.email" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.email }}</p>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5 ml-1">{{ user ? 'Nueva Contraseña' : 'Contraseña de Acceso *' }}</label>
                                    <div class="relative flex items-center w-full">
                                        <span class="material-symbols-outlined absolute left-3.5 inset-y-0 flex items-center text-zinc-400 text-[18px] pointer-events-none">lock</span>
                                        <input 
                                            v-model="form.password" 
                                            :type="showPassword ? 'text' : 'password'" 
                                            :placeholder="user ? 'Dejar vacío para mantener' : 'Mínimo 8 caracteres'" 
                                            class="w-full pl-11 pr-12 py-2.5 bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200 dark:border-gray-700 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all dark:text-white outline-none"
                                        >
                                        <button 
                                            type="button" 
                                            @click="showPassword = !showPassword"
                                            class="absolute right-3.5 inset-y-0 flex items-center justify-center text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors animate-fade-in"
                                        >
                                            <span class="material-symbols-outlined text-[18px] pointer-events-none">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.password" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.password }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Accesos -->
                        <div class="space-y-3.5">
                            <div class="flex items-center gap-2.5 mb-0.5 ml-1">
                                <span class="w-1.5 h-4 bg-emerald-500 rounded-full"></span>
                                <h3 class="text-xs font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Rol y Asignación</h3>
                            </div>
                            
                            <div class="space-y-3.5">
                                <div>
                                    <label class="block text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5 ml-1">Rol Administrativo *</label>
                                    <div class="relative" :class="{ 'z-[60]': isOpenRole, 'z-20': !isOpenRole }" ref="roleDropdownRef">
                                        <div @click="isOpenRole = !isOpenRole"
                                             class="w-full pl-11 pr-10 py-2.5 bg-zinc-50 dark:bg-gray-900/50 border rounded-2xl text-sm flex items-center justify-between cursor-pointer focus:ring-4 focus:ring-indigo-500/10 transition-all shadow-sm font-semibold text-zinc-700 dark:text-zinc-300"
                                             :class="{
                                                 'border-indigo-500 ring-2 ring-indigo-500/10': isOpenRole, 
                                                 'border-rose-500 ring-2 ring-rose-500/10 dark:border-rose-500': form.errors.role,
                                                 'border-zinc-200 dark:border-gray-700': !isOpenRole && !form.errors.role
                                             }">
                                            <span class="material-symbols-outlined absolute left-3.5 inset-y-0 flex items-center text-zinc-400 text-[20px] pointer-events-none">admin_panel_settings</span>
                                            <div v-if="getSelectedRole" class="flex flex-col truncate">
                                                <span class="text-xs font-bold text-zinc-900 dark:text-white truncate">{{ getSelectedRole.label }}</span>
                                            </div>
                                            <span v-else class="text-zinc-400 italic">Seleccionar rol</span>
                                            <span class="material-symbols-outlined text-zinc-400 transition-transform duration-200" :class="{'rotate-180': isOpenRole}">expand_more</span>
                                        </div>
                                        <div v-if="isOpenRole" class="absolute z-50 w-full mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-gray-700 rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200 origin-top">
                                            <div class="max-h-60 overflow-y-auto custom-scrollbar p-1.5">
                                                <div v-for="role in roles" :key="role.value" @click="selectRole(role.value)" class="group flex items-center justify-between p-3 rounded-2xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer transition-all border border-transparent mb-1" :class="{'bg-indigo-50 dark:bg-indigo-900/30': form.role === role.value}">
                                                    <span class="text-xs font-bold" :class="form.role === role.value ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-900 dark:text-white'">{{ role.label }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.role" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.role }}</p>
                                </div>

                                <div v-if="form.role?.toLowerCase() === 'consumidor'">
                                    <label class="block text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5 ml-1">Área / Departamento *</label>
                                    <div class="relative" :class="{ 'z-[50]': isOpenArea, 'z-10': !isOpenArea }" ref="areaDropdownRef">
                                        <div @click="isOpenArea = !isOpenArea"
                                             class="w-full pl-11 pr-10 py-2.5 bg-zinc-50 dark:bg-gray-900/50 border rounded-2xl text-sm flex items-center justify-between cursor-pointer focus:ring-4 focus:ring-indigo-500/10 transition-all shadow-sm font-semibold text-zinc-700 dark:text-zinc-300"
                                             :class="{
                                                 'border-indigo-500 ring-2 ring-indigo-500/10': isOpenArea, 
                                                 'border-rose-500 ring-2 ring-rose-500/10 dark:border-rose-500': form.errors.area,
                                                 'border-zinc-200 dark:border-gray-700': !isOpenArea && !form.errors.area
                                             }">
                                            <span class="material-symbols-outlined absolute left-3.5 inset-y-0 flex items-center text-zinc-400 text-[20px] pointer-events-none">corporate_fare</span>
                                            <div v-if="getSelectedArea" class="flex flex-col truncate">
                                                <span class="text-xs font-bold text-zinc-900 dark:text-white truncate">{{ getSelectedArea.label }}</span>
                                            </div>
                                            <span v-else class="text-zinc-400 italic">Seleccione el área...</span>
                                            <span class="material-symbols-outlined text-zinc-400 transition-transform duration-200" :class="{'rotate-180': isOpenArea}">expand_more</span>
                                        </div>
                                        <div v-if="isOpenArea" class="absolute z-50 w-full mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-gray-700 rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200 origin-top">
                                            <div class="max-h-60 overflow-y-auto custom-scrollbar p-1.5">
                                                <div v-for="area in areas" :key="area.value" @click="selectArea(area.value)" class="group flex items-center justify-between p-3 rounded-2xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer transition-all border border-transparent mb-1" :class="{'bg-indigo-50 dark:bg-indigo-900/30': form.area === area.value}">
                                                    <span class="text-xs font-bold" :class="form.area === area.value ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-900 dark:text-white'">{{ area.label }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.area" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.area }}</p>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1.5 ml-1">Sucursal de Asignación *</label>
                                    <div class="relative" :class="{ 'z-[40]': isOpenBranch, 'z-0': !isOpenBranch }" ref="branchDropdownRef">
                                        <div @click="isOpenBranch = !isOpenBranch"
                                             class="w-full pl-11 pr-10 py-2.5 bg-zinc-50 dark:bg-gray-900/50 border rounded-2xl text-sm flex items-center justify-between cursor-pointer focus:ring-4 focus:ring-indigo-500/10 transition-all shadow-sm font-semibold text-zinc-700 dark:text-zinc-300"
                                             :class="{
                                                 'border-indigo-500 ring-2 ring-indigo-500/10': isOpenBranch, 
                                                 'border-rose-500 ring-2 ring-rose-500/10 dark:border-rose-500': form.errors.branch_id,
                                                 'border-zinc-200 dark:border-gray-700': !isOpenBranch && !form.errors.branch_id
                                             }">
                                            <span class="material-symbols-outlined absolute left-3.5 inset-y-0 flex items-center text-zinc-400 text-[20px] pointer-events-none">storefront</span>
                                            <div v-if="getSelectedBranch" class="flex flex-col truncate">
                                                <span class="text-xs font-bold text-zinc-900 dark:text-white truncate">{{ getSelectedBranch.name }}</span>
                                            </div>
                                            <span v-else class="text-zinc-400 italic">Seleccionar sucursal</span>
                                            <span class="material-symbols-outlined text-zinc-400 transition-transform duration-200" :class="{'rotate-180': isOpenBranch}">expand_more</span>
                                        </div>
                                        <div v-if="isOpenBranch" class="absolute z-50 w-full mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-gray-700 rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200 origin-top">
                                            <div class="max-h-60 overflow-y-auto custom-scrollbar p-1.5">
                                                <div v-for="branch in branches" :key="branch.id" @click="selectBranch(branch)" class="group flex items-center justify-between p-3 rounded-2xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer transition-all border border-transparent mb-1" :class="{'bg-indigo-50 dark:bg-indigo-900/30': form.branch_id === branch.id}">
                                                    <span class="text-xs font-bold" :class="form.branch_id === branch.id ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-900 dark:text-white'">{{ branch.name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.branch_id" class="mt-1.5 text-[10px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ form.errors.branch_id }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Integrated inside the form flow -->
                    <div class="pt-4 border-t border-zinc-100 dark:border-gray-700 bg-zinc-50/20 dark:bg-gray-900/10 flex flex-col-reverse sm:flex-row items-center justify-end gap-3 -mx-5 -mb-5 sm:-mx-6 sm:-mb-6 p-4 sm:p-5 sm:rounded-b-3xl">
                        <button type="button" @click="$emit('close')"
                                class="w-full sm:w-auto px-6 py-3 text-xs font-black text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 uppercase tracking-widest transition-all rounded-2xl hover:bg-zinc-100 dark:hover:bg-gray-800">
                            Descartar Cambios
                        </button>
                        <button type="submit" :disabled="form.processing"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-xs font-black rounded-2xl transition-all shadow-lg shadow-indigo-600/20 active:scale-95 disabled:opacity-50 uppercase tracking-widest">
                            <span v-if="form.processing" class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                            {{ user ? 'Actualizar Perfil' : 'Dar de Alta Usuario' }}
                        </button>
                    </div>
                </form>
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
