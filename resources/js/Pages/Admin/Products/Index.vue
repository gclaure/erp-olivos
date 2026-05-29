<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import ImportModal from './Partials/ImportModal.vue';

const props = defineProps({
    products: Object,
    filters: Object,
    categories: Array,
});

const search = ref(props.filters.search || '');
const categoryId = ref(props.filters.category_id || '');
const showImportModal = ref(false);

// Gallery state
const showGallery = ref(false);
const galleryImages = ref([]);
const galleryTitle = ref('');
const currentImageIndex = ref(0);

const openGallery = (images, title) => {
    if (!images || images.length === 0) return;
    galleryImages.value = images;
    galleryTitle.value = title;
    currentImageIndex.value = 0;
    showGallery.value = true;
};

const nextImage = () => {
    currentImageIndex.value = (currentImageIndex.value + 1) % galleryImages.value.length;
};

const prevImage = () => {
    currentImageIndex.value = (currentImageIndex.value - 1 + galleryImages.value.length) % galleryImages.value.length;
};

// Filtering
watch([search, categoryId], debounce(() => {
    router.get(route('admin.products.index'), {
        search: search.value,
        category_id: categoryId.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 500));

const confirmDelete = (id) => {
    if (confirm('¿Estás seguro de eliminar este producto?')) {
        router.delete(route('admin.products.destroy', id));
    }
};
</script>

<template>
    <Head title="Productos" />

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                    <a :href="route('admin.products.template')"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-zinc-100 dark:bg-gray-700 text-zinc-700 dark:text-zinc-200 text-sm font-bold rounded-xl hover:bg-zinc-200 dark:hover:bg-gray-600 transition-all active:scale-95 border border-zinc-200 dark:border-gray-600 w-full sm:w-auto">
                        <span class="material-symbols-outlined text-lg">description</span>
                        <span>Plantilla</span>
                    </a>
                </div>
                <button @click="router.visit(route('admin.products.create'))"
                        class="inline-flex items-center justify-center gap-2.5 px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 active:scale-95 group w-full sm:w-auto">
                    <span class="material-symbols-outlined group-hover:rotate-90 transition-transform">add</span>
                    <span>Nuevo Producto</span>
                </button>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 mb-8 items-center">
            <div class="lg:col-span-3 relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-zinc-400 group-focus-within:text-indigo-500 transition-colors">search</span>
                </div>
                <input v-model="search"
                       type="text"
                       placeholder="Buscar productos por nombre o código..."
                       class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-800 border-zinc-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white placeholder-zinc-400 shadow-sm">
            </div>
            
            <div class="lg:col-span-2">
                <select v-model="categoryId" class="w-full py-3 bg-white dark:bg-gray-800 border-zinc-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all dark:text-white shadow-sm">
                    <option value="">Todas las categorías</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl border border-zinc-200 dark:border-gray-700 shadow-xl overflow-hidden transition-all mb-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-100 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center w-16">#</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center w-24">Preview</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Información Base</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-left hidden lg:table-cell">Categoría</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center w-28">Stock</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-right">Precio</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-center hidden md:table-cell">Estado</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-right w-32">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-700/50">
                        <tr v-for="(product, index) in products.data" :key="product.id" class="bg-white dark:bg-gray-800 transition-all duration-200 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 group">
                            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
                                <span class="text-xs font-bold text-zinc-400 dark:text-zinc-200 bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1.5 rounded-lg">
                                    {{ products.meta.from + index }}
                                </span>
                            </td>
                            <td class="px-6 py-4 align-middle text-center">
                                <div class="relative inline-block group/img cursor-pointer" @click="openGallery(product.drive_links || [], product.name)">
                                    <img v-if="product.drive_links?.length > 0" :src="product.drive_links[0]" class="w-12 h-12 rounded-xl object-cover border-2 border-white dark:border-gray-700 shadow-sm group-hover/img:scale-110 transition-all">
                                    <div v-else class="w-12 h-12 rounded-xl bg-zinc-50 dark:bg-gray-700/50 flex items-center justify-center border border-dashed border-zinc-200 dark:border-gray-600">
                                        <span class="material-symbols-outlined text-zinc-300 dark:text-zinc-600">image</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="flex flex-col">
                                    <span class="font-bold text-zinc-900 dark:text-zinc-100 tracking-tight text-sm">{{ product.name }}</span>
                                    <div class="flex items-center gap-2 mt-1 font-mono text-[10px]">
                                        <span class="text-zinc-400 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 px-1.5 rounded">{{ product.code || 'S/C' }}</span>
                                        <span class="font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest text-[9px]">{{ product.unit_of_measure?.abbreviation || 'UND' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle hidden lg:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="cat in product.categories" :key="cat.id" class="text-[10px] bg-zinc-100 dark:bg-zinc-700 px-2 py-0.5 rounded-full text-zinc-600 dark:text-zinc-300">
                                        {{ cat.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center align-middle">
                                <span :class="[
                                    'text-sm font-black tracking-tight',
                                    product.total_stock > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                                ]">
                                    {{ product.total_stock }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                <span class="text-base font-black text-zinc-900 dark:text-white tracking-tight">
                                    <small class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 mr-0.5 uppercase">Bs.</small>{{ product.price }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center align-middle hidden md:table-cell">
                                <span v-if="product.is_active" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/20">
                                    Activo
                                </span>
                                <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-gray-600">
                                    Inactivo
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="router.visit(route('admin.products.edit', product.id))" class="w-9 h-9 flex items-center justify-center text-zinc-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </button>
                                    <button @click="confirmDelete(product.id)" class="w-9 h-9 flex items-center justify-center text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-all">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="products.meta.links.length > 3" class="px-6 py-5 border-t border-zinc-100 dark:border-gray-700 bg-zinc-50 dark:bg-zinc-800 flex justify-center gap-2">
                <template v-for="link in products.meta.links" :key="link.label">
                    <Link :href="link.url || '#'" v-html="link.label" :class="[
                        'px-3 py-1 text-sm rounded-lg transition-all',
                        link.active ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100'
                    ]" />
                </template>
            </div>
        </div>

        <ImportModal :show="showImportModal" @close="showImportModal = false" />

        <!-- Gallery Lightbox -->
        <div v-if="showGallery" class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-zinc-950/90 backdrop-blur-xl p-4">
            <div class="absolute top-0 left-0 w-full p-8 flex justify-between items-start">
                <div class="px-6 py-3 bg-white/10 backdrop-blur-xl border border-white/20 rounded-full text-white flex items-center gap-3">
                    <span class="material-symbols-outlined text-indigo-400">inventory_2</span>
                    <h3 class="font-bold">{{ galleryTitle }}</h3>
                </div>
                <button @click="showGallery = false" class="px-6 py-3 bg-rose-600 text-white rounded-full font-bold flex items-center gap-2">
                    Cerrar <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="flex-1 flex items-center justify-center relative w-full">
                <button v-if="galleryImages.length > 1" @click="prevImage" class="absolute left-4 w-16 h-16 bg-white/10 rounded-full text-white flex items-center justify-center"><span class="material-symbols-outlined text-4xl">chevron_left</span></button>
                <img :src="galleryImages[currentImageIndex]" class="max-w-full max-h-[80vh] object-contain rounded-2xl shadow-2xl ring-1 ring-white/20">
                <button v-if="galleryImages.length > 1" @click="nextImage" class="absolute right-4 w-16 h-16 bg-white/10 rounded-full text-white flex items-center justify-center"><span class="material-symbols-outlined text-4xl">chevron_right</span></button>
            </div>

            <div v-if="galleryImages.length > 1" class="absolute bottom-10 flex gap-4 p-4 bg-zinc-900/50 rounded-3xl backdrop-blur-xl">
                <button v-for="(img, idx) in galleryImages" :key="idx" @click="currentImageIndex = idx" class="w-16 h-16 rounded-xl overflow-hidden border-2 transition-all" :class="idx === currentImageIndex ? 'border-indigo-500 scale-110' : 'border-transparent opacity-50'">
                    <img :src="img" class="w-full h-full object-cover">
                </button>
            </div>
        </div>
    </div>
</template>
