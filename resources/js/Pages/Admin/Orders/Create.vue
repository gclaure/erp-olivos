<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useImageCompression } from '@/Composables/useImageCompression';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    nextFormattedNumber: {
        type: String,
        default: '00001',
    },
    todayDate: {
        type: String,
        required: true,
    },
    deliveryModes: {
        type: Array,
        default: () => [],
    },
    complementProviders: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => ['QR', 'Efectivo', 'Transferencia', 'Tarjeta'],
    },
    catalogProducts: {
        type: Array,
        default: () => [],
    },
});

const { compressAndConvertToWebP } = useImageCompression();

const createDefaultItem = () => ({
    uid: `${Date.now()}_${Math.random().toString(36).slice(2, 7)}`,
    product_id: null,
    product_name: 'Torta',
    quantity: 1,
    portions_or_size: '15 porciones',
    unit_price: 0,
    custom_description: '',
    reference_notes: '',
    observations: '',
    details: [
        { label: 'Sabor de masas', value: '' },
        { label: 'Rellenos', value: '' },
        { label: 'Alto', value: '' },
    ],
    complements: [],
    images: [],
    imagePreviews: [],
    compressingImages: false,
});

const form = useForm({
    customer: {
        id: null,
        full_name: '',
        phone: '',
    },
    registered_at: props.todayDate,
    delivery_date: props.todayDate,
    delivery_time: '12:00 PM',
    delivery_mode: 'pickup',
    delivery_address: '',
    general_notes: '',
    items: [createDefaultItem()],
    complements: [],
});

// Autocompletado de clientes existentes por celular o nombre
const customerSuggestions = ref([]);
const showCustomerDropdown = ref(false);
const searchingCustomers = ref(false);
const selectedExistingCustomer = ref(null);
let customerSearchTimeout = null;

const triggerCustomerSearch = (query) => {
    if (customerSearchTimeout) {
        clearTimeout(customerSearchTimeout);
    }

    const clean = (query || '').trim();
    if (clean.length < 2) {
        customerSuggestions.value = [];
        showCustomerDropdown.value = false;
        return;
    }

    customerSearchTimeout = setTimeout(async () => {
        searchingCustomers.value = true;
        try {
            const { data } = await axios.get(route('admin.orders.customers.search'), {
                params: { q: clean },
            });
            customerSuggestions.value = data.customers || [];
            showCustomerDropdown.value = customerSuggestions.value.length > 0;
        } catch {
            customerSuggestions.value = [];
        } finally {
            searchingCustomers.value = false;
        }
    }, 260);
};

const selectCustomerSuggestion = (cust) => {
    form.customer.id = cust.id;
    form.customer.full_name = cust.full_name;
    form.customer.phone = cust.phone;
    if (cust.address && !form.delivery_address) {
        form.delivery_address = cust.address;
    }
    selectedExistingCustomer.value = cust;
    showCustomerDropdown.value = false;
};

const clearSelectedCustomer = () => {
    form.customer.id = null;
    selectedExistingCustomer.value = null;
};

// Horas rápidas sugeridas
const quickTimes = ['09:00 AM', '10:30 AM', '12:00 PM', '03:00 PM', '05:00 PM', '06:30 PM'];

// Especificaciones rápidas sugeridas
const quickSpecLabels = [
    { label: 'Sabor de masas', placeholder: 'Ej: chocolate-vainilla-chocolate' },
    { label: 'Rellenos', placeholder: 'Ej: Los respectivos / Dulce de leche' },
    { label: 'Alto', placeholder: 'Ej: 8 cm' },
    { label: 'Cobertura', placeholder: 'Ej: Crema chantilly / Buttercream' },
    { label: 'Dedicatoria', placeholder: 'Ej: Feliz Cumpleaños Andrea' },
    { label: 'Forma / Pisos', placeholder: 'Ej: Redonda 1 piso' },
];

// Gestión de productos en el pedido
const addProductItem = () => {
    form.items.push(createDefaultItem());
};

const removeProductItem = (index) => {
    if (form.items.length <= 1) return;
    const item = form.items[index];
    if (item?.imagePreviews) {
        item.imagePreviews.forEach((p) => URL.revokeObjectURL(p.url));
    }
    form.items.splice(index, 1);
};

const addDetailSpec = (item, presetLabel = '') => {
    item.details.push({
        label: presetLabel,
        value: '',
    });
};

const removeDetailSpec = (item, detailIndex) => {
    item.details.splice(detailIndex, 1);
};

const addItemComplement = (item, presetDesc = '', presetProvider = 'client') => {
    item.complements.push({
        description: presetDesc,
        provider_type: presetProvider,
    });
};

const removeItemComplement = (item, compIndex) => {
    item.complements.splice(compIndex, 1);
};

