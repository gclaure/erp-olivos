<script setup>
import { Head } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

const props = defineProps({
    stats: Object,
    recentTenants: Array,
    expiringSoonList: Array,
    asesorStats: Array,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

defineOptions({ layout: SuperAdminLayout });
</script>

<template>
    <Head title="Dashboard Super Admin" />

    <div>
        <!-- Título -->
        <div class="mb-6">
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-white">Panel Super Admin</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Vista general del sistema SaaS</p>
        </div>

        <!-- Cards de métricas principales -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <!-- Total Empresas -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-zinc-100 dark:border-gray-700 p-4 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Empresas</p>
                        <p class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ stats.totalTenants }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" /></svg>
                            {{ stats.newTenantsThisMonth }} nuevas este mes
                        </p>
                    </div>
                    <div class="p-2 sm:p-3 bg-violet-50 dark:bg-violet-900/30 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6 text-violet-600 dark:text-violet-400"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-9h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                    </div>
                </div>
            </div>

            <!-- Empresas Activas -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-zinc-100 dark:border-gray-700 p-4 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-zinc-500 dark:text-zinc-400">Activas</p>
                        <p class="text-xl sm:text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.activeTenants }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ stats.trialTenants }} en prueba</p>
                    </div>
                    <div class="p-2 sm:p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600 dark:text-emerald-400"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </div>
                </div>
            </div>

            <!-- Ingresos Mes -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-zinc-100 dark:border-gray-700 p-4 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-zinc-500 dark:text-zinc-400">Ingresos del Mes</p>
                        <p class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white mt-1">Bs. {{ formatCurrency(stats.monthlyRevenue) }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Total: Bs. {{ formatCurrency(stats.totalRevenue) }}</p>
                    </div>
                    <div class="p-2 sm:p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-400"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                    </div>
                </div>
            </div>

            <!-- Alertas -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-zinc-100 dark:border-gray-700 p-4 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-zinc-500 dark:text-zinc-400">Alertas</p>
                        <p class="text-xl sm:text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ stats.expiringSoon }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">por vencer (7 días)</p>
                    </div>
                    <div class="p-2 sm:p-3 bg-amber-50 dark:bg-amber-900/30 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6 text-amber-600 dark:text-amber-400"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secciones de detalle -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Suscripciones por vencer -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-zinc-100 dark:border-gray-700 shadow-sm">
                <div class="p-4 sm:p-6 border-b border-zinc-100 dark:border-gray-700">
                    <h2 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-amber-500"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        Próximas a Vencer
                    </h2>
                </div>
                <div class="p-4 sm:p-6">
                    <div v-for="(sub, index) in expiringSoonList" :key="sub.id" 
                         class="flex items-center justify-between py-3"
                         :class="index !== expiringSoonList.length - 1 ? 'border-b border-zinc-100 dark:border-gray-700' : ''">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ sub.tenant?.name || 'Sin empresa' }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ sub.plan?.name || 'Sin plan' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold"
                                :class="sub.days_remaining <= 3 ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'">
                                {{ sub.days_remaining }} {{ sub.days_remaining === 1 ? 'día' : 'días' }}
                            </span>
                            <p class="text-xs text-zinc-400 mt-0.5">{{ new Date(sub.ends_at).toLocaleDateString('es-ES') }}</p>
                        </div>
                    </div>
                    <p v-if="expiringSoonList.length === 0" class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-4">
                        No hay suscripciones próximas a vencer.
                    </p>
                </div>
            </div>

            <!-- Últimas empresas -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-zinc-100 dark:border-gray-700 shadow-sm">
                <div class="p-4 sm:p-6 border-b border-zinc-100 dark:border-gray-700">
                    <h2 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-violet-500"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-9h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                        Empresas Recientes
                    </h2>
                </div>
                <div class="p-4 sm:p-6">
                    <div v-for="(tenant, index) in recentTenants" :key="tenant.id" 
                         class="flex items-center justify-between py-3"
                         :class="index !== recentTenants.length - 1 ? 'border-b border-zinc-100 dark:border-gray-700' : ''">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-gray-700 flex items-center justify-center text-xs font-bold text-zinc-500 dark:text-zinc-300">
                                {{ tenant.name.substring(0, 2).toUpperCase() }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ tenant.name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ tenant.created_at_human }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold"
                            :class="{
                                'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300': tenant.status === 'ACTIVE',
                                'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300': tenant.status === 'TRIAL',
                                'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300': tenant.status === 'SUSPENDED',
                                'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300': tenant.status === 'CANCELLED'
                            }">
                            {{ tenant.status_label || tenant.status }}
                        </span>
                    </div>
                    <p v-if="recentTenants.length === 0" class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-4">
                        No hay empresas registradas.
                    </p>
                </div>
            </div>

            <!-- Rendimiento de Asesores -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-zinc-100 dark:border-gray-700 shadow-sm lg:col-span-2">
                <div class="p-4 sm:p-6 border-b border-zinc-100 dark:border-gray-700">
                    <h2 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a5.946 5.946 0 0 0-.94 3.198l.001.031c0 .225.012.447.037.666A11.944 11.944 0 0 1 12 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0 1 18 18.719m-12 0a5.97 5.97 0 0 1 .941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a5.946 5.946 0 0 0-.94 3.198l.001.031c0 .225.012.447.037.666A11.944 11.944 0 0 1 12 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0 1 18 18.719m-12 0a5.97 5.97 0 0 1 .941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a5.946 5.946 0 0 0-.94 3.198l.001.031c0 .225.012.447.037.666A11.944 11.944 0 0 1 12 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0 1 18 18.719" /></svg>
                        Rendimiento de Asesores
                    </h2>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div v-for="asesor in asesorStats" :key="asesor.id" 
                             class="p-4 bg-zinc-50 dark:bg-gray-700 rounded-xl border border-zinc-100 dark:border-gray-600">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                    {{ asesor.name.substring(0, 2).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-zinc-900 dark:text-white truncate">{{ asesor.name }}</p>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 uppercase tracking-wider font-semibold">
                                        {{ asesor.companies_count }} {{ asesor.companies_count === 1 ? 'Cliente' : 'Clientes' }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-xs text-zinc-400">Cuota de mercado</span>
                                <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">
                                    {{ stats.totalTenants > 0 ? ((asesor.companies_count / stats.totalTenants) * 100).toFixed(1) : 0 }}%
                                </span>
                            </div>
                            <div class="w-full bg-zinc-200 dark:bg-gray-600 rounded-full h-1.5 mt-2">
                                <div class="bg-indigo-600 h-1.5 rounded-full" :style="{ width: (stats.totalTenants > 0 ? (asesor.companies_count / stats.totalTenants) * 100 : 0) + '%' }"></div>
                            </div>
                        </div>
                        <div v-if="asesorStats.length === 0" class="col-span-full py-8 text-center text-zinc-400 dark:text-zinc-500">
                            No hay asesores con clientes registrados.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen de Suscripciones -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-zinc-100 dark:border-gray-700 shadow-sm lg:col-span-2">
                <div class="p-4 sm:p-6 border-b border-zinc-100 dark:border-gray-700">
                    <h2 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-500"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" /></svg>
                        Estado de Suscripciones
                    </h2>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.activeSubscriptions }}</p>
                            <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-1">Activas</p>
                        </div>
                        <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.expiredSubscriptions }}</p>
                            <p class="text-sm text-red-700 dark:text-red-300 mt-1">Expiradas</p>
                        </div>
                        <div class="text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">Bs. {{ formatCurrency(stats.pendingPayments) }}</p>
                            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">Pendiente de Cobro</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
