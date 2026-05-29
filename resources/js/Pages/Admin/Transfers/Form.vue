<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    transfer: Object,
    items: Array,
    originWarehouses: Array,
    destinationWarehouses: Array,
});

const form = useForm({
    origin_warehouse_id: props.transfer?.origin_warehouse_id || '',
    destination_warehouse_id: props.transfer?.destination_warehouse_id || '',
    notes: props.transfer?.notes || '',
    items: props.items || [],
    is_request: false,
});

const productSearch = ref('');
const results = ref([]);
const isSearching = ref(false);

const searchProducts = debounce(async () => {
    if (!form.origin_warehouse_id) {
        results.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const response = await axios.get(route('admin.transfers.search-products'), {
            params: {
                search: productSearch.value,
                warehouse_id: form.origin_warehouse_id
            }
        });
        results.value = response.data;
    } catch (error) {
        console.error('Error searching products:', error);
    } finally {
        isSearching.value = false;
    }
}, 300);

watch(productSearch, () => {
    searchProducts();
});

const selectProduct = (product) => {
    if (!form.origin_warehouse_id) {
        Swal.fire({
            icon: 'warning',
            title: 'Almacén Origen requerido',
            text: 'Selecciona primero el almacén de origen para validar stock.',
            confirmButtonColor: '#7c3aed',
        });
        return;
    }

    if (product.available_stock <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Sin Stock',
            text: `El producto ${product.name} no tiene stock disponible en este almacén.`,
            confirmButtonColor: '#7c3aed',
        });
        return;
    }

    const existingIndex = form.items.findIndex(item => item.id === product.id);
    if (existingIndex !== -1) {
        form.items[existingIndex].quantity++;
    } else {
        form.items.push({
            id: product.id,
            name: product.name,
            code: product.code,
            quantity: 1,
            stock: product.available_stock
        });
    }

    productSearch.value = '';
    results.value = [];
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = (isRequest) => {
    form.is_request = isRequest;
    
    if (props.transfer) {
        form.put(route('admin.transfers.update', props.transfer.id), {
            onSuccess: () => {
                // Éxito
            },
        });
    } else {
        form.post(route('admin.transfers.store'), {
            onSuccess: () => {
                // Éxito
            },
        });
    }
};

const summaryVisible = computed(() => form.origin_warehouse_id && form.destination_warehouse_id);

// Validación de almacenes iguales
watch(() => [form.origin_warehouse_id, form.destination_warehouse_id], ([origin, dest]) => {
    if (origin && dest && origin === dest) {
        form.destination_warehouse_id = '';
        form.clearErrors('destination_warehouse_id');
        
        Swal.fire({
            icon: 'error',
            title: 'Movimiento Inválido',
            text: 'El almacén de origen y destino no pueden ser el mismo.',
            confirmButtonColor: '#7c3aed',
        });
    }
});

// Si cambia el almacén de origen, reseteamos los items porque el stock ya no es válido
watch(() => form.origin_warehouse_id, (newVal, oldVal) => {
    if (oldVal && form.items.length > 0) {
        Swal.fire({
            title: '¿Cambiar Almacén de Origen?',
            text: "Se eliminarán los productos seleccionados ya que el stock depende del almacén de origen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#7c3aed',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.items = [];
            } else {
                form.origin_warehouse_id = oldVal;
            }
        });
    }
});
</script>

