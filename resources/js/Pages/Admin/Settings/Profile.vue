<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    user: Object,
    company: Object,
    activeBranch: Object,
    status: String,
});

// Perfil Form
const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
});

const updateProfile = () => {
    profileForm.patch(route('admin.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success logic if needed
        },
    });
};

// Password Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const updatePassword = () => {
    passwordForm.put(route('admin.password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        },
    });
};

// Password Strength Logic
const strength = computed(() => {
    if (!passwordForm.password) return 0;
    let s = 0;
    if (passwordForm.password.length >= 8) s += 25;
    if (/[A-Z]/.test(passwordForm.password)) s += 25;
    if (/[0-9]/.test(passwordForm.password)) s += 25;
    if (/[^A-Za-z0-9]/.test(passwordForm.password)) s += 25;
    return s;
});

const strengthColor = computed(() => {
    if (strength.value <= 25) return 'bg-rose-500';
    if (strength.value <= 50) return 'bg-amber-500';
    if (strength.value <= 75) return 'bg-blue-500';
    return 'bg-emerald-500';
});

const strengthTextColor = computed(() => {
    if (strength.value <= 25) return 'text-rose-500';
    if (strength.value <= 50) return 'text-amber-500';
    if (strength.value <= 75) return 'text-blue-500';
    return 'text-emerald-500';
});

const strengthText = computed(() => {
    if (strength.value === 0) return '';
    if (strength.value <= 25) return 'Muy Débil';
    if (strength.value <= 50) return 'Débil';
    if (strength.value <= 75) return 'Segura';
    return 'Muy Segura';
});

