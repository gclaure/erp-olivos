<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    show: { type: Boolean, default: false },
    product: { type: Object, default: null },
    operationType: { type: String, default: 'consumption' },
    cart: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'add-to-cart']);

const page = usePage();

const isConsumer = computed(() => {
    if (props.operationType !== 'consumption') return false;
    const user = page.props.auth?.user;
    if (!user) return false;
    const roles = user.roles || [];
    const isSpecialAdmin = user.is_super_admin || roles.some(r => ['Admin', 'admin', 'Administrador', 'administrador', 'Super Admin'].includes(r));
    return roles.some(r => ['Consumidor', 'consumidor'].includes(r)) && !isSpecialAdmin;
});

const isConsumptionMode = computed(() => props.operationType === 'consumption');

const isAdmin = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    if (user.is_super_admin) return true;
    const roles = user.roles || [];
    return roles.some(r => ['Admin', 'admin', 'Administrador', 'administrador', 'Super Admin', 'super-admin'].includes(r));
});

const isSupply = computed(() => props.product?.type === 'insumo' || props.product?.is_inventoriable === false);

const availableQty = computed(() => {
    if (!props.product) return 0;
    if (isSupply.value) return 999999;
    const physical = parseFloat(props.product.stocks?.[0]?.quantity ?? props.product.stock ?? 0);
    const reserved = parseFloat(props.product.reserved_quantity || 0);
    return Math.max(0, physical - reserved);
});

const hasStock = computed(() => isSupply.value || availableQty.value > 0);

const canAdd = computed(() => isConsumer.value || (isConsumptionMode.value && isAdmin.value) || hasStock.value);

const myReserved = computed(() => {
    if (!props.product) return 0;
    return Math.floor(parseFloat(props.product.my_reserved_quantity || 0));
});

const qty = ref(1);
const maxQty = computed(() => (isSupply.value || isConsumer.value || (isConsumptionMode.value && isAdmin.value)) ? 99999 : Math.max(1, Math.floor(availableQty.value)));

watch(() => props.show, (val) => {
    if (val) {
        qty.value = 1;
    }
});

const increment = () => {
    qty.value = Math.min(qty.value + 1, maxQty.value);
};

const decrement = () => {
    qty.value = Math.max(qty.value - 1, 1);
};

const updateInput = (e) => {
    let v = parseInt(e.target.value, 10);
    if (isNaN(v) || v < 1) v = 1;
    qty.value = Math.min(v, maxQty.value);
};

const handleAddToCart = () => {
    if (!props.product || !canAdd.value) return;
    emit('add-to-cart', props.product, qty.value);
    emit('close');
};
</script>

