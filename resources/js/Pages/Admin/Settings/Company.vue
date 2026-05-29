<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css';

const props = defineProps({
    company: Object,
});

const form = useForm({
    name: props.company?.name || '',
    business_name: props.company?.business_name || '',
    nit: props.company?.nit || '',
    phone: props.company?.phone || '',
    email: props.company?.email || '',
    show_name: props.company?.show_name ?? true,
    logo: null,
    inventory_method: props.company?.inventory_method || 'PROMEDIO_PONDERADO',
    inventories_closed_until: props.company?.inventories_closed_until || '',
});

const dropzoneRef = ref(null);
const dropzoneInstance = ref(null);

onMounted(() => {
    if (dropzoneRef.value) {
        dropzoneInstance.value = new Dropzone(dropzoneRef.value, {
            url: '/',
            autoProcessQueue: false,
            maxFiles: 1,
            acceptedFiles: 'image/*',
            addRemoveLinks: true,
            dictDefaultMessage: `
                <div class='flex flex-col items-center justify-center space-y-3 p-4'>
                    <div class='size-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center border border-indigo-100 dark:border-indigo-500/20 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-300'>
                        <svg class='size-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'></path></svg>
                    </div>
                    <div class='text-center text-zinc-600 dark:text-zinc-400'>
                        <p class='text-sm font-bold'>Arrastra el logo aquí</p>
                        <p class='text-xs'>o haz clic para buscar</p>
                    </div>
                </div>
            `,
            dictRemoveFile: 'Quitar Logo',
            init: function() {
                this.on('addedfile', (file) => {
                    form.logo = file;
                    if (this.files.length > 1) {
                        this.removeFile(this.files[0]);
                    }
                });
                this.on('removedfile', () => {
                    form.logo = null;
                });
            }
        });
    }
});

const submit = () => {
    form.post(route('admin.company.update'), {
        forceFormData: true,
        onSuccess: () => {
            if (dropzoneInstance.value) {
                dropzoneInstance.value.removeAllFiles(true);
            }
        },
    });
};
</script>

