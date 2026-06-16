<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import axios from 'axios';

const props = defineProps({
    warehouses: Array,
    providers: Array,
    preloadedItems: {
        type: Array,
        default: () => []
    },
    fromConsumptionIds: {
        type: Array,
        default: () => []
    },
    defaultWarehouseId: {
        type: String,
        default: ''
    }
});

const form = useForm({
    provider_id: '',
    warehouse_id: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
    details: [],
    from_consumption_id: '',
    from_consumption_ids: [],
});

// Selectores & Dropdowns
const productSearch = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const showDropdown = ref(false);

const providerSearch = ref('');
const providerSearchResults = ref([]);
const isSearchingProviders = ref(false);
const showProviderDropdown = ref(false);

// Proveedores
const searchProviders = async (query = '') => {
    isSearchingProviders.value = true;
    try {
        const response = await axios.get(route('admin.api.selects.providers'), {
            params: { search: query }
        });
        providerSearchResults.value = response.data;
        showProviderDropdown.value = true;
    } catch (error) {
        console.error('Error buscando proveedores:', error);
    } finally {
        isSearchingProviders.value = false;
    }
};

const debouncedProviderSearch = debounce((val) => {
    searchProviders(val);
}, 300);

watch(providerSearch, (newVal) => {
    if (newVal === '') {
        form.provider_id = '';
    }
    debouncedProviderSearch(newVal);
});

const selectProvider = (p) => {
    form.provider_id = p.id;
    providerSearch.value = p.name;
    showProviderDropdown.value = false;
};



// Productos
const searchProducts = async (query = '') => {
    isSearching.value = true;
    try {
        const response = await axios.get(route('admin.api.selects.products'), {
            params: { search: query }
        });
        searchResults.value = response.data;
        showDropdown.value = true;
    } catch (error) {
        console.error('Error buscando productos:', error);
    } finally {
        isSearching.value = false;
    }
};

const debouncedSearch = debounce((val) => {
    searchProducts(val);
}, 300);

watch(productSearch, (newVal) => {
    debouncedSearch(newVal);
});

const subtotal = computed(() => {
    return form.details.reduce((acc, item) => 
        acc + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0), 0
    );
});

const total = computed(() => subtotal.value);

const addProduct = (product) => {
    if (form.details.some(d => d.product_id === product.id)) {
        productSearch.value = '';
        showDropdown.value = false;
        return;
    }

    form.details.push({
        product_id: product.id,
        name: product.name,
        code: product.code,
        quantity: 1,
        unit_price: parseFloat(product.price) || 0,
        unit: product.unit || 'UND',
        package_name: product.package_name || '',
        units_per_package: parseFloat(product.units_per_package) || 1,
        purchase_format: product.package_name ? 'package' : 'base',
        warehouses: product.warehouses || [],
    });

    productSearch.value = '';
    searchResults.value = [];
    showDropdown.value = false;
};

const removeProduct = (index) => {
    form.details.splice(index, 1);
};

