<script setup>
const props = defineProps({
    sale: Object,
});

const formatMoney = (val) => {
    return parseFloat(val).toFixed(2);
};

const cleanPhone = (phone) => {
    return phone ? phone.replace(/\D/g, '') : '';
};
</script>

<template>
    <div>
        <!-- Observaciones de Recojo (Solo para Retiro en Local) -->
        <div v-if="sale.delivery_mode.value === 'retiro_local'" class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 dark:border-amber-600 rounded-r-xl p-4 flex items-start gap-4 shadow-sm">
            <div class="w-10 h-10 rounded-lg bg-white dark:bg-amber-800 flex items-center justify-center border border-amber-200 dark:border-amber-700 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-amber-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 17.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <span class="text-[9px] font-black text-amber-600 dark:text-amber-500 uppercase tracking-widest block mb-0.5">Observaciones para Recojo</span>
                <p class="text-xs text-amber-900 dark:text-amber-200 leading-tight font-bold font-sans">
                    {{ sale.delivery_observations || 'EL CLIENTE RETIRARÁ PERSONALMENTE LOS PRODUCTOS EN SUCURSAL.' }}
                </p>
            </div>
        </div>

        <!-- Información de Envío a Domicilio (Solo para Envío a Domicilio) -->
        <div v-if="sale.delivery_mode.value === 'envio_domicilio'" class="space-y-4 bg-blue-50/50 dark:bg-blue-900/10 rounded-2xl border border-blue-100 dark:border-blue-800/50 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-4 bg-blue-500 rounded-full"></div>
                    <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">Detalles de Entrega en Domicilio</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-400 opacity-60">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a.75.75 0 0 1 .75.75 6.75 6.75 0 0 1-13.5 0 .75.75 0 0 1 .75-.75h12Z" />
                </svg>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1.5 md:col-span-2">
                    <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-blue-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg> Dirección de Envío
                    </label>
                    <div class="p-3 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 text-[11px] font-extrabold text-zinc-700 dark:text-zinc-200 leading-relaxed shadow-sm min-h-[50px]">
                        {{ sale.delivery_address || 'SIN DIRECCIÓN REGISTRADA' }}
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-blue-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-10.5v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                        </svg> Referencia de Llegada
                    </label>
                    <div class="p-3 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 text-[11px] font-extrabold text-zinc-700 dark:text-zinc-200 leading-relaxed shadow-sm min-h-[50px]">
                        {{ sale.delivery_reference || 'SIN REFERENCIA REGISTRADA' }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Zona / Barrio</label>
                    <div class="p-2.5 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 text-[11px] font-black text-zinc-900 dark:text-white uppercase truncate">
                        {{ sale.delivery_zone || 'N/A' }}
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-rose-500 uppercase tracking-widest">Costo de Envío</label>
                    <div class="p-2.5 bg-white dark:bg-secondary-800 rounded-xl border border-rose-200 dark:border-rose-900/50 text-[11px] font-black text-rose-600 dark:text-rose-400">
                        Bs. {{ formatMoney(sale.delivery_cost) }}
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Horario Entrega</label>
                    <div class="p-2.5 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 text-[11px] font-black text-zinc-900 dark:text-white truncate">
                        {{ sale.delivery_time_slot || 'POR DEFINIR' }}
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-blue-500 dark:text-blue-400 uppercase tracking-widest">Maps / Ubicación</label>
                    <a v-if="sale.delivery_map_url" :href="sale.delivery_map_url" target="_blank" class="p-2.5 bg-white dark:bg-secondary-800 rounded-xl border border-blue-200 dark:border-blue-900/50 text-blue-600 dark:text-blue-400 text-[11px] font-black flex items-center justify-center gap-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-blue-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg> Ver Mapa
                    </a>
                    <div v-else class="p-2.5 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 text-[11px] text-zinc-300 dark:text-secondary-600 font-extrabold flex items-center justify-center italic">
                        SIN LINK
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex items-center gap-3 p-3 bg-white/50 dark:bg-secondary-800/40 rounded-xl border border-zinc-100 dark:border-secondary-700 min-h-[46px]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-zinc-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <div class="min-w-0">
                        <span class="text-[8px] font-black text-zinc-400 uppercase block tracking-tighter">Receptor / Contacto</span>
                        <span class="text-[11px] font-black text-zinc-900 dark:text-white uppercase truncate block">{{ sale.delivery_contact_name || sale.client.name }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 bg-white/50 dark:bg-secondary-800/40 rounded-xl border border-zinc-100 dark:border-secondary-700 min-h-[46px]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-emerald-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.33.185.506.548.438.912l-1.323 7.126a3.375 3.375 0 0 1-3.315 2.76H8.25a3.375 3.375 0 0 1-3.315-2.76l-1.323-7.126a.75.75 0 0 1 .438-.912l16.2-7.5ZM8.25 19.5v-7.5M15.75 19.5v-7.5" />
                    </svg>
                    <div class="min-w-0">
                        <span class="text-[8px] font-black text-zinc-400 uppercase block tracking-tighter">Teléfono Contacto</span>
                        <a :href="`https://wa.me/${cleanPhone(sale.delivery_contact_phone || sale.client.phone)}`" 
                           target="_blank" 
                           class="text-[11px] font-black text-emerald-600 dark:text-emerald-400 uppercase hover:underline truncate block">
                            {{ sale.delivery_contact_phone || sale.client.phone }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de Punto de Encuentro (Solo para Punto de Encuentro) -->
        <div v-if="sale.delivery_mode.value === 'punto_encuentro'" class="space-y-4 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-2xl border border-indigo-100 dark:border-indigo-800/50 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
                    <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Detalles del Punto de Encuentro</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-400 opacity-60">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
            </div>

            <div class="space-y-1.5">
                <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-indigo-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                    </svg> Ubicación del Encuentro
                </label>
                <div class="p-3 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 text-[11px] font-extrabold text-zinc-700 dark:text-zinc-200 leading-relaxed shadow-sm">
                    {{ sale.delivery_address || 'SIN UBICACIÓN REGISTRADA' }}
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Contacto -->
                <div class="flex flex-col gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Receptor / Contacto</label>
                        <div class="p-2.5 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 text-[11px] font-black text-zinc-900 dark:text-white uppercase truncate">
                            {{ sale.delivery_contact_name || sale.client.name }}
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Teléfono Contacto</label>
                        <div class="flex items-center gap-3 p-2.5 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 min-h-[46px]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-emerald-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.33.185.506.548.438.912l-1.323 7.126a3.375 3.375 0 0 1-3.315 2.76H8.25a3.375 3.375 0 0 1-3.315-2.76l-1.323-7.126a.75.75 0 0 1 .438-.912l16.2-7.5ZM8.25 19.5v-7.5M15.75 19.5v-7.5" />
                            </svg>
                            <div class="min-w-0">
                                <a :href="`https://wa.me/${cleanPhone(sale.delivery_contact_phone || sale.client.phone)}`" target="_blank" class="text-[11px] font-black text-emerald-600 dark:text-emerald-400 hover:underline truncate block">
                                    {{ sale.delivery_contact_phone || sale.client.phone }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fecha y Hora -->
                <div class="bg-indigo-100/30 dark:bg-indigo-900/40 rounded-xl p-3.5 border border-indigo-200/50 dark:border-indigo-800/50 h-full flex flex-col justify-center shadow-inner">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5 text-center">
                            <label class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest block">Fecha Acordada</label>
                            <div class="p-2 bg-white dark:bg-secondary-800 rounded-lg border border-indigo-200 dark:border-indigo-700 text-[11px] font-black text-zinc-900 dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 inline mr-1 opacity-50">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                {{ sale.formatted_delivery_at ? sale.formatted_delivery_at.split(' ')[0] : 'PENDIENTE' }}
                            </div>
                        </div>
                        <div class="space-y-1.5 text-center">
                            <label class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest block">Hora Acordada</label>
                            <div class="p-2 bg-white dark:bg-secondary-800 rounded-lg border border-indigo-200 dark:border-indigo-700 text-[11px] font-black text-zinc-900 dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 inline mr-1 opacity-50">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                {{ sale.formatted_delivery_at ? sale.formatted_delivery_at.split(' ')[1] : '--:--' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Observaciones Adicionales</label>
                <div class="p-3 bg-white/50 dark:bg-secondary-900/40 rounded-xl border border-zinc-100 dark:border-secondary-700 text-[11px] text-zinc-600 dark:text-secondary-400 italic font-bold">
                    {{ sale.delivery_observations || 'SIN NOTAS ADICIONALES PARA EL REPARTIDOR.' }}
                </div>
            </div>
        </div>

        <!-- Información de Envío por Encomienda (Solo para Encomienda) -->
        <div v-if="sale.delivery_mode.value === 'envio_encomienda'" class="space-y-4 bg-slate-50/50 dark:bg-slate-900/10 rounded-2xl border border-slate-100 dark:border-slate-800/50 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-4 bg-slate-500 rounded-full"></div>
                    <span class="text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest">Detalles de Envío por Encomienda</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-slate-400 opacity-60">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
            </div>

            <!-- Contacto -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Receptor / Contacto</label>
                    <div class="flex items-center p-2.5 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 min-h-[46px]">
                        <span class="text-[11px] font-black text-zinc-900 dark:text-white uppercase truncate">
                            {{ sale.delivery_contact_name || sale.client.name }}
                        </span>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Teléfono Contacto</label>
                    <div class="flex items-center gap-3 p-2.5 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 min-h-[46px]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-emerald-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.33.185.506.548.438.912l-1.323 7.126a3.375 3.375 0 0 1-3.315 2.76H8.25a3.375 3.375 0 0 1-3.315-2.76l-1.323-7.126a.75.75 0 0 1 .438-.912l16.2-7.5ZM8.25 19.5v-7.5M15.75 19.5v-7.5" />
                        </svg>
                        <a :href="`https://wa.me/${cleanPhone(sale.delivery_contact_phone || sale.client.phone)}`" target="_blank" class="text-[11px] font-black text-emerald-600 dark:text-emerald-400 hover:underline">
                            {{ sale.delivery_contact_phone || sale.client.phone }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Empresa -->
            <div class="space-y-1.5">
                <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Empresa de Transporte</label>
                <div class="p-3 bg-white dark:bg-secondary-800 rounded-xl border border-zinc-200 dark:border-secondary-700 text-[11px] font-black text-zinc-900 dark:text-white uppercase flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a.75.75 0 0 1 .75.75 6.75 6.75 0 0 1-13.5 0 .75.75 0 0 1 .75-.75h12Z" />
                    </svg>
                    {{ sale.shipping_company || 'POR DEFINIR' }}
                </div>
            </div>

            <!-- Sucursales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Sucursal Origen</label>
                    <div class="p-2.5 bg-zinc-50 dark:bg-secondary-900/40 rounded-xl border border-zinc-200 dark:border-secondary-700 text-[11px] font-bold text-zinc-700 dark:text-zinc-300 uppercase">
                        {{ sale.shipping_origin || 'N/A' }}
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Sucursal Destino</label>
                    <div class="p-2.5 bg-zinc-50 dark:bg-secondary-900/40 rounded-xl border border-zinc-200 dark:border-secondary-700 text-[11px] font-bold text-zinc-700 dark:text-zinc-300 uppercase">
                        {{ sale.shipping_destination || 'N/A' }}
                    </div>
                </div>
            </div>

            <!-- Costo y Observaciones -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-rose-500 uppercase tracking-widest">Costo Encomienda</label>
                    <div class="p-2.5 bg-white dark:bg-secondary-800 rounded-xl border border-rose-200 dark:border-rose-900/50 text-[11px] font-black text-rose-600 dark:text-rose-400">
                        Bs. {{ formatMoney(sale.delivery_cost) }}
                    </div>
                </div>
                <div class="sm:col-span-2 space-y-1.5">
                    <label class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Observaciones Adicionales</label>
                    <div class="p-2.5 bg-white/50 dark:bg-secondary-900/40 rounded-xl border border-zinc-100 dark:border-secondary-700 text-[11px] text-zinc-600 dark:text-secondary-400 italic font-bold">
                        {{ sale.delivery_observations || 'SIN NOTAS ADICIONALES.' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
