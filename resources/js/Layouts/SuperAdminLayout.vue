<script setup>
import { ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;

const mobileOpen = ref(false);
const darkMode = ref(localStorage.getItem('darkMode') === 'true');
const userMenuOpen = ref(false);

const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    localStorage.setItem('darkMode', darkMode.value);
    document.documentElement.classList.toggle('dark', darkMode.value);
};

onMounted(() => {
    document.documentElement.classList.toggle('dark', darkMode.value);
});

const navItems = [
    { label: 'Dashboard', route: 'superadmin.dashboard', icon: 'home' },
    { label: 'Empresas', route: 'superadmin.tenants.index', icon: 'building-office' },
    { label: 'Planes', route: 'superadmin.plans.index', icon: 'square-3-stack-3d' },
    { label: 'Suscripciones', route: 'superadmin.subscriptions.index', icon: 'credit-card' },
    { label: 'Pagos', route: 'superadmin.payments.index', icon: 'banknotes' },
    { label: 'Asesores', route: 'superadmin.asesores.index', icon: 'user-group' },
    { label: 'Roles y Permisos', route: 'superadmin.roles.index', icon: 'shield-check' },
];

const logout = () => {
    router.post('/logout');
};

const isRouteActive = (routeName) => {
    return page.component.startsWith(routeName.replace('superadmin.', 'SuperAdmin/').replace('.index', ''));
};
</script>

