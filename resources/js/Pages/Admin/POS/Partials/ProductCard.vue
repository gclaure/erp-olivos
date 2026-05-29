<script setup>
import { computed } from 'vue';

const props = defineProps({
    product: Object,
    operationType: String,
});

const emit = defineEmits(['add']);

const availableQty = computed(() => {
    const physical = parseFloat(props.product.stocks?.[0]?.quantity || 0);
    const reserved = parseFloat(props.product.reserved_quantity || 0);
    return Math.max(0, physical - reserved);
});

const hasStock = computed(() => availableQty.value > 0);
</script>

<template>
    <div 
        @click="(hasStock || operationType === 'consumption') ? $emit('add', product) : null"
        :title="operationType === 'consumption' ? `${product.name}\nStock disponible: ${Math.floor(availableQty)}` : `${product.name}\nPrecio: ${parseFloat(product.price).toFixed(2)} Bs.\nStock disponible: ${Math.floor(availableQty)}`"
        class="relative rounded-xl sm:rounded-2xl overflow-hidden flex flex-col transition-all duration-300 group bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 hover:border-emerald-500 dark:hover:border-emerald-400 hover:shadow-lg hover:shadow-emerald-500/10 cursor-pointer"
        :class="{ 'opacity-60 cursor-not-allowed': !hasStock && operationType !== 'consumption' }"
    >
        <!-- Imagen del producto -->
        <div class="aspect-[4/3] flex items-center justify-center p-2 sm:p-4 relative overflow-hidden transition-colors duration-300 bg-gradient-to-b from-zinc-50 via-white to-zinc-50 dark:from-secondary-900 dark:via-secondary-800 dark:to-secondary-900">
            <!-- Efecto de luz radial sutil para modo oscuro -->
            <div class="absolute inset-0 dark:bg-[radial-gradient(circle_at_50%_50%,rgba(16,185,129,0.05),transparent_70%)] pointer-events-none"></div>

            <img 
                v-if="product.image_path" 
                :src="product.image_path" 
                :alt="product.name" 
                class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300 z-10" 
                loading="lazy"
            >
            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-200 dark:text-secondary-700 z-10">
                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.125a3.375 3.375 0 0 1-3.375 3.375H7.75a3.375 3.375 0 0 1-3.375-3.375L3.75 7.5m16.5 0-1.25-2.25a3.375 3.375 0 0 0-3-1.5H8a3.375 3.375 0 0 0-3 1.5L3.75 7.5m16.5 0h-16.5" />
            </svg>
            
            <!-- Overlay hover con botón + (solo productos con stock disponible, o cualquiera si es consumo interno) -->
            <div 
                v-if="hasStock || operationType === 'consumption'"
                class="hidden lg:flex absolute inset-0 bg-secondary-900/40 backdrop-blur-[1px] opacity-0 group-hover:opacity-100 transition-all duration-300 items-center justify-center z-20"
            >
                <div class="flex flex-col items-center gap-2 transform translate-y-2 group-hover:translate-y-0 transition-all duration-300 ease-out">
                    <div class="w-12 h-12 bg-emerald-600 dark:bg-emerald-500 text-white rounded-full shadow-lg flex items-center justify-center transition-transform hover:scale-110 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <span class="px-3 py-1 bg-emerald-600 dark:bg-emerald-500 text-white text-[9px] font-black uppercase tracking-widest rounded-lg shadow-lg">
                        Agregar
                    </span>
                </div>
            </div>

            <!-- Badge Stock -->
            <div class="absolute top-2 right-2 sm:top-3 sm:right-3 z-10">
                <div 
                    :class="hasStock ? 'bg-emerald-600' : 'bg-rose-500'"
                    class="text-white w-6 h-6 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-xs sm:text-sm font-black shadow-md border-2 border-white dark:border-secondary-800"
                >
                    {{ Math.floor(availableQty) }}
                </div>
            </div>

            <!-- Badge Reservado -->
            <div v-if="parseFloat(product.reserved_quantity) > 0" class="absolute top-2 left-2 pointer-events-none">
                <span class="px-1.5 py-0.5 bg-orange-500 text-white text-[8px] font-black uppercase rounded shadow-sm border border-orange-400">
                    Res: {{ Math.floor(product.reserved_quantity) }}
                </span>
            </div>
        </div>

        <!-- Info del producto -->
        <div class="px-2 py-2 sm:px-3 sm:py-3 flex flex-col flex-1 border-t border-zinc-100 dark:border-secondary-700 transition-colors duration-300">
            <div class="flex items-center justify-between mb-1">
                <span class="text-[9px] sm:text-[10px] font-mono text-zinc-400 dark:text-secondary-500 uppercase truncate">{{ product.code }}</span>
            </div>
            <h3 class="text-xs sm:text-sm font-semibold text-zinc-900 dark:text-secondary-50 leading-snug mb-1 line-clamp-2 uppercase">{{ product.name }}</h3>
            


            <div v-if="operationType !== 'consumption'" class="mt-auto flex items-center justify-between">
                <span class="text-sm sm:text-lg font-extrabold text-emerald-600 uppercase leading-none">Bs. {{ parseFloat(product.price).toFixed(2) }}</span>
            </div>
        </div>
    </div>
</template>