</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Settings Heading -->
            <div class="relative mb-6 w-full">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white leading-tight">Mi Configuración</h1>
                <p class="text-lg text-zinc-500 dark:text-zinc-400 mb-6">Gestiona tu perfil y los ajustes de tu cuenta.</p>
                <hr class="border-zinc-200 dark:border-zinc-800">
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-20">
                <!-- Sidebar: Información General (4/12) -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Tarjeta de Usuario -->
                    <div class="p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden relative group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <span class="material-symbols-outlined text-6xl">account_circle</span>
                        </div>
                        
                        <div class="relative flex flex-col items-center text-center">
                            <!-- Avatar -->
                            <div class="w-24 h-24 rounded-full bg-indigo-500 flex items-center justify-center text-white text-3xl font-bold ring-4 ring-indigo-50 dark:ring-indigo-900/30 scale-110 mb-4 shadow-lg">
                                {{ user.initials }}
                            </div>
                            
                            <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ profileForm.name }}</h2>
                            <p class="text-zinc-500 dark:text-zinc-400">{{ user.email }}</p>
                            
                            <div class="mt-6 w-full grid grid-cols-2 gap-2">
                                <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-800 flex flex-col items-center">
                                    <span class="text-[9px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Estado</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Activo</span>
                                </div>
                                <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-800 flex flex-col items-center">
                                    <span class="text-[9px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Rol</span>
                                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ user.role }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 w-full flex items-center justify-center gap-2">
                                 <span class="material-symbols-outlined text-zinc-400 text-lg">calendar_month</span>
                                 <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Miembro desde <span class="font-semibold italic text-zinc-700 dark:text-zinc-200">{{ user.created_at }}</span>
                                 </p>
                            </div>
                        </div>
                    </div>

                    <!-- Información de la Empresa -->
                    <div v-if="company" class="p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm">
                        <h3 class="mb-6 flex items-center gap-2 font-bold uppercase tracking-wider text-zinc-400 text-sm">
                            <span class="material-symbols-outlined text-indigo-500">corporate_fare</span>
                            Tu Empresa
                        </h3>

                        <div class="flex items-center gap-4 mb-6">
                            <template v-if="company.logo_url">
                                <img :src="company.logo_url" class="size-16 object-contain rounded-xl border border-zinc-100 dark:border-zinc-800 p-2 bg-white dark:bg-zinc-800 shadow-sm" />
                            </template>
                            <template v-else>
                                <div class="size-16 bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center rounded-xl border border-zinc-200 dark:border-zinc-700">
                                    <span class="material-symbols-outlined text-3xl text-zinc-300">corporate_fare</span>
                                </div>
                            </template>
                            <div>
                                <h4 class="text-lg font-bold tracking-tight leading-tight text-zinc-900 dark:text-white">{{ company.name }}</h4>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ company.nit }}</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-zinc-400 text-lg mt-0.5">location_on</span>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-zinc-400 uppercase">Dirección Fiscal</span>
                                    <p class="text-xs leading-tight text-zinc-600 dark:text-zinc-300">{{ company.address }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-zinc-400 text-lg mt-0.5">call</span>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-zinc-400 uppercase">Contacto</span>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-300">{{ company.phone || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sucursal Activa -->
                    <div v-if="activeBranch" class="p-6 bg-gradient-to-br from-indigo-500/10 to-transparent dark:from-indigo-500/5 border border-indigo-100 dark:border-indigo-500/20 rounded-2xl relative overflow-hidden group">
                        <div class="absolute -bottom-4 -right-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <span class="material-symbols-outlined text-7xl">storefront</span>
                        </div>
                        
                        <div class="relative flex flex-col gap-3">
                            <div class="flex justify-between items-start">
                                <div class="p-2.5 bg-white dark:bg-indigo-950 rounded-xl shadow-sm border border-indigo-100/50 dark:border-indigo-500/30">
                                    <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400">location_on</span>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                    {{ activeBranch.is_main ? 'Principal' : 'Sucursal' }}
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 font-bold uppercase tracking-widest">Trabajando en</p>
                                <h4 class="text-lg text-indigo-950 dark:text-indigo-100 font-extrabold">{{ activeBranch.name }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content: Formularios (8/12) -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Actualizar Información -->
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-800/20">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                    <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400">badge</span>
                                </div>
                                <div>
                                    <h3 class="text-md font-bold text-zinc-900 dark:text-white">Información del Perfil</h3>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Actualiza tu nombre y correo electrónico.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <form @submit.prevent="updateProfile" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-zinc-700 dark:text-zinc-200 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-xs text-zinc-400">person</span>
                                            Nombre Completo
                                        </label>
                                        <input v-model="profileForm.name" 
                                               type="text" 
                                               class="w-full border rounded-lg py-2 px-3 bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-zinc-700 dark:text-zinc-200" 
                                               placeholder="Ej. Juan Pérez" 
                                               required />
                                        <p v-if="profileForm.errors.name" class="text-xs text-rose-500 mt-1">{{ profileForm.errors.name }}</p>
                                    </div>
                                    
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-zinc-700 dark:text-zinc-200 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-xs text-zinc-400">mail</span>
                                            Correo Electrónico
                                        </label>
                                        <input :value="user.email" 
                                               type="email" 
                                               disabled 
                                               class="w-full border rounded-lg py-2 px-3 bg-zinc-50 dark:bg-zinc-800/50 border-zinc-200 dark:border-zinc-700 text-zinc-400 dark:text-zinc-500 cursor-not-allowed" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-4 mt-6 border-t border-zinc-100 dark:border-zinc-800">
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-2 italic">
                                        <span class="material-symbols-outlined text-lg">info</span>
                                        Tus datos están protegidos y son privados.
                                    </p>
                                    
                                    <div class="flex items-center gap-4">
                                        <transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                            <p v-if="profileForm.recentlySuccessful" class="text-emerald-500 font-bold flex items-center gap-1 text-sm">
                                                <span class="material-symbols-outlined text-lg">check_circle</span>
                                                ¡Guardado!
                                            </p>
                                        </transition>
                                        <button type="submit" 
                                                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg transition-all shadow-md shadow-indigo-600/20 disabled:opacity-50"
                                                :disabled="profileForm.processing">
                                            <span v-if="profileForm.processing" class="material-symbols-outlined animate-spin mr-2">refresh</span>
                                            <span v-else class="material-symbols-outlined mr-2">check</span>
                                            Guardar Cambios
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Cambiar Contraseña -->
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-800/20">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-rose-100 dark:bg-rose-900/30 rounded-lg">
                                    <span class="material-symbols-outlined text-rose-600 dark:text-rose-400">shield_lock</span>
                                </div>
                                <div>
                                    <h3 class="text-md font-bold text-zinc-900 dark:text-white">Seguridad de la Cuenta</h3>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Administra tu contraseña y seguridad.</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <form @submit.prevent="updatePassword" class="space-y-6">
                                <div class="space-y-6">
                                    <!-- Contraseña Actual -->
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-zinc-700 dark:text-zinc-200 block mb-1">Contraseña Actual</label>
                                        <div class="relative group">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                                <span class="material-symbols-outlined text-lg">key</span>
                                            </div>
                                            <input v-model="passwordForm.current_password"
                                                   :type="showCurrentPassword ? 'text' : 'password'"
                                                   class="w-full border rounded-lg block appearance-none text-sm py-2 h-10 pl-10 pr-10 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500 shadow-sm border-zinc-200 dark:border-zinc-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                                                   placeholder="••••••••"
                                                   required
                                                   autocomplete="current-password" />
                                            <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-400 hover:text-indigo-500 transition-colors z-10 outline-none">
                                                <span class="material-symbols-outlined text-lg">{{ showCurrentPassword ? 'visibility_off' : 'visibility' }}</span>
                                            </button>
                                        </div>
                                        <p v-if="passwordForm.errors.current_password" class="text-xs text-rose-500 mt-1">{{ passwordForm.errors.current_password }}</p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                                        <!-- Nueva Contraseña -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-bold text-zinc-700 dark:text-zinc-200 block mb-1">Nueva Contraseña</label>
                                            <div class="relative group">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                                    <span class="material-symbols-outlined text-lg">lock</span>
                                                </div>
                                                <input v-model="passwordForm.password"
                                                       :type="showNewPassword ? 'text' : 'password'"
                                                       class="w-full border rounded-lg block appearance-none text-sm py-2 h-10 pl-10 pr-10 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500 shadow-sm border-zinc-200 dark:border-zinc-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-mono"
                                                       placeholder="••••••••"
                                                       required
                                                       autocomplete="new-password" />
                                                <button type="button" @click="showNewPassword = !showNewPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-400 hover:text-indigo-500 transition-colors z-10 outline-none">
                                                    <span class="material-symbols-outlined text-lg">{{ showNewPassword ? 'visibility_off' : 'visibility' }}</span>
                                                </button>
                                            </div>
                                            <p v-if="passwordForm.errors.password" class="text-xs text-rose-500 mt-1">{{ passwordForm.errors.password }}</p>

                                            <!-- Strength Bar -->
                                            <div v-if="passwordForm.password.length > 0" class="mt-2.5 p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-800 shadow-sm">
                                                <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest mb-2">
                                                    <span class="text-zinc-500">Seguridad:</span>
                                                    <span :class="strengthTextColor">{{ strengthText }}</span>
                                                </div>
                                                <div class="grid grid-cols-4 gap-1.5 h-1.5">
                                                     <div class="rounded-full transition-all duration-500" :class="strength >= 25 ? strengthColor : 'bg-zinc-200 dark:bg-zinc-700'"></div>
                                                     <div class="rounded-full transition-all duration-500" :class="strength >= 50 ? strengthColor : 'bg-zinc-200 dark:bg-zinc-700'"></div>
                                                     <div class="rounded-full transition-all duration-500" :class="strength >= 75 ? strengthColor : 'bg-zinc-200 dark:bg-zinc-700'"></div>
                                                     <div class="rounded-full transition-all duration-500" :class="strength >= 100 ? strengthColor : 'bg-zinc-200 dark:bg-zinc-700'"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Confirmar Nueva Contraseña -->
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-bold text-zinc-700 dark:text-zinc-200 block mb-1">Confirmar Nueva Contraseña</label>
                                            <div class="relative group">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-zinc-400">
                                                    <span class="material-symbols-outlined text-lg">verified_user</span>
                                                </div>
                                                <input v-model="passwordForm.password_confirmation"
                                                       :type="showConfirmPassword ? 'text' : 'password'"
                                                       class="w-full border rounded-lg block appearance-none text-sm py-2 h-10 pl-12 pr-10 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500 shadow-sm border-zinc-200 dark:border-zinc-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-mono"
                                                       placeholder="••••••••"
                                                       required
                                                       autocomplete="new-password" />
                                                <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-400 hover:text-indigo-500 transition-colors z-10 outline-none">
                                                    <span class="material-symbols-outlined text-lg">{{ showConfirmPassword ? 'visibility_off' : 'visibility' }}</span>
                                                </button>
                                            </div>
                                            <p v-if="passwordForm.errors.password_confirmation" class="text-xs text-rose-500 mt-1">{{ passwordForm.errors.password_confirmation }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end pt-6 mt-8 border-t border-zinc-100 dark:border-zinc-800 gap-4">
                                    <transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                        <p v-if="passwordForm.recentlySuccessful" class="text-emerald-500 font-bold flex items-center gap-1 text-sm">
                                            <span class="material-symbols-outlined text-lg">check_circle</span>
                                            ¡Actualizada!
                                        </p>
                                    </transition>
                                    <button type="submit" 
                                            class="inline-flex items-center px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-lg transition-all shadow-md shadow-rose-600/20 disabled:opacity-50"
                                            :disabled="passwordForm.processing">
                                        <span v-if="passwordForm.processing" class="material-symbols-outlined animate-spin mr-2">refresh</span>
                                        <span v-else class="material-symbols-outlined mr-2">lock_open</span>
                                        Actualizar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Transiciones suaves */
.v-enter-active, .v-leave-active {
  transition: opacity 0.5s ease;
}
.v-enter-from, .v-leave-to {
  opacity: 0;
}
</style>
