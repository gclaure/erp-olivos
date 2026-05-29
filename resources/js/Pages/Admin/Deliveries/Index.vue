<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeliveryModal from './Partials/DeliveryModal.vue';
import CancelModal from './Partials/CancelModal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    deliveries: Object,
    filters: Object,
    pendingCount: Number,
    deliveredCount: Number,
    cancelledCount: Number,
    flash: Object,
});

const search = ref(props.filters.search || '');
const deliveryMode = ref(props.filters.delivery_mode || '');
const activeTab = ref(props.filters.tab || 'pending');

// Modals state
const showDeliveryModal = ref(false);
const showCancelModal = ref(false);
const selectedSale = ref(null);

const setTab = (tab) => {
    activeTab.value = tab;
    updateFilters();
};

const updateFilters = () => {
    router.get(route('admin.deliveries.index'), {
        search: search.value,
        delivery_mode: deliveryMode.value,
        tab: activeTab.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch([search, deliveryMode], () => {
    updateFilters();
});

const selectSale = (sale) => {
    selectedSale.value = sale;
    showDeliveryModal.value = true;
};

const confirmCancel = (sale) => {
    selectedSale.value = sale;
    showCancelModal.value = true;
};

const handleDeliver = (date) => {
    router.post(route('admin.deliveries.deliver', selectedSale.value.id), {
        actual_delivery_date: date
    }, {
        onSuccess: () => {
            showDeliveryModal.value = false;
            if (props.flash?.saleId) {
                printReceipt(props.flash.saleId);
            }
        }
    });
};

const handleAnnul = (reason) => {
    router.post(route('admin.deliveries.annul', selectedSale.value.id), {
        reason: reason
    }, {
        onSuccess: () => {
            showCancelModal.value = false;
        }
    });
};

const printReceipt = (saleId, format = 'media') => {
    const url = route('admin.sales.print', { sale: saleId }) + '?format=' + format;
    const win = window.open(url, '_blank');
    
    if (!win) {
        Swal.fire({
            icon: 'info',
            title: 'Comprobante listo',
            text: 'El comprobante no pudo abrirse automáticamente. Por favor, permita las ventanas emergentes.',
            showCancelButton: true,
            confirmButtonText: 'Abrir manualmente',
            cancelButtonText: 'Entendido',
            confirmButtonColor: '#4f46e5',
        }).then((result) => {
            if (result.isConfirmed) {
                window.open(url, '_blank');
            }
        });
    }
};

const calculateSeniority = (createdAt) => {
    const created = new Date(createdAt);
    const now = new Date();
    const diffTime = Math.abs(now - created);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    const diffHours = Math.floor((diffTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const diffMins = Math.floor((diffTime % (1000 * 60 * 60)) / (1000 * 60));
    
    let colorClass = 'bg-zinc-100 text-zinc-600 dark:bg-secondary-700 dark:text-secondary-300';
    const totalHours = (diffTime / (1000 * 60 * 60));
    
    if (totalHours > 48) {
        colorClass = 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-100 dark:border-rose-500/20';
    } else if (totalHours > 24) {
        colorClass = 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border border-amber-100 dark:border-amber-500/20';
    }
    
    return {
        text: `${diffDays}d ${diffHours}h ${diffMins}m`,
        colorClass
    };
};

const calculateWaitTime = (createdAt, deliveredAt) => {
    const created = new Date(createdAt);
    const delivered = new Date(deliveredAt);
    const diffTime = Math.abs(delivered - created);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    const diffHours = Math.floor((diffTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const diffMins = Math.floor((diffTime % (1000 * 60 * 60)) / (1000 * 60));
    
    return `${diffDays}d ${diffHours}h ${diffMins}m`;
};
</script>

<template>
    <Head title="Despacho de Pedidos" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-secondary-400">
                    <span>Logística</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                    <span class="font-bold text-zinc-900 dark:text-white">Despacho de Pedidos</span>
                </div>
            </div>
        </template>

        <div class="w-full">
            <!-- Tabs Section -->
            <div class="flex items-center gap-2 mb-6 bg-zinc-100/50 dark:bg-secondary-900/50 p-1 rounded-2xl w-full sm:w-fit border border-zinc-200 dark:border-secondary-700 overflow-x-auto no-scrollbar whitespace-nowrap">
                <button 
                    @click="setTab('pending')"
                    :class="[
                        'flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all shrink-0',
                        activeTab === 'pending' 
                            ? 'bg-white dark:bg-secondary-800 text-primary-600 dark:text-primary-400 shadow-sm ring-1 ring-zinc-200 dark:ring-secondary-700' 
                            : 'text-zinc-500 hover:text-zinc-700 dark:text-secondary-400 dark:hover:text-secondary-200 hover:bg-zinc-100/50 dark:hover:bg-secondary-800/50'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Pendientes de Despacho
                    <span v-if="pendingCount > 0" class="ml-1 px-2 py-0.5 rounded-full text-[10px] bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400">
                        {{ pendingCount }}
                    </span>
                </button>
                <button 
                    @click="setTab('delivered')"
                    :class="[
                        'flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all shrink-0',
                        activeTab === 'delivered' 
                            ? 'bg-white dark:bg-secondary-800 text-emerald-600 dark:text-emerald-400 shadow-sm ring-1 ring-zinc-200 dark:ring-secondary-700' 
                            : 'text-zinc-500 hover:text-zinc-700 dark:text-secondary-400 dark:hover:text-secondary-200 hover:bg-zinc-100/50 dark:hover:bg-secondary-800/50'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Productos Entregados
                    <span v-if="deliveredCount > 0" class="ml-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400">
                        {{ deliveredCount }}
                    </span>
                </button>
                <button 
                    @click="setTab('cancelled')"
                    :class="[
                        'flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all shrink-0',
                        activeTab === 'cancelled' 
                            ? 'bg-white dark:bg-secondary-800 text-rose-600 dark:text-rose-400 shadow-sm ring-1 ring-zinc-200 dark:ring-secondary-700' 
                            : 'text-zinc-500 hover:text-zinc-700 dark:text-secondary-400 dark:hover:text-secondary-200 hover:bg-zinc-100/50 dark:hover:bg-secondary-800/50'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Anulados / Cancelados
                    <span v-if="cancelledCount > 0" class="ml-1 px-2 py-0.5 rounded-full text-[10px] bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400">
                        {{ cancelledCount }}
                    </span>
                </button>
            </div>

            <!-- Filters Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8 relative z-10">
                <div class="lg:col-span-2 bg-surface dark:bg-secondary-800 rounded-2xl border border-zinc-200 dark:border-secondary-700 shadow-sm p-4 backdrop-blur-sm">
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400 dark:text-secondary-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <input 
                            v-model="search"
                            type="text"
                            placeholder="Buscar por N° de venta o cliente..."
                            class="w-full pl-11 pr-4 py-2.5 bg-zinc-50 dark:bg-secondary-900 border-none focus:ring-2 focus:ring-primary-500/20 rounded-xl text-sm transition-all dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500"
                        >
                    </div>
                </div>
                <div class="bg-surface dark:bg-secondary-800 rounded-2xl border border-zinc-200 dark:border-secondary-700 shadow-sm p-4 backdrop-blur-sm">
                    <select 
                        v-model="deliveryMode"
                        class="w-full rounded-xl border-none bg-zinc-50 dark:bg-secondary-900 text-sm focus:ring-2 focus:ring-primary-500/20 transition-all dark:text-white"
                    >
                        <option value="">Filtrar por Modo de Entrega</option>
                        <option value="retiro_local">Retiro en Local</option>
                        <option value="envio_domicilio">Envío a Domicilio</option>
                        <option value="punto_encuentro">Punto de Encuentro</option>
                        <option value="envio_encomienda">Envío por Encomienda</option>
                    </select>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 shadow-xl overflow-hidden transition-all mb-8 backdrop-blur-sm">
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-zinc-50 dark:bg-secondary-900/50 border-b border-zinc-100 dark:border-secondary-700">
                            <tr>
                                <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest text-center w-16">#</th>
                                <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest text-center w-24">Folio</th>
                                <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest">Cliente</th>
                                <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest hidden lg:table-cell">Documento</th>
                                <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest">Celular</th>
                                <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest hidden md:table-cell">Almacén</th>
                                <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest">Modo Entrega</th>
                                <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest text-center">
                                    <template v-if="activeTab === 'pending'">Antigüedad</template>
                                    <template v-else-if="activeTab === 'delivered'">Fecha Entrega</template>
                                    <template v-else>Fecha Cancelación</template>
                                </th>
                                <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest text-right">Cant.</th>
                                <th v-if="activeTab === 'delivered' || activeTab === 'cancelled'" class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest text-center">Responsable</th>
                                <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest text-right w-48">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50">
                            <tr v-for="(sale, index) in deliveries.data" :key="sale.id" class="bg-white dark:bg-transparent transition-all duration-200 hover:bg-zinc-100/50 dark:hover:bg-secondary-700/50 group">
                                <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
                                    <span class="text-xs font-bold text-zinc-400 dark:text-secondary-300 bg-zinc-100 dark:bg-secondary-700 px-2.5 py-1.5 rounded-lg border border-transparent dark:border-secondary-600/50">
                                        {{ String(index + 1).padStart(2, '0') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-middle text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="font-mono text-sm font-bold text-primary-600 dark:text-primary-400 tracking-tighter">
                                            #{{ sale.formatted_number }}
                                        </span>
                                        <span class="text-[9px] text-zinc-500 dark:text-secondary-400 font-medium uppercase">{{ sale.formatted_created_at }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <span class="font-bold text-zinc-900 dark:text-white tracking-tight text-sm leading-tight">{{ sale.client.name }}</span>
                                </td>
                                <td class="px-6 py-4 align-middle hidden lg:table-cell">
                                    <span v-if="sale.client.document_number" class="text-[10px] text-zinc-500 dark:text-secondary-400 font-medium flex items-center gap-1 leading-none uppercase">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 opacity-60">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                                        </svg>
                                        {{ sale.client.document_number }}
                                    </span>
                                    <span v-else class="text-[10px] text-zinc-300 dark:text-secondary-600 italic">No registra</span>
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <div v-if="sale.client.phone" class="flex items-center gap-2">
                                        <span class="text-[10px] text-zinc-500 dark:text-secondary-400 font-medium flex items-center gap-1 leading-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 opacity-60">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H3.75A2.25 2.25 0 0 0 1.5 4.5v2.25Z" />
                                            </svg>
                                            {{ sale.client.phone }}
                                        </span>
                                        <a :href="`https://wa.me/${sale.client.phone.replace(/\D/g, '')}`" 
                                           target="_blank" 
                                           title="Contactar por WhatsApp"
                                           class="p-1 rounded-md bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition-all flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.33.185.506.548.438.912l-1.323 7.126a3.375 3.375 0 0 1-3.315 2.76H8.25a3.375 3.375 0 0 1-3.315-2.76l-1.323-7.126a.75.75 0 0 1 .438-.912l16.2-7.5ZM8.25 19.5v-7.5M15.75 19.5v-7.5" />
                                            </svg>
                                        </a>
                                    </div>
                                    <span v-else class="text-[10px] text-zinc-300 dark:text-secondary-600 italic">No registra</span>
                                </td>
                                <td class="px-6 py-4 align-middle hidden md:table-cell">
                                    <span class="text-[11px] text-zinc-600 dark:text-secondary-300 flex items-center gap-2 font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 opacity-40 dark:opacity-60">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h18" />
                                        </svg>
                                        {{ sale.warehouse.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <span class="inline-flex w-fit items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-bold uppercase tracking-wider bg-zinc-100 dark:bg-secondary-700 text-zinc-700 dark:text-secondary-300 border border-zinc-200 dark:border-secondary-600/50">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-primary-500 dark:text-primary-400">
                                            <path v-if="sale.delivery_mode.icon === 'clock'" stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            <path v-else-if="sale.delivery_mode.icon === 'truck'" stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.806H14.25M16.5 18.75h-2.25m0-11.25V4.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.5 3 4.004 3 4.625V14.25m11.25-11.25h-.375a1.125 1.125 0 0 0-1.125 1.125V14.25m-1.5 0h3" />
                                            <path v-else-if="sale.delivery_mode.icon === 'map-pin'" stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            <path v-else-if="sale.delivery_mode.icon === 'cube'" stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                        <span>{{ sale.delivery_mode.label }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
                                    <div v-if="activeTab === 'pending'" class="inline-flex flex-col items-center px-3 py-1 rounded-xl" :class="calculateSeniority(sale.created_at).colorClass">
                                        <span class="text-[10px] font-bold uppercase tracking-tighter">
                                            {{ calculateSeniority(sale.created_at).text }}
                                        </span>
                                        <span class="text-[8px] opacity-70 font-medium leading-none mt-0.5">PENDIENTE</span>
                                    </div>
                                    <div v-else-if="activeTab === 'delivered'" class="inline-flex flex-col items-center px-3 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-500/20">
                                        <div class="flex flex-col items-center border-b border-emerald-200 dark:border-emerald-500/20 pb-1 mb-1 w-full">
                                            <span class="text-[10px] font-bold uppercase tracking-tighter leading-none">
                                                {{ sale.formatted_delivered_at ? sale.formatted_delivered_at.split(' ')[0] : 'N/A' }}
                                            </span>
                                            <span class="text-[8px] opacity-70 font-medium leading-none mt-1">
                                                {{ sale.formatted_delivered_at ? sale.formatted_delivered_at.split(' ')[1] : '' }}
                                            </span>
                                        </div>
                                        <span class="text-[8px] font-bold text-emerald-600 dark:text-emerald-400/80 tracking-tight uppercase">
                                            {{ calculateWaitTime(sale.created_at, sale.delivered_at) }}
                                        </span>
                                    </div>
                                    <div v-else class="inline-flex flex-col items-center px-3 py-1 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-100 dark:border-rose-500/20">
                                        <span class="text-[10px] font-bold uppercase tracking-tighter">
                                            {{ sale.formatted_cancelled_at ? sale.formatted_cancelled_at.split(' ')[0] : 'N/A' }}
                                        </span>
                                        <span class="text-[8px] opacity-70 font-medium leading-none mt-0.5">
                                            {{ sale.formatted_cancelled_at ? sale.formatted_cancelled_at.split(' ')[1] : '' }}
                                        </span>
                                    </div>
                                    <div v-if="activeTab === 'cancelled' && sale.reason_cancel" class="mt-1 text-[9px] text-rose-500 font-medium italic max-w-[100px] truncate mx-auto" :title="sale.reason_cancel">
                                        {{ sale.reason_cancel }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right align-middle font-mono font-bold text-sm text-zinc-500 dark:text-secondary-400">
                                    {{ sale.total_quantity }}
                                </td>
                                <td v-if="activeTab === 'delivered'" class="px-6 py-4 align-middle text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-1 border border-emerald-200 dark:border-emerald-500/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                            </svg>
                                        </div>
                                        <span class="text-[10px] font-bold text-zinc-600 dark:text-secondary-300 uppercase tracking-tight truncate max-w-[80px]" :title="sale.delivered_by_name">
                                            {{ sale.delivered_by_name.split(' ')[0] }}
                                        </span>
                                    </div>
                                </td>
                                <td v-else-if="activeTab === 'cancelled'" class="px-6 py-4 align-middle text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-7 h-7 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mb-1 border border-rose-200 dark:border-rose-500/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                            </svg>
                                        </div>
                                        <span class="text-[10px] font-bold text-zinc-600 dark:text-secondary-300 uppercase tracking-tight truncate max-w-[80px]" :title="sale.cancelled_by_name">
                                            {{ sale.cancelled_by_name.split(' ')[0] }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <div class="flex items-center justify-end gap-2">
                                        <template v-if="activeTab === 'pending'">
                                            <button 
                                                @click="selectSale(sale)" 
                                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-primary-500/20 active:scale-95 group/btn"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 group-hover/btn:animate-bounce">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.806H14.25M16.5 18.75h-2.25m0-11.25V4.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.5 3 4.004 3 4.625V14.25m11.25-11.25h-.375a1.125 1.125 0 0 0-1.125 1.125V14.25m-1.5 0h3" />
                                                </svg>
                                                Despachar
                                            </button>
                                            
                                            <button 
                                                @click="confirmCancel(sale)" 
                                                title="Cancelar despacho"
                                                class="w-10 h-10 flex items-center justify-center rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-sm border border-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-600 dark:hover:text-white group/cancel"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 transition-transform group-hover/cancel:rotate-90">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </template>
                                        <button 
                                            v-else
                                            @click="selectSale(sale)" 
                                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-secondary-700 dark:hover:bg-secondary-600 text-zinc-700 dark:text-secondary-200 rounded-xl text-xs font-bold transition-all active:scale-95 group/btn border border-zinc-200 dark:border-secondary-600"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            Ver Detalles
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="deliveries.data.length === 0">
                                <td :colspan="activeTab === 'pending' ? 10 : 11" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="p-4 bg-zinc-50 dark:bg-secondary-900 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-300 dark:text-secondary-700">
                                                <path v-if="activeTab === 'pending'" stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.745 3.745 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                                <path v-else stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25m-2.25-2.25 2.25-2.25m-2.25 2.25-2.25 2.25M3.75 7.5l1.724-4.57c.13-.343.465-.568.831-.568h11.39c.366 0 .701.225.831.568l1.724 4.57M3.75 7.5h16.5" />
                                            </svg>
                                        </div>
                                        <div class="max-w-xs mx-auto">
                                            <p class="text-zinc-900 dark:text-white font-bold">
                                                {{ activeTab === 'pending' ? '¡Todo al día!' : 'No hay historial' }}
                                            </p>
                                            <p class="text-xs text-zinc-500 dark:text-secondary-500">
                                                {{ activeTab === 'pending' ? 'No hay pedidos pendientes de despacho por el momento.' : 'Aún no se han registrado productos entregados en este periodo.' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile View -->
                <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-secondary-800">
                    <div v-for="(sale, index) in deliveries.data" :key="sale.id" 
                         class="p-5 space-y-5 bg-white dark:bg-secondary-800/50 active:bg-zinc-50 dark:active:bg-secondary-700/50 transition-colors">
                        
                        <!-- Header Info: Folio and Mode -->
                        <div class="flex items-start justify-between">
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-black text-primary-600 dark:text-primary-400 uppercase tracking-widest leading-none">#{{ sale.formatted_number }}</span>
                                <span class="text-[11px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-tighter">{{ sale.formatted_created_at }}</span>
                            </div>
                            <div class="flex flex-col items-end gap-1.5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-zinc-100 dark:bg-secondary-700 text-zinc-800 dark:text-secondary-200 border border-zinc-200 dark:border-secondary-600/50 shadow-sm">
                                     {{ sale.delivery_mode.label }}
                                </span>
                                <div v-if="activeTab === 'pending'" class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-tighter" :class="calculateSeniority(sale.created_at).colorClass">
                                    {{ calculateSeniority(sale.created_at).text }}
                                </div>
                            </div>
                        </div>

                        <!-- Client and Logistics Info -->
                        <div class="flex flex-col items-center py-4 bg-zinc-50/50 dark:bg-secondary-900/30 rounded-2xl border border-zinc-100 dark:border-secondary-700/50 space-y-1">
                            <span class="text-[10px] font-black text-zinc-400 uppercase tracking-[0.2em] mb-1">Cliente</span>
                            <h3 class="text-lg font-black text-zinc-900 dark:text-white tracking-tight leading-none uppercase">{{ sale.client.name }}</h3>
                            
                            <div v-if="sale.client.phone" class="flex items-center gap-2 mt-2">
                                <span class="material-symbols-outlined text-emerald-500 text-[16px]">call</span>
                                <span class="text-sm font-black text-emerald-600 dark:text-emerald-400 tracking-tighter">{{ sale.client.phone }}</span>
                                <a :href="`https://wa.me/${sale.client.phone.replace(/\D/g, '')}`" target="_blank" class="w-6 h-6 flex items-center justify-center bg-emerald-500 text-white rounded-lg shadow-sm">
                                    <span class="material-symbols-outlined text-[14px]">chat</span>
                                </a>
                            </div>
                            
                            <div class="flex items-center gap-4 mt-3 pt-3 border-t border-zinc-200/50 dark:border-secondary-700/50 w-full px-6">
                                <div class="flex-1 flex flex-col items-center">
                                    <span class="text-[9px] font-black text-zinc-400 uppercase tracking-widest">Sede</span>
                                    <span class="text-[11px] font-bold text-zinc-600 dark:text-secondary-300 truncate max-w-[100px]">{{ sale.warehouse.name }}</span>
                                </div>
                                <div class="w-px h-6 bg-zinc-200 dark:bg-secondary-700"></div>
                                <div class="flex-1 flex flex-col items-center">
                                    <span class="text-[9px] font-black text-zinc-400 uppercase tracking-widest">Items</span>
                                    <span class="text-[11px] font-black text-zinc-900 dark:text-white">{{ sale.total_quantity }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status and Actions -->
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <template v-if="activeTab === 'delivered'">
                                    <span class="text-[9px] font-black text-zinc-400 uppercase tracking-widest">Entregado en:</span>
                                    <span class="text-[11px] font-black text-emerald-600 dark:text-emerald-400">{{ calculateWaitTime(sale.created_at, sale.delivered_at) }}</span>
                                </template>
                                <template v-else-if="activeTab === 'cancelled'">
                                    <span class="text-[9px] font-black text-zinc-400 uppercase tracking-widest">Anulado</span>
                                    <span class="text-[11px] font-black text-rose-600 dark:text-rose-400">{{ sale.formatted_cancelled_at?.split(' ')[0] }}</span>
                                </template>
                            </div>

                            <div class="flex items-center gap-2">
                                <template v-if="activeTab === 'pending'">
                                    <button @click="confirmCancel(sale)"
                                            class="w-12 h-12 flex items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/20 shadow-sm active:scale-90 transition-all">
                                        <span class="material-symbols-outlined font-black">block</span>
                                    </button>
                                    <button @click="selectSale(sale)"
                                            class="h-12 px-6 flex items-center justify-center gap-2 rounded-2xl bg-zinc-900 dark:bg-primary-600 text-white shadow-xl shadow-zinc-900/20 dark:shadow-primary-600/20 active:scale-95 transition-all">
                                        <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                                        <span class="text-xs font-black uppercase tracking-widest">Despachar</span>
                                    </button>
                                </template>
                                <button v-else @click="selectSale(sale)"
                                        class="h-12 px-6 flex items-center justify-center gap-2 rounded-2xl bg-zinc-100 dark:bg-secondary-700 text-zinc-900 dark:text-white border border-zinc-200 dark:border-secondary-600 active:scale-95 transition-all">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    <span class="text-xs font-black uppercase tracking-widest">Detalles</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State for Mobile -->
                    <div v-if="deliveries.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800">
                        <div class="flex flex-col items-center opacity-40 py-10">
                            <span class="material-symbols-outlined text-4xl mb-4 text-zinc-300 dark:text-zinc-600">inventory_2</span>
                            <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-sm">Sin despachos registrados</p>
                        </div>
                    </div>
                </div>

                <div v-if="deliveries.links.length > 3" class="px-6 py-4 bg-zinc-50/50 dark:bg-secondary-900/30 border-t border-zinc-100 dark:border-secondary-700/50 flex justify-center">
                    <nav class="flex gap-1">
                        <Link v-for="(link, i) in deliveries.links" :key="i"
                              :href="link.url || '#'"
                              v-html="link.label"
                              :class="[
                                  'px-4 py-2 text-sm font-bold rounded-xl transition-all',
                                  link.active ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/20' : 
                                  link.url ? 'bg-white dark:bg-secondary-800 text-zinc-500 dark:text-secondary-400 hover:bg-zinc-100 dark:hover:bg-secondary-700' : 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'
                              ]"
                        />
                    </nav>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <DeliveryModal 
            :show="showDeliveryModal" 
            :sale="selectedSale" 
            @close="showDeliveryModal = false"
            @process="handleDeliver"
        />

        <CancelModal 
            :show="showCancelModal" 
            @close="showCancelModal = false"
            @confirm="handleAnnul"
        />
    </AdminLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
