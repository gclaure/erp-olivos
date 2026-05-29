<script setup>
import { ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    cart: Array,
    subtotal: Number,
    globalDiscountAmount: Number,
    finalTotal: Number,
    selectedClient: Object,
    clientSearchQuery: String,
    clients: Array,
    loadingClients: Boolean,
    isFixedDiscount: Boolean,
    receiptType: String,
    isEditing: Boolean,
    operationType: {
        type: String,
        default: 'sale'
    }
});

const emit = defineEmits([
    'update:clientSearchQuery',
    'select-client',
    'remove-from-cart',
    'update-quantity',
    'update-discount',
    'clear-cart',
    'open-confirm',
    'toggle-discount-type',
    'toggle-receipt-type',
    'open-client-modal',
    'submit-consumption'
]);

const paymentMethod = ref('efectivo');

// Estado para Consumos Internos
const requestedBy = ref('');
const customRequestedBy = ref('');
const notes = ref('');

const getRequestedByValue = () => {
    return requestedBy.value === 'Otro' ? customRequestedBy.value : requestedBy.value;
};

const submitConsumption = () => {
    const area = getRequestedByValue().trim();
    if (!area) {
        Swal.fire({
            icon: 'error',
            title: 'Campo Requerido',
            text: 'Por favor, seleccione o escriba el área solicitante.',
            customClass: {
                confirmButton: 'bg-blue-600 text-white px-4 py-2 rounded-lg font-bold'
            }
        });
        return;
    }
    if (props.cart.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Carrito Vacío',
            text: 'Agregue al menos un producto a la solicitud de consumo.',
            customClass: {
                confirmButton: 'bg-blue-600 text-white px-4 py-2 rounded-lg font-bold'
            }
        });
        return;
    }

    emit('submit-consumption', {
        requested_by: area,
        notes: notes.value
    });
};

const formatMoney = (val) => {
    return parseFloat(val).toFixed(2);
};

const getItemTotals = (item) => {
    const lineTotal = item.price * item.quantity;
    let discountAmount = 0;
    
    if (props.isFixedDiscount) {
        discountAmount = parseFloat(item.discount) || 0;
    } else {
        const disc = Math.min(parseFloat(item.discount) || 0, 100);
        discountAmount = (lineTotal * disc) / 100;
    }
    
    const finalLineTotal = Math.max(0, lineTotal - discountAmount);
    const costTotal = item.unit_cost * item.quantity;
    const profit = finalLineTotal - costTotal;
    
    return {
        finalLineTotal,
        profit
    };
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    try {
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    } catch (e) {
        return dateStr;
    }
};
</script>

