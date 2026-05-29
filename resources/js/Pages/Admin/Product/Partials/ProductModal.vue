<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useImageCompression } from '@/Composables/useImageCompression';

const { compressAndConvertToWebP } = useImageCompression();

const props = defineProps({
    show: Boolean,
    product: Object,
    categories: Array,
    units: Array,
    warehouses: Array,
});

const emit = defineEmits(['close']);
const page = usePage();

const maxImages = computed(() => {
    const limit = page.props.company?.plan?.features?.max_images_per_product;
    if (limit === -1) return 99; // Ilimitado
    return limit || 1;
});

const isBasicPlan = computed(() => page.props.company?.plan?.slug === 'basico');

const form = useForm({
    id: null,
    name: '',
    code: '',
    description: '',
    price: 0,
    min_stock: 0,
    is_active: true,
    has_expiration: false,
    show_in_ecommerce: false,
    category_ids: [],
    unit_of_measure_id: '',
    warehouse_id: '',
    units_per_package: 1,
    package_name: '',
    location: '',
    brand: '',
    slug: '',
    drive_links: [],
    new_images: [], // Nuevas imágenes locales (Blobs)
    image: null,
    image_preview: null,
});

const imageUrlInput = ref('');
const isGeneratingCode = ref(false);
const isProcessingImage = ref(false); // Estado de carga para imágenes

// Multiselect Category Logic
const categorySearch = ref('');
const isCategoryMenuOpen = ref(false);

const filteredCategories = computed(() => {
    if (!categorySearch.value) return props.categories;
    return props.categories.filter(cat => 
        cat.name.toLowerCase().includes(categorySearch.value.toLowerCase())
    );
});

const selectedCategories = computed(() => {
    return props.categories.filter(cat => form.category_ids.includes(cat.id));
});

const toggleCategory = (id) => {
    const index = form.category_ids.indexOf(id);
    if (index === -1) {
        form.category_ids.push(id);
    } else {
        form.category_ids.splice(index, 1);
    }
};

watch(() => props.show, (isVisible) => {
    if (isVisible) {
        if (props.product) {
            const newProduct = props.product;
            form.id = newProduct.id;
            form.name = newProduct.name;
            form.code = newProduct.code || '';
            form.description = newProduct.description || '';
            form.price = newProduct.price;
            form.min_stock = newProduct.min_stock;
            form.is_active = Boolean(newProduct.is_active);
            form.has_expiration = Boolean(newProduct.has_expiration);
            form.show_in_ecommerce = Boolean(newProduct.show_in_ecommerce);
            form.category_ids = newProduct.category_ids || [];
            form.unit_of_measure_id = newProduct.unit_of_measure?.id || '';
            form.warehouse_id = (newProduct.warehouse_ids && newProduct.warehouse_ids.length > 0)
                ? newProduct.warehouse_ids[0]
                : (newProduct.warehouse_id || '');
            form.units_per_package = newProduct.units_per_package || 1;
            form.package_name = newProduct.package_name || '';
            form.location = newProduct.location || '';
            form.brand = newProduct.brand || '';
            form.slug = newProduct.slug || '';
            form.drive_links = [...(newProduct.drive_links || [])];
            form.new_images = [];
        } else {
            form.reset();
            form.new_images = [];
            if (props.warehouses && props.warehouses.length > 0) {
                form.warehouse_id = props.warehouses[0].id;
            }
        }
    }
}, { immediate: true });