// Procesamiento de imágenes: Comprimir primero y convertir a .webp en el navegador
const handleItemImagesChange = async (event, item) => {
    const selectedFiles = Array.from(event.target.files || []);
    if (!selectedFiles.length) return;

    item.compressingImages = true;

    try {
        for (const rawFile of selectedFiles) {
            if (!rawFile.type.startsWith('image/')) {
                continue;
            }

            const originalSizeKB = Math.round(rawFile.size / 1024);

            // 1. Comprimir y 2. Convertir a .webp (calidad 0.80, máx 1600px)
            const webpFile = await compressAndConvertToWebP(rawFile, {
                maxSizeMB: 0.75,
                maxWidthOrHeight: 1600,
                fileType: 'image/webp',
                initialQuality: 0.8,
            });

            const compressedSizeKB = Math.round(webpFile.size / 1024);
            const previewUrl = URL.createObjectURL(webpFile);

            item.images.push(webpFile);
            item.imagePreviews.push({
                url: previewUrl,
                name: webpFile.name,
                originalSizeKB,
                compressedSizeKB,
            });
        }
    } catch {
        Swal.fire({
            icon: 'error',
            title: 'Error al procesar imagen',
            text: 'No se pudo comprimir y convertir una de las fotografías a formato WebP.',
        });
    } finally {
        item.compressingImages = false;
        event.target.value = '';
    }
};

const removeItemImage = (item, imgIndex) => {
    const preview = item.imagePreviews[imgIndex];
    if (preview?.url) {
        URL.revokeObjectURL(preview.url);
    }
    item.images.splice(imgIndex, 1);
    item.imagePreviews.splice(imgIndex, 1);
};

const allPreviewComplements = computed(() => {
    const list = [];
    form.items.forEach((it) => {
        (it.complements || []).forEach((c) => {
            if (c.description && c.description.trim() !== '') {
                list.push(c);
            }
        });
    });
    return list;
});

const anyImageCompressing = computed(() => form.items.some((it) => it.compressingImages));

const submitOrder = () => {
    if (anyImageCompressing.value) {
        Swal.fire({
            icon: 'info',
            title: 'Procesando imágenes',
            text: 'Espere un momento mientras termina la compresión a formato .webp.',
        });
        return;
    }

    form.post(route('admin.orders.store'), {
        forceFormData: true,
        preserveScroll: true,
        onError: () => {
            Swal.fire({
                icon: 'warning',
                title: 'Verifique los campos requeridos',
                text: 'Revise los datos del cliente, fecha de entrega o productos del pedido.',
            });
        },
    });
};

onBeforeUnmount(() => {
    form.items.forEach((item) => {
        (item.imagePreviews || []).forEach((p) => URL.revokeObjectURL(p.url));
    });
});
</script>

