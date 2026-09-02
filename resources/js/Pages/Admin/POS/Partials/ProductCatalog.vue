<script setup>
import { ref } from 'vue';
import ProductCard from './ProductCard.vue';

const props = defineProps({
    products: Array,
    loading: Boolean,
    pagination: Object,
    searchQuery: String,
    typeFilter: { type: String, default: '' },
    warehouseName: String,
    warehouses: { type: Array, default: () => [] },
    activeWarehouseId: { type: Number, default: null },
    operationType: String,
    cart: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:searchQuery', 'update:typeFilter', 'page-change', 'add-to-cart', 'change-warehouse', 'update-quantity', 'show-detail']);

const showWarehouseDropdown = ref(false);

const selectWarehouse = (warehouse) => {
    showWarehouseDropdown.value = false;
    emit('change-warehouse', warehouse);
};

const setTypeFilter = (type) => {
    emit('update:typeFilter', type);
};
</script>

<template>
    <div class="w-full lg:w-[55%] flex flex-col h-full lg:h-full border-b lg:border-b-0 lg:border-r border-zinc-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 transition-colors duration-300">
        <!-- Buscador superior -->
        <div class="p-4 bg-[#f8f9fa] dark:bg-secondary-800/50 border-b border-zinc-200 dark:border-secondary-700 shadow-sm flex-shrink-0 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-zinc-500 dark:text-secondary-400 mr-3">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <div class="relative flex-1">
                <input 
                    id="pos-search-input"
                    type="text" 
                    :value="searchQuery"
                    @input="e => $emit('update:searchQuery', e.target.value)"
                    placeholder="BUSQUE UN PRODUCTO AQUÍ...." 
                    class="w-full bg-transparent border-none text-sm font-semibold text-zinc-600 dark:text-secondary-200 placeholder:text-zinc-400 dark:placeholder:text-secondary-500 focus:ring-0 uppercase focus:outline-none"
                    autofocus
                >
            </div>

            <!-- Informacion Operativa -->
            <div class="ml-2 sm:ml-4 flex items-center gap-2 flex-shrink-0">
                <!-- Selector de Almacén -->
                <div class="relative">
                    <button
                        @click="showWarehouseDropdown = !showWarehouseDropdown"
                        class="flex items-center gap-1.5 px-2 py-1.5 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-lg shadow-sm hover:border-emerald-400 dark:hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-all group cursor-pointer"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                        </svg>
                        <span class="text-[9px] sm:text-[10px] font-bold text-zinc-600 dark:text-secondary-300 uppercase max-w-[80px] sm:max-w-[120px] truncate">{{ warehouseName }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            class="w-2.5 h-2.5 text-zinc-400 dark:text-secondary-500 transition-transform duration-200 flex-shrink-0"
                            :class="showWarehouseDropdown ? 'rotate-180' : ''"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <!-- Dropdown lista de almacenes -->
                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="opacity-0 scale-95 -translate-y-1"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 -translate-y-1"
                    >
                        <div
                            v-if="showWarehouseDropdown"
                            class="absolute top-full right-0 mt-1.5 min-w-[180px] bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-xl shadow-lg z-50 py-1 overflow-hidden"
                        >
                            <p class="px-3 pt-2 pb-1 text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Seleccionar almacén</p>
                            <button
                                v-for="wh in warehouses"
                                :key="wh.id"
                                @click="selectWarehouse(wh)"
                                class="w-full text-left flex items-center gap-2 px-3 py-2 hover:bg-zinc-50 dark:hover:bg-secondary-700 transition-colors"
                                :class="wh.id === activeWarehouseId ? 'bg-emerald-50 dark:bg-emerald-500/10' : ''"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                    :class="wh.id === activeWarehouseId ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-secondary-600'"
                                ></span>
                                <span class="text-[11px] font-bold uppercase tracking-tight"
                                    :class="wh.id === activeWarehouseId ? 'text-emerald-700 dark:text-emerald-400' : 'text-zinc-600 dark:text-secondary-300'"
                                >{{ wh.name }}</span>
                            </button>
                            <div v-if="!warehouses.length" class="px-3 py-2 text-[11px] text-zinc-400 dark:text-secondary-500">Sin almacenes</div>
                        </div>
                    </Transition>

                    <!-- Overlay para cerrar el dropdown -->
                    <div
                        v-if="showWarehouseDropdown"
                        class="fixed inset-0 z-40"
                        @click="showWarehouseDropdown = false"
                    ></div>
                </div>
            </div>

        </div>

        <!-- Barra de Filtros de Tipo de Producto -->
        <div class="px-4 py-2 bg-white dark:bg-secondary-800/80 border-b border-zinc-200/80 dark:border-secondary-700/80 flex items-center justify-between gap-2 overflow-x-auto scrollbar-none flex-shrink-0">
            <div class="flex items-center gap-1.5 sm:gap-2">
                <button
                    type="button"
                    @click="setTypeFilter('')"
                    class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                    :class="!typeFilter ? 'bg-zinc-900 text-white shadow-sm dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 dark:bg-secondary-700/60 text-zinc-600 dark:text-secondary-300 hover:bg-zinc-200 dark:hover:bg-secondary-700'"
                >
                    <span class="material-symbols-outlined text-[15px]">apps</span>
                    <span>Todos</span>
                </button>

                <button
                    type="button"
                    @click="setTypeFilter('insumo')"
                    class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                    :class="typeFilter === 'insumo' ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' : 'bg-amber-50/80 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900/40 hover:bg-amber-100 dark:hover:bg-amber-950/50'"
                >
                    <span class="material-symbols-outlined text-[15px]">label</span>
                    <span>Insumos</span>
                </button>

                <button
                    type="button"
                    @click="setTypeFilter('materia_prima')"
                    class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                    :class="typeFilter === 'materia_prima' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-indigo-50/80 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-900/40 hover:bg-indigo-100 dark:hover:bg-indigo-950/50'"
                >
                    <span class="material-symbols-outlined text-[15px]">inventory_2</span>
                    <span>Materia Prima</span>
                </button>
            </div>

            <div v-if="pagination?.total !== undefined" class="text-[10px] font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest whitespace-nowrap hidden sm:block">
                {{ pagination.total }} {{ pagination.total === 1 ? 'Producto' : 'Productos' }}
            </div>
        </div>

        <!-- Grilla de productos -->
        <div class="flex-1 overflow-y-auto p-3 sm:p-5 bg-white dark:bg-secondary-900 custom-scrollbar transition-colors duration-300" style="min-height: 0;">
            <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4 animate-pulse">
                <div v-for="i in 12" :key="i" class="rounded-2xl border border-zinc-100 bg-zinc-50/50 p-4 space-y-4">
                    <div class="aspect-square bg-zinc-200 rounded-xl"></div>
                    <div class="h-3 bg-zinc-200 rounded w-2/3"></div>
                    <div class="h-5 bg-zinc-200 rounded w-full"></div>
                </div>
            </div>

            <div v-else-if="products.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                <ProductCard 
                    v-for="product in products" 
                    :key="product.id" 
                    :product="product"
                    :cart="cart"
                    :operation-type="operationType"
                    @add="(product, qty) => $emit('add-to-cart', product, qty)"
                    @update-quantity="(productId, qty, warehouseId) => $emit('update-quantity', productId, qty, warehouseId)"
                    @show-detail="(product) => $emit('show-detail', product)"
                />
            </div>

            <div v-else class="h-full flex flex-col items-center justify-center text-center px-4">
                <div class="w-16 h-16 bg-zinc-100 dark:bg-secondary-800 rounded-2xl flex items-center justify-center mb-4 shadow-sm border border-transparent dark:border-secondary-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-zinc-300 dark:text-secondary-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <p class="text-zinc-500 dark:text-secondary-400 font-bold text-base mb-1 uppercase tracking-tight">Busque productos en el catálogo</p>
                <p class="text-[11px] text-zinc-400 dark:text-secondary-500 font-bold uppercase">Verifique el almacén seleccionado o el stock actual.</p>
            </div>
        </div>

        <!-- Paginación -->
        <div v-if="pagination.last_page > 1" class="flex-shrink-0 px-3 sm:px-5 py-2.5 bg-[#f8f9fa] dark:bg-secondary-800 border-t border-zinc-200 dark:border-secondary-700 flex items-center justify-center relative transition-colors">
            <span class="text-[11px] font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-tight absolute left-5 hidden xl:inline-block">
                {{ pagination.from || 0 }} – {{ pagination.to || 0 }} de {{ pagination.total || 0 }}
            </span>

            <div class="flex items-center gap-1.5 p-1 bg-zinc-100 dark:bg-secondary-900 rounded-xl">
                <button 
                    @click="$emit('page-change', 1)"
                    :disabled="pagination.current_page === 1"
                    class="w-9 h-9 flex items-center justify-center rounded-lg bg-white dark:bg-secondary-800 shadow-sm text-zinc-400 dark:text-secondary-500 hover:text-emerald-600 dark:hover:text-emerald-400 border border-transparent hover:border-emerald-100 dark:hover:border-emerald-500/30 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m18.75 4.5-7.5 7.5 7.5 7.5m-6-15L5.25 12l7.5 7.5" />
                    </svg>
                </button>
                <button 
                    @click="$emit('page-change', pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="w-9 h-9 flex items-center justify-center rounded-lg bg-white shadow-sm text-zinc-400 hover:text-emerald-600 border border-transparent hover:border-emerald-100 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </button>
                
                <div class="flex items-center px-4 h-9 bg-emerald-500 rounded-lg shadow-lg shadow-emerald-500/20">
                    <span class="text-white text-xs font-black">
                        PÁGINA {{ pagination.current_page }} <span class="text-emerald-200 mx-1">/</span> {{ pagination.last_page }}
                    </span>
                </div>

                <button 
                    @click="$emit('page-change', pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="w-9 h-9 flex items-center justify-center rounded-lg bg-white dark:bg-secondary-800 shadow-sm text-zinc-400 dark:text-secondary-500 hover:text-emerald-600 dark:hover:text-emerald-400 border border-transparent hover:border-emerald-100 dark:hover:border-emerald-500/30 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
                <button 
                    @click="$emit('page-change', pagination.last_page)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="w-9 h-9 flex items-center justify-center rounded-lg bg-white shadow-sm text-zinc-400 hover:text-emerald-600 border border-transparent hover:border-emerald-100 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </div>

        </div>
    </div>
</template>