<template>
    <Head :title="transfer ? 'Editar Transferencia' : 'Nueva Transferencia'" />

    <AdminLayout>
        <template #breadcrumbs>
            <div class="flex items-center gap-2 text-sm text-zinc-500">
                <Link :href="route('admin.dashboard')" class="hover:text-violet-600 transition-colors">Dashboard</Link>
                <span>/</span>
                <Link :href="route('admin.transfers.index')" class="hover:text-violet-600 transition-colors">Transferencias</Link>
                <span>/</span>
                <span class="text-zinc-900 dark:text-zinc-100 font-medium">{{ transfer ? 'Editar' : 'Nueva' }}</span>
            </div>
        </template>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.transfers.index')" 
                      class="p-2 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-all">
                    <span class="material-symbols-outlined text-[24px]">arrow_back</span>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ transfer ? 'Editar Transferencia' : 'Nueva Solicitud de Transferencia' }}</h1>
                    <p class="text-sm text-zinc-500 mt-1">Prepara el movimiento de productos entre almacenes</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <!-- Sidebar: Configuración -->
            <div class="space-y-6">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white mb-6 flex items-center gap-2">
                        <div class="p-1.5 bg-violet-50 dark:bg-violet-900/20 rounded-lg text-violet-600">
                            <span class="material-symbols-outlined text-[20px]">home</span>
                        </div>
                        Configuración
                    </h2>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-2">Almacén Origen</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                    <span class="material-symbols-outlined text-[18px]">home</span>
                                </div>
                                <select v-model="form.origin_warehouse_id" 
                                        class="block w-full pl-10 pr-3 py-2.5 border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all outline-none">
                                    <option value="">Selecciona origen</option>
                                    <option v-for="w in originWarehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                                </select>
                            </div>
                            <p v-if="form.errors.origin_warehouse_id" class="text-xs text-rose-500 mt-1">{{ form.errors.origin_warehouse_id }}</p>
                        </div>

                        <div class="flex justify-center -my-3 relative z-10">
                            <div class="w-8 h-8 rounded-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-400 shadow-sm">
                                <span class="material-symbols-outlined text-[16px]">arrow_downward</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-2">Almacén Destino</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                    <span class="material-symbols-outlined text-[18px]">location_on</span>
                                </div>
                                <select v-model="form.destination_warehouse_id" 
                                        class="block w-full pl-10 pr-3 py-2.5 border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all outline-none">
                                    <option value="">Selecciona destino</option>
                                    <option v-for="w in destinationWarehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                                </select>
                            </div>
                            <p v-if="form.errors.destination_warehouse_id" class="text-xs text-rose-500 mt-1">{{ form.errors.destination_warehouse_id }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-2">Notas / Observaciones</label>
                            <textarea v-model="form.notes" rows="3" placeholder="Motivo de la transferencia..."
                                      class="block w-full px-4 py-2.5 border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all outline-none resize-none"></textarea>
                        </div>
                    </div>

                    <div class="mt-8 space-y-3">
                        <button @click="submit(true)" :disabled="form.processing"
                                class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-violet-500/25 disabled:opacity-50">
                            <span v-if="!form.processing" class="material-symbols-outlined text-[20px]">check</span>
                            <span v-else class="animate-spin w-5 h-5 border-2 border-white/30 border-t-white rounded-full"></span>
                            Confirmar y Solicitar
                        </button>
                        <button @click="submit(false)" :disabled="form.processing"
                                class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 text-sm font-bold rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all disabled:opacity-50">
                            <span class="material-symbols-outlined text-[20px]">bookmark</span>
                            Guardar Borrador
                        </button>
                    </div>
                </div>

                <div v-if="summaryVisible" class="p-4 bg-violet-50 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-900/30 rounded-2xl">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-[20px] text-violet-500 mt-0.5">info</span>
                        <div class="text-sm text-violet-800 dark:text-violet-400">
                            <p class="font-bold">Resumen del movimiento</p>
                            <p class="mt-1">
                                Estás solicitando mover <strong>{{ form.items.length }}</strong> producto(s) desde el inventario central.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área Principal: Selección de Productos -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white mb-4">Productos a transferir</h2>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                <span class="material-symbols-outlined text-[20px]">search</span>
                            </div>
                            <input v-model="productSearch" type="text" 
                                   :disabled="!form.origin_warehouse_id"
                                   @focus="searchProducts"
                                   @click="searchProducts"
                                   :placeholder="!form.origin_warehouse_id ? 'Selecciona almacén origen primero...' : 'Busca por nombre o código de producto...'"
                                   class="block w-full pl-10 pr-3 py-3 border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all outline-none" />
                            
                            <div v-if="results.length > 0" class="absolute z-50 w-full mt-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl overflow-y-auto max-h-[380px] divide-y divide-zinc-100 dark:divide-zinc-800 custom-scrollbar">
                                <button v-for="product in results" :key="product.id"
                                        @click="selectProduct(product)"
                                        class="w-full text-left px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 flex items-center justify-between transition-colors border-b last:border-0 border-zinc-100 dark:border-zinc-800 group">
                                    <div class="flex flex-col text-left">
                                        <span class="font-bold text-zinc-900 dark:text-white group-hover:text-violet-600 transition-colors">{{ product.name }}</span>
                                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ product.code }}</span>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Stock: {{ Number(product.available_stock || 0).toFixed(2) }}</span>
                                        <span class="text-[10px] font-bold px-2.5 py-1 bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 rounded-lg">Seleccionar</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.items" class="text-xs text-rose-500 mt-2">{{ form.errors.items }}</p>
                    </div>

                    <!-- Tabla de Items -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-widest text-[10px] border-b border-zinc-200 dark:border-zinc-800">
                                    <th class="pb-4 text-left">Producto</th>
                                    <th class="pb-4 text-center w-28">Cantidad</th>
                                    <th class="pb-4 text-center w-28">Stock Origen</th>
                                    <th class="pb-4 text-center w-28">Stock Final</th>
                                    <th class="pb-4 text-right w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <tr v-for="(item, index) in form.items" :key="item.id" class="group">
                                    <td class="py-5">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-zinc-900 dark:text-white">{{ item.name }}</span>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ item.code }}</span>
                                        </div>
                                    </td>
                                    <td class="py-5 px-4 text-center">
                                        <input v-model="item.quantity" type="number" min="0.0001" step="0.0001"
                                               class="w-24 px-3 py-2 text-center border border-zinc-200 dark:border-zinc-800 rounded-lg bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all outline-none" />
                                    </td>
                                    <td class="py-5 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="px-3 py-1 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 rounded-lg text-xs font-bold">
                                                {{ Number(item.stock).toFixed(2) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-5 text-center">
                                        <div class="flex flex-col items-center">
                                            <span :class="[
                                                'px-3 py-1 rounded-lg text-xs font-black transition-colors',
                                                (item.stock - item.quantity) < 0 
                                                    ? 'bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/30' 
                                                    : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/30'
                                            ]">
                                                {{ Number(item.stock - item.quantity).toFixed(2) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-5 text-right">
                                        <button @click="removeItem(index)" 
                                                class="p-2 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-all">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="form.items.length === 0">
                                    <td colspan="4" class="py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 bg-zinc-50 dark:bg-zinc-800 rounded-2xl flex items-center justify-center mb-4 border border-zinc-100 dark:border-zinc-700 shadow-inner">
                                                <span class="material-symbols-outlined text-[32px] text-zinc-300 dark:text-zinc-600">shopping_cart</span>
                                            </div>
                                            <p class="font-bold text-zinc-400 dark:text-zinc-500">No has agregado productos todavía.</p>
                                            <p class="text-xs text-zinc-400 mt-1">Utiliza el buscador superior para añadir ítems.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
