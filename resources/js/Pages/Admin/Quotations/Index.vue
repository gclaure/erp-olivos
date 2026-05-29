<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    quotations: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const updateFilters = debounce(() => {
    router.get(route('admin.quotations.index'), {
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(search, () => {
    updateFilters();
});

const convertToSale = (id) => {
    Swal.fire({
        title: '¿Convertir esta cotización a Venta definitiva?',
        text: 'Esto descontará inventario y requiere un módulo de Ventas operativo.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981', // Emerald 500
        cancelButtonColor: '#6b7280', // Gray 500
        confirmButtonText: 'Sí, ejecutar venta',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.quotations.convert-to-sale', id));
        }
    });
};

const cancelQuotation = (id) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: '¿Deseas cancelar esta Cotización?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e', // Rose 500
        cancelButtonColor: '#6b7280', // Gray 500
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, mantener',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.quotations.cancel', id));
        }
    });
};

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

const printQuotation = (id) => {
    const format = usePage().props.activePOS?.receipt_type || 
                  usePage().props.company?.receipt_type || 'media';
    const url = route('admin.quotations.print', { quotation: id, format: format });
    window.open(url, '_blank');
};

</script>

<template>
    <Head title="Cotizaciones" />

    <AdminLayout>
        <!-- Breadcrumbs logic is usually in Layout or hardcoded for simple pages -->
        <div class="hidden sm:flex items-center gap-2 text-sm text-zinc-500 mb-6">
            <span class="material-symbols-outlined text-sm">home</span>
            <span class="material-symbols-outlined text-sm">chevron_right</span>
            <span class="font-bold text-zinc-900 dark:text-white">Cotizaciones</span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Lista de Cotizaciones</h1>
                <p class="text-sm text-zinc-500 mt-1">Gestión de presupuestos y propuestas comerciales para clientes</p>
            </div>
            <Link v-if="false" :href="route('admin.quotations.create')" 
                  class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20">
                <span class="material-symbols-outlined text-lg">add</span>
                Nueva Cotización
            </Link>
        </div>

        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 mb-6 shadow-sm">
            <div class="p-4">
                <div class="relative group max-w-xs">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400 group-focus-within:text-indigo-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Buscar por cliente..." 
                           class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none">
                </div>
            </div>
        </div>

        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden shadow-sm">
            <div class="hidden md:block overflow-x-auto custom-scrollbar">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                        <tr v-for="(quotation, i) in quotations.data" :key="quotation.id" class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                            <td class="px-6 py-4 text-zinc-500 font-mono text-xs">
                                {{ (quotations.meta.current_page - 1) * quotations.meta.per_page + i + 1 }}
                            </td>
                            <td class="px-6 py-4 text-zinc-900 dark:text-white capitalize">
                                {{ quotation.formatted_date }}
                            </td>
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                                {{ quotation.client.name }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="quotation.status === 'cancelada'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400">
                                    Cancelada
                                </span>
                                <span v-else-if="quotation.status === 'completada'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400" title="Ejecutada como Venta">
                                    Completada
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400">
                                    Pendiente
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-zinc-900 dark:text-white whitespace-nowrap">
                                <small class="text-[10px] font-bold text-zinc-400 uppercase mr-0.5">Bs.</small>
                                {{ formatMoney(quotation.total) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <template v-if="quotation.status === 'pendiente'">
                                        <button @click="printQuotation(quotation.id)" 
                                                class="p-2 text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" 
                                                title="Imprimir Proforma">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231a1.125 1.125 0 0 1-1.12-1.227L6.34 18m11.318-4.171a41.07 41.07 0 0 0-11.318 0m11.318 0c.232-.23.442-.47.63-.718m-11.948.718c-.23-.23-.442-.47-.63-.718m12.578-.625a1.875 1.875 0 0 0-1.875-1.875h-.375V4.312c0-.621-.504-1.125-1.125-1.125H8.938c-.621 0-1.125.504-1.125 1.125v5.063h-.375a1.875 1.875 0 0 0-1.875 1.875v.375c0 .156.015.31.044.458m12.166 0c.03-.149.044-.303.044-.458m-11.411 0c.03-.149.044-.303.044-.458" />
                                            </svg>
                                        </button>
                                        <Link :href="route('admin.pos', quotation.id)" 
                                              class="p-2 text-zinc-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors" 
                                              title="Facturar / Venta Directa">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                            </svg>
                                        </Link>
                                        <button @click="convertToSale(quotation.id)" 
                                                class="p-2 text-zinc-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors" 
                                                title="Ejecutar Venta">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                        </button>
                                        <button @click="cancelQuotation(quotation.id)" 
                                                class="p-2 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" 
                                                title="Cancelar Cotización">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button @click="printQuotation(quotation.id)" 
                                                class="p-2 text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" 
                                                title="Imprimir Proforma">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231a1.125 1.125 0 0 1-1.12-1.227L6.34 18m11.318-4.171a41.07 41.07 0 0 0-11.318 0m11.318 0c.232-.23.442-.47.63-.718m-11.948.718c-.23-.23-.442-.47-.63-.718m12.578-.625a1.875 1.875 0 0 0-1.875-1.875h-.375V4.312c0-.621-.504-1.125-1.125-1.125H8.938c-.621 0-1.125.504-1.125 1.125v5.063h-.375a1.875 1.875 0 0 0-1.875 1.875v.375c0 .156.015.31.044.458m12.166 0c.03-.149.044-.303.044-.458m-11.411 0c.03-.149.044-.303.044-.458" />
                                            </svg>
                                        </button>
                                        <button disabled class="p-2 text-zinc-300 cursor-not-allowed" title="Cotización Finalizada/Cancelada">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="quotations.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-300 mx-auto mb-3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="text-zinc-500 font-medium">Sin cotizaciones registradas</p>
                                <p class="text-sm text-zinc-400 mt-1">Crea nuevos presupuestos para tus clientes desde aquí</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-gray-700">
                <div v-for="(quotation, i) in quotations.data" :key="quotation.id" 
                     class="p-4 space-y-4 bg-white dark:bg-gray-800/50">
                    
                    <!-- Header Info -->
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">#{{ (quotations.meta.current_page - 1) * quotations.meta.per_page + i + 1 }}</span>
                            <span class="text-xs font-bold text-zinc-900 dark:text-white capitalize mt-0.5">{{ quotation.formatted_date }}</span>
                        </div>
                        <span v-if="quotation.status === 'cancelada'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/20">
                            Cancelada
                        </span>
                        <span v-else-if="quotation.status === 'completada'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                            Completada
                        </span>
                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-800/20">
                            Pendiente
                        </span>
                    </div>

                    <!-- Details Grid -->
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Cliente</span>
                            <span class="text-xs font-bold text-zinc-900 dark:text-white truncate max-w-[180px]">{{ quotation.client.name }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Total</span>
                            <span class="text-sm font-black text-zinc-900 dark:text-white">Bs. {{ formatMoney(quotation.total) }}</span>
                        </div>
                    </div>

                    <!-- Action Row -->
                    <div class="flex justify-end items-center gap-3 pt-2">
                        <!-- Imprimir Proforma siempre visible si no está cancelada -->
                        <button @click="printQuotation(quotation.id)" 
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 active:scale-90 transition-all border border-indigo-100 dark:border-indigo-800/20" 
                                title="Imprimir Proforma">
                            <span class="material-symbols-outlined text-lg">print</span>
                        </button>

                        <template v-if="quotation.status === 'pendiente'">
                            <!-- Facturar / Venta Directa -->
                            <Link :href="route('admin.pos', quotation.id)" 
                                  class="w-10 h-10 flex items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 active:scale-90 transition-all border border-emerald-100 dark:border-emerald-800/20" 
                                  title="Facturar / Venta Directa">
                                <span class="material-symbols-outlined text-lg">point_of_sale</span>
                            </Link>
                            
                            <!-- Ejecutar Venta -->
                            <button @click="convertToSale(quotation.id)" 
                                    class="w-10 h-10 flex items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 active:scale-90 transition-all border border-emerald-100 dark:border-emerald-800/20" 
                                    title="Ejecutar Venta">
                                <span class="material-symbols-outlined text-lg">check_circle</span>
                            </button>

                            <!-- Cancelar Cotización -->
                            <button @click="cancelQuotation(quotation.id)" 
                                    class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 active:scale-90 transition-all border border-rose-100 dark:border-rose-800/20" 
                                    title="Cancelar Cotización">
                                <span class="material-symbols-outlined text-lg">block</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Empty State for Mobile -->
                <div v-if="quotations.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800">
                    <div class="flex flex-col items-center opacity-40 py-10">
                        <span class="material-symbols-outlined text-4xl mb-4 text-zinc-300 dark:text-zinc-600">description</span>
                        <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-sm">Sin cotizaciones registradas</p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="quotations.meta.last_page > 1" class="px-6 py-4 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50 dark:bg-gray-600 flex justify-center">
                <nav class="flex gap-1">
                    <Link v-for="(link, i) in quotations.meta.links" :key="i"
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
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 6px;
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
}
</style>
