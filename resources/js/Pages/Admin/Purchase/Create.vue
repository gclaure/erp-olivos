<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import axios from 'axios';

const props = defineProps({
    warehouses: Array,
});

const form = useForm({
    provider_id: '',
    warehouse_id: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
    voucher_type: 'sin_factura',
    payment_type: 'contado',
    due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    down_payment: 0,
    details: [],
});

// Search Logic
const productSearch = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const showDropdown = ref(false);
const showWarehouseDropdown = ref(false);
const showVoucherDropdown = ref(false);
const showPaymentDropdown = ref(false);

const voucherTypes = [
    { value: 'factura', label: 'Con Factura', icon: 'receipt_long' },
    { value: 'sin_factura', label: 'Sin Factura', icon: 'receipt' },
];

const paymentTypes = [
    { value: 'contado', label: 'Al Contado', icon: 'payments' },
    { value: 'credito', label: 'A Crédito', icon: 'credit_card' },
];

const selectedVoucherLabel = computed(() => {
    return voucherTypes.find(v => v.value === form.voucher_type)?.label || 'Seleccionar...';
});

const selectedPaymentLabel = computed(() => {
    return paymentTypes.find(p => p.value === form.payment_type)?.label || 'Seleccionar...';
});

const selectVoucher = (type) => {
    form.voucher_type = type;
    showVoucherDropdown.value = false;
};

const selectPayment = (type) => {
    form.payment_type = type;
    showPaymentDropdown.value = false;
};

// Provider Search
const providerSearch = ref('');
const providerSearchResults = ref([]);
const isSearchingProviders = ref(false);
const showProviderDropdown = ref(false);

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
    debouncedProviderSearch(newVal);
});

const selectProvider = (p) => {
    form.provider_id = p.id;
    providerSearch.value = p.name;
    showProviderDropdown.value = false;
};

const selectedWarehouseName = computed(() => {
    const wh = props.warehouses.find(w => w.id === form.warehouse_id);
    return wh ? wh.name : 'Seleccionar almacén...';
});

const selectWarehouse = (wh) => {
    form.warehouse_id = wh.id;
    showWarehouseDropdown.value = false;
};

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
        has_expiration: Boolean(product.has_expiration),
        expiration_date: '',
        
        // Atributos de empaque y unidad
        unit: product.unit || 'UND',
        package_name: product.package_name || '',
        units_per_package: parseFloat(product.units_per_package) || 1,
        purchase_format: product.package_name ? 'package' : 'base',
    });

    productSearch.value = '';
    searchResults.value = [];
    showDropdown.value = false;
};

const removeProduct = (index) => {
    form.details.splice(index, 1);
};

const hasExpirationErrors = computed(() =>
    form.details.some(item => item.has_expiration && !item.expiration_date)
);

const submit = () => {
    if (hasExpirationErrors.value) return;

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
                has_expiration: item.has_expiration,
                expiration_date: item.has_expiration ? item.expiration_date : null,
            };
        });

        return {
            ...data,
            details: transformedDetails
        };
    }).post(route('admin.purchases.store'));
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
    if (!event.target.closest('.warehouse-select-container')) {
        showWarehouseDropdown.value = false;
    }
    if (!event.target.closest('.provider-search-container')) {
        showProviderDropdown.value = false;
    }
    if (!event.target.closest('.voucher-select-container')) {
        showVoucherDropdown.value = false;
    }
    if (!event.target.closest('.payment-select-container')) {
        showPaymentDropdown.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    window.removeEventListener('click', handleClickOutside);
});

</script>

