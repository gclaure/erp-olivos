<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    plans: Object
});

const showModal = ref(false);
const editId = ref(null);

const form = useForm({
    name: '',
    slug: '',
    price: 0,
    duration_days: 30,
    billing_period: 'MONTHLY',
    is_active: true,
    features: {
        max_users: 3,
        max_branches: 1,
        max_products: 500,
        max_warehouses: 1,
        max_pos: 1,
        has_catalog: false,
        max_images_per_product: 1
    }
});

const openCreate = () => {
    editId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (plan) => {
    editId.value = plan.id;
    form.name = plan.name;
    form.slug = plan.slug;
    form.price = plan.price;
    form.duration_days = plan.duration_days;
    form.billing_period = plan.billing_period;
    form.is_active = plan.is_active;
    form.features = { ...plan.features };
    form.clearErrors();
    showModal.value = true;
};

const save = () => {
    if (editId.value) {
        form.put(route('superadmin.plans.update', editId.value), {
            onSuccess: () => {
                showModal.value = false;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    icon: 'success',
                    title: 'Plan actualizado exitosamente.'
                });
            }
        });
    } else {
        form.post(route('superadmin.plans.store'), {
            onSuccess: () => {
                showModal.value = false;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    icon: 'success',
                    title: 'Plan creado exitosamente.'
                });
            }
        });
    }
};

const toggleActive = (plan) => {
    router.post(route('superadmin.plans.toggle-active', plan.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: 'success',
                title: plan.is_active ? 'Plan desactivado.' : 'Plan activado.'
            });
        }
    });
};

const confirmDelete = (plan) => {
    Swal.fire({
        title: '¿Eliminar este plan?',
        text: 'No se puede eliminar si hay suscripciones activas.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#71717a',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('superadmin.plans.destroy', plan.id), {
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        icon: 'success',
                        title: 'Plan eliminado.'
                    });
                }
            });
        }
    });
};

const featuresConfig = [
    { key: 'max_users', label: 'Usuarios', icon: 'users', type: 'number' },
    { key: 'max_branches', label: 'Sucursales', icon: 'building-office', type: 'number' },
    { key: 'max_products', label: 'Productos', icon: 'cube', type: 'number' },
    { key: 'max_warehouses', label: 'Almacenes', icon: 'home', type: 'number' },
    { key: 'max_pos', label: 'Puntos de Venta', icon: 'computer-desktop', type: 'number' },
    { key: 'max_images_per_product', label: 'Imágenes por Producto', icon: 'photo', type: 'number' },
    { key: 'has_catalog', label: 'Catálogo', icon: 'shopping-bag', type: 'boolean' },
];
</script>