const submit = () => {
    form.transform((data) => {
        const transformedDetails = data.details.map(item => {
            const isPackage = item.purchase_format === 'package' && item.package_name;
            const factor = parseFloat(item.units_per_package) || 1;
            
            const finalQty = isPackage 
                ? (parseFloat(item.quantity) * factor) 
                : parseFloat(item.quantity);
                
            const finalPrice = isPackage 
                ? (parseFloat(item.unit_price) / factor) 
                : parseFloat(item.unit_price);
                
            return {
                product_id: item.product_id,
                quantity: parseFloat(finalQty.toFixed(4)),
                unit_price: parseFloat(finalPrice.toFixed(4)),
            };
        });

        return {
            ...data,
            details: transformedDetails
        };
    }).post(route('admin.purchase-orders.store'));
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

// Close dropdown on click outside
const handleClickOutside = (event) => {
    if (!event.target.closest('.product-search-container')) {
        showDropdown.value = false;
    }
    if (!event.target.closest('.provider-search-container')) {
        showProviderDropdown.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
    // Cargar proveedores iniciales
    searchProviders('');

    // Cargar desde Inertia props si es una consolidación
    if (props.fromConsumptionIds && props.fromConsumptionIds.length > 0) {
        form.from_consumption_ids = props.fromConsumptionIds;
    }

    if (props.preloadedItems && props.preloadedItems.length > 0) {
        form.details = props.preloadedItems.map(item => ({
            product_id: item.product_id,
            name: item.product_name,
            code: item.product_code,
            quantity: item.quantity,
            unit_price: item.unit_price,
            unit: item.unit || 'UND',
            package_name: item.package_name || '',
            units_per_package: parseFloat(item.units_per_package) || 1,
            purchase_format: item.package_name ? 'package' : 'base',
            warehouses: item.warehouses || [],
        }));
    }

    if (props.defaultWarehouseId) {
        form.warehouse_id = props.defaultWarehouseId;
    }

    // Cargar parámetros de URL si viene desde una solicitud de consumo interno
    const params = new URLSearchParams(window.location.search);
    const fromConsumptionId = params.get('from_consumption_id');
    const urlWarehouseId = params.get('warehouse_id');
    const urlItemsRaw = params.get('items');

    if (fromConsumptionId) {
        form.from_consumption_id = fromConsumptionId;
    }

    if (urlWarehouseId) {
        form.warehouse_id = urlWarehouseId;
    }

    if (urlItemsRaw) {
        try {
            const parsedItems = JSON.parse(urlItemsRaw);
            form.details = parsedItems.map(item => ({
                product_id: item.product_id,
                name: item.product_name,
                code: item.product_code,
                quantity: item.quantity,
                unit_price: item.unit_price,
                warehouses: item.warehouses || [],
            }));
        } catch (error) {
            console.error('Error al precargar los productos faltantes de consumo:', error);
        }
    }
});

onUnmounted(() => {
    window.removeEventListener('click', handleClickOutside);
});

</script>

<template>
    <Head title="Crear Solicitud de Compra" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div class="text-left">
                <h1 class="text-2xl font-black text-zinc-900 dark:text-white leading-tight tracking-tight uppercase">Crear Solicitud de Compra</h1>
                <p class="text-sm text-zinc-500 dark:text-secondary-400 mt-1">Registra una nueva solicitud interna de compra para aprobación administrativa.</p>
            </div>
            <Link :href="route('admin.purchase-orders.index')" 
                  class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-white dark:bg-secondary-800 text-zinc-600 dark:text-secondary-300 text-xs font-black uppercase tracking-widest rounded-2xl border border-zinc-200 dark:border-secondary-700 hover:bg-zinc-50 dark:hover:bg-secondary-700 transition-all shadow-sm active:scale-95">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Volver
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Global Errors -->
            <div v-if="Object.keys(form.errors).length > 0" 
                 class="p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-2xl flex items-start gap-3 text-rose-600 dark:text-rose-400 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300">
                <span class="material-symbols-outlined text-xl">error</span>
                <div class="flex-1 text-xs font-black uppercase tracking-widest leading-relaxed">
                    Hay errores en el formulario. Por favor revisa los campos remarcados antes de proceder.
                    <ul class="list-disc pl-4 mt-2">
                        <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                    </ul>
                </div>
            </div>

            <!-- 1. Datos Básicos -->
            <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 shadow-sm relative">
                <div class="h-1.5 w-full bg-gradient-to-r from-zinc-400 to-zinc-500 rounded-t-3xl"></div>
                <div class="p-5 border-b border-zinc-100 dark:border-secondary-700 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-900/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 flex items-center justify-center shadow-inner">
                            <span class="material-symbols-outlined">description</span>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-zinc-900 dark:text-white uppercase tracking-tight">1. Datos Básicos</h2>
                            <p class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Establezca la fecha y el almacén de destino de la solicitud</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Fecha -->
                        <div>
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">Fecha *</label>
                            <input v-model="form.date" type="date" required
                                   :class="{'border-rose-500 ring-4 ring-rose-500/10': form.errors.date}"
                                   class="w-full px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-secondary-700 rounded-2xl text-sm font-semibold text-zinc-900 dark:text-white focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500 transition-all">
                        </div>

                    </div>

                    <!-- Notas -->
                    <div class="mt-6">
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">Notas / Observaciones de la Solicitud</label>
                        <textarea v-model="form.notes" rows="2"
                                  placeholder="Indique justificación de la compra, urgencias u otras notas relevantes..."
                                  class="w-full px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-secondary-700 rounded-2xl text-sm font-semibold text-zinc-900 dark:text-white focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500 transition-all"></textarea>
                    </div>
                </div>
            </div>

            <!-- 2. Detalle de Solicitud -->
            <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 shadow-sm relative">
                <div class="h-1.5 w-full bg-gradient-to-r from-zinc-400 to-zinc-500 rounded-t-3xl"></div>
                <div class="p-5 border-b border-zinc-100 dark:border-secondary-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-zinc-50/50 dark:bg-zinc-900/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 flex items-center justify-center shadow-inner">
                            <span class="material-symbols-outlined">playlist_add</span>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-zinc-900 dark:text-white uppercase tracking-tight">2. Productos Solicitados</h2>
                            <p class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Añada los productos y cantidades estimadas a solicitar</p>
                        </div>
                    </div>

                    <!-- Product Search -->
                    <div class="relative product-search-container w-full sm:w-80">
                        <div class="relative">
                            <input v-model="productSearch" type="text"
                                   placeholder="Buscar producto por nombre o código..."
                                   @focus="showDropdown = true"
                                   class="w-full pl-11 pr-4 py-2.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-secondary-700 rounded-2xl text-xs font-semibold text-zinc-900 dark:text-white focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500 transition-all">
                            <span class="material-symbols-outlined absolute left-4 top-2 text-zinc-400 text-lg">search</span>
                        </div>

                        <!-- Dropdown Productos -->
                        <div v-if="showDropdown && searchResults.length > 0"
                             class="absolute right-0 z-50 w-full mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-2xl shadow-xl overflow-hidden max-h-60 overflow-y-auto animate-in fade-in duration-150">
                            <button type="button" v-for="product in searchResults" :key="product.id"
                                    @click="addProduct(product)"
                                    class="w-full px-4 py-3 text-left hover:bg-zinc-50 dark:hover:bg-zinc-950 flex items-center justify-between text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-secondary-700 last:border-0">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold">{{ product.name }}</span>
                                    <span class="text-[10px] font-semibold text-zinc-400 mt-0.5">{{ product.code }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 rounded text-zinc-600 dark:text-zinc-300">Bs. {{ formatNumber(product.price) }}</span>
                                    <span class="material-symbols-outlined text-zinc-400">add</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-zinc-50/50 dark:bg-zinc-900/30 border-b border-zinc-100 dark:border-secondary-700">
                                <tr class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">
                                    <th class="px-6 py-4">Código</th>
                                    <th class="px-6 py-4">Producto</th>
                                    <th class="px-6 py-4 text-center">Almacén</th>
                                    <th class="px-6 py-4 w-44 text-center">Formato de Compra</th>
                                    <th class="px-6 py-4 text-center" style="width: 130px;">Cantidad</th>
                                    <th class="px-6 py-4 text-center" style="width: 150px;">Costo Unitario (Est.)</th>
                                    <th class="px-6 py-4 text-right">Subtotal</th>
                                    <th class="px-6 py-4 text-center" style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700">
                                <tr v-for="(item, index) in form.details" :key="item.product_id"
                                    class="hover:bg-zinc-50/50 dark:hover:bg-secondary-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-zinc-400 dark:text-secondary-400">
                                        {{ item.code }}
                                    </td>
                                    <td class="px-6 py-4 font-black text-zinc-900 dark:text-white">
                                        {{ item.name }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div v-if="item.warehouses && item.warehouses.length > 0" class="flex flex-wrap justify-center items-center gap-1.5">
                                            <span v-for="wName in item.warehouses" :key="wName"
                                                  class="inline-flex items-center gap-1 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/30 rounded-lg border border-indigo-100 dark:border-indigo-800/10"
                                                  title="Almacén de registro">
                                                <span class="material-symbols-outlined text-[10px]">warehouse</span>
                                                {{ wName }}
                                            </span>
                                        </div>
                                        <span v-else class="text-zinc-400 dark:text-zinc-600">—</span>
                                    </td>
                                    <!-- Formato de Compra -->
                                    <td class="px-6 py-4">
                                        <div v-if="item.package_name && item.units_per_package > 0">
                                            <select v-model="item.purchase_format" class="w-full min-w-[140px] px-3 py-2 bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 rounded-xl text-xs font-black uppercase tracking-widest text-center cursor-pointer transition-colors duration-200">
                                                <option value="base">{{ item.unit }} (Base)</option>
                                                <option value="package">{{ item.package_name }} ({{ parseFloat(item.units_per_package) }} {{ item.unit }})</option>
                                            </select>
                                        </div>
                                        <div v-else class="px-3 py-2 bg-zinc-100/50 dark:bg-secondary-800 text-zinc-400 dark:text-secondary-500 rounded-xl text-[10px] font-black uppercase tracking-widest text-center border border-dashed border-zinc-200 dark:border-secondary-700">
                                            {{ item.unit }} (Base)
                                        </div>
                                    </td>
                                    <!-- Cantidad -->
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="flex items-center justify-center border border-zinc-200 dark:border-secondary-700 rounded-2xl overflow-hidden bg-zinc-50 dark:bg-zinc-900/50 w-28 mx-auto">
                                                <button type="button" @click="item.quantity > 1 ? item.quantity-- : null"
                                                        class="px-2.5 py-1.5 hover:bg-zinc-100 dark:hover:bg-secondary-700 text-zinc-500 dark:text-secondary-400 transition-colors flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-xs">remove</span>
                                                </button>
                                                <input type="number" step="any" v-model.number="item.quantity" min="0.0001" required
                                                       class="w-full text-center border-0 bg-transparent text-xs font-black text-zinc-950 dark:text-white p-0 focus:ring-0">
                                                <button type="button" @click="item.quantity++"
                                                        class="px-2.5 py-1.5 hover:bg-zinc-100 dark:hover:bg-secondary-700 text-zinc-500 dark:text-secondary-400 transition-colors flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-xs">add</span>
                                                </button>
                                            </div>
                                            <span class="text-[9px] font-black text-zinc-400 uppercase tracking-widest mt-1">
                                                {{ item.purchase_format === 'package' ? item.package_name : item.unit }}
                                            </span>
                                        </div>
                                    </td>
                                    <!-- Costo Unitario -->
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="relative w-32 mx-auto">
                                                <input type="number" step="any" v-model.number="item.unit_price" min="0" required
                                                       class="w-full pl-7 pr-3 py-1.5 bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-secondary-700 rounded-2xl text-center text-xs font-black text-zinc-900 dark:text-white focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500 transition-all">
                                                <span class="absolute left-3 top-2 text-[10px] font-black text-zinc-400">Bs</span>
                                            </div>
                                            <span class="text-[9px] font-black text-zinc-400 uppercase tracking-widest mt-1">
                                                por {{ item.purchase_format === 'package' ? item.package_name : item.unit }}
                                            </span>
                                        </div>
                                    </td>
                                    <!-- Subtotal -->
                                    <td class="px-6 py-4 text-right font-black text-zinc-950 dark:text-white text-xs">
                                        Bs. {{ formatNumber((item.quantity || 0) * (item.unit_price || 0)) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button type="button" @click="removeProduct(index)"
                                                class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-600 hover:text-white text-rose-600 dark:text-rose-400 flex items-center justify-center border border-rose-100 dark:border-rose-900/20 active:scale-95 transition-all mx-auto"
                                                title="Quitar">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="form.details.length === 0">
                                    <td colspan="7" class="px-6 py-24 text-center">
                                        <div class="flex flex-col items-center opacity-40">
                                            <span class="material-symbols-outlined text-4xl text-zinc-300 dark:text-zinc-600 mb-3">list_alt</span>
                                            <p class="text-zinc-500 dark:text-zinc-400 font-black uppercase tracking-widest text-[10px]">Agregue productos a la solicitud utilizando el buscador</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer Totales -->
                <div v-if="form.details.length > 0" 
                     class="p-8 border-t border-zinc-100 dark:border-secondary-700 bg-zinc-50/50 dark:bg-zinc-900/50 rounded-b-3xl">
                    <div class="flex flex-col sm:flex-row justify-end items-center gap-6">
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Total Solicitado (Est.):</span>
                            <span class="text-xl font-black text-zinc-950 dark:text-white">Bs. {{ formatNumber(total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones Guardar -->
            <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end pt-2">
                <Link :href="route('admin.purchase-orders.index')"
                      class="px-6 py-3.5 bg-white dark:bg-secondary-800 text-zinc-600 dark:text-secondary-300 text-xs font-black uppercase tracking-widest rounded-2xl border border-zinc-200 dark:border-secondary-700 hover:bg-zinc-50 dark:hover:bg-secondary-700 transition-all shadow-sm active:scale-95 text-center">
                    Cancelar
                </Link>
                <button type="submit" :disabled="form.processing || form.details.length === 0"
                        class="px-8 py-3.5 bg-indigo-600 dark:bg-indigo-500 text-white text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-all shadow-md active:scale-95 disabled:opacity-50 disabled:pointer-events-none text-center">
                    Registrar Solicitud
                </button>
            </div>
        </form>
    </AdminLayout>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
