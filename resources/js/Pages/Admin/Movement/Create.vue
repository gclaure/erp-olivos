<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import axios from 'axios';

const props = defineProps({
    warehouses: Array,
    types: Array,
});

const form = useForm({
    warehouse_id: '',
    type: 'entrada',
    date: new Date().toISOString().split('T')[0],
    reason: '',
    notes: '',
    items: [],
});

const productSearch = ref('');
const results = ref([]);
const isSearching = ref(false);
const resultsOpen = ref(false);

const searchProducts = debounce(async () => {
    if (!productSearch.value && results.value.length === 0) {
        // Cargar algunos productos iniciales si está vacío? O dejar vacío
    }
    
    isSearching.value = true;
    try {
        const response = await axios.get(route('admin.movements.search-products'), {
            params: {
                search: productSearch.value,
                warehouse_id: form.warehouse_id
            }
        });
        results.value = response.data;
        if (results.value.length > 0 || isSearching.value) {
            resultsOpen.value = true;
        }
    } catch (error) {
        console.error('Error buscando productos:', error);
    } finally {
        isSearching.value = false;
    }
}, 300);

watch(productSearch, () => {
    searchProducts();
});

const selectProduct = (product) => {
    if (!form.warehouse_id) {
        alert('Por favor, selecciona primero un almacén.');
        return;
    }

    const existingItem = form.items.find(item => item.product_id === product.id);
    if (existingItem) {
        existingItem.quantity++;
    } else {
        form.items.push({
            product_id: product.id,
            name: product.name,
            code: product.code,
            quantity: 1,
            stock: product.stock
        });
    }

    productSearch.value = '';
    resultsOpen.value = false;
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    form.post(route('admin.movements.store'));
};

</script>