<template>
    <SuperAdminLayout>
        <Head title="Gestión de Planes" />

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">Gestión de Planes</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Configura los planes y sus límites</p>
            </div>
            <button @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nuevo Plan
            </button>
        </div>

        <!-- Cards de planes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
            <div v-for="plan in plans.data" :key="plan.id" 
                 :class="['bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden transition-opacity', !plan.is_active ? 'opacity-60' : '']">
                <!-- Header del plan -->
                <div class="p-4 sm:p-6 border-b border-zinc-100 dark:border-gray-600">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white">{{ plan.name }}</h3>
                        <span v-if="plan.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400">Activo</span>
                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">Inactivo</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl sm:text-3xl font-extrabold text-violet-600 dark:text-violet-400">{{ plan.price_formatted }}</span>
                        <span class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">/ {{ plan.duration_days }} días</span>
                    </div>
                </div>

                <!-- Features -->
                <div class="p-4 sm:p-6 space-y-3">
                    <div v-for="feat in featuresConfig" :key="feat.key" class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                            <!-- Icons based on feat.icon -->
                            <svg v-if="feat.icon === 'users'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400 dark:text-zinc-500"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                            <svg v-if="feat.icon === 'building-office'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400 dark:text-zinc-500"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-9h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                            <svg v-if="feat.icon === 'cube'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400 dark:text-zinc-500"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                            <svg v-if="feat.icon === 'home'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400 dark:text-zinc-500"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                            <svg v-if="feat.icon === 'computer-desktop'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400 dark:text-zinc-500"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" /></svg>
                            <svg v-if="feat.icon === 'photo'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400 dark:text-zinc-500"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                            <svg v-if="feat.icon === 'shopping-bag'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400 dark:text-zinc-500"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                            {{ feat.label }}
                        </span>
                        <span v-if="feat.type === 'boolean'" class="font-bold">
                            {{ plan.features[feat.key] ? '✅' : '❌' }}
                        </span>
                        <span v-else :class="['font-semibold', plan.features[feat.key] == -1 ? 'text-violet-600 dark:text-violet-400' : 'text-zinc-900 dark:text-white']">
                            {{ plan.features[feat.key] == -1 ? '∞ Ilimitado' : plan.features[feat.key] }}
                        </span>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="px-4 sm:px-6 py-3 sm:py-4 bg-zinc-50 dark:bg-gray-600 flex items-center justify-end gap-2 border-t border-zinc-200 dark:border-gray-500">
                    <button @click="openEdit(plan)"
                            class="p-2 text-zinc-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/30 rounded-lg transition-colors" title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                    </button>
                    <button @click="toggleActive(plan)"
                            :class="['p-2 rounded-lg transition-colors', plan.is_active ? 'text-zinc-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30' : 'text-zinc-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30']" 
                            :title="plan.is_active ? 'Desactivar' : 'Activar'">
                        <svg v-if="plan.is_active" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    </button>
                    <button @click="confirmDelete(plan)"
                            class="p-2 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    </button>
                </div>
            </div>

            <div v-if="plans.data.length === 0" class="col-span-full bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mb-3"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" /></svg>
                    <p class="text-zinc-500 dark:text-zinc-400 font-medium">No hay planes configurados</p>
                    <p class="text-sm text-zinc-400 dark:text-zinc-500 mt-1">Crea un nuevo plan para comenzar</p>
                </div>
            </div>
        </div>

        <!-- Modal Create/Edit -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity">
            <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <!-- Modal Header -->
                <div class="p-6 border-b border-zinc-100 dark:border-gray-600">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">{{ editId ? 'Editar Plan' : 'Nuevo Plan' }}</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-normal mt-1">{{ editId ? 'Actualiza los datos del plan' : 'Configura un nuevo plan de suscripción' }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <form @submit.prevent="save" class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nombre *</label>
                                <input v-model="form.name" type="text" placeholder="Plan Básico" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500 transition-all" />
                                <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Slug *</label>
                                <input v-model="form.slug" type="text" placeholder="basico" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500 transition-all" />
                                <p class="text-[10px] text-zinc-400 mt-1">Identificador único</p>
                                <p v-if="form.errors.slug" class="text-xs text-rose-500 mt-1">{{ form.errors.slug }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Precio (Bs.) *</label>
                                <input v-model="form.price" type="number" step="0.01" min="0" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500" />
                                <p v-if="form.errors.price" class="text-xs text-rose-500 mt-1">{{ form.errors.price }}</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Duración (días) *</label>
                                <input v-model="form.duration_days" type="number" min="1" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500" />
                                <p class="text-[10px] text-zinc-400 mt-1">Para ciclos no estandares</p>
                                <p v-if="form.errors.duration_days" class="text-xs text-rose-500 mt-1">{{ form.errors.duration_days }}</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Ciclo de Facturación *</label>
                                <select v-model="form.billing_period" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500">
                                    <option value="MONTHLY">Mensual</option>
                                    <option value="YEARLY">Anual</option>
                                </select>
                                <p class="text-[10px] text-zinc-400 mt-1">Define el ancla de cobro</p>
                                <p v-if="form.errors.billing_period" class="text-xs text-rose-500 mt-1">{{ form.errors.billing_period }}</p>
                            </div>
                        </div>

                        <div class="p-4 bg-zinc-50 dark:bg-gray-600 rounded-xl border border-zinc-200/60 dark:border-gray-500">
                            <p class="text-xs sm:text-sm font-semibold text-zinc-900 dark:text-white mb-4">
                                Límites del Plan
                                <span class="text-xs font-normal text-zinc-400 dark:text-zinc-500 ml-1">(Usar -1 para ilimitado)</span>
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="feat in featuresConfig" :key="'field-'+feat.key" class="space-y-1">
                                    <label class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ feat.label }}</label>
                                    <template v-if="feat.type === 'boolean'">
                                        <select v-model="form.features[feat.key]" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500">
                                            <option :value="true">✅ Incluido</option>
                                            <option :value="false">❌ No disponible</option>
                                        </select>
                                    </template>
                                    <input v-else v-model="form.features[feat.key]" type="number" min="-1" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500" />
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="p-6 bg-zinc-50/50 dark:bg-gray-700/50 border-t border-zinc-100 dark:border-gray-600 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                    <button @click="showModal = false" class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-zinc-500 hover:bg-zinc-100 dark:hover:bg-gray-600 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button @click="save" 
                            :disabled="form.processing"
                            class="w-full sm:w-auto px-8 py-2.5 bg-violet-600 text-white text-sm font-bold rounded-xl hover:bg-violet-700 shadow-lg shadow-violet-500/20 transition-all disabled:opacity-50">
                        {{ editId ? 'Guardar Cambios' : 'Crear Plan' }}
                    </button>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
