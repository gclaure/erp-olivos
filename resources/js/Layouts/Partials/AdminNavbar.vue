<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import NotificationBell from '@/Components/Admin/NotificationBell.vue';
import Swal from 'sweetalert2';

const Toast = Swal.mixin({
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true,
    background: '#18181b',
    color: '#ffffff'
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const activeBranch = computed(() => page.props.activeBranch);

const props = defineProps({
    darkMode: Boolean
});

const emit = defineEmits(['toggle-sidebar', 'toggle-dark-mode']);

const branchMenuOpen = ref(false);
const userMenuOpen = ref(false);
const copied = ref(false);

const companySlug = computed(() => page.props.company_slug);

const copyCatalogUrl = () => {
    if (!companySlug.value) return;
    
    const url = `${window.location.origin}/tienda/${companySlug.value}`;
    navigator.clipboard.writeText(url).then(() => {
        copied.value = true;
        Toast.fire({
            icon: 'success',
            title: 'Enlace copiado al portapapeles'
        });
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    });
};

const availableBranches = computed(() => page.props.availableBranches);

const switchBranch = (branchId) => {
    if (branchId === activeBranch.value.id) {
        branchMenuOpen.value = false;
        return;
    }

    router.post(route('admin.branches.switch'), { branch_id: branchId }, {
        onSuccess: () => {
            branchMenuOpen.value = false;
        }
    });
};

const currentLabel = computed(() => {
    const sidebarItems = window.initialSidebarItems || [];
    for (const item of sidebarItems) {
        // Verificar si es la ruta principal del item
        if (item.route && route().current(item.route)) return item.label;
        
        // Verificar en los hijos
        if (item.children) {
            const activeChild = item.children.find(child => child.route && route().current(child.route));
            if (activeChild) return activeChild.label;
        }
    }
    
    // Fallback para rutas específicas o no listadas
    if (route().current('admin.dashboard')) return 'Dashboard';
    if (route().current('admin.profile.*')) return 'Mi Perfil';
    if (route().current('admin.ecommerce.settings.*')) return 'Ajustes de Catálogo';
    if (route().current('admin.company.*')) return 'Configuración de Empresa';
    if (route().current('admin.roles.*')) return 'Roles y Permisos';
    
    return 'Analíticas';
});

const logout = () => {
    router.post('/logout');
};

const toggleSidebar = () => emit('toggle-sidebar');
const toggleDarkMode = () => emit('toggle-dark-mode');

</script>

<template>
    <header class="h-16 bg-surface border-b border-zinc-200 dark:border-gray-600 flex items-center justify-between px-4 lg:px-6 flex-shrink-0 z-50 sticky top-0 shadow-sm">
        <div class="flex items-center gap-4">
            <!-- Mobile Burger -->
            <button @click="$emit('toggle-mobile-sidebar')" 
                    class="lg:hidden p-2 rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-gray-800 transition-colors">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <!-- Desktop Burger -->
            <button @click="toggleSidebar" 
                    class="hidden lg:flex p-2 rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-gray-800 transition-colors">
                <span class="material-symbols-outlined">menu</span>
            </button>

            <!-- Breadcrumbs Fallback -->
            <div class="hidden sm:flex items-center gap-2 text-sm text-zinc-500">
                <span class="material-symbols-outlined text-sm">home</span>
                <span>Admin</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="font-bold text-zinc-900 dark:text-white">{{ currentLabel }}</span>
            </div>
        </div>

        <!-- Right Side Actions -->
        <div class="flex items-center gap-3">
            <!-- Branch Indicator -->
            <div v-if="activeBranch?.hasActive" class="relative">
                <button @click="branchMenuOpen = !branchMenuOpen" 
                        class="hidden md:flex items-center gap-3 px-4 py-2 bg-zinc-100 dark:bg-gray-800 rounded-xl border border-zinc-200 dark:border-gray-700 shadow-sm hover:bg-zinc-200 dark:hover:bg-gray-700 transition-all cursor-pointer group">
                    <svg class="w-4 h-4 text-zinc-400 dark:text-indigo-400 group-hover:scale-110 transition-transform" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M3.75 21H20.25M4.5 3H19.5M5.25 3V21M18.75 3V21M9 6.75H10.5M9 9.75H10.5M9 12.75H10.5M13.5 6.75H15M13.5 9.75H15M13.5 12.75H15M9 21V17.625C9 17.0037 9.50368 16.5 10.125 16.5H13.875C14.4963 16.5 15 17.0037 15 17.625V21" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    <div class="flex flex-col text-left">
                        <span class="text-[9px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider leading-none">Sucursal Activa</span>
                        <span class="text-xs font-black text-zinc-900 dark:text-white mt-0.5 leading-tight">{{ activeBranch.name }}</span>
                    </div>
                    <span class="material-symbols-outlined text-zinc-400 transition-transform duration-300 text-lg"
                          :class="{ 'rotate-180': branchMenuOpen }">
                        expand_more
                    </span>
                </button>

                <div v-if="branchMenuOpen" 
                     @click="branchMenuOpen = false"
                     class="fixed inset-0 z-40"></div>

                <!-- Branch Dropdown -->
                <div v-if="branchMenuOpen" 
                     class="absolute left-0 mt-2 w-64 bg-white dark:bg-zinc-900 rounded-xl shadow-xl border border-zinc-200 dark:border-zinc-800 py-1 z-50 overflow-hidden">
                    <div class="px-4 py-2 text-[10px] font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-100 dark:border-zinc-800">
                        Cambiar Sucursal
                    </div>
                    <div class="max-h-60 overflow-y-auto">
                        <button v-for="branch in availableBranches" 
                                :key="branch.id"
                                @click="switchBranch(branch.id)"
                                class="flex items-center justify-between w-full px-4 py-3 text-sm transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800 text-left"
                                :class="branch.id === activeBranch.id ? 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-900/10' : 'text-zinc-700 dark:text-zinc-200'">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-lg">{{ branch.id === activeBranch.id ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
                                {{ branch.name }}
                            </div>
                            <span v-if="branch.id === activeBranch.id" class="text-[10px] bg-indigo-100 dark:bg-indigo-900/30 px-2 py-0.5 rounded-full">Actual</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Copy Catalog Link -->
            <button v-if="companySlug"
                    @click="copyCatalogUrl"
                    class="flex items-center gap-2 px-2 sm:px-3 py-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-lg border border-indigo-100 dark:border-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-all group relative"
                    title="Copiar enlace del catálogo para WhatsApp">
                <span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">
                    {{ copied ? 'check_circle' : 'share' }}
                </span>
                <span class="hidden md:inline text-xs font-bold">{{ copied ? '¡Copiado!' : 'Copiar Catálogo' }}</span>
                
                <!-- Tooltip Feedback (Desktop only) -->
                <div v-if="copied" 
                     class="hidden md:block absolute -bottom-10 left-1/2 -translate-x-1/2 bg-zinc-800 text-white text-[10px] py-1 px-2 rounded shadow-xl animate-bounce">
                    Link copiado
                </div>
            </button>

            <!-- Theme Toggle -->
            <button @click="toggleDarkMode" 
                    class="p-2 rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-gray-800 transition-colors">
                <span v-if="darkMode" class="material-symbols-outlined">light_mode</span>
                <span v-else class="material-symbols-outlined">dark_mode</span>
            </button>

            <!-- Notifications -->
            <NotificationBell />

            <!-- User Menu -->
            <div class="relative">
                <button @click="userMenuOpen = !userMenuOpen" 
                        class="flex items-center gap-2 p-1 rounded-lg hover:bg-zinc-100 dark:hover:bg-gray-800 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold shadow-md shadow-indigo-600/20">
                        {{ user?.initials || 'A' }}
                    </div>
                    <span class="hidden sm:block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ user?.name }}
                    </span>
                    <span class="material-symbols-outlined text-zinc-400 transition-transform duration-300"
                          :class="{ 'rotate-180': userMenuOpen }">
                        expand_more
                    </span>
                </button>
                
                <div v-if="userMenuOpen" 
                     @click="userMenuOpen = false"
                     class="fixed inset-0 z-40"></div>
                
                <!-- Dropdown -->
                <div v-if="userMenuOpen" 
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-900 rounded-xl shadow-xl border border-zinc-200 dark:border-zinc-800 py-1 z-50 overflow-hidden transition-all">
                    <Link :href="route('admin.profile.edit')" 
                          class="flex items-center gap-3 px-4 py-2.5 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                        <span class="material-symbols-outlined text-zinc-400">account_circle</span>
                        Mi Perfil
                    </Link>
                    <hr class="border-zinc-100 dark:border-zinc-800">
                    <button @click="logout" 
                            class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors">
                        <span class="material-symbols-outlined">logout</span>
                        Cerrar Sesión
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>