<template>
    <Head title="Registrar Pedido" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-8">
            <div class="text-left">
                <div class="flex items-center gap-2.5 mb-1">
                    <span class="inline-flex items-center px-3 py-1 rounded-xl bg-indigo-600 text-white text-xs font-black uppercase tracking-wider shadow-sm">
                        Reserva #{{ nextFormattedNumber }}
                    </span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">
                        Registro de Pedido
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white leading-tight tracking-tight uppercase">
                    Registrar Pedido de Cliente
                </h1>
                <p class="text-xs sm:text-sm text-zinc-500 dark:text-secondary-400 mt-1">
                    Ingrese los datos del cliente, entrega, productos personalizados, fotografías (.webp), complementos e información económica.
                </p>
            </div>

            <Link
                :href="route('admin.orders.index')"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-secondary-800 text-zinc-600 dark:text-secondary-300 text-xs font-black uppercase tracking-widest rounded-2xl border border-zinc-200 dark:border-secondary-700 hover:bg-zinc-50 dark:hover:bg-secondary-700 transition-all shadow-sm active:scale-95 w-full sm:w-auto min-h-[44px]"
            >
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Historial de Pedidos
            </Link>
        </div>

        <form @submit.prevent="submitOrder" class="space-y-6">
            <!-- Errores Globales -->
            <div
                v-if="Object.keys(form.errors).length > 0"
                class="p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-2xl flex items-start gap-3 text-rose-600 dark:text-rose-400 shadow-sm"
            >
                <span class="material-symbols-outlined text-xl">error</span>
                <div class="flex-1 text-xs font-black uppercase tracking-widest leading-relaxed">
                    Hay errores en el formulario. Por favor revise los campos requeridos antes de guardar:
                    <ul class="list-disc pl-4 mt-2">
                        <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                    </ul>
                </div>
            </div>

            <!-- 1. Datos del Cliente e Información de la Reserva -->
            <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 shadow-sm relative">
                <div class="h-1.5 w-full bg-gradient-to-r from-zinc-400 to-zinc-500 rounded-t-3xl"></div>
                <div class="p-4 sm:p-5 border-b border-zinc-100 dark:border-secondary-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-zinc-50/50 dark:bg-zinc-900/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 flex items-center justify-center shadow-inner">
                            <span class="material-symbols-outlined">person</span>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-zinc-900 dark:text-white uppercase tracking-tight">
                                1. Datos del Cliente y Reserva
                            </h2>
                            <p class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">
                                Información personal del cliente, fecha, hora y modalidad de entrega
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="selectedExistingCustomer"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300 text-[11px] font-black uppercase tracking-wider w-fit"
                    >
                        <span class="material-symbols-outlined text-sm">verified</span>
                        <span>Cliente Recurrente ({{ selectedExistingCustomer.orders_count || 1 }} pedidos)</span>
                        <button type="button" @click="clearSelectedCustomer" class="hover:text-rose-500 ml-1 font-black">✕</button>
                    </div>
                </div>

                <div class="p-4 sm:p-6 lg:p-8 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                        <!-- Nombre Completo con autocompletado -->
                        <div class="sm:col-span-2 relative">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                Nombre Completo del Cliente *
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.customer.full_name"
                                    type="text"
                                    placeholder="Ej: Andrea Longhi"
                                    @input="triggerCustomerSearch(form.customer.full_name)"
                                    :class="{'border-rose-500 ring-4 ring-rose-500/10': form.errors['customer.full_name']}"
                                    class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 shadow-sm font-bold"
                                />
                                <span
                                    v-if="searchingCustomers"
                                    class="material-symbols-outlined absolute right-4 top-3 text-indigo-500 animate-spin text-lg"
                                >
                                    sync
                                </span>
                            </div>
                            <p v-if="form.errors['customer.full_name']" class="mt-2 ml-1 text-[10px] font-black text-rose-500 uppercase tracking-widest">
                                {{ form.errors['customer.full_name'] }}
                            </p>

                            <!-- Sugerencias de Clientes Registrados -->
                            <div
                                v-if="showCustomerDropdown && customerSuggestions.length"
                                class="absolute z-50 left-0 right-0 mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-2xl shadow-2xl max-h-60 overflow-y-auto custom-scrollbar divide-y divide-zinc-100 dark:divide-secondary-700"
                            >
                                <button
                                    v-for="cust in customerSuggestions"
                                    :key="cust.id"
                                    type="button"
                                    @click="selectCustomerSuggestion(cust)"
                                    class="w-full px-4 py-3 text-left hover:bg-indigo-50 dark:hover:bg-secondary-700 flex items-center justify-between transition-colors"
                                >
                                    <div>
                                        <div class="text-xs font-black text-zinc-900 dark:text-white uppercase">{{ cust.full_name }}</div>
                                        <div class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">Cel: {{ cust.phone }}</div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-secondary-900 text-[10px] font-black uppercase text-zinc-600 dark:text-secondary-300">
                                        {{ cust.orders_count || 0 }} pedidos
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Número de Celular -->
                        <div class="sm:col-span-1">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                Número de Celular *
                            </label>
                            <input
                                v-model="form.customer.phone"
                                type="text"
                                placeholder="Ej: 78631135"
                                @input="triggerCustomerSearch(form.customer.phone)"
                                :class="{'border-rose-500 ring-4 ring-rose-500/10': form.errors['customer.phone']}"
                                class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 shadow-sm font-bold"
                            />
                            <p v-if="form.errors['customer.phone']" class="mt-2 ml-1 text-[10px] font-black text-rose-500 uppercase tracking-widest">
                                {{ form.errors['customer.phone'] }}
                            </p>
                        </div>

                        <!-- Fecha de Registro -->
                        <div class="sm:col-span-1">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                Fecha de Registro
                            </label>
                            <input
                                v-model="form.registered_at"
                                type="date"
                                class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white shadow-sm font-semibold"
                            />
                        </div>

                        <!-- Fecha Programada de Entrega -->
                        <div class="sm:col-span-1">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                Fecha Programada de Entrega *
                            </label>
                            <input
                                v-model="form.delivery_date"
                                type="date"
                                required
                                :class="{'border-rose-500 ring-4 ring-rose-500/10': form.errors.delivery_date}"
                                class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white shadow-sm font-semibold"
                            />
                            <p v-if="form.errors.delivery_date" class="mt-2 ml-1 text-[10px] font-black text-rose-500 uppercase tracking-widest">
                                {{ form.errors.delivery_date }}
                            </p>
                        </div>

                        <!-- Hora de Entrega -->
                        <div class="sm:col-span-1">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                Hora de Entrega *
                            </label>
                            <input
                                v-model="form.delivery_time"
                                type="text"
                                placeholder="Ej: 12:00 PM"
                                :class="{'border-rose-500 ring-4 ring-rose-500/10': form.errors.delivery_time}"
                                class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 shadow-sm font-bold"
                            />
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <button
                                    v-for="t in quickTimes"
                                    :key="t"
                                    type="button"
                                    @click="form.delivery_time = t"
                                    class="px-2 py-1 text-[10px] font-black uppercase rounded-lg border transition-colors"
                                    :class="form.delivery_time === t
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-zinc-50 dark:bg-secondary-900 text-zinc-600 dark:text-secondary-300 border-zinc-200 dark:border-secondary-700 hover:bg-zinc-100 dark:hover:bg-secondary-700'"
                                >
                                    {{ t }}
                                </button>
                            </div>
                        </div>

                        <!-- Modalidad de Entrega -->
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                Modalidad de Entrega *
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    @click="form.delivery_mode = 'pickup'"
                                    class="flex items-center gap-3 p-3.5 rounded-2xl border transition-all text-left min-h-[48px]"
                                    :class="form.delivery_mode === 'pickup'
                                        ? 'border-indigo-600 bg-indigo-50/70 dark:bg-indigo-950/40 text-zinc-900 dark:text-white ring-2 ring-indigo-500/20'
                                        : 'border-zinc-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 text-zinc-600 dark:text-secondary-300 hover:bg-zinc-50 dark:hover:bg-secondary-700'"
                                >
                                    <span class="material-symbols-outlined text-xl" :class="form.delivery_mode === 'pickup' ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-400'">
                                        storefront
                                    </span>
                                    <div>
                                        <div class="text-xs font-black uppercase tracking-wider">Recoger en tienda</div>
                                        <div class="text-[10px] text-zinc-500 dark:text-secondary-400">Entrega en sucursal</div>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    @click="form.delivery_mode = 'delivery'"
                                    class="flex items-center gap-3 p-3.5 rounded-2xl border transition-all text-left min-h-[48px]"
                                    :class="form.delivery_mode === 'delivery'
                                        ? 'border-indigo-600 bg-indigo-50/70 dark:bg-indigo-950/40 text-zinc-900 dark:text-white ring-2 ring-indigo-500/20'
                                        : 'border-zinc-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 text-zinc-600 dark:text-secondary-300 hover:bg-zinc-50 dark:hover:bg-secondary-700'"
                                >
                                    <span class="material-symbols-outlined text-xl" :class="form.delivery_mode === 'delivery' ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-400'">
                                        local_shipping
                                    </span>
                                    <div>
                                        <div class="text-xs font-black uppercase tracking-wider">Entrega a domicilio</div>
                                        <div class="text-[10px] text-zinc-500 dark:text-secondary-400">Envío a dirección</div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Dirección (cuando corresponda) -->
                        <div v-if="form.delivery_mode === 'delivery'" class="sm:col-span-2 lg:col-span-4">
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                Dirección de Entrega *
                            </label>
                            <input
                                v-model="form.delivery_address"
                                type="text"
                                placeholder="Ingrese la dirección exacta, referencias de la casa o ubicación..."
                                :class="{'border-rose-500 ring-4 ring-rose-500/10': form.errors.delivery_address}"
                                class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 shadow-sm font-semibold"
                            />
                            <p v-if="form.errors.delivery_address" class="mt-2 ml-1 text-[10px] font-black text-rose-500 uppercase tracking-widest">
                                {{ form.errors.delivery_address }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Detalle de los Productos Solicitados -->
            <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 shadow-sm relative">
                <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-t-3xl"></div>
                <div class="p-4 sm:p-5 border-b border-zinc-100 dark:border-secondary-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-zinc-50/50 dark:bg-zinc-900/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-inner">
                            <span class="material-symbols-outlined">cake</span>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-zinc-900 dark:text-white uppercase tracking-tight">
                                2. Detalle de los Productos Solicitados ({{ form.items.length }})
                            </h2>
                            <p class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">
                                Especifique porciones, sabores, rellenos, decoración, fotografías (.webp) y complementos
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="addProductItem"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-sm active:scale-95 min-h-[44px]"
                    >
                        <span class="material-symbols-outlined text-lg">add_circle</span>
                        Agregar Otro Producto
                    </button>
                </div>

                <div class="p-4 sm:p-6 lg:p-8 space-y-8">
                    <!-- Tarjeta por cada producto -->
                    <div
                        v-for="(item, itemIdx) in form.items"
                        :key="item.uid"
                        class="rounded-3xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50/50 dark:bg-secondary-900/40 p-4 sm:p-6 space-y-6"
                    >
                        <!-- Encabezado del Producto -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-zinc-200/80 dark:border-secondary-700">
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-xl bg-indigo-600 text-white text-xs font-black uppercase tracking-wider">
                                    Producto #{{ itemIdx + 1 }}
                                </span>
                                <span class="text-sm font-black text-zinc-900 dark:text-white uppercase">
                                    {{ item.quantity || 1 }} {{ item.product_name || 'Producto' }}
                                    <template v-if="item.portions_or_size">de {{ item.portions_or_size }}</template>
                                </span>
                            </div>

                            <button
                                v-if="form.items.length > 1"
                                type="button"
                                @click="removeProductItem(itemIdx)"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-600 hover:text-white text-rose-600 dark:text-rose-400 text-xs font-black uppercase tracking-wider transition-all w-fit"
                            >
                                <span class="material-symbols-outlined text-base">delete</span>
                                Quitar Producto
                            </button>
                        </div>

                        <!-- Campos principales del producto -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                    Nombre o Tipo de Producto *
                                </label>
                                <input
                                    v-model="item.product_name"
                                    type="text"
                                    list="catalog-products-list"
                                    placeholder="Ej: Torta, Cupcakes, Tartaleta..."
                                    class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 shadow-sm font-bold"
                                />
                                <datalist id="catalog-products-list">
                                    <option v-for="prod in catalogProducts" :key="prod.id" :value="prod.name" />
                                </datalist>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                    Cantidad *
                                </label>
                                <input
                                    v-model="item.quantity"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white shadow-sm font-black"
                                />
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                    Tamaño o Número de Porciones
                                </label>
                                <input
                                    v-model="item.portions_or_size"
                                    type="text"
                                    placeholder="Ej: 15 porciones"
                                    class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 shadow-sm font-bold"
                                />
                            </div>
                        </div>

                        <!-- Características y Especificaciones Personalizadas -->
                        <div class="bg-white dark:bg-secondary-800 rounded-2xl p-4 sm:p-5 border border-zinc-200 dark:border-secondary-700 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <h3 class="text-xs font-black uppercase tracking-wider text-zinc-900 dark:text-white">
                                        Características Específicas del Producto
                                    </h3>
                                    <p class="text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest mt-0.5">
                                        Sabor de masas, rellenos, altura, cobertura o cualquier atributo personalizado
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="addDetailSpec(item, '')"
                                    class="inline-flex items-center gap-1 px-3.5 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/50 text-indigo-700 dark:text-indigo-300 text-xs font-black uppercase tracking-wider hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition-colors w-fit"
                                >
                                    <span class="material-symbols-outlined text-base">add</span>
                                    Añadir Característica
                                </button>
                            </div>

                            <!-- Botones rápidos de características -->
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="preset in quickSpecLabels"
                                    :key="preset.label"
                                    type="button"
                                    @click="addDetailSpec(item, preset.label)"
                                    class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 text-zinc-600 dark:text-secondary-300 hover:border-indigo-500 dark:hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                                >
                                    + {{ preset.label }}
                                </button>
                            </div>

                            <!-- Lista de especificaciones -->
                            <div v-if="item.details.length" class="space-y-3">
                                <div
                                    v-for="(detail, dIdx) in item.details"
                                    :key="dIdx"
                                    class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center"
                                >
                                    <div class="sm:col-span-1">
                                        <input
                                            v-model="detail.label"
                                            type="text"
                                            placeholder="Atributo (Ej: Sabor de masas)"
                                            class="w-full px-3.5 py-2.5 bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 rounded-xl text-xs font-black text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                                        />
                                    </div>
                                    <div class="sm:col-span-2 flex items-center gap-2">
                                        <input
                                            v-model="detail.value"
                                            type="text"
                                            placeholder="Valor (Ej: chocolate-vainilla-chocolate)"
                                            class="flex-1 px-3.5 py-2.5 bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 rounded-xl text-xs font-semibold text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                                        />
                                        <button
                                            type="button"
                                            @click="removeDetailSpec(item, dIdx)"
                                            class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-600 hover:text-white text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0 transition-colors"
                                            title="Quitar característica"
                                        >
                                            <span class="material-symbols-outlined text-base">close</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Decoración / Descripción Personalizada y Observaciones -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                    Decoración / Descripción Personalizada
                                </label>
                                <textarea
                                    v-model="item.custom_description"
                                    rows="3"
                                    placeholder="Ej: Cobertura de crema blanca lisa y crema roja en el borde con textura y algunas perlitas minis rojas."
                                    class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 shadow-sm font-medium"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                    Observaciones Adicionales del Producto
                                </label>
                                <textarea
                                    v-model="item.observations"
                                    rows="3"
                                    placeholder="Observaciones adicionales sobre este producto..."
                                    class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 shadow-sm font-medium"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Referencias Visuales y Fotografías (.webp) -->
                        <div class="bg-white dark:bg-secondary-800 rounded-2xl p-4 sm:p-5 border border-zinc-200 dark:border-secondary-700 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-xs font-black uppercase tracking-wider text-zinc-900 dark:text-white">
                                            Referencias Visuales y Fotografías
                                        </h3>
                                        <span class="px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 text-[9px] font-black uppercase tracking-widest border border-indigo-200 dark:border-indigo-800/40">
                                            Compresión + .WebP Automática
                                        </span>
                                    </div>
                                    <p class="text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest mt-0.5">
                                        Las fotografías se comprimen primero y se convierten automáticamente a formato .webp
                                    </p>
                                </div>

                                <label class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-wider cursor-pointer transition-all shadow-sm w-fit min-h-[42px]">
                                    <span class="material-symbols-outlined text-base">add_a_photo</span>
                                    <span>{{ item.compressingImages ? 'Comprimiendo a .webp...' : 'Adjuntar Fotografías' }}</span>
                                    <input
                                        type="file"
                                        multiple
                                        accept="image/*"
                                        class="hidden"
                                        :disabled="item.compressingImages"
                                        @change="handleItemImagesChange($event, item)"
                                    />
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                <div>
                                    <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                        Observaciones sobre las Referencias
                                    </label>
                                    <input
                                        v-model="item.reference_notes"
                                        type="text"
                                        placeholder="Ej: TAL CUAL IMAGEN"
                                        class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 rounded-xl text-xs font-bold text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 focus:border-indigo-500"
                                    />
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        @click="item.reference_notes = 'TAL CUAL IMAGEN'"
                                        class="px-3 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 text-amber-800 dark:text-amber-300 hover:bg-amber-100 transition-colors"
                                    >
                                        TAL CUAL IMAGEN
                                    </button>
                                    <button
                                        type="button"
                                        @click="item.reference_notes = 'SIMILAR A IMAGEN (CON CAMBIOS EN DETALLES)'"
                                        class="px-3 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 text-zinc-600 dark:text-secondary-300 hover:bg-zinc-100 dark:hover:bg-secondary-700 transition-colors"
                                    >
                                        SIMILAR A IMAGEN
                                    </button>
                                </div>
                            </div>

                            <!-- Galería de miniaturas .webp -->
                            <div v-if="item.imagePreviews.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 pt-2">
                                <div
                                    v-for="(img, imgIdx) in item.imagePreviews"
                                    :key="imgIdx"
                                    class="relative rounded-2xl overflow-hidden border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900"
                                >
                                    <img :src="img.url" :alt="img.name" class="w-full h-32 object-cover" />
                                    <div class="p-2.5">
                                        <div class="text-[11px] font-black text-zinc-900 dark:text-white truncate">{{ img.name }}</div>
                                        <div class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase mt-0.5">
                                            .WEBP · {{ img.compressedSizeKB }} KB
                                            <span class="text-zinc-400 line-through ml-1">{{ img.originalSizeKB }} KB</span>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeItemImage(item, imgIdx)"
                                        class="absolute top-2 right-2 w-7 h-7 rounded-full bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center shadow"
                                        title="Quitar fotografía"
                                    >
                                        <span class="material-symbols-outlined text-sm">close</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Complementos Adicionales y Responsable -->
                        <div class="bg-white dark:bg-secondary-800 rounded-2xl p-4 sm:p-5 border border-zinc-200 dark:border-secondary-700 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <h3 class="text-xs font-black uppercase tracking-wider text-zinc-900 dark:text-white">
                                        Complementos Adicionales (Cliente / Negocio)
                                    </h3>
                                    <p class="text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest mt-0.5">
                                        Registre toppers, bases o adornos e indique quién los proporcionará
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="addItemComplement(item, '', 'client')"
                                    class="inline-flex items-center gap-1 px-3.5 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/50 text-indigo-700 dark:text-indigo-300 text-xs font-black uppercase tracking-wider hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition-colors w-fit"
                                >
                                    <span class="material-symbols-outlined text-base">add</span>
                                    Añadir Complemento
                                </button>
                            </div>

                            <!-- Botones rápidos de complementos -->
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    type="button"
                                    @click="addItemComplement(item, 'Topper personalizado', 'client')"
                                    class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 text-zinc-600 dark:text-secondary-300 hover:border-indigo-500 transition-colors"
                                >
                                    + Topper personalizado (Cliente)
                                </button>
                                <button
                                    type="button"
                                    @click="addItemComplement(item, 'Bolitas de colores', 'client')"
                                    class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 text-zinc-600 dark:text-secondary-300 hover:border-indigo-500 transition-colors"
                                >
                                    + Bolitas de colores (Cliente)
                                </button>
                                <button
                                    type="button"
                                    @click="addItemComplement(item, 'Base blanca de trupan', 'business')"
                                    class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 text-zinc-600 dark:text-secondary-300 hover:border-indigo-500 transition-colors"
                                >
                                    + Base blanca de trupan (Negocio)
                                </button>
                            </div>

                            <div v-if="item.complements.length" class="space-y-3">
                                <div
                                    v-for="(comp, cIdx) in item.complements"
                                    :key="cIdx"
                                    class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center"
                                >
                                    <div class="sm:col-span-2">
                                        <input
                                            v-model="comp.description"
                                            type="text"
                                            placeholder="Descripción del complemento (Ej: Topper personalizado)"
                                            class="w-full px-3.5 py-2.5 bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 rounded-xl text-xs font-semibold text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 focus:border-indigo-500"
                                        />
                                    </div>
                                    <div class="sm:col-span-1 flex items-center gap-2">
                                        <div class="flex-1 grid grid-cols-2 rounded-xl overflow-hidden border border-zinc-200 dark:border-secondary-700">
                                            <button
                                                type="button"
                                                @click="comp.provider_type = 'client'"
                                                class="py-2 text-[10px] font-black uppercase tracking-wider transition-colors"
                                                :class="comp.provider_type === 'client'
                                                    ? 'bg-amber-600 text-white'
                                                    : 'bg-zinc-50 dark:bg-secondary-900 text-zinc-600 dark:text-secondary-400'"
                                            >
                                                Cliente
                                            </button>
                                            <button
                                                type="button"
                                                @click="comp.provider_type = 'business'"
                                                class="py-2 text-[10px] font-black uppercase tracking-wider transition-colors"
                                                :class="comp.provider_type === 'business'
                                                    ? 'bg-indigo-600 text-white'
                                                    : 'bg-zinc-50 dark:bg-secondary-900 text-zinc-600 dark:text-secondary-400'"
                                            >
                                                Negocio
                                            </button>
                                        </div>

                                        <button
                                            type="button"
                                            @click="removeItemComplement(item, cIdx)"
                                            class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-600 hover:text-white text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0 transition-colors"
                                            title="Quitar complemento"
                                        >
                                            <span class="material-symbols-outlined text-base">close</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Observaciones y Resumen de la Reserva -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Panel Izquierdo: Observaciones y Notas Generales -->
                <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 shadow-sm relative flex flex-col justify-between">
                    <div>
                        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-t-3xl"></div>
                        <div class="p-4 sm:p-5 border-b border-zinc-100 dark:border-secondary-700 flex items-center gap-3 bg-zinc-50/50 dark:bg-zinc-900/50">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-inner">
                                <span class="material-symbols-outlined">notes</span>
                            </div>
                            <div>
                                <h2 class="text-base font-black text-zinc-900 dark:text-white uppercase tracking-tight">
                                    3. Observaciones de la Reserva
                                </h2>
                                <p class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">
                                    Instrucciones de entrega, detalles del cliente o notas para taller y despacho
                                </p>
                            </div>
                        </div>

                        <div class="p-4 sm:p-6 space-y-4">
                            <div>
                                <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2 ml-1">
                                    Notas y Observaciones Generales (Opcional)
                                </label>
                                <textarea
                                    v-model="form.general_notes"
                                    rows="5"
                                    placeholder="Ej: Cliente recogerá personalmente a mediodía. Cuidar embalaje de pisos altos..."
                                    class="w-full px-4 py-3 bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-sm transition-all text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-secondary-500 shadow-sm font-medium"
                                ></textarea>
                            </div>

                            <div class="space-y-1.5">
                                <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-400 uppercase tracking-widest ml-1">
                                    Sugerencias de Notas Rápidas
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        type="button"
                                        @click="form.general_notes = (form.general_notes ? form.general_notes + ' · ' : '') + 'Pedido coordinado vía WhatsApp'"
                                        class="px-2.5 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 text-zinc-600 dark:text-secondary-300 hover:border-indigo-500 transition-colors"
                                    >
                                        + Vía WhatsApp
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.general_notes = (form.general_notes ? form.general_notes + ' · ' : '') + 'Cliente recogerá personalmente'"
                                        class="px-2.5 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 text-zinc-600 dark:text-secondary-300 hover:border-indigo-500 transition-colors"
                                    >
                                        + Recojo personal
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.general_notes = (form.general_notes ? form.general_notes + ' · ' : '') + 'Requiere embalaje reforzado'"
                                        class="px-2.5 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-zinc-50 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 text-zinc-600 dark:text-secondary-300 hover:border-indigo-500 transition-colors"
                                    >
                                        + Embalaje reforzado
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Derecho: Vista Previa en Vivo estilo Reserva #00001 (Sin montos) -->
                <div class="bg-surface dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 shadow-sm overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="p-4 sm:p-5 bg-zinc-50 dark:bg-secondary-900/60 border-b border-zinc-200 dark:border-secondary-700 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">
                                    Registro de pedido
                                </div>
                                <div class="text-lg font-black text-zinc-900 dark:text-white uppercase">
                                    Reserva #{{ nextFormattedNumber }}
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-xl bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest">
                                Vista Previa
                            </span>
                        </div>

                        <div class="p-5 sm:p-6 space-y-4 text-xs sm:text-sm">
                            <!-- Cliente y Entrega -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-4 border-b border-zinc-100 dark:border-secondary-700">
                                <div>
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400">Cliente</span>
                                    <div class="text-sm sm:text-base font-black text-zinc-900 dark:text-white mt-0.5">
                                        {{ form.customer.full_name || 'Nombre del cliente' }}
                                    </div>
                                    <div class="text-xs font-bold text-zinc-500 dark:text-secondary-300">
                                        {{ form.customer.phone || 'Sin celular' }}
                                    </div>
                                </div>

                                <div>
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400">Fecha de entrega</span>
                                    <div class="text-sm font-black text-zinc-900 dark:text-white mt-0.5">
                                        {{ form.delivery_date || '-' }}
                                    </div>
                                    <div class="text-xs font-black text-indigo-600 dark:text-indigo-400 mt-0.5">
                                        {{ form.delivery_mode === 'delivery' ? 'Entrega a domicilio' : 'Recoger en tienda' }} · {{ form.delivery_time || '-' }}
                                    </div>
                                </div>
                            </div>

                            <!-- Productos solicitados -->
                            <div class="pb-4 border-b border-zinc-100 dark:border-secondary-700 space-y-3">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400">Productos solicitados</span>
                                <div v-for="(it, idx) in form.items" :key="idx" class="space-y-1.5">
                                    <div class="font-black text-zinc-900 dark:text-white">
                                        {{ it.quantity || 1 }} {{ it.product_name || 'Producto' }}
                                        <template v-if="it.portions_or_size">de {{ it.portions_or_size }}</template>
                                    </div>
                                    <ul class="list-disc list-inside text-xs text-zinc-700 dark:text-secondary-200 space-y-0.5">
                                        <template v-for="(d, dIdx) in it.details" :key="dIdx">
                                            <li v-if="d.label && d.value">
                                                <span class="font-bold">{{ d.label }}:</span> {{ d.value }}
                                            </li>
                                        </template>
                                    </ul>
                                    <div v-if="it.custom_description" class="text-xs text-zinc-600 dark:text-secondary-300 pt-1">
                                        <span class="font-black uppercase text-[10px] text-indigo-600 dark:text-indigo-400 block">Decoración:</span>
                                        {{ it.custom_description }}
                                    </div>
                                    <div v-if="it.reference_notes" class="pt-0.5">
                                        <span class="inline-block px-2.5 py-0.5 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40 text-[10px] font-black uppercase">
                                            Referencia: {{ it.reference_notes }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Complementos -->
                            <div v-if="allPreviewComplements.length" class="pb-4 border-b border-zinc-100 dark:border-secondary-700 space-y-1">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400">Complementos</span>
                                <ul class="list-disc list-inside text-xs text-zinc-700 dark:text-secondary-200 space-y-0.5">
                                    <li v-for="(c, cIdx) in allPreviewComplements" :key="cIdx">
                                        {{ c.description }} — <span class="font-black">{{ c.provider_type === 'business' ? 'Negocio' : 'Cliente' }}</span>.
                                    </li>
                                </ul>
                            </div>

                            <!-- Observaciones registradas -->
                            <div v-if="form.general_notes" class="space-y-1 pt-1">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400">Observaciones Generales</span>
                                <p class="text-xs text-zinc-700 dark:text-secondary-200">
                                    {{ form.general_notes }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción Final -->
                    <div class="p-4 sm:p-5 bg-zinc-50 dark:bg-secondary-900/50 border-t border-zinc-100 dark:border-secondary-700 flex flex-col-reverse sm:flex-row justify-end gap-3">
                        <Link
                            :href="route('admin.orders.index')"
                            class="px-6 py-3.5 bg-white dark:bg-secondary-800 text-zinc-600 dark:text-secondary-300 text-xs font-black uppercase tracking-widest rounded-2xl border border-zinc-200 dark:border-secondary-700 hover:bg-zinc-50 dark:hover:bg-secondary-700 transition-all text-center"
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || anyImageCompressing"
                            class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-md active:scale-95 disabled:opacity-50"
                        >
                            <span class="material-symbols-outlined text-base">save</span>
                            <span>{{ form.processing ? 'Guardando Reserva...' : 'Guardar Reserva de Pedido' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.dark input:not([type='checkbox']):not([type='radio']):not([type='file']),
.dark textarea,
.dark select {
    background-color: rgb(var(--color-secondary-900)) !important;
    color: rgb(var(--color-text-primary)) !important;
    border-color: rgb(var(--color-secondary-700));
    color-scheme: dark;
}

.dark input::placeholder,
.dark textarea::placeholder {
    color: rgb(var(--color-secondary-500)) !important;
}
</style>