<template>
    <div class="w-full lg:w-[45%] flex flex-col h-full bg-[#f5f6f8] dark:bg-secondary-900 transition-colors duration-300">
        
        <!-- HEADER CARRITO -->
        <div class="flex-shrink-0 bg-gradient-to-r from-slate-800 to-slate-700 dark:from-secondary-800 dark:to-secondary-700 px-5 py-3 flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-white font-black text-sm tracking-tight leading-none uppercase">Carrito</h2>
                    </div>
                    <p class="text-slate-400 text-[10px] font-medium mt-1">{{ cart.length }} productos</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <template v-if="operationType !== 'consumption'">
                    <button 
                        @click="$emit('toggle-discount-type')" 
                        class="flex items-center gap-1.5 px-2 py-1 bg-blue-500/20 text-blue-400 border border-blue-500/30 rounded-lg transition-colors"
                    >
                        <svg v-if="isFixedDiscount" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 14.25 6-6m4.5-3.493V21.75l-3.75-2.25-3.75 2.25-3.75-2.25-3.75 2.25V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185ZM9.75 9h.008v.008H9.75V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 4.5h.008v.008h-.008V13.5Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span class="text-[9px] font-bold uppercase tracking-wider">{{ isFixedDiscount ? 'Bs.' : '%' }}</span>
                    </button>

                    <!-- Toggle Formato Impresión -->
                    <button 
                        @click="$emit('toggle-receipt-type')" 
                        class="flex items-center gap-1.5 px-2 py-1 transition-all rounded-lg border"
                        :class="receiptType === 'rollo' 
                            ? 'bg-amber-500/20 text-amber-500 border-amber-500/30' 
                            : 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30'"
                        :title="`Formato: ${receiptType === 'rollo' ? 'Rollo (Térmico)' : 'Media Página'}`"
                    >
                        <svg v-if="receiptType === 'rollo'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <span class="text-[9px] font-bold uppercase tracking-wider">
                            {{ receiptType === 'rollo' ? 'Rollo' : 'Media' }}
                        </span>
                    </button>
                </template>

                <template v-if="cart.length > 0">
                    <button @click="$emit('clear-cart')" class="text-[10px] font-bold text-rose-600 hover:text-white hover:bg-rose-600 bg-rose-50/20 border border-rose-200/30 px-3 py-1.5 rounded-lg transition-all duration-200 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                         Vaciar
                    </button>
                </template>
            </div>
        </div>

        <!-- LISTA DE ITEMS -->
        <!-- LISTA DE ITEMS -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-3" style="min-height: 0;">
            <div v-if="cart.length > 0" class="space-y-3">
                <div 
                    v-for="item in cart" 
                    :key="item.id" 
                    class="bg-white dark:bg-secondary-800 rounded-2xl p-4 shadow-sm border border-zinc-100 dark:border-secondary-700 flex flex-col gap-3 group transition-all hover:border-emerald-300 dark:hover:border-emerald-500/50 hover:shadow-md"
                >
                    <div class="flex justify-between items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-black text-zinc-900 dark:text-secondary-50 uppercase leading-tight tracking-tight">{{ item.name }}</h4>
                            <p class="text-[11px] font-bold text-zinc-400 dark:text-secondary-500 mt-1 uppercase tracking-wide">
                                <span class="text-zinc-500 dark:text-secondary-400">{{ item.code }}</span> 
                                <span v-if="item.unit">- {{ item.unit }}</span>
                            </p>
                        </div>
                        <button @click="$emit('remove-from-cart', item.id)" class="w-8 h-8 flex items-center justify-center rounded-full text-zinc-300 dark:text-secondary-600 hover:text-rose-500 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Datos extendidos para solicitudes de consumo interno (Ultra-Compacto) -->
                    <div v-if="operationType === 'consumption'" class="grid grid-cols-2 sm:grid-cols-3 gap-x-3 gap-y-1.5 pt-2 text-[10px] border-t border-dashed border-zinc-150 dark:border-secondary-700/60">
                        <!-- Categoría -->
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-wider flex-shrink-0">Cat:</span>
                            <span class="font-extrabold text-zinc-700 dark:text-secondary-300 uppercase truncate" :title="item.categories && item.categories.length > 0 ? item.categories.join(', ') : 'N/A'">
                                {{ item.categories && item.categories.length > 0 ? item.categories[0] : 'N/A' }}
                            </span>
                        </div>
                        
                        <!-- Marca -->
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-wider flex-shrink-0">Marca:</span>
                            <span class="font-extrabold text-zinc-700 dark:text-secondary-300 uppercase truncate" :title="item.brand || 'N/A'">
                                {{ item.brand || 'N/A' }}
                            </span>
                        </div>

                        <!-- Unidad de Medida -->
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-wider flex-shrink-0">Medida:</span>
                            <span class="font-extrabold text-zinc-700 dark:text-secondary-300 uppercase truncate" :title="item.unit_name ? `${item.unit_name} (${item.unit})` : 'N/A'">
                                {{ item.unit || 'N/A' }}
                            </span>
                        </div>

                        <!-- Ubicación -->
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-wider flex-shrink-0">Ubic:</span>
                            <span class="font-extrabold text-zinc-700 dark:text-secondary-300 uppercase truncate" :title="item.location || 'N/A'">
                                {{ item.location || 'N/A' }}
                            </span>
                        </div>

                        <!-- Empaque & U x Epq -->
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-wider flex-shrink-0">Empaque:</span>
                            <span class="font-extrabold text-zinc-700 dark:text-secondary-300 uppercase truncate" :title="item.package_name ? `${item.package_name} (${parseFloat(item.units_per_package || 0)} uds)` : 'N/A'">
                                {{ item.package_name || 'N/A' }} ({{ parseFloat(item.units_per_package || 0) }})
                            </span>
                        </div>

                        <!-- Vencimiento -->
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-wider flex-shrink-0">Vence:</span>
                            <span :class="[
                                'font-black uppercase truncate',
                                item.has_expiration ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400 dark:text-secondary-500'
                            ]">
                                {{ item.has_expiration ? 'SÍ' : 'NO' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 pt-3 sm:pt-2 border-t border-zinc-100 dark:border-secondary-700">
                        <div class="flex items-center gap-4 w-full" :class="operationType === 'consumption' ? 'justify-between' : 'sm:w-auto'">
                            <div class="flex flex-col gap-1.5 flex-shrink-0">
                                <span class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">
                                    Cantidad Solicitada <span v-if="item.unit" class="text-indigo-500 dark:text-indigo-400">({{ item.unit }})</span>
                                </span>
                                <div class="flex items-center bg-zinc-100 dark:bg-secondary-900 rounded-xl p-1 w-fit border border-zinc-200/60 dark:border-secondary-700/60">
                                    <button @click="$emit('update-quantity', item.id, Math.max(1, parseFloat(item.quantity) - 1))" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-secondary-800 shadow-sm text-zinc-600 dark:text-secondary-300 hover:text-rose-500 dark:hover:text-rose-400 transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                        </svg>
                                    </button>
                                    <input 
                                        type="text" 
                                        :value="item.quantity" 
                                        @input="e => $emit('update-quantity', item.id, e.target.value)"
                                        class="w-12 bg-transparent border-none text-center text-sm font-black text-zinc-800 dark:text-secondary-100 focus:ring-0 p-0 uppercase"
                                    >
                                    <button @click="$emit('update-quantity', item.id, parseFloat(item.quantity) + 1)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-secondary-800 shadow-sm text-zinc-600 dark:text-secondary-300 hover:text-emerald-500 dark:hover:text-emerald-400 transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                    </button>
                                </div>
                                <!-- Indicador de equivalencia para empaques -->
                                <div v-if="item.package_name && parseFloat(item.units_per_package) > 1" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 mt-1 uppercase tracking-wider">
                                    Equivale a: 
                                    <span class="font-black underline decoration-2 decoration-indigo-400">
                                        {{ (parseFloat(item.quantity || 0) / parseFloat(item.units_per_package)).toFixed(2) }} {{ item.package_name }}s
                                    </span>
                                </div>
                            </div>

                            <div v-if="operationType !== 'consumption'" class="flex flex-col gap-1.5 flex-shrink-0">
                                <span class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Desc. (%)</span>
                                <input 
                                    type="text" 
                                    :value="item.discount" 
                                    @input="e => $emit('update-discount', item.id, e.target.value)"
                                    class="w-16 h-10 text-center rounded-xl border-zinc-100 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-sm font-bold text-zinc-700 dark:text-secondary-300 focus:border-emerald-400 dark:focus:border-emerald-500 focus:ring-0 transition-all uppercase"
                                >
                            </div>
                        </div>

                        <div v-if="operationType !== 'consumption'" class="text-right flex flex-col justify-between items-end sm:min-w-[100px] w-full sm:w-auto">
                            <div class="flex flex-col gap-1 w-full mt-1.5 opacity-90">
                                <div class="flex justify-between items-center bg-slate-50 dark:bg-secondary-900 px-1.5 py-0.5 rounded border border-slate-200/60 dark:border-secondary-700 shadow-sm gap-2">
                                    <span class="text-[8px] font-bold text-slate-500 dark:text-secondary-500 uppercase tracking-wider">P. Venta</span>
                                    <span class="text-[9px] font-black text-slate-700 dark:text-secondary-300">Bs. {{ formatMoney(item.price) }}</span>
                                </div>
                                <div class="flex justify-between items-center bg-amber-50 dark:bg-amber-500/10 px-1.5 py-0.5 rounded border border-amber-200/60 dark:border-amber-500/20 shadow-sm gap-2">
                                    <span class="text-[8px] font-bold text-amber-600/80 dark:text-amber-500/80 uppercase tracking-wider">Costo</span>
                                    <span class="text-[9px] font-black text-amber-700 dark:text-amber-400">Bs. {{ formatMoney(item.unit_cost) }}</span>
                                </div>
                                <div :class="[
                                    'flex justify-between items-center px-1.5 py-0.5 rounded border shadow-sm gap-2',
                                    getItemTotals(item).profit < 0 
                                        ? 'bg-rose-50 dark:bg-rose-500/10 border-rose-200/60 dark:border-rose-500/20' 
                                        : 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200/60 dark:border-emerald-500/20'
                                ]">
                                    <span :class="[
                                        'text-[8px] font-bold uppercase tracking-wider',
                                        getItemTotals(item).profit < 0 
                                            ? 'text-rose-600/80 dark:text-rose-500/80' 
                                            : 'text-emerald-600/80 dark:text-emerald-500/80'
                                    ]">Ganancia</span>
                                    <span :class="[
                                        'text-[9px] font-black',
                                        getItemTotals(item).profit < 0 
                                            ? 'text-rose-700 dark:text-rose-400' 
                                            : 'text-emerald-700 dark:text-emerald-400'
                                    ]">Bs. {{ formatMoney(getItemTotals(item).profit) }}</span>
                                </div>
                            </div>
                            <div class="mt-2.5">
                                <span class="text-lg font-black text-emerald-600 tracking-tight leading-none">Bs. {{ formatMoney(getItemTotals(item).finalLineTotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="h-full flex flex-col items-center justify-center p-6 text-center">
                <div class="w-24 h-24 bg-white dark:bg-secondary-800 rounded-3xl flex items-center justify-center mb-5 shadow-sm border border-slate-200/80 dark:border-secondary-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-slate-200 dark:text-secondary-700">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
                <p class="text-slate-500 dark:text-secondary-400 font-bold text-sm uppercase tracking-tight">El carrito está vacío</p>
                <p class="text-[11px] text-slate-400 dark:text-secondary-600 mt-1.5 max-w-[200px] leading-relaxed uppercase font-bold tracking-tight">Haga clic en los productos del catálogo para agregarlos aquí.</p>
            </div>
        </div>

        <!-- FOOTER TOTALES -->
        <div class="flex-shrink-0 bg-white dark:bg-secondary-800 border-t border-zinc-100 dark:border-secondary-700 transition-colors duration-300">
            <!-- Si es consumo interno -->
            <div v-if="operationType === 'consumption'" class="px-5 py-4 space-y-4">
                <div>
                    <label class="block text-[11px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider mb-2">Área Solicitante *</label>
                    <div class="space-y-2">
                        <select 
                            v-model="requestedBy"
                            class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-sm font-semibold text-zinc-700 dark:text-secondary-300 focus:border-blue-500 focus:ring-0 transition-all uppercase"
                        >
                            <option value="" disabled>Seleccione el área...</option>
                            <option value="Cocina">Cocina</option>
                            <option value="Pastelería">Pastelería</option>
                            <option value="Producción">Producción</option>
                            <option value="Almacén">Almacén</option>
                            <option value="Administración">Administración</option>
                            <option value="Otro">Otro (Especificar)</option>
                        </select>
                        
                        <input 
                            v-if="requestedBy === 'Otro'"
                            v-model="customRequestedBy"
                            type="text"
                            placeholder="Escriba el nombre del área..."
                            class="w-full h-11 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-sm font-semibold text-zinc-700 dark:text-secondary-300 focus:border-blue-500 focus:ring-0 transition-all uppercase"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider mb-2">Notas / Observaciones</label>
                    <textarea 
                        v-model="notes"
                        rows="2"
                        placeholder="Notas adicionales para el almacenero..."
                        class="w-full p-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-sm font-semibold text-zinc-700 dark:text-secondary-300 focus:border-blue-500 focus:ring-0 transition-all resize-none uppercase"
                    ></textarea>
                </div>

                <button 
                    @click="submitConsumption"
                    class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 transition-colors text-white rounded-xl font-black text-xs uppercase flex items-center justify-center gap-2 shadow-lg hover:shadow-blue-500/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    Enviar Solicitud de Consumo
                </button>
            </div>

            <!-- Si es venta ordinaria -->
            <div v-else class="px-5 py-3 bg-zinc-50 dark:bg-secondary-900/50">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-secondary-500">Subtotal</span>
                    <span class="text-xs font-semibold text-zinc-700 dark:text-secondary-300">Bs. {{ formatMoney(subtotal) }}</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-black uppercase tracking-wide text-zinc-800 dark:text-secondary-100">TOTAL A PAGAR</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-[9px] font-bold text-zinc-500 dark:text-secondary-500">Bs.</span>
                        <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">Bs. {{ formatMoney(finalTotal) }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-3">
                    <button @click="paymentMethod = 'efectivo'" 
                            :class="paymentMethod === 'efectivo' ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-200 dark:ring-emerald-500/40' : 'bg-zinc-100 dark:bg-secondary-800 text-zinc-600 dark:text-secondary-400'"
                            class="py-2.5 rounded-lg text-xs font-bold uppercase transition-all flex items-center justify-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                        </svg>
                        Efectivo
                    </button>
                    <button @click="paymentMethod = 'tarjeta'" 
                            :class="paymentMethod === 'tarjeta' ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-200 dark:ring-emerald-500/40' : 'bg-zinc-100 dark:bg-secondary-800 text-zinc-600 dark:text-secondary-400'"
                            class="py-2.5 rounded-lg text-xs font-bold uppercase transition-all flex items-center justify-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75-3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V14.1" />
                        </svg>
                        Tarjeta
                    </button>
                    <button @click="paymentMethod = 'qr'" 
                            :class="paymentMethod === 'qr' ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-200 dark:ring-emerald-500/40' : 'bg-zinc-100 dark:bg-secondary-800 text-zinc-600 dark:text-secondary-400'"
                            class="py-2.5 rounded-lg text-xs font-bold uppercase transition-all flex items-center justify-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 15.75h.008v.008H13.5V15.75Zm2.25 0h.008v.008H15.75V15.75Zm0 2.25h.008v.008H15.75V18Zm-2.25 0h.008v.008H13.5V18Zm2.25-2.25h.008v.008H15.75V15.75Zm0 2.25h.008v.008H15.75V18Zm2.25-2.25h.008v.008H18V15.75Zm0 2.25h.008v.008H18V18Zm2.25-2.25h.008v.008H20.25V15.75Zm0 2.25h.008v.008H20.25V18Zm-2.25-2.25h.008v.008H18V15.75Zm0 2.25h.008v.008H18V18Z" />
                        </svg>
                        QR
                    </button>
                </div>

                <div class="flex gap-2">
                    <button @click="$emit('open-confirm')" class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 transition-colors text-white rounded-xl font-bold text-xs uppercase flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        Registrar venta
                    </button>
                    <button v-if="!isEditing" @click="$emit('open-confirm', 'quotation')" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 transition-colors text-white rounded-xl font-bold text-xs uppercase flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        Registrar proforma
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
