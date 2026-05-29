<script setup>
import { ref, watch, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseSelect from '@/Components/Admin/BaseSelect.vue';
import ProductModal from './Partials/ProductModal.vue';
import ImportModal from './Partials/ImportModal.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    products: Object,
    filters: Object,
    categories: Array,
    units: Array,
    warehouses: Array,
    providers: Array,
});

const search = ref(props.filters.search || '');
const warehouseId = ref(props.filters.warehouse_id || null);
const lowStock = ref(props.filters.low_stock || false);

const showModal = ref(false);
const showImportModal = ref(false);
const editingProduct = ref(null);

const showImagePreview = ref(false);
const previewImages = ref([]);
const previewName = ref('');
const currentImageIndex = ref(0);

const openGallery = (images, name) => {
    if (!images || images.length === 0) return;
    previewImages.value = images;
    previewName.value = name;
    currentImageIndex.value = 0;
    showImagePreview.value = true;
};

const nextImage = () => {
    currentImageIndex.value = (currentImageIndex.value + 1) % previewImages.value.length;
};

const prevImage = () => {
    currentImageIndex.value = (currentImageIndex.value - 1 + previewImages.value.length) % previewImages.value.length;
};

const updateFilters = debounce(() => {
    router.get(route('admin.products.index'), {
        search: search.value,
        warehouse_id: warehouseId.value,
        low_stock: lowStock.value ? 1 : 0,
    }, {
        only: ['products', 'filters'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 500);

watch([search, warehouseId, lowStock], () => {
    updateFilters();
});

const openCreateModal = () => {
    editingProduct.value = null;
    showModal.value = true;
};

const openEditModal = (product) => {
    editingProduct.value = product;
    showModal.value = true;
};

const deleteProduct = (product) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Esta acción eliminará el producto "${product.name}" de forma permanente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5', // Indigo 600
        cancelButtonColor: '#f43f5e', // Rose 500
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#000000',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.products.destroy', product.id));
        }
    });
};

const downloadTemplate = () => {
    window.location.href = route('admin.products.template');
};

</script>