const submit = () => {
    if (form.id) {
        form.transform((data) => ({
            ...data,
            warehouse_ids: data.warehouse_id ? [data.warehouse_id] : [],
            _method: 'PUT'
        })).post(route('admin.products.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.transform((data) => ({
            ...data,
            warehouse_ids: data.warehouse_id ? [data.warehouse_id] : [],
        })).post(route('admin.products.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    form.reset();
    emit('close');
};

const generateCode = async () => {
    if (!form.name || form.category_ids.length === 0) return;
    isGeneratingCode.value = true;
    try {
        const response = await axios.get(route('admin.products.generate-sku'), {
            params: { name: form.name, category_ids: form.category_ids }
        });
        form.code = response.data.code;
    } catch (error) {
        console.error(error);
    } finally {
        isGeneratingCode.value = false;
    }
};


/**
 * Remueve una imagen local pendiente de subida
 */
const removeLocalImage = (index) => {
    form.new_images.splice(index, 1);
};

/**
 * Genera una URL de previsualización para un archivo local
 */
const getLocalPreview = (file) => {
    return URL.createObjectURL(file);
};

const handleImageUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const totalImages = form.drive_links.length + form.new_images.length;
    if (totalImages >= maxImages.value) {
        alert(`Límite máximo de ${maxImages.value} ${maxImages.value === 1 ? 'imagen' : 'imágenes'} alcanzado`);
        return;
    }

    isProcessingImage.value = true;
    
    try {
        // 1. PROCESAR EN FRONTEND (Reducción y WebP)
        const processedFile = await compressAndConvertToWebP(file);
        
        // 2. GUARDAR LOCALMENTE (No subir aún)
        form.new_images.push(processedFile);
        
    } catch (error) {
        console.error(error);
        alert('Error al procesar la imagen: ' + error.message);
    } finally {
        isProcessingImage.value = false;
        e.target.value = '';
    }
};

const addDriveLink = async () => {
    if (!imageUrlInput.value) return;
    
    const totalImages = form.drive_links.length + form.new_images.length;
    if (totalImages >= maxImages.value) {
        alert(`Límite máximo de ${maxImages.value} ${maxImages.value === 1 ? 'imagen' : 'imágenes'} alcanzado`);
        return;
    }

    isProcessingImage.value = true;
    try {
        const response = await axios.post(route('admin.products.process-image-url'), {
            url: imageUrlInput.value
        });
        form.drive_links.push(response.data.url);
        imageUrlInput.value = '';
    } catch (error) {
        console.error(error);
        alert('Error al procesar la URL: ' + (error.response?.data?.error || error.message));
    } finally {
        isProcessingImage.value = false;
    }
};

const removeDriveLink = (index) => {
    // Opcional: Podríamos llamar a un endpoint para borrar el archivo del servidor si es local
    form.drive_links.splice(index, 1);
};

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-900/40 backdrop-blur-sm overflow-y-auto custom-scrollbar">
        <div class="bg-white dark:bg-gray-800 w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden my-auto border border-zinc-100 dark:border-gray-700">
            <!-- Form Header -->
            <div class="p-4 sm:p-6 pb-0">
                <div class="flex items-center gap-3 sm:gap-4 border-b border-zinc-100 dark:border-gray-600 pb-4 mb-2 sm:pb-5 text-left">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 sm:w-7 sm:h-7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-5.25v9" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white leading-tight">
                            {{ form.id ? 'Editar Producto' : 'Nuevo Producto' }}
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 font-normal mt-0.5 sm:mt-1">
                            {{ form.id ? 'Actualiza los datos del producto' : 'Agrega un nuevo producto al catálogo' }}
                        </p>
                    </div>
                    <button @click="closeModal" class="ml-auto p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submit" class="p-4 sm:p-6 pt-2">
                <div class="space-y-5">
                    <!-- Fila 1: Nombre, Código, Unidad de Medida -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1">Nombre *</label>
                            <input v-model="form.name" type="text" placeholder="Nombre del producto" required
                                   class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-lg text-sm shadow-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-colors">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1">Código</label>
                            <div class="relative group">
                                <input v-model="form.code" type="text" placeholder="SKU o código (Opcional)"
                                       class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-lg text-sm shadow-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-colors">
                                <button type="button" @click="generateCode"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-indigo-600 hover:text-indigo-800 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4" :class="{ 'animate-spin': isGeneratingCode }">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1">Unidad de Medida *</label>
                            <select v-model="form.unit_of_measure_id" required
                                    class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-lg text-sm shadow-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-colors">
                                <option value="" disabled selected>Seleccionar unidad</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Fila 2: Stock Mínimo, Almacén, Categorías -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1">Stock Mínimo *</label>
                            <input v-model="form.min_stock" type="number" placeholder="0" required
                                   class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-lg text-sm shadow-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-colors">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1">Almacén *</label>
                            <select v-model="form.warehouse_id" required
                                    class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-lg text-sm shadow-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-colors">
                                <option value="" disabled selected>Seleccionar almacén</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1 relative">
                            <label class="block text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1">Categorías *</label>
                            
                            <!-- Multiselect Trigger/Container -->
                            <div @click="isCategoryMenuOpen = !isCategoryMenuOpen"
                                 class="min-h-[38px] w-full px-2 py-1.5 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-lg text-sm shadow-sm cursor-pointer flex flex-wrap gap-1.5 items-center transition-all focus-within:ring-1 focus-within:ring-indigo-500">
                                
                                <template v-if="selectedCategories.length > 0">
                                    <div v-for="cat in selectedCategories" :key="cat.id"
                                         class="flex items-center gap-1 px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-md text-[10px] font-bold uppercase border border-indigo-100 dark:border-indigo-800/50">
                                        {{ cat.name }}
                                        <button @click.stop="toggleCategory(cat.id)" class="hover:text-indigo-900 dark:hover:text-white transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <span v-else class="text-zinc-400 dark:text-zinc-500 px-1">Seleccionar categorías...</span>

                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" 
                                     class="w-4 h-4 ml-auto text-zinc-400 transition-transform" :class="{ 'rotate-180': isCategoryMenuOpen }">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>

                            <!-- Dropdown Menu -->
                            <div v-if="isCategoryMenuOpen" 
                                 class="absolute z-[110] left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-zinc-200 dark:border-gray-600 rounded-xl shadow-xl overflow-hidden animate-in fade-in zoom-in duration-100">
                                
                                <div class="p-2 border-b border-zinc-100 dark:border-gray-700">
                                    <input v-model="categorySearch" type="text" placeholder="Buscar categoría..." @click.stop
                                           class="w-full px-3 py-1.5 text-xs bg-zinc-50 dark:bg-zinc-900 border-none rounded-lg focus:ring-0 dark:text-white">
                                </div>

                                <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                    <div v-for="cat in filteredCategories" :key="cat.id"
                                         @click.stop="toggleCategory(cat.id)"
                                         class="px-4 py-2 text-sm flex items-center justify-between cursor-pointer transition-colors"
                                         :class="[
                                             form.category_ids.includes(cat.id) 
                                             ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-bold' 
                                             : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700'
                                         ]">
                                        {{ cat.name }}
                                        <svg v-if="form.category_ids.includes(cat.id)" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </div>
                                    <div v-if="filteredCategories.length === 0" class="px-4 py-6 text-center text-xs text-zinc-400">
                                        No se encontraron categorías
                                    </div>
                                </div>
                            </div>

                            <!-- Invisible overlay to close on outside click -->
                            <div v-if="isCategoryMenuOpen" @click="isCategoryMenuOpen = false" class="fixed inset-0 z-[105]"></div>
                        </div>
                    </div>

                    <!-- Fila 3: U x Epq, Nombre del Empaque -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1">U x Epq (Unidades x Empaque)</label>
                            <input v-model="form.units_per_package" type="number" step="0.0001" placeholder="1"
                                   class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-lg text-sm shadow-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-colors">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1">Nombre del Empaque</label>
                            <input v-model="form.package_name" type="text" placeholder="Ej: Caja, Bolsa, Saco"
                                   class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-lg text-sm shadow-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-colors">
                        </div>
                    </div>

                    <!-- Fila 4: Ubicación, Marca -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1">Ubicación</label>
                            <input v-model="form.location" type="text" placeholder="Ej: B-123-45"
                                   class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-lg text-sm shadow-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-colors">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1">Marca</label>
                            <input v-model="form.brand" type="text" placeholder="Ej: pil, paceña, etc."
                                   class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-lg text-sm shadow-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-colors">
                        </div>
                    </div>

                    <!-- Imágenes Section -->
                    <div class="space-y-3 pt-2">
                        <label class="block text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1">Imágenes del Producto (URLs - Máximo {{ maxImages }})</label>
                        <div class="flex gap-2">
                            <input v-model="imageUrlInput" type="url" placeholder="https://ejemplo.com/imagen.jpg"
                                   class="flex-1 px-4 py-2.5 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-xl text-sm shadow-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                            <button type="button" @click="addDriveLink"
                                    class="p-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/20 active:scale-95 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </button>
                        </div>

                        <!-- Grid Imágenes -->
                        <div v-if="form.drive_links.length > 0 || form.new_images.length > 0 || isProcessingImage" class="flex flex-wrap gap-5 py-4">
                            <!-- Imágenes ya guardadas en el servidor -->
                            <div v-for="(link, index) in form.drive_links" :key="'link-' + index" class="relative group animate-in zoom-in duration-300">
                                <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-2xl overflow-hidden border border-zinc-200 dark:border-secondary-700 shadow-xl bg-white dark:bg-secondary-800 flex items-center justify-center p-2 group-hover:border-indigo-400 transition-all duration-300">
                                    <img :src="link" class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-110">
                                </div>
                                <button type="button" @click="removeDriveLink(index)"
                                        class="absolute -top-3 -right-3 bg-rose-500 text-white w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-600 shadow-xl transition-all duration-300 opacity-0 group-hover:opacity-100 scale-50 group-hover:scale-100 z-30 border-4 border-white dark:border-secondary-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Imágenes locales nuevas (pendientes de subida) -->
                            <div v-for="(file, index) in form.new_images" :key="'local-' + index" class="relative group animate-in zoom-in duration-300">
                                <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-2xl overflow-hidden border-2 border-indigo-200 dark:border-indigo-500/30 shadow-xl bg-white dark:bg-secondary-800 flex items-center justify-center p-2 group-hover:border-indigo-500 transition-all duration-300">
                                    <img :src="getLocalPreview(file)" class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-110">
                                    <!-- Badge de "Nuevo" -->
                                    <div class="absolute top-2 left-2 px-2 py-0.5 bg-indigo-600 text-[8px] font-black text-white rounded-full uppercase tracking-tighter">Local</div>
                                </div>
                                <button type="button" @click="removeLocalImage(index)"
                                        class="absolute -top-3 -right-3 bg-rose-500 text-white w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-600 shadow-xl transition-all duration-300 opacity-0 group-hover:opacity-100 scale-50 group-hover:scale-100 z-30 border-4 border-white dark:border-secondary-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Estado de Carga (Spinner) -->
                            <div v-if="isProcessingImage" class="w-28 h-28 sm:w-36 sm:h-36 rounded-2xl border-2 border-dashed border-indigo-400 dark:border-indigo-500 flex flex-col items-center justify-center bg-indigo-50/30 dark:bg-indigo-900/10 animate-pulse">
                                <svg class="animate-spin h-8 w-8 text-indigo-600 dark:text-indigo-400 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-tighter">Procesando...</span>
                            </div>
                        </div>
                        <!-- Dropzone Local -->
                        <div v-if="form.drive_links.length < maxImages && !isProcessingImage" class="relative group">
                            <input type="file" @change="handleImageUpload" class="absolute inset-0 opacity-0 cursor-pointer z-10" accept="image/*">
                            <div class="flex flex-col items-center justify-center w-full py-6 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-2xl bg-zinc-50 dark:bg-zinc-900/50 group-hover:bg-zinc-100 dark:group-hover:bg-zinc-800/50 transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-zinc-300 dark:text-zinc-600 mb-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                </svg>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 font-black uppercase tracking-widest text-center">
                                    Cargar Imagen Local (Máx. {{ maxImages }} total)
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Áreas de Texto a Ancho Completo -->
                    <div class="space-y-6 pt-4">
                        <!-- Descripción Corta -->
                        <div class="space-y-2">
                            <label class="flex justify-between items-center px-1">
                                <span class="text-[11px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Descripción Corta</span>
                                <span :class="[
                                    'text-[10px] font-bold px-2 py-0.5 rounded-full',
                                    (form.description?.length || 0) > 1800 ? 'bg-amber-100 text-amber-600' : 'text-zinc-400'
                                ]">
                                    {{ form.description?.length || 0 }} / 2000
                                </span>
                            </label>
                            <textarea v-model="form.description" rows="3" maxlength="2000"
                                      placeholder="Resume las características principales para la tienda web..."
                                      class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-zinc-200 dark:border-gray-600 rounded-2xl text-sm shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 dark:text-white transition-all resize-y"></textarea>
                        </div>
                    </div>

                    <!-- Footer Switches (UX Refactored) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-6 border-t border-zinc-100 dark:border-gray-700">
                        <!-- Inventario (Estado) -->
                        <div @click="form.is_active = !form.is_active"
                             :class="[
                                 'p-4 rounded-2xl border-2 flex items-center justify-between transition-all duration-300 cursor-pointer group',
                                 form.is_active 
                                    ? 'bg-indigo-50/50 dark:bg-indigo-900/10 border-indigo-200 dark:border-indigo-500/30 shadow-sm shadow-indigo-100/50' 
                                    : 'bg-zinc-50 dark:bg-gray-800/40 border-zinc-200 dark:border-gray-700 opacity-80 hover:opacity-100'
                             ]">
                            <div class="flex items-center gap-3">
                                <div :class="[
                                    'w-9 h-9 rounded-xl flex items-center justify-center transition-colors flex-shrink-0',
                                    form.is_active ? 'bg-indigo-600 text-white' : 'bg-zinc-200 dark:bg-gray-700 text-zinc-400'
                                ]">
                                    <span class="material-symbols-outlined text-lg">{{ form.is_active ? 'check_circle' : 'pause_circle' }}</span>
                                </div>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-widest" :class="form.is_active ? 'text-indigo-900 dark:text-indigo-100' : 'text-zinc-500'">Inventario</p>
                                    <p class="text-[10px] font-medium leading-tight mt-0.5" :class="form.is_active ? 'text-indigo-600/80' : 'text-zinc-400'">
                                        {{ form.is_active ? 'Disponible' : 'Pausado' }}
                                    </p>
                                </div>
                            </div>
                            <div :class="[
                                'relative w-10 h-5 rounded-full transition-colors duration-300 flex-shrink-0',
                                form.is_active ? 'bg-indigo-600' : 'bg-zinc-300 dark:bg-zinc-600'
                            ]">
                                <div :class="['absolute top-1 w-3 h-3 rounded-full bg-white transition-transform duration-300 shadow-sm', form.is_active ? 'translate-x-6' : 'translate-x-1']"></div>
                            </div>
                        </div>

                        <!-- Fecha de Vencimiento -->
                        <div @click="form.has_expiration = !form.has_expiration"
                             :class="[
                                 'p-4 rounded-2xl border-2 flex items-center justify-between transition-all duration-300 cursor-pointer group',
                                 form.has_expiration
                                    ? 'bg-amber-50/50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-500/30 shadow-sm shadow-amber-100/50'
                                    : 'bg-zinc-50 dark:bg-gray-800/40 border-zinc-200 dark:border-gray-700 opacity-80 hover:opacity-100'
                             ]">
                            <div class="flex items-center gap-3">
                                <div :class="[
                                    'w-9 h-9 rounded-xl flex items-center justify-center transition-colors flex-shrink-0',
                                    form.has_expiration ? 'bg-amber-500 text-white' : 'bg-zinc-200 dark:bg-gray-700 text-zinc-400'
                                ]">
                                    <span class="material-symbols-outlined text-lg">{{ form.has_expiration ? 'event_available' : 'event_busy' }}</span>
                                </div>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-widest" :class="form.has_expiration ? 'text-amber-900 dark:text-amber-100' : 'text-zinc-500'">Vencimiento</p>
                                    <p class="text-[10px] font-medium leading-tight mt-0.5" :class="form.has_expiration ? 'text-amber-600/80' : 'text-zinc-400'">
                                        {{ form.has_expiration ? 'Requiere fecha' : 'Sin vencimiento' }}
                                    </p>
                                </div>
                            </div>
                            <div :class="[
                                'relative w-10 h-5 rounded-full transition-colors duration-300 flex-shrink-0',
                                form.has_expiration ? 'bg-amber-500' : 'bg-zinc-300 dark:bg-zinc-600'
                            ]">
                                <div :class="['absolute top-1 w-3 h-3 rounded-full bg-white transition-transform duration-300 shadow-sm', form.has_expiration ? 'translate-x-6' : 'translate-x-1']"></div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-2 sm:gap-3 w-full pt-4 border-t border-zinc-100 dark:border-gray-700 mt-4">
                    <button type="button" @click="closeModal"
                            class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="form.processing"
                            class="w-full sm:w-auto px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-md shadow-indigo-500/20 transition-all active:scale-95 disabled:opacity-50">
                        {{ form.processing ? 'Guardando...' : (form.id ? 'Guardar Cambios' : 'Crear Producto') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
}
</style>
