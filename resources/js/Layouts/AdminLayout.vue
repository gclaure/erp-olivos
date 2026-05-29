<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminNavbar from './Partials/AdminNavbar.vue';
import Swal from 'sweetalert2';

// Estado global (Singleton) para persistir el sidebar entre navegaciones
const sidebarItems = ref(window.initialSidebarItems || []);

const page = usePage();
const user = computed(() => page.props.auth.user);
const company = computed(() => page.props.company);

const activeBranch = computed(() => page.props.activeBranch);

const mobileOpen = ref(false);
const sidebarMini = ref(localStorage.getItem('sidebarMini') === 'true');
const darkMode = ref(localStorage.getItem('darkMode') === 'true');
const openMenus = ref(JSON.parse(localStorage.getItem('openMenus') || '[]'));

const toggleSidebar = () => {
    sidebarMini.value = !sidebarMini.value;
    localStorage.setItem('sidebarMini', sidebarMini.value);
    document.documentElement.classList.toggle('sidebar-mini', sidebarMini.value);
};

const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    localStorage.setItem('darkMode', darkMode.value);
    document.documentElement.classList.toggle('dark', darkMode.value);
};

const toggleMenu = (label) => {
    const index = openMenus.value.indexOf(label);
    if (index > -1) {
        openMenus.value.splice(index, 1);
    } else {
        openMenus.value.push(label);
    }
    localStorage.setItem('openMenus', JSON.stringify(openMenus.value));
};

const isMenuOpen = (label) => {
    return openMenus.value.includes(label);
};

onMounted(() => {
    document.documentElement.classList.toggle('dark', darkMode.value);
    document.documentElement.classList.toggle('sidebar-mini', sidebarMini.value);
});

// Map icons from Blade to Material Symbols or Heroicons
const getIcon = (iconName) => {
    const map = {
        'home': 'home',
        'cube': 'inventory_2',
        'shopping-bag': 'shopping_bag',
        'tag': 'sell',
        'swatch': 'palette',
        'home-modern': 'storefront',
        'book-open': 'menu_book',
        'adjustments-horizontal': 'tune',
        'truck': 'local_shipping',
        'user-group': 'group',
        'users': 'person_search',
        'credit-card': 'payments',
        'document-text': 'description',
        'shopping-cart': 'shopping_cart',
        'plus-circle': 'add_circle',
        'clipboard-document-list': 'assignment',
        'banknotes': 'payments',
        'computer-desktop': 'point_of_sale',
        'document-duplicate': 'content_copy',
        'cog-6-tooth': 'settings',
        'map-pin': 'location_on',
        'tv': 'monitor',
        'chart-pie': 'analytics',
        'bars-3': 'menu',
        'x-mark': 'close',
        'chevron-down': 'expand_more',
        'user-circle': 'account_circle',
        'arrow-left-on-rectangle': 'logout',
        'building-office': 'corporate_fare',
        'building-storefront': 'storefront',
        'identification': 'badge',
        'envelope': 'mail',
        'phone': 'call'
    };
    return map[iconName] || iconName;
};

// Alert Handlers (SweetAlert2)
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: flash.success,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }
    if (flash?.error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: flash.error
        });
    }
}, { immediate: true });

</script>