<template>
    <Head title="Nuevo Movimiento" />

    <AdminLayout>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex-1 text-left">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white leading-tight tracking-tight">Registrar Nuevo Movimiento</h1>
                <div class="mt-4 p-4 rounded-xl bg-zinc-50 dark:bg-secondary-700/50 border-l-4 border-amber-500 flex items-start gap-4 shadow-sm max-w-3xl">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-xl">info</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Módulo de Ajustes Manuales</h4>
                        <p class="text-xs text-zinc-500 dark:text-secondary-400 mt-1 leading-relaxed">
                            Este módulo permite realizar ajustes directos al stock por motivos excepcionales como <span class="text-zinc-800 dark:text-secondary-200 font-bold italic">mermas, daños, saldos iniciales</span> o <span class="text-zinc-800 dark:text-secondary-200 font-bold italic">correcciones de inventario</span>.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.movements.index')"
                      class="px-4 py-2 text-sm font-bold text-zinc-500 dark:text-secondary-400 hover:bg-zinc-100 dark:hover:bg-secondary-700 rounded-xl transition-colors">
                    Cancelar
                </Link>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sidebar (Datos del Ajuste) -->
            <div class="space-y-6 lg:order-1">
                <div class="bg-surface dark:bg-secondary-800 rounded-2xl border border-zinc-200 dark:border-secondary-700 shadow-sm p-6 text-left">
                    <h2 class="text-lg font-black text-zinc-900 dark:text-white mb-6 uppercase tracking-widest text-center border-b border-zinc-100 dark:border-secondary-700 pb-4">Datos del Ajuste</h2>
                    
                    <div class="space-y-5">
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Almacén</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors text-lg">home</span>
                                </div>
                                <select v-model="form.warehouse_id" required
                                        class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white">
                                    <option value="" disabled>Selecciona almacén</option>
                                    <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Tipo de Movimiento</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="t in types" :key="t.value" type="button"
                                        @click="form.type = t.value"
                                        :class="[
                                            'px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all border shadow-sm',
                                            form.type === t.value 
                                                ? (t.value === 'entrada' ? 'bg-emerald-600 border-emerald-600 text-white shadow-emerald-500/20' : 'bg-rose-600 border-rose-600 text-white shadow-rose-500/20')
                                                : 'bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 text-zinc-500 dark:text-secondary-400 hover:border-zinc-300 dark:hover:border-secondary-600'
                                        ]">
                                    {{ t.label }}
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Fecha</label>
                            <input v-model="form.date" type="date" required
                                   class="w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Motivo / Concepto</label>
                            <input v-model="form.reason" type="text" required placeholder="Ej: Ajuste por merma, Saldo inicial..."
                                   class="w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest ml-1">Notas adicionales</label>
                            <textarea v-model="form.notes" rows="3" placeholder="Observaciones internas..."
                                      class="w-full px-4 py-2.5 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white shadow-sm resize-none"></textarea>
                        </div>
                    </div>

                    <div class="mt-8">
                        <button type="submit" :disabled="form.processing"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-indigo-500/20 transition-all active:scale-95 disabled:opacity-50 group">
                            <span v-if="!form.processing" class="material-symbols-outlined text-xl group-hover:rotate-12 transition-transform">task_alt</span>
                            <svg v-else class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Procesando...' : 'Registrar Movimiento' }}
                        </button>
                    </div>
                </div>

                <div v-if="form.warehouse_id && form.type" 
                     class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/30 rounded-2xl p-5 shadow-sm animate-in fade-in zoom-in duration-300">
                    <div class="flex items-start gap-4">
                        <span class="material-symbols-outlined text-amber-500 text-2xl mt-0.5">warning</span>
                        <div class="text-sm text-amber-800 dark:text-amber-200">
                            <p class="font-black uppercase tracking-tight text-zinc-900 dark:text-white">¡Atención!</p>
                            <p class="mt-1 leading-relaxed">
                                Estás registrando una <strong class="uppercase">{{ form.type }}</strong> en el inventario. Este proceso es irreversible y genera una entrada inmediata en el Kardex.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área Principal (Productos a ajustar) -->
            <div class="lg:col-span-2 space-y-6 lg:order-2">
                <div class="bg-surface dark:bg-secondary-800 rounded-2xl border border-zinc-200 dark:border-secondary-700 shadow-sm p-6 text-left min-h-[500px]">
                    <div class="mb-8">
                        <h2 class="text-xl font-black text-zinc-900 dark:text-white mb-6 uppercase tracking-tight">Productos a ajustar</h2>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-zinc-400 text-xl">search</span>
                            </div>
                            <input v-model="productSearch"
                                   type="text"
                                   placeholder="Busca por nombre o código de producto..."
                                   autocomplete="off"
                                   @focus="() => { resultsOpen = true; searchProducts(); }"
                                   @input="resultsOpen = true"
                                   class="w-full pl-12 pr-4 py-3 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm placeholder-zinc-400">
                            
                            
                            <!-- Dropdown de Resultados -->
                            <div v-if="resultsOpen && (results.length > 0 || isSearching)" 
                                 class="absolute z-[110] w-full mt-2 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 rounded-2xl shadow-2xl overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                                <div v-if="isSearching" class="px-4 py-8 text-center">
                                    <svg class="animate-spin h-8 w-8 text-indigo-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <div v-else>
                                    <button v-for="p in results" :key="p.id"
                                            type="button"
                                            @click="selectProduct(p)"
                                            class="w-full text-left px-5 py-4 hover:bg-zinc-50 dark:hover:bg-secondary-800 flex items-center justify-between transition-colors border-b last:border-0 border-zinc-100 dark:border-secondary-700 group">
                                        <div class="flex flex-col">
                                            <span class="font-black text-zinc-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ p.name }}</span>
                                            <span class="text-[10px] text-zinc-400 dark:text-secondary-500 font-mono tracking-tighter">{{ p.code || 'S/C' }}</span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <div class="text-right">
                                                <p class="text-[9px] font-black uppercase text-zinc-400 dark:text-secondary-500 tracking-widest">Stock</p>
                                                <p :class="['text-xs font-bold', p.stock > 0 ? 'text-zinc-600 dark:text-secondary-300' : 'text-rose-500 font-black']">{{ p.stock }}</p>
                                            </div>
                                            <span class="material-symbols-outlined text-zinc-300 dark:text-secondary-700 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all">chevron_right</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Overlay para cerrar el menú al hacer clic fuera -->
                            <div v-if="resultsOpen" @click="resultsOpen = false" class="fixed inset-0 z-[105]"></div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="overflow-x-auto rounded-2xl border border-zinc-100 dark:border-secondary-700/50 shadow-inner bg-zinc-50/30 dark:bg-secondary-900/30">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-zinc-400 dark:text-secondary-500 font-black uppercase tracking-widest text-[10px] border-b border-zinc-100 dark:border-secondary-700">
                                        <th class="px-6 py-4 text-left">Producto Seleccionado</th>
                                        <th class="px-6 py-4 text-center w-36">Cantidad</th>
                                        <th class="px-6 py-4 text-center w-28">Stock Almacén</th>
                                        <th class="px-6 py-4 text-right w-16"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50">
                                    <tr v-for="(item, index) in form.items" :key="item.product_id"
                                        class="animate-in slide-in-from-left-2 duration-300 group">
                                        <td class="px-6 py-5">
                                            <div class="flex flex-col">
                                                <span class="font-black text-zinc-900 dark:text-white text-base tracking-tight">{{ item.name }}</span>
                                                <span class="text-[10px] text-zinc-400 dark:text-secondary-500 font-mono tracking-tighter">{{ item.code || 'S/C' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="relative group/input">
                                                <input v-model="item.quantity" 
                                                       type="number" 
                                                       min="0.0001" 
                                                       step="0.0001"
                                                       required
                                                       class="w-full px-3 py-2 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm text-center font-black dark:text-white transition-all shadow-sm">
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <div :class="[
                                                'inline-flex flex-col items-center px-3 py-1 rounded-xl border shadow-sm',
                                                item.stock > 0 ? 'bg-zinc-50 dark:bg-secondary-700/50 border-zinc-100 dark:border-secondary-600 text-zinc-600 dark:text-secondary-300' : 'bg-rose-50 dark:bg-rose-900/20 border-rose-100 dark:border-rose-800/30 text-rose-600 dark:text-rose-400'
                                            ]">
                                                <span class="text-xs font-black">{{ item.stock }}</span>
                                                <span class="text-[8px] uppercase tracking-widest font-black opacity-60">unid.</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 text-right">
                                            <button @click="removeItem(index)" type="button"
                                                    class="w-9 h-9 flex items-center justify-center rounded-xl text-rose-400 hover:text-white hover:bg-rose-500 transition-all active:scale-90 shadow-sm hover:shadow-rose-500/30 border border-rose-100 dark:border-rose-900/30">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="form.items.length === 0">
                                        <td colspan="4" class="px-6 py-24 text-center">
                                            <div class="flex flex-col items-center opacity-40">
                                                <div class="w-20 h-20 bg-zinc-50 dark:bg-secondary-700 rounded-3xl flex items-center justify-center mb-4 border-2 border-dashed border-zinc-200 dark:border-secondary-600">
                                                    <span class="material-symbols-outlined text-5xl text-zinc-300 dark:text-secondary-600">shopping_basket</span>
                                                </div>
                                                <p class="text-zinc-500 dark:text-secondary-400 font-black uppercase tracking-widest text-sm">No has agregado productos todavía</p>
                                                <p class="text-xs text-zinc-400 dark:text-secondary-500 mt-2">Utiliza el buscador superior para añadir ítems al ajuste.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>

<style scoped>
/* Transiciones suaves */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
}

/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}
</style>