<template>
    <div v-if="show && product" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-sm overflow-y-auto animate-in fade-in duration-200">
        <div class="relative bg-white dark:bg-secondary-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-zinc-200 dark:border-secondary-700 text-left my-auto">
            <!-- Modal Header -->
            <div class="p-4 sm:p-5 border-b border-zinc-100 dark:border-secondary-700 flex items-center justify-between bg-zinc-50/50 dark:bg-secondary-800/80">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-xl">info</span>
                    <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-secondary-50">Datos del Producto</h3>
                </div>
                <button 
                    type="button"
                    @click="$emit('close')"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-zinc-400 hover:text-zinc-600 dark:hover:text-secondary-200 hover:bg-zinc-100 dark:hover:bg-secondary-700 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 sm:p-6 space-y-4 max-h-[75vh] overflow-y-auto custom-scrollbar">
                <!-- Imagen y Nombre Principal -->
                <div class="flex flex-col sm:flex-row gap-4 items-center sm:items-start bg-zinc-50 dark:bg-secondary-900/50 p-4 rounded-xl border border-zinc-100 dark:border-secondary-700">
                    <div class="w-28 h-28 sm:w-32 sm:h-32 flex-shrink-0 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 p-2 flex items-center justify-center relative overflow-hidden shadow-sm">
                        <img 
                            v-if="product.image_path" 
                            :src="product.image_path" 
                            :alt="product.name" 
                            class="max-h-full max-w-full object-contain"
                        >
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-300 dark:text-secondary-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.125a3.375 3.375 0 0 1-3.375 3.375H7.75a3.375 3.375 0 0 1-3.375-3.375L3.75 7.5m16.5 0-1.25-2.25a3.375 3.375 0 0 0-3-1.5H8a3.375 3.375 0 0 0-3 1.5L3.75 7.5m16.5 0h-16.5" />
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0 text-center sm:text-left space-y-1.5">
                        <span class="text-[11px] font-mono font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-wide">{{ product.code }}</span>
                        <h4 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white uppercase leading-snug">{{ product.name }}</h4>
                        
                        <div class="flex flex-wrap gap-1.5 justify-center sm:justify-start pt-1">
                            <!-- Tipo de Producto -->
                            <span 
                                :class="isSupply ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800'"
                                class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wide border"
                            >
                                {{ product.type_label || (isSupply ? 'Insumo' : 'Materia Prima') }}
                            </span>

                            <!-- Categorías -->
                            <span 
                                v-for="cat in (product.categories || [])" 
                                :key="cat"
                                class="px-2 py-0.5 rounded text-[10px] font-semibold bg-zinc-100 dark:bg-secondary-700 text-zinc-700 dark:text-secondary-300 border border-zinc-200 dark:border-secondary-600 uppercase"
                            >
                                {{ cat }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cuadrícula de Especificaciones -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <!-- Unidad de Medida -->
                    <div class="p-3 bg-zinc-50 dark:bg-secondary-900/30 rounded-xl border border-zinc-100 dark:border-secondary-700 space-y-0.5">
                        <span class="text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest">Unidad de Medida</span>
                        <p class="font-bold text-zinc-800 dark:text-secondary-100">
                            {{ product.unit_name || 'No especificada' }} 
                            <span v-if="product.unit" class="text-zinc-400 font-normal">({{ product.unit }})</span>
                        </p>
                    </div>

                    <!-- Presentación / Empaque -->
                    <div class="p-3 bg-zinc-50 dark:bg-secondary-900/30 rounded-xl border border-zinc-100 dark:border-secondary-700 space-y-0.5">
                        <span class="text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest">Presentación</span>
                        <p class="font-bold text-zinc-800 dark:text-secondary-100">
                            {{ product.package_name || 'Individual' }}
                            <span v-if="product.units_per_package > 1" class="text-zinc-500 dark:text-secondary-400 font-semibold"> (x{{ product.units_per_package }})</span>
                        </p>
                    </div>

                    <!-- Marca -->
                    <div class="p-3 bg-zinc-50 dark:bg-secondary-900/30 rounded-xl border border-zinc-100 dark:border-secondary-700 space-y-0.5">
                        <span class="text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest">Marca</span>
                        <p class="font-bold text-zinc-800 dark:text-secondary-100">
                            {{ product.brand || 'No registrada' }}
                        </p>
                    </div>

                    <!-- Ubicación Física -->
                    <div class="p-3 bg-zinc-50 dark:bg-secondary-900/30 rounded-xl border border-zinc-100 dark:border-secondary-700 space-y-0.5">
                        <span class="text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest">Ubicación</span>
                        <p class="font-bold text-zinc-800 dark:text-secondary-100">
                            {{ product.location || 'Almacén General' }}
                        </p>
                    </div>
                </div>

                <!-- Estado de Disponibilidad y Stock -->
                <div class="p-3.5 rounded-xl border flex items-center justify-between"
                     :class="(isConsumer || hasStock) ? 'bg-emerald-50/70 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-900/40 text-emerald-800 dark:text-emerald-300' : 'bg-rose-50/70 dark:bg-rose-950/20 border-rose-200 dark:border-rose-900/40 text-rose-800 dark:text-rose-300'">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl" :class="(isConsumer || hasStock) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                            {{ (isConsumer || hasStock) ? 'check_circle' : 'cancel' }}
                        </span>
                        <div>
                            <p class="font-bold text-xs uppercase tracking-wide">
                                <template v-if="isConsumer">
                                    Disponible para Consumo
                                </template>
                                <template v-else-if="isSupply">
                                    Insumo Permanente (Disponible)
                                </template>
                                <template v-else>
                                    Stock Disponible: {{ Math.floor(availableQty) }} {{ product.unit || 'uds' }}
                                </template>
                            </p>
                            <p v-if="!isConsumer && parseFloat(product.reserved_quantity) > 0" class="text-[10px] text-zinc-500 dark:text-secondary-400 font-medium">
                                Total Reservado: {{ Math.floor(product.reserved_quantity) }} {{ product.unit || 'uds' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer con Selector de Cantidad y Agregar -->
            <div class="p-4 sm:p-5 bg-zinc-50 dark:bg-secondary-800/80 border-t border-zinc-100 dark:border-secondary-700 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div v-if="canAdd" class="flex items-center gap-2 w-full sm:w-auto justify-center sm:justify-start">
                    <span class="text-xs font-bold text-zinc-600 dark:text-secondary-300 uppercase">Cantidad:</span>
                    <div class="flex items-center gap-1.5 bg-white dark:bg-secondary-700 p-1 rounded-xl border border-zinc-200 dark:border-secondary-600 shadow-sm">
                        <button
                            type="button"
                            @click="decrement"
                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-secondary-800 text-zinc-700 dark:text-secondary-200 font-black text-base hover:bg-zinc-200 dark:hover:bg-secondary-600 active:scale-95 transition-all select-none"
                        >
                            −
                        </button>
                        <input
                            type="text"
                            :value="qty"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            @input="updateInput"
                            class="w-12 h-8 text-center rounded-lg bg-transparent text-zinc-900 dark:text-secondary-100 font-extrabold text-sm border-none focus:ring-0 focus:outline-none"
                        >
                        <button
                            type="button"
                            @click="increment"
                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-600 dark:bg-emerald-500 text-white font-black text-base hover:bg-emerald-700 active:scale-95 transition-all select-none"
                        >
                            +
                        </button>
                    </div>
                </div>
                <div v-else class="text-xs text-rose-500 font-bold uppercase">
                    Producto no disponible
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button 
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2.5 text-xs font-bold text-zinc-500 dark:text-secondary-400 hover:bg-zinc-100 dark:hover:bg-secondary-700 rounded-xl transition-colors uppercase"
                    >
                        Cerrar
                    </button>
                    <button 
                        v-if="canAdd"
                        type="button"
                        @click="handleAddToCart"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-lg shadow-emerald-600/20 transition-all active:scale-95 cursor-pointer"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ isConsumptionMode ? 'Agregar a la Solicitud' : 'Agregar al Pedido' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