<template>
    <Head title="Configuración de la Empresa" />

    <AdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Configuración de la Empresa</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Administra la información general de tu empresa que se muestra en el sistema y documentos.</p>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <!-- Columna de Previsualización -->
                <div class="space-y-6">
                    <div class="p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm">
                        <h2 class="text-base font-bold text-zinc-900 dark:text-white mb-6 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-zinc-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            Previsualización
                        </h2>

                        <div class="flex flex-col items-center p-6 bg-zinc-50 dark:bg-zinc-800/30 rounded-2xl border border-zinc-100 dark:border-zinc-800 text-center">
                            <div class="relative group mb-4">
                                <img v-if="form.logo" :src="URL.createObjectURL(form.logo)" class="size-32 object-contain rounded-xl border border-white dark:border-zinc-700 p-2 bg-white shadow-sm" />
                                <img v-else-if="company?.logo_url" :src="company.logo_url" class="size-32 object-contain rounded-xl border border-white dark:border-zinc-700 p-2 bg-white shadow-sm" />
                                <img v-else src="/img/logo-inventory.png" class="size-32 object-contain rounded-xl border border-white dark:border-zinc-700 p-2 bg-white shadow-sm" />
                                
                                <div v-if="form.processing" class="absolute inset-0 bg-black/50 rounded-xl flex items-center justify-center">
                                    <svg class="animate-spin h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>

                            <h3 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ form.name || 'Nombre de la Empresa' }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ form.business_name || 'Razón Social' }}</p>
                            
                            <div class="mt-4 w-full pt-4 border-t border-zinc-100 dark:border-zinc-800 grid grid-cols-2 gap-4">
                                <div class="text-left">
                                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest block">NIT</span>
                                    <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ form.nit || '---' }}</p>
                                </div>
                                <div class="text-left">
                                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest block">Teléfono</span>
                                    <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ form.phone || '---' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Edición -->
                <div class="xl:col-span-2">
                    <div class="p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Logo de la Empresa</label>
                                    
                                    <div class="w-full group">
                                        <div 
                                            ref="dropzoneRef" 
                                            class="dropzone !bg-zinc-50/50 dark:!bg-zinc-900/50 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl !min-h-[180px] flex items-center justify-center cursor-pointer transition-all duration-300 hover:border-indigo-400 dark:hover:border-indigo-500/50 hover:bg-white dark:hover:bg-zinc-800/80 shadow-sm hover:shadow-md"
                                        >
                                        </div>
                                    </div>

                                    <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30 rounded-lg">
                                        <p class="flex items-center gap-2 text-[11px] text-amber-700 dark:text-amber-400 font-medium">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                            </svg>
                                            El logotipo no debe contener texto (solo el icono/isotipo).
                                        </p>
                                    </div>

                                    <p class="text-[11px] text-zinc-500 mt-2">Tamaño recomendado: 512x512px. Máximo 2MB.</p>
                                    <p v-if="form.errors.logo" class="text-xs text-rose-500 mt-1">{{ form.errors.logo }}</p>
                                </div>

                                <!-- Inputs -->
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nombre Comercial *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H22.25m-11.14 0c0-3.57 2.893-6.463 6.463-6.463s6.463 2.893 6.463 6.463M2.25 10.5V6.75A2.25 2.25 0 0 1 4.5 4.5h3.29a2.25 2.25 0 0 1 1.712.793l1.32 1.63a2.25 2.25 0 0 0 1.712.793H19.5a2.25 2.25 0 0 1 2.25 2.25V10.5M2.25 10.5v9a2.25 2.25 0 0 0 2.25 2.25h9" />
                                            </svg>
                                        </div>
                                        <input v-model="form.name" type="text" required placeholder="Ej. Mi Tienda" class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" />
                                    </div>
                                    <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Representante Legal *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h18" />
                                            </svg>
                                        </div>
                                        <input v-model="form.business_name" type="text" required placeholder="Ej. Mi Tienda S.R.L." class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" />
                                    </div>
                                    <p v-if="form.errors.business_name" class="text-xs text-rose-500 mt-1">{{ form.errors.business_name }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">NIT *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0z" />
                                            </svg>
                                        </div>
                                        <input v-model="form.nit" type="text" required placeholder="Ej. 123456789" class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" />
                                    </div>
                                    <p v-if="form.errors.nit" class="text-xs text-rose-500 mt-1">{{ form.errors.nit }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Teléfono</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25z" />
                                            </svg>
                                        </div>
                                        <input v-model="form.phone" type="text" placeholder="Ej. +591 70000000" class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" />
                                    </div>
                                    <p v-if="form.errors.phone" class="text-xs text-rose-500 mt-1">{{ form.errors.phone }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Correo Electrónico</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                            </svg>
                                        </div>
                                        <input v-model="form.email" type="email" placeholder="contacto@empresa.com" class="block w-full pl-10 pr-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" />
                                    </div>
                                    <p v-if="form.errors.email" class="text-xs text-rose-500 mt-1">{{ form.errors.email }}</p>
                                </div>

                                <!-- Configuración de Inventario -->
                                <div class="col-span-full border-t border-zinc-100 dark:border-zinc-800 pt-6 mt-2">
                                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white mb-4 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-indigo-500">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                        </svg>
                                        Configuración de Inventario (ERP-Grade)
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Método de Valuación</label>
                                            <div class="relative group">
                                                <select 
                                                    v-model="form.inventory_method" 
                                                    :disabled="company?.has_inventory_movements"
                                                    class="block w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none disabled:bg-zinc-100 dark:disabled:bg-zinc-900/50 disabled:cursor-not-allowed"
                                                >
                                                    <option value="PROMEDIO_PONDERADO">Promedio Ponderado</option>
                                                    <option value="PEPS">PEPS (Primeras Entradas, Primeras Salidas)</option>
                                                </select>
                                                <div v-if="company?.has_inventory_movements" class="mt-2 p-2 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 rounded-md">
                                                    <p class="text-[10px] text-indigo-600 dark:text-indigo-400 font-medium leading-tight">
                                                        Bloqueado: Ya existen movimientos en el Kardex. Para cambiar el método, el inventario debe estar vacío.
                                                    </p>
                                                </div>
                                            </div>
                                            <p v-if="form.errors.inventory_method" class="text-xs text-rose-500 mt-1">{{ form.errors.inventory_method }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Cierre Contable de Inventario</label>
                                            <div class="relative">
                                                <input 
                                                    v-model="form.inventories_closed_until" 
                                                    type="date" 
                                                    class="block w-full px-3 py-2 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none"
                                                />
                                            </div>
                                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-1 italic">
                                                No se permitirán movimientos antes de esta fecha.
                                            </p>
                                            <p v-if="form.errors.inventories_closed_until" class="text-xs text-rose-500 mt-1">{{ form.errors.inventories_closed_until }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Switch -->
                                <div class="col-span-full">
                                    <div class="p-4 bg-zinc-50 dark:bg-zinc-950/20 rounded-xl border border-zinc-200 dark:border-zinc-800 flex items-center justify-between gap-4">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-zinc-800 dark:text-zinc-100 text-sm">Mostrar nombre en el menú</h4>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Si se desactiva, solo aparecerá el logo en la barra lateral.</p>
                                        </div>
                                        
                                        <div class="flex items-center">
                                            <button type="button" 
                                                    @click="form.show_name = !form.show_name"
                                                    class="relative inline-flex items-center group focus:outline-none"
                                                    role="switch"
                                                    :aria-checked="form.show_name">
                                                <!-- Pista (Track) -->
                                                <div class="h-4 w-10 rounded-full transition-colors duration-200 ease-in-out"
                                                     :class="form.show_name ? 'bg-indigo-400/60' : 'bg-zinc-400'"></div>
                                                
                                                <!-- Círculo (Knob) -->
                                                <div class="absolute h-6 w-6 rounded-full shadow-md transform transition-all duration-200 ease-in-out flex items-center justify-center"
                                                     :class="form.show_name ? 'translate-x-5 bg-indigo-600' : 'translate-x-[-4px] bg-white'">
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 justify-end pt-4">
                                <button type="submit" 
                                        :disabled="form.processing"
                                        class="inline-flex items-center gap-2 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-lg shadow-indigo-600/20 transition-all disabled:opacity-50">
                                    <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Actualizar Información
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.dropzone {
    border: none !important;
}

:deep(.dz-preview) {
    background: transparent !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    display: flex !important;
    justify-content: center !important;
}

:deep(.dz-image) {
    border-radius: 12px !important;
    width: 120px !important;
    height: 120px !important;
}

:deep(.dz-remove) {
    color: #ef4444 !important;
    text-decoration: none !important;
    font-size: 11px !important;
    font-weight: bold !important;
    margin-top: 8px !important;
}
</style>