<template>
    <Head title="Registrar Compra" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div class="text-left">
                <h1 class="text-2xl font-black text-zinc-900 dark:text-white leading-tight tracking-tight uppercase">Registrar Compra</h1>
                <p class="text-sm text-zinc-500 dark:text-secondary-400 mt-1">Ingresa los datos para registrar una nueva entrada de productos.</p>
            </div>
            <Link :href="route('admin.purchases.index')" 
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
                            <p class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Establezca el proveedor, fecha y destino</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div class="lg:col-span-1">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">Fecha *</label>
                            <input v-model="form.date" type="date" required
                                   :class="{'border-rose-500 ring-4 ring-rose-500/10': form.errors.date}"
                                   class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm font-medium">
                            <div v-if="form.errors.date" class="mt-2 ml-1 text-[10px] font-black text-rose-500 uppercase tracking-widest animate-in fade-in slide-in-from-top-1">
                                {{ form.errors.date }}
                            </div>
                        </div>

                        <div class="lg:col-span-1 relative warehouse-select-container">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">Almacén Destino *</label>
                            <div @click="showWarehouseDropdown = !showWarehouseDropdown" 
                                 :class="{'border-rose-500 ring-4 ring-rose-500/10': form.errors.warehouse_id}"
                                 class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 hover:border-indigo-500 dark:hover:border-indigo-500 rounded-2xl text-sm transition-all dark:text-white shadow-sm font-black uppercase tracking-widest text-[11px] cursor-pointer flex items-center justify-between group">
                                <span>{{ selectedWarehouseName }}</span>
                                <span class="material-symbols-outlined text-zinc-400 group-hover:text-indigo-500 transition-transform" :class="{'rotate-180': showWarehouseDropdown}">expand_more</span>
                            </div>
                            <div v-if="form.errors.warehouse_id" class="mt-2 ml-1 text-[10px] font-black text-rose-500 uppercase tracking-widest animate-in fade-in slide-in-from-top-1">
                                {{ form.errors.warehouse_id }}
                            </div>

                            <!-- Dropdown List -->
                            <div v-if="showWarehouseDropdown" 
                                 class="absolute z-50 left-0 right-0 mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                                <ul class="py-1">
                                    <li v-for="wh in warehouses" :key="wh.id"
                                        @click="selectWarehouse(wh)"
                                        class="px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer flex items-center gap-3 transition-colors text-[11px] font-black uppercase tracking-widest text-zinc-600 dark:text-zinc-300">
                                        <span class="material-symbols-outlined text-lg opacity-60">warehouse</span>
                                        {{ wh.name }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="lg:col-span-2 relative provider-search-container">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">Proveedor *</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span v-if="isSearchingProviders" class="material-symbols-outlined text-indigo-400 animate-spin text-xl">sync</span>
                                    <span v-else class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-600 transition-colors">person</span>
                                </div>
                                <input v-model="providerSearch"
                                       type="text"
                                       placeholder="Buscar proveedor por nombre o documento..."
                                       :class="{'border-rose-500 ring-4 ring-rose-500/10': form.errors.provider_id}"
                                       class="w-full pl-12 pr-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm font-black uppercase tracking-widest text-[11px]"
                                       @focus="searchProviders(providerSearch)">
                                <div v-if="form.errors.provider_id" class="mt-2 ml-1 text-[10px] font-black text-rose-500 uppercase tracking-widest animate-in fade-in slide-in-from-top-1">
                                    {{ form.errors.provider_id }}
                                </div>
                                
                                <!-- Dropdown de Resultados -->
                                <div v-if="showProviderDropdown && providerSearchResults.length > 0" 
                                     class="absolute z-50 left-0 right-0 mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-2xl shadow-2xl max-h-60 overflow-y-auto custom-scrollbar animate-in fade-in zoom-in-95 duration-200">
                                    <ul class="py-1">
                                        <li v-for="p in providerSearchResults" :key="p.id"
                                            @click="selectProvider(p)"
                                            class="px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer flex items-center justify-between transition-colors border-b last:border-0 border-zinc-50 dark:border-secondary-700">
                                            <div class="flex flex-col">
                                                <span class="font-black text-zinc-900 dark:text-white text-[11px] uppercase tracking-tight">{{ p.name }}</span>
                                                <span class="text-[9px] font-black text-indigo-500 uppercase tracking-widest">{{ p.document_number || 'S/N' }}</span>
                                            </div>
                                            <span v-if="form.provider_id === p.id" class="material-symbols-outlined text-indigo-600">check_circle</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">Notas Generales</label>
                            <textarea v-model="form.notes" rows="2" 
                                      placeholder="Escribe alguna referencia comercial u observación técnica (Opcional)..."
                                      class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm font-medium resize-none"></textarea>
                        </div>

                        <div class="lg:col-span-1 relative voucher-select-container">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">Comprobante *</label>
                            <div @click="showVoucherDropdown = !showVoucherDropdown" 
                                 class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 hover:border-indigo-500 dark:hover:border-indigo-500 rounded-2xl text-sm transition-all dark:text-white shadow-sm font-black uppercase tracking-widest text-[11px] cursor-pointer flex items-center justify-between group">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg text-zinc-400 group-hover:text-indigo-500">
                                        {{ voucherTypes.find(v => v.value === form.voucher_type)?.icon }}
                                    </span>
                                    <span>{{ selectedVoucherLabel }}</span>
                                </div>
                                <span class="material-symbols-outlined text-zinc-400 group-hover:text-indigo-500 transition-transform" :class="{'rotate-180': showVoucherDropdown}">expand_more</span>
                            </div>

                            <!-- Dropdown List -->
                            <div v-if="showVoucherDropdown" 
                                 class="absolute z-50 left-0 right-0 mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                                <ul class="py-1">
                                    <li v-for="type in voucherTypes" :key="type.value"
                                        @click="selectVoucher(type.value)"
                                        class="px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer flex items-center gap-3 transition-colors text-[11px] font-black uppercase tracking-widest text-zinc-600 dark:text-zinc-300">
                                        <span class="material-symbols-outlined text-lg opacity-60">{{ type.icon }}</span>
                                        {{ type.label }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="lg:col-span-1 relative payment-select-container">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">Tipo de Pago *</label>
                            <div @click="showPaymentDropdown = !showPaymentDropdown" 
                                 class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 hover:border-indigo-500 dark:hover:border-indigo-500 rounded-2xl text-sm transition-all dark:text-white shadow-sm font-black uppercase tracking-widest text-[11px] cursor-pointer flex items-center justify-between group">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg text-zinc-400 group-hover:text-indigo-500">
                                        {{ paymentTypes.find(p => p.value === form.payment_type)?.icon }}
                                    </span>
                                    <span>{{ selectedPaymentLabel }}</span>
                                </div>
                                <span class="material-symbols-outlined text-zinc-400 group-hover:text-indigo-500 transition-transform" :class="{'rotate-180': showPaymentDropdown}">expand_more</span>
                            </div>

                            <!-- Dropdown List -->
                            <div v-if="showPaymentDropdown" 
                                 class="absolute z-50 left-0 right-0 mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                                <ul class="py-1">
                                    <li v-for="type in paymentTypes" :key="type.value"
                                        @click="selectPayment(type.value)"
                                        class="px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer flex items-center gap-3 transition-colors text-[11px] font-black uppercase tracking-widest text-zinc-600 dark:text-zinc-300">
                                        <span class="material-symbols-outlined text-lg opacity-60">{{ type.icon }}</span>
                                        {{ type.label }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <template v-if="form.payment_type === 'credito'">
                            <div class="lg:col-span-1 animate-in fade-in slide-in-from-left-4 duration-300">
                                <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">Fecha Vencimiento *</label>
                                <input v-model="form.due_date" type="date" required
                                       class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm font-medium">
                            </div>

                            <div class="lg:col-span-1 animate-in fade-in slide-in-from-left-4 duration-300">
                                <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">Adelanto (Opcional)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-zinc-500 text-sm font-black">Bs.</span>
                                    </div>
                                    <input v-model="form.down_payment" type="number" step="0.01" 
                                           class="w-full pl-12 pr-4 py-3 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm font-black tracking-tight">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- 2. Detalle de Compra -->
            <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 shadow-sm relative">
                <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-t-3xl"></div>
                <div class="p-5 border-b border-zinc-100 dark:border-secondary-700 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-900/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-inner">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-zinc-900 dark:text-white uppercase tracking-tight">2. Detalle de Compra</h2>
                            <p class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Busque y agregue los productos al listado</p>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <!-- Buscador Asíncrono -->
                    <div class="product-search-container relative w-full bg-indigo-50/50 dark:bg-indigo-900/10 p-5 rounded-3xl border border-indigo-100 dark:border-indigo-900/30 mb-8"
                         :class="{'border-rose-200 bg-rose-50/30': form.errors.details}">
                        <label class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2 ml-1"
                               :class="{'text-rose-500': form.errors.details}">Buscador de Producto (Asíncrono)</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span v-if="isSearching" class="material-symbols-outlined text-indigo-400 animate-spin text-xl">sync</span>
                                <span v-else class="material-symbols-outlined text-indigo-400 group-focus-within:text-indigo-600 transition-colors">qr_code_scanner</span>
                            </div>
                            <input v-model="productSearch"
                                   type="text"
                                   placeholder="Escriba el nombre o código para buscar..."
                                   class="w-full pl-12 pr-4 py-3 bg-white dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm font-black uppercase tracking-widest text-[11px]"
                                   @focus="searchProducts(productSearch)">
                            
                            <div v-if="form.errors.details" class="mt-2 ml-1 text-[10px] font-black text-rose-500 uppercase tracking-widest animate-in fade-in slide-in-from-top-1">
                                {{ form.errors.details }}
                            </div>
                            
                            <!-- Dropdown de Resultados -->
                            <div v-if="showDropdown && searchResults.length > 0" 
                                 class="absolute z-50 left-0 right-0 mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-2xl shadow-2xl max-h-72 overflow-y-auto custom-scrollbar">
                                <ul class="py-2">
                                    <li v-for="product in searchResults" :key="product.id"
                                        @click="addProduct(product)"
                                        class="px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer flex items-center justify-between transition-colors border-b last:border-0 border-zinc-50 dark:border-secondary-700">
                                        <div class="flex flex-col">
                                            <span class="font-black text-zinc-900 dark:text-white text-xs uppercase tracking-tight">{{ product.name }}</span>
                                            <span class="text-[9px] font-black text-indigo-500 uppercase tracking-widest">{{ product.code }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-[10px] font-black text-zinc-400 uppercase tracking-widest block">Precio Ref.</span>
                                            <span class="text-sm font-black text-zinc-900 dark:text-white tracking-tighter">Bs. {{ formatNumber(product.price) }}</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div v-else-if="showDropdown && productSearch.length >= 2 && !isSearching"
                                 class="absolute z-50 left-0 right-0 mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-2xl shadow-xl p-6 text-center">
                                <span class="material-symbols-outlined text-4xl text-zinc-300 mb-2">search_off</span>
                                <p class="text-xs font-black text-zinc-500 uppercase tracking-widest">No se encontraron productos</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Carrito -->
                    <div class="border border-zinc-200 dark:border-secondary-700 rounded-3xl overflow-hidden shadow-sm">
                        <div class="overflow-x-auto min-h-[200px]">
                            <table class="w-full text-sm text-left whitespace-nowrap">
                                <thead class="bg-zinc-50 dark:bg-secondary-700/50 border-b border-zinc-200 dark:border-secondary-700 text-zinc-500 dark:text-secondary-400 font-black uppercase tracking-widest text-[10px]">
                                    <tr>
                                        <th class="px-6 py-4">Producto</th>
                                        <th class="px-6 py-4 w-44 text-center">Formato de Compra</th>
                                        <th class="px-6 py-4 w-32 text-center">Cantidad</th>
                                        <th class="px-6 py-4 w-44 text-right">Costo Unitario</th>
                                        <th class="px-6 py-4 w-52 text-center">Vencimiento</th>
                                        <th class="px-6 py-4 text-right w-40">Subtotal</th>
                                        <th class="px-6 py-4 text-center w-16">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50">
                                    <tr v-for="(item, index) in form.details" :key="item.product_id"
                                        class="hover:bg-zinc-50 dark:hover:bg-secondary-700/50 transition-colors group">
                                        <td class="px-6 py-5">
                                            <div class="flex flex-col">
                                                <span class="font-black text-zinc-900 dark:text-white tracking-tight text-base leading-tight">{{ item.name }}</span>
                                                <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mt-1 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[14px]">sell</span>
                                                    {{ item.code || '--' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
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
                                        <td class="px-6 py-5">
                                            <div class="flex flex-col items-center">
                                                <input v-model="item.quantity" type="number" step="0.01" min="0.01"
                                                       :class="{'border-rose-500 ring-2 ring-rose-500/10': form.errors[`details.${index}.quantity`]}"
                                                       class="w-24 px-3 py-2 bg-zinc-50 dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white text-center font-black tracking-tight">
                                                <span class="text-[9px] font-black text-zinc-400 uppercase tracking-widest mt-1">
                                                    {{ item.purchase_format === 'package' ? item.package_name : item.unit }}
                                                </span>
                                            </div>
                                            <div v-if="form.errors[`details.${index}.quantity`]" class="text-[9px] font-black text-rose-500 uppercase tracking-tight mt-1 text-center">
                                                Requerido
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-[9px] font-black text-zinc-400">Bs.</span>
                                                </div>
                                                <input v-model="item.unit_price" type="number" step="0.0001" min="0.01"
                                                       :class="{'border-rose-500 ring-2 ring-rose-500/10': form.errors[`details.${index}.unit_price`]}"
                                                       class="w-36 pl-9 pr-3 py-2 bg-zinc-50 dark:bg-secondary-900 border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl text-sm transition-all dark:text-white text-right font-black tracking-tight">
                                            </div>
                                            <div class="text-[9px] font-black text-zinc-400 uppercase tracking-widest mt-1 text-right">
                                                por {{ item.purchase_format === 'package' ? item.package_name : item.unit }}
                                            </div>
                                            <div v-if="form.errors[`details.${index}.unit_price`]" class="text-[9px] font-black text-rose-500 uppercase tracking-tight mt-1 text-right">
                                                Inválido
                                            </div>
                                        </td>
                                        <!-- Celda de Vencimiento -->
                                        <td class="px-6 py-5">
                                            <div class="flex flex-col items-center gap-2">
                                                <!-- Badge de estado (solo lectura, viene del producto) -->
                                                <span
                                                    :class="item.has_expiration
                                                        ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700'
                                                        : 'bg-zinc-100 dark:bg-secondary-700 text-zinc-400 dark:text-secondary-500 border-zinc-200 dark:border-secondary-600'"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border text-[9px] font-black uppercase tracking-widest"
                                                >
                                                    <span class="material-symbols-outlined text-[12px]">
                                                        {{ item.has_expiration ? 'event_available' : 'event_busy' }}
                                                    </span>
                                                    {{ item.has_expiration ? 'Con vencimiento' : 'Sin vencimiento' }}
                                                </span>
                                                <!-- Input fecha (solo si el producto requiere vencimiento) -->
                                                <div v-if="item.has_expiration" class="animate-in fade-in slide-in-from-top-2 duration-200 w-full">
                                                    <input
                                                        v-model="item.expiration_date"
                                                        type="date"
                                                        :required="item.has_expiration"
                                                        :class="{
                                                            'border-rose-500 ring-2 ring-rose-500/10': form.errors[`details.${index}.expiration_date`] || (item.has_expiration && !item.expiration_date)
                                                        }"
                                                        class="w-full px-3 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-400/20 rounded-xl text-[11px] transition-all dark:text-white font-bold text-center"
                                                    >
                                                    <div
                                                        v-if="form.errors[`details.${index}.expiration_date`] || (item.has_expiration && !item.expiration_date)"
                                                        class="text-[9px] font-black text-rose-500 uppercase tracking-tight mt-1 text-center"
                                                    >
                                                        {{ form.errors[`details.${index}.expiration_date`] || 'Obligatorio' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-5 text-right font-black text-zinc-900 dark:text-white text-lg tracking-tighter">
                                            Bs. {{ formatNumber(item.quantity * item.unit_price) }}
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <button type="button" @click="removeProduct(index)"
                                                    class="w-9 h-9 inline-flex items-center justify-center rounded-xl text-zinc-300 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-all active:scale-90 border border-transparent hover:border-rose-100 dark:hover:border-rose-800">
                                                <span class="material-symbols-outlined text-xl leading-none">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="form.details.length === 0">
                                        <td colspan="5" class="px-6 py-24 text-center">
                                            <div class="flex flex-col items-center opacity-40">
                                                <div class="w-20 h-20 bg-zinc-50 dark:bg-secondary-700 rounded-3xl flex items-center justify-center mb-4 border-2 border-dashed border-zinc-200 dark:border-secondary-600">
                                                    <span class="material-symbols-outlined text-5xl text-zinc-300 dark:text-secondary-600">shopping_cart</span>
                                                </div>
                                                <p class="text-zinc-500 dark:text-secondary-400 font-black uppercase tracking-widest text-sm">No hay productos en la compra</p>
                                                <p class="text-xs text-zinc-400 dark:text-secondary-500 mt-2">Utilice el buscador superior para agregar elementos.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="form.details.length > 0" class="bg-zinc-50/50 dark:bg-secondary-900/50 border-t border-zinc-100 dark:border-secondary-700">
                                    <tr>
                                        <td colspan="4" class="px-8 py-6 text-right text-zinc-500 dark:text-secondary-400 font-black uppercase text-[10px] tracking-widest">
                                            Total General de Compra
                                        </td>
                                        <td class="px-8 py-6 text-right text-indigo-700 dark:text-indigo-400 font-black text-3xl tracking-tighter">
                                            Bs. {{ formatNumber(total) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-4 pt-6 pb-12">
                <Link :href="route('admin.purchases.index')"
                      class="px-8 py-3 text-xs font-black text-zinc-500 dark:text-secondary-400 hover:bg-zinc-100 dark:hover:bg-secondary-700 rounded-2xl transition-all border border-transparent hover:border-zinc-200 dark:hover:border-secondary-600 uppercase tracking-widest">
                    Cancelar
                </Link>
                <button type="submit" :disabled="form.processing || form.details.length === 0 || hasExpirationErrors"
                        class="inline-flex items-center justify-center gap-3 px-10 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all active:scale-95 disabled:opacity-50 shadow-xl shadow-indigo-500/30">
                    <span v-if="form.processing" class="material-symbols-outlined animate-spin text-xl">sync</span>
                    <span v-else-if="hasExpirationErrors" class="material-symbols-outlined text-xl text-amber-300">event_busy</span>
                    <span v-else class="material-symbols-outlined text-xl">check_circle</span>
                    <span v-if="hasExpirationErrors">Fecha de vencimiento requerida</span>
                    <span v-else>Guardar Compra</span>
                </button>

            </div>
        </form>
    </AdminLayout>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
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

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>