<template>
    <div class="font-sans antialiased bg-app text-text-primary-app h-screen flex overflow-hidden">
        
        <!-- Mobile Sidebar Overlay -->
        <div v-if="mobileOpen" 
             @click="mobileOpen = false"
             class="fixed inset-0 z-[70] bg-black/60 lg:hidden transition-opacity duration-300">
        </div>

        <!-- Sidebar -->
        <aside :class="[
            'fixed lg:static inset-y-0 left-0 z-[80] bg-zinc-900 text-white flex flex-col transition-all duration-300 overflow-hidden',
            sidebarMini ? 'lg:w-16' : 'lg:w-64',
            mobileOpen ? 'w-64 translate-x-0' : 'w-64 -translate-x-full lg:translate-x-0'
        ]">
            
            <!-- Logo Section -->
            <div class="py-6 flex flex-col items-center justify-center border-b border-zinc-800 flex-shrink-0 bg-zinc-950/20 overflow-hidden"
                 :class="sidebarMini ? 'px-2' : 'px-6'">
                <Link :href="route('admin.dashboard')" class="flex flex-col items-center justify-center w-full gap-4 group">
                    <div class="w-full h-16 flex items-center justify-center px-4">
                        <img :src="company?.logo_url || '/img/logo-inventory.png'" 
                             class="max-w-full max-h-full object-contain transition-all duration-700 group-hover:scale-110" 
                             :alt="company?.name">
                    </div>
                    <span v-if="(!sidebarMini || mobileOpen) && company?.show_name"
                          class="text-[10px] font-bold tracking-[0.25em] uppercase text-zinc-400 text-center transition-all duration-500 mt-1 truncate px-4">
                        {{ company?.name || 'Tu Inventario' }}
                    </span>
                </Link>
                <button v-if="mobileOpen" @click="mobileOpen = false" class="absolute top-4 right-4 p-1 text-zinc-400 hover:text-white lg:hidden">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Navigation -->
            <nav scroll-region class="sidebar-scroll flex-1 overflow-y-auto py-4 space-y-1"
                 :class="sidebarMini ? 'px-1.5' : 'px-3'">
                <template v-for="(item, index) in sidebarItems" :key="index">
                    <!-- Header -->
                    <div v-if="item.header" 
                         v-show="!sidebarMini"
                         class="mt-5 mb-2 px-3 text-[11px] font-semibold text-zinc-500 uppercase tracking-widest whitespace-nowrap overflow-hidden transition-all duration-300">
                        {{ item.header }}
                    </div>
                    <div v-if="item.header && sidebarMini"
                         class="mx-2 border-t border-zinc-700 mt-3 mb-2"></div>

                    <!-- Menu Item -->
                    <div v-if="!item.header" class="relative">
                        <!-- Simple Link -->
                        <Link v-if="!item.children"
                              :href="route(item.route)"
                              preserve-scroll
                              :class="[
                                  'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group',
                                  route().current(item.route) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-zinc-400 hover:text-white hover:bg-white/5'
                              ]">
                            <span class="material-symbols-outlined flex-shrink-0" :class="route().current(item.route) ? 'text-white' : 'text-zinc-500 group-hover:text-zinc-300'">
                                {{ getIcon(item.icon) }}
                            </span>
                            <span v-if="!sidebarMini" class="truncate">{{ item.label }}</span>
                        </Link>

                        <!-- Dropdown Menu -->
                        <div v-else>
                            <button @click="toggleMenu(item.label)"
                                    :class="[
                                        'w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group',
                                        isMenuOpen(item.label) ? 'text-white bg-white/5' : 'text-zinc-400 hover:text-white hover:bg-white/5'
                                    ]">
                                <span class="material-symbols-outlined flex-shrink-0" :class="isMenuOpen(item.label) ? 'text-indigo-400' : 'text-zinc-500 group-hover:text-zinc-300'">
                                    {{ getIcon(item.icon) }}
                                </span>
                                <span v-if="!sidebarMini" class="flex-1 text-left truncate">{{ item.label }}</span>
                                <span v-if="!sidebarMini" 
                                      class="material-symbols-outlined text-sm transition-transform duration-300"
                                      :class="{ 'rotate-180': isMenuOpen(item.label) }">
                                    expand_more
                                </span>
                            </button>
                            
                            <!-- Submenu -->
                            <div v-if="isMenuOpen(item.label) && !sidebarMini" 
                                 class="mt-1 ml-9 space-y-1 transition-all duration-300">
                                <template v-for="child in item.children" :key="child.label">
                                    <!-- Enlace en nueva pestaña para el catálogo (Solo si hay slug) -->
                                    <a v-if="child.route === 'public.catalog'"
                                       v-show="page.props.company_slug || company?.slug"
                                       :href="(page.props.company_slug || company?.slug) ? route('public.catalog', { company_slug: page.props.company_slug || company?.slug }) : '#'"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-colors text-zinc-500 hover:text-zinc-300 hover:bg-white/5">
                                        <span class="material-symbols-outlined text-[18px]">
                                            {{ getIcon(child.icon) }}
                                        </span>
                                        {{ child.label }}
                                    </a>

                                    <!-- Enlace normal para el resto de las rutas -->
                                    <Link v-else
                                          :href="route(child.route)"
                                          preserve-scroll
                                          :class="[
                                              'flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-colors',
                                              route().current(child.route) ? 'text-indigo-400 bg-indigo-400/10' : 'text-zinc-500 hover:text-zinc-300 hover:bg-white/5'
                                          ]">
                                        <span class="material-symbols-outlined text-[18px]">
                                            {{ getIcon(child.icon) }}
                                        </span>
                                        {{ child.label }}
                                    </Link>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </nav>

            <!-- User Info (Bottom) -->
            <div class="border-t border-zinc-800 flex-shrink-0 transition-all duration-300"
                 :class="sidebarMini ? 'p-2' : 'p-4'">
                <div class="flex items-center gap-3" :class="sidebarMini ? 'justify-center' : ''">
                    <div class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0 shadow-lg">
                        {{ user?.initials || 'A' }}
                    </div>
                    <div v-if="!sidebarMini" class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ user?.name || 'Usuario' }}</p>
                        <p class="text-[10px] text-zinc-500 truncate uppercase tracking-tighter">{{ user?.email || '' }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Header -->
            <AdminNavbar 
                :dark-mode="darkMode"
                @toggle-sidebar="toggleSidebar"
                @toggle-mobile-sidebar="mobileOpen = true"
                @toggle-dark-mode="toggleDarkMode"
            />

            <!-- Page Content -->
            <main scroll-region class="flex-1 overflow-y-auto p-4 lg:p-6 bg-app">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: #52525b; border-radius: 4px; }

/* Transiciones suaves para los submenús */
.v-enter-active, .v-leave-active {
  transition: opacity 0.3s ease;
}
.v-enter-from, .v-leave-to {
  opacity: 0;
}
</style>