<template>
    <div class="font-sans antialiased bg-app text-text-primary-app min-h-screen">
        <!-- Mobile Overlay -->
        <div v-if="mobileOpen" 
             @click="mobileOpen = false"
             class="fixed inset-0 z-40 bg-black/60 lg:hidden transition-opacity duration-300">
        </div>

        <!-- Mobile Sidebar -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white flex flex-col shadow-2xl lg:hidden transition-transform duration-300',
            mobileOpen ? 'translate-x-0' : '-translate-x-full'
        ]">
            <div class="py-5 flex items-center justify-center px-4 border-b border-slate-700 flex-shrink-0 relative">
                <Link :href="route('superadmin.dashboard')" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-violet-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </div>
                    <span class="text-lg font-bold tracking-wide text-white">Super Admin</span>
                </Link>
                <button @click="mobileOpen = false" class="absolute top-4 right-4 p-1 text-slate-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <Link v-for="nav in navItems" :key="nav.route"
                      :href="route(nav.route)"
                      :class="[
                          'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200',
                          route().current(nav.route) ? 'bg-violet-600/20 text-violet-300 border-l-2 border-violet-500' : 'text-slate-400 hover:text-white hover:bg-slate-800'
                      ]">
                    <span class="w-5 h-5 flex items-center justify-center" :class="route().current(nav.route) ? 'text-violet-400' : 'text-slate-500'">
                        <!-- Iconos dinámicos basados en Heroicons -->
                        <template v-if="nav.icon === 'home'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg></template>
                        <template v-if="nav.icon === 'building-office'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-9h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg></template>
                        <template v-if="nav.icon === 'square-3-stack-3d'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.42 12.895c.17.03.346.05.523.058m0 0a.75.75 0 1 0 .146-1.493H7.09a.75.75 0 0 0-.146 1.493Zm5.34 4.98a.75.75 0 0 0 .146-1.493H12.51a.75.75 0 0 0-.146 1.493Zm5.34-4.98a.75.75 0 0 0 .146-1.493H17.93a.75.75 0 0 0-.146 1.493ZM6.42 12.895a23.23 23.23 0 0 1 0-4.79m11.16 4.79a23.23 23.23 0 0 0 0-4.79M6.42 8.105a23.23 23.23 0 0 1 11.16 0m-11.16 0h.008v.008h-.008V8.105Zm11.16 0h.008v.008h-.008V8.105Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h.375c.621 0 1.125.504 1.125 1.125v6.75c0 .621-.504 1.125-1.125 1.125h-.375A1.125 1.125 0 0 1 3 19.875v-6.75ZM19.5 13.125c0-.621.504-1.125 1.125-1.125h.375c.621 0 1.125.504 1.125 1.125v6.75c0 .621-.504 1.125-1.125 1.125h-.375a1.125 1.125 0 0 1-1.125-1.125v-6.75Z" /></svg></template>
                        <template v-if="nav.icon === 'credit-card'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg></template>
                        <template v-if="nav.icon === 'banknotes'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg></template>
                        <template v-if="nav.icon === 'user-group'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a5.946 5.946 0 0 0-.94 3.198l.001.031c0 .225.012.447.037.666A11.944 11.944 0 0 1 12 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0 1 18 18.719m-12 0a5.97 5.97 0 0 1 .941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a5.946 5.946 0 0 0-.94 3.198l.001.031c0 .225.012.447.037.666A11.944 11.944 0 0 1 12 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0 1 18 18.719m-12 0a5.97 5.97 0 0 1 .941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a5.946 5.946 0 0 0-.94 3.198l.001.031c0 .225.012.447.037.666A11.944 11.944 0 0 1 12 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0 1 18 18.719" /></svg></template>
                        <template v-if="nav.icon === 'shield-check'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" /></svg></template>
                    </span>
                    <span>{{ nav.label }}</span>
                </Link>
            </nav>
            <div class="border-t border-slate-700 p-4 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-violet-600 flex items-center justify-center text-white text-sm font-bold">{{ user?.initials || 'SA' }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ user?.name || 'Super Admin' }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ user?.email || '' }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Desktop Layout -->
        <div class="flex h-screen overflow-hidden">
            <!-- Desktop Sidebar -->
            <aside class="hidden lg:flex flex-col w-64 bg-slate-900 text-white flex-shrink-0 overflow-hidden">
                <div class="py-5 flex items-center justify-center px-4 border-b border-slate-700 flex-shrink-0">
                    <Link :href="route('superadmin.dashboard')" class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-violet-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </div>
                        <span class="text-lg font-bold tracking-wide text-white">Super Admin</span>
                    </Link>
                </div>
                <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                    <Link v-for="nav in navItems" :key="nav.route"
                          :href="route(nav.route)"
                          :class="[
                              'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200',
                              route().current(nav.route) ? 'bg-violet-600/20 text-violet-300 border-l-2 border-violet-500' : 'text-slate-400 hover:text-white hover:bg-slate-800'
                          ]">
                        <span class="w-5 h-5 flex items-center justify-center" :class="route().current(nav.route) ? 'text-violet-400' : 'text-slate-500'">
                            <!-- Iconos dinámicos -->
                            <template v-if="nav.icon === 'home'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg></template>
                            <template v-if="nav.icon === 'building-office'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-9h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg></template>
                            <template v-if="nav.icon === 'square-3-stack-3d'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.42 12.895c.17.03.346.05.523.058m0 0a.75.75 0 1 0 .146-1.493H7.09a.75.75 0 0 0-.146 1.493Zm5.34 4.98a.75.75 0 0 0 .146-1.493H12.51a.75.75 0 0 0-.146 1.493Zm5.34-4.98a.75.75 0 0 0 .146-1.493H17.93a.75.75 0 0 0-.146 1.493ZM6.42 12.895a23.23 23.23 0 0 1 0-4.79m11.16 4.79a23.23 23.23 0 0 0 0-4.79M6.42 8.105a23.23 23.23 0 0 1 11.16 0m-11.16 0h.008v.008h-.008V8.105Zm11.16 0h.008v.008h-.008V8.105Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h.375c.621 0 1.125.504 1.125 1.125v6.75c0 .621-.504 1.125-1.125 1.125h-.375A1.125 1.125 0 0 1 3 19.875v-6.75ZM19.5 13.125c0-.621.504-1.125 1.125-1.125h.375c.621 0 1.125.504 1.125 1.125v6.75c0 .621-.504 1.125-1.125 1.125h-.375a1.125 1.125 0 0 1-1.125-1.125v-6.75Z" /></svg></template>
                            <template v-if="nav.icon === 'credit-card'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg></template>
                            <template v-if="nav.icon === 'banknotes'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg></template>
                            <template v-if="nav.icon === 'user-group'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a5.946 5.946 0 0 0-.94 3.198l.001.031c0 .225.012.447.037.666A11.944 11.944 0 0 1 12 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0 1 18 18.719m-12 0a5.97 5.97 0 0 1 .941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a5.946 5.946 0 0 0-.94 3.198l.001.031c0 .225.012.447.037.666A11.944 11.944 0 0 1 12 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0 1 18 18.719m-12 0a5.97 5.97 0 0 1 .941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a5.946 5.946 0 0 0-.94 3.198l.001.031c0 .225.012.447.037.666A11.944 11.944 0 0 1 12 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0 1 18 18.719m-12 0a5.97 5.97 0 0 1 .941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a5.946 5.946 0 0 0-.94 3.198l.001.031c0 .225.012.447.037.666A11.944 11.944 0 0 1 12 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0 1 18 18.719" /></svg></template>
                            <template v-if="nav.icon === 'shield-check'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" /></svg></template>
                        </span>
                        <span>{{ nav.label }}</span>
                    </Link>
                </nav>
                <div class="border-t border-slate-700 p-4 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-violet-600 flex items-center justify-center text-white text-sm font-bold">{{ user?.initials || 'SA' }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ user?.name || 'Super Admin' }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ user?.email || '' }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
                <!-- Header -->
                <header class="h-16 bg-surface border-b border-zinc-200 dark:border-gray-600 flex items-center justify-between px-4 lg:px-6 flex-shrink-0 z-10 shadow-sm">
                    <div class="flex items-center gap-4">
                        <button @click="mobileOpen = true" type="button"
                                class="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                        </button>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" /></svg> Super Admin
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.dashboard')" 
                              class="inline-flex items-center gap-2 px-3 py-1.5 text-sm text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                              title="Ir al panel de administración">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                            <span class="hidden sm:inline">Panel Admin</span>
                        </Link>

                        <button @click="toggleDarkMode" type="button"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-zinc-500 dark:text-zinc-300 hover:text-zinc-700 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-gray-600 transition-colors focus:outline-none">
                            <svg v-if="darkMode" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M3 12h2.25m.386-6.364 1.591-1.591M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
                        </button>

                        <div class="relative">
                            <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-gray-600 transition-colors focus:outline-none">
                                <div class="w-8 h-8 rounded-full bg-violet-600 flex items-center justify-center text-white text-xs font-bold">{{ user?.initials || 'SA' }}</div>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200 hidden sm:block">{{ user?.name || 'Admin' }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" :class="['w-3 h-3 text-zinc-400 hidden sm:block transition-transform', userMenuOpen ? 'rotate-180' : '']"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                            <div v-if="userMenuOpen" 
                                 @click.away="userMenuOpen = false"
                                 class="absolute right-0 mt-2 w-48 bg-surface rounded-lg shadow-lg border border-zinc-200 dark:border-gray-600 py-1 z-50">
                                <Link :href="route('admin.profile.edit')" class="flex items-center gap-2 px-4 py-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg> Mi Perfil
                                </Link>
                                <hr class="my-1 border-zinc-100 dark:border-gray-600">
                                <button @click="logout" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg> Cerrar Sesión
                                </button>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto p-4 lg:p-6 bg-app">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
[v-cloak] { display: none; }
</style>