<template>
    <Head title="Productos" />

    <AdminLayout>
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">Productos</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Gestión integral del catálogo e inventario técnico</p>
            </div>
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 w-full sm:w-auto">
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-3 w-full sm:w-auto">
                    <button @click="showImportModal = true"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-zinc-100 dark:bg-gray-700 text-zinc-700 dark:text-zinc-200 text-sm font-bold rounded-xl hover:bg-zinc-200 dark:hover:bg-gray-600 transition-all active:scale-95 border border-zinc-200 dark:border-gray-600 w-full sm:w-auto">
                        <span class="material-symbols-outlined text-lg">cloud_upload</span>
                        <span>Importar</span>
                    </button>
                    <button @click="downloadTemplate"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-zinc-100 dark:bg-gray-700 text-zinc-700 dark:text-zinc-200 text-sm font-bold rounded-xl hover:bg-zinc-200 dark:hover:bg-gray-600 transition-all active:scale-95 border border-zinc-200 dark:border-gray-600 w-full sm:w-auto">
                        <span class="material-symbols-outlined text-lg">description</span>
                        <span>Plantilla</span>
                    </button>
                </div>
                <button @click="openCreateModal"
                        class="inline-flex items-center justify-center gap-2.5 px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 active:scale-95 group w-full sm:w-auto">
                    <span class="material-symbols-outlined text-lg transition-transform group-hover:rotate-90">add</span>
                    <span>Nuevo Producto</span>
                </button>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 mb-8 items-center">
            <!-- Search Bar -->
            <div class="lg:col-span-3 relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors">search</span>
                </div>
                <input v-model="search"
                       type="text"
                       placeholder="Buscar productos por nombre o código..."
                       class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-800 border-zinc-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white placeholder-zinc-400 shadow-sm">
            </div>
            
            <!-- Warehouse Filter -->
            <div class="lg:col-span-2">
                <BaseSelect v-model="warehouseId"
                           :options="[{ id: null, name: 'Todos los almacenes' }, ...warehouses]"
                           icon="inventory_2"
                           placeholder="Todos los almacenes" />
            </div>

            <!-- Low Stock Toggle -->
            <div class="lg:col-span-1">
                <button @click="lowStock = !lowStock"
                        :class="[
                            'w-full h-[50px] rounded-2xl border transition-all duration-300 flex items-center justify-between px-4 group shadow-sm overflow-hidden relative',
                            lowStock 
                                ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-500/30' 
                                : 'bg-white dark:bg-gray-800 border-zinc-200 dark:border-gray-700 text-zinc-500 dark:text-zinc-400 hover:border-indigo-500'
                        ]">
                    <div class="flex items-center gap-2.5 z-10">
                        <div :class="[
                            'p-1.5 rounded-lg transition-colors',
                            lowStock ? 'bg-white/20' : 'bg-amber-50 dark:bg-amber-900/20'
                        ]">
                            <span class="material-symbols-outlined text-sm" :class="lowStock ? 'text-white' : 'text-amber-600'">warning</span>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-widest leading-none">Stock Bajo</span>
                    </div>
                    
                    <div :class="[
                        'relative w-9 h-5 rounded-full transition-all duration-300 flex items-center px-0.5 z-10',
                        lowStock ? 'bg-indigo-400/50' : 'bg-zinc-200 dark:bg-zinc-700'
                    ]">
                        <div :class="[
                            'w-4 h-4 rounded-full bg-white transition-all duration-300 shadow-md transform',
                            lowStock ? 'translate-x-4' : 'translate-x-0'
                        ]"></div>
                    </div>

                    <!-- Subtle background glow when active -->
                    <div v-if="lowStock" class="absolute -right-4 -bottom-4 w-16 h-16 bg-white/10 rounded-full blur-2xl"></div>
                </button>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-surface dark:bg-gray-800 rounded-3xl border border-zinc-200 dark:border-gray-700 shadow-xl overflow-hidden mb-8">
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-100 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Código</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Producto</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center">Stock [und]</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center">U x Epq.</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center">Epq.</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center">Und.</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center">Status</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-right w-32">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/30">
                        <tr v-for="(product, index) in products.data" :key="product.id" 
                            class="bg-white dark:bg-secondary-800/40 transition-all duration-200 hover:bg-zinc-50 dark:hover:bg-secondary-700 group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-mono font-bold text-zinc-500 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">
                                    {{ product.code || 'S/C' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative flex-shrink-0 cursor-pointer" @click="openGallery(product.drive_links, product.name)">
                                        <img v-if="product.image_path" :src="product.image_path" 
                                             class="w-10 h-10 rounded-lg object-cover border border-zinc-200 dark:border-gray-700 shadow-sm">
                                        <div v-else class="w-10 h-10 rounded-lg bg-zinc-50 dark:bg-gray-700/50 flex items-center justify-center border border-dashed border-zinc-200 dark:border-gray-600">
                                            <span class="material-symbols-outlined text-zinc-300 dark:text-zinc-600 text-sm">image</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-zinc-900 dark:text-white leading-tight">{{ product.name }}</span>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                            <span class="text-[10px] text-zinc-400 uppercase tracking-widest">{{ product.categories.map(c => c.name).join(', ') }}</span>
                                            <span v-if="product.warehouses && product.warehouses.length > 0" class="text-[10px] text-zinc-300 dark:text-zinc-600">•</span>
                                            <span v-for="wName in product.warehouses" :key="wName" 
                                                  class="inline-flex items-center gap-1.5 text-[9px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/30 px-2 py-0.5 rounded"
                                                  title="Almacén de registro">
                                                <span class="material-symbols-outlined text-[12px]">warehouse</span>
                                                {{ wName }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span :class="[
                                    'text-sm font-black tracking-tight',
                                    product.current_stock > product.min_stock ? 'text-emerald-600 dark:text-emerald-400' : 
                                    product.current_stock > 0 ? 'text-amber-500 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400'
                                ]">
                                    {{ Number(product.current_stock).toFixed(2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400">
                                    {{ Number(product.units_per_package || 1).toFixed(0) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="text-sm font-black text-zinc-700 dark:text-zinc-300">
                                        {{ (Number(product.current_stock) / Number(product.units_per_package || 1)).toFixed(2) }}
                                    </span>
                                    <span v-if="product.package_name" class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-tighter">
                                        {{ product.package_name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">
                                    {{ product.unit_of_measure?.abbreviation || 'UND' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="product.current_stock > 0" class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest px-2 py-1 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-100 dark:border-emerald-800/20">
                                    OK
                                </span>
                                <span v-else class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest px-2 py-1 bg-rose-50 dark:bg-rose-900/20 rounded-lg border border-rose-100 dark:border-rose-800/20">
                                    AGOTADO
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openEditModal(product)"
                                            class="w-9 h-9 flex items-center justify-center text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all active:scale-90">
                                        <span class="material-symbols-outlined text-lg">edit_square</span>
                                    </button>
                                    <button @click="deleteProduct(product)"
                                            class="w-9 h-9 flex items-center justify-center text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-all active:scale-90">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="products.data.length === 0">
                            <td colspan="8" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center py-10">
                                    <div class="w-20 h-20 bg-zinc-50 dark:bg-gray-700/50 rounded-2xl flex items-center justify-center mb-6 border-2 border-dashed border-zinc-200 dark:border-gray-700">
                                        <span class="material-symbols-outlined text-4xl text-zinc-300 dark:text-zinc-600">inventory_2</span>
                                    </div>
                                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white">No hay productos</h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2 max-w-xs mx-auto">Comienza agregando tus productos o importa tu inventario desde Excel.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden flex flex-col divide-y divide-zinc-100 dark:divide-gray-700">
                <div v-for="(product, index) in products.data" :key="product.id" 
                     class="p-4 space-y-4 bg-white dark:bg-gray-800/50">
                    
                    <!-- Basic Info Row -->
                    <div class="flex items-start gap-4">
                        <div class="relative flex-shrink-0 cursor-pointer" @click="openGallery(product.drive_links, product.name)">
                            <img v-if="product.image_path" 
                                 :src="product.image_path" 
                                 class="w-16 h-16 rounded-2xl object-cover shadow-sm border border-zinc-100 dark:border-gray-700">
                            <div v-else class="w-16 h-16 rounded-2xl bg-zinc-50 dark:bg-gray-700 flex items-center justify-center border border-dashed border-zinc-200 dark:border-gray-600">
                                <span class="material-symbols-outlined text-zinc-300">image</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">#{{ String(index + 1).padStart(2, '0') }}</span>
                                <span v-if="product.is_active" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                                    Activo
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-gray-600">
                                    Inactivo
                                </span>
                            </div>
                            <h4 class="font-bold text-zinc-900 dark:text-white text-base truncate mt-0.5">{{ product.name }}</h4>
                            <div class="flex items-center gap-2 mt-1 font-mono text-[10px]">
                                <span class="text-zinc-400 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 px-1.5 rounded">{{ product.code || 'S/C' }}</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest text-[9px]">{{ product.unit_of_measure?.abbreviation || 'UND' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Categoría</span>
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ product.categories.map(c => c.name).join(', ') || '—' }}</span>
                        </div>
                        <div v-if="product.warehouses && product.warehouses.length > 0" class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Almacenes</span>
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase text-right max-w-[200px] truncate" :title="product.warehouses.join(', ')">{{ product.warehouses.join(', ') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-semibold text-zinc-400 uppercase tracking-widest">Stock Actual</span>
                            <span :class="[
                                'text-sm font-black tracking-tight',
                                product.current_stock > product.min_stock ? 'text-emerald-600 dark:text-emerald-400' : 
                                product.current_stock > 0 ? 'text-amber-500 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400'
                            ]">
                                {{ Number(product.current_stock).toFixed(2) }} <small class="text-[9px] font-bold opacity-60">{{ product.unit_of_measure?.abbreviation || 'UND' }}</small>
                            </span>
                        </div>
                    </div>

                    <!-- Action Row -->
                    <div class="flex justify-end items-center gap-3 pt-2">
                        <button @click="openEditModal(product)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 active:scale-90 transition-all border border-amber-100 dark:border-amber-800/20"
                                title="Editar">
                            <span class="material-symbols-outlined text-lg">edit</span>
                        </button>
                        <button @click="deleteProduct(product)"
                                class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 active:scale-90 transition-all border border-rose-100 dark:border-rose-900/20"
                                title="Eliminar">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State for Mobile -->
                <div v-if="products.data.length === 0" class="p-10 text-center bg-white dark:bg-gray-800">
                    <div class="flex flex-col items-center py-10">
                        <div class="w-20 h-20 bg-zinc-50 dark:bg-gray-700/50 rounded-2xl flex items-center justify-center mb-6 border-2 border-dashed border-zinc-200 dark:border-gray-700">
                            <span class="material-symbols-outlined text-4xl text-zinc-300 dark:text-zinc-600">inventory_2</span>
                        </div>
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">No hay productos</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2 max-w-xs mx-auto">Comienza agregando tus productos o importa tu inventario desde Excel.</p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="products.meta && products.meta.links" class="px-6 py-5 border-t border-zinc-100 dark:border-gray-700 bg-zinc-50 dark:bg-zinc-800 flex justify-center">
                <nav class="flex gap-1 overflow-x-auto scrollbar-hide pb-2 sm:pb-0 whitespace-nowrap max-w-full">
                    <Link v-for="(link, i) in products.meta.links" :key="i"
                          :href="link.url || '#'"
                          v-html="link.label"
                          :class="[
                              'px-4 py-2 text-sm font-bold rounded-xl transition-all shrink-0',
                              link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 
                              link.url ? 'bg-white dark:bg-gray-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' : 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'
                          ]"
                    />
                </nav>
            </div>
        </div>

        <!-- Modals -->
        <ProductModal :show="showModal" 
                      :product="editingProduct" 
                      :categories="categories" 
                      :units="units"
                      :warehouses="warehouses"
                      @close="showModal = false; editingProduct = null" />
        
        <ImportModal :show="showImportModal" 
                     :warehouses="warehouses" 
                     :providers="providers"
                     @close="showImportModal = false" />

        <!-- Image Gallery Lightbox -->
        <div v-if="showImagePreview" 
             class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-zinc-950/85 backdrop-blur-xl"
             @keydown.escape="showImagePreview = false">
            
            <div class="absolute top-0 left-0 w-full p-4 sm:p-8 flex justify-between items-start z-[10000]">
                <div class="px-5 py-2.5 sm:px-6 sm:py-3 bg-white/10 backdrop-blur-xl border border-white/20 rounded-full shadow-2xl flex items-center gap-3 transform transition-all duration-300">
                    <span class="material-symbols-outlined text-indigo-400">inventory_2</span>
                    <h3 class="text-sm sm:text-base font-semibold tracking-wide text-white max-w-[150px] sm:max-w-md truncate">{{ previewName }}</h3>
                    <span v-if="previewImages.length > 1" class="flex items-center justify-center bg-white/20 text-white text-[10px] sm:text-xs px-2.5 py-1 rounded-full font-mono font-bold border border-white/10 shadow-inner">
                        {{ currentImageIndex + 1 }} / {{ previewImages.length }}
                    </span>
                </div>
                <button @click="showImagePreview = false" class="group flex items-center justify-center gap-2 px-5 py-2.5 sm:px-6 sm:py-3 bg-rose-600/90 hover:bg-rose-600 text-white backdrop-blur-xl rounded-full shadow-2xl transition-all duration-300 z-[10000] border border-white/20">
                    <span class="text-xs sm:text-sm font-bold tracking-wider uppercase">Cerrar</span>
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <button v-if="previewImages.length > 1" @click.stop="prevImage" class="absolute left-4 sm:left-6 top-1/2 -translate-y-1/2 w-12 h-12 sm:w-16 sm:h-16 bg-white/5 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-xl transition-all duration-300 border border-white/10 z-[10000] shadow-2xl">
                <span class="material-symbols-outlined text-3xl">chevron_left</span>
            </button>
            <button v-if="previewImages.length > 1" @click.stop="nextImage" class="absolute right-4 sm:right-6 top-1/2 -translate-y-1/2 w-12 h-12 sm:w-16 sm:h-16 bg-white/5 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-xl transition-all duration-300 border border-white/10 z-[10000] shadow-2xl">
                <span class="material-symbols-outlined text-3xl">chevron_right</span>
            </button>
            
            <div class="flex-1 w-full max-w-7xl flex items-center justify-center p-4 sm:p-24 relative overflow-hidden" @click="showImagePreview = false">
                <div v-for="(img, index) in previewImages" :key="index"
                     v-show="currentImageIndex === index" 
                     class="absolute inset-0 flex items-center justify-center p-4 sm:p-12 transition-opacity duration-500">
                    <img :src="img" :alt="previewName" class="max-w-full max-h-[85vh] sm:max-h-[75vh] object-contain drop-shadow-2xl rounded-2xl ring-1 ring-white/10">
                </div>
            </div>
            
            <div v-if="previewImages.length > 1" class="absolute bottom-6 sm:bottom-10 left-1/2 -translate-x-1/2 z-[10000] max-w-full px-4">
                <div class="flex items-center justify-start sm:justify-center gap-2 sm:gap-4 p-2 sm:p-3 bg-zinc-900/60 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-2xl overflow-x-auto scrollbar-hide">
                    <button v-for="(img, index) in previewImages" :key="'thumb-'+index"
                            @click.stop="currentImageIndex = index" 
                            class="relative flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 rounded-lg overflow-hidden transition-all duration-300" 
                            :class="currentImageIndex === index ? 'ring-2 ring-indigo-400 ring-offset-2 ring-offset-zinc-950 scale-110' : 'opacity-40 hover:opacity-100 border border-white/20'">
                        <img :src="img" class="w-full h-full object-cover">
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
