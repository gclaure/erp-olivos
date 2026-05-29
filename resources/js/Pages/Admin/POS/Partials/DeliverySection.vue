<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
    form: Object,
    shippingHistory: Object,
    defaultClient: Object,
    permissions: {
        type: Array,
        default: () => []
    }
});

const deliveryMode = ref(props.form.delivery_mode || 'venta_directa');

// Mapeo de modos a permisos
const modePermissions = {
    'retiro_local': 'pos-delivery-pickup',
    'envio_domicilio': 'pos-delivery-home',
    'punto_encuentro': 'pos-delivery-point',
    'envio_encomienda': 'pos-delivery-package'
};

const availableModes = computed(() => {
    const allModes = [
        { id: 'venta_directa', label: 'Directa', icon: 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z' },
        { id: 'retiro_local', label: 'Retiro', icon: 'M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-16.5 0h19.5m-18-3h16.5m-16.5-3h16.5m-16.5-3h16.5m-16.5-3h16.5m-16.5-3h16.5m-16.5-3h16.5' },
        { id: 'envio_domicilio', label: 'Domicilio', icon: 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125V11.25c0-4.446-3.542-7.875-8.25-7.875H9.75M3.375 18.75H21m-1.5-3.75h.375a1.125 1.125 0 00 1.125-1.125V11.25c0-4.446-3.542-7.875-8.25-7.875H9.75' },
        { id: 'punto_encuentro', label: 'Punto E.', icon: 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z' },
        { id: 'envio_encomienda', label: 'Encomienda', icon: 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-10.5v10.5' }
    ];

    return allModes.map(mode => {
        const permission = modePermissions[mode.id];
        // Si no tiene permiso requerido, está deshabilitado por rol (a menos que sea venta directa)
        const hasPermission = !permission || props.permissions.includes(permission);
        return { ...mode, hasPermission };
    });
});

// Sync with form (two-way)
watch(deliveryMode, (val) => props.form.delivery_mode = val);
watch(() => props.form.delivery_mode, (val) => {
    if (val && val !== deliveryMode.value) {
        deliveryMode.value = val;
    }
});
</script>

<template>
    <div class="space-y-4">
        <p class="text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-widest mb-3 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-orange-500">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125V11.25c0-4.446-3.542-7.875-8.25-7.875H9.75M3.375 18.75H21m-1.5-3.75h.375a1.125 1.125 0 0 0 1.125-1.125V11.25c0-4.446-3.542-7.875-8.25-7.875H9.75m4.5 15V13.5m0 0h-4.5m4.5 0c0-1.657 1.007-3 2.25-3s2.25 1.343 2.25 3m-4.5 0C11.5 13.5 10.5 12.157 10.5 10.5s1.007-3 2.25-3s2.25 1.343 2.25 3m-13.5 0c0 1.657 1.007 3 2.25 3s2.25-1.343 2.25-3m-4.5 0C4.5 8.843 5.5 7.5 6.75 7.5s2.25 1.343 2.25 3m0-3V3.375c0-.621.504-1.125 1.125-1.125h1.5a1.125 1.125 0 0 1 1.125 1.125V7.5" />
            </svg>
            MODO DE ENTREGA *
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
            <button 
                v-for="mode in availableModes"
                :key="mode.id"
                type="button"
                @click="deliveryMode = mode.id"
                :disabled="!mode.hasPermission || (defaultClient && form.client_id === defaultClient.id && mode.id !== 'venta_directa')"
                class="flex flex-col items-center justify-center p-2.5 rounded-2xl border-2 transition-all gap-1.5"
                :class="[
                    deliveryMode === mode.id 
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 ring-4 ring-blue-500/10' 
                        : 'border-zinc-100 dark:border-secondary-700 bg-white dark:bg-secondary-900 text-zinc-400 dark:text-secondary-500 hover:border-blue-200 dark:hover:border-blue-500/30',
                    (!mode.hasPermission || (defaultClient && form.client_id === defaultClient.id && mode.id !== 'venta_directa')) ? 'opacity-30 cursor-not-allowed grayscale' : ''
                ]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="mode.icon" />
                </svg>
                <span class="text-[9px] font-black uppercase tracking-tighter leading-none">{{ mode.label }}</span>
            </button>
        </div>

        <!-- FORMULARIOS CONDICIONALES DE ENTREGA -->
        <div v-if="deliveryMode !== 'venta_directa'" class="space-y-4 pt-4">
            <!-- Retiro en Local -->
            <div v-if="deliveryMode === 'retiro_local'" class="p-4 rounded-2xl bg-emerald-500/5 border border-emerald-500/10 space-y-3">
                <label class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="m2.695 14.763-1.262 3.154a.5.5 0 0 0 .65.65l3.154-1.262a.5.5 0 0 0 .153-.081l.343-.343a.499.499 0 0 0 .15-.226l.465-1.396a1.5 1.5 0 0 0-.253-1.383l-1.055-1.319a.5.5 0 0 1 .012-.64l7.152-7.152a1.5 1.5 0 0 0-2.121-2.121L2.777 10.239a.5.5 0 0 1-.64.012l-1.319-1.055a1.5 1.5 0 0 0-1.383-.253l-1.396.465a.5.5 0 0 0-.226.15l-.343.343a.5.5 0 0 0-.081.153Z" /><path d="m18.232 1.768-4.243 4.243a.5.5 0 0 1-.707 0l-.707-.707a.5.5 0 0 1 0-.707l4.243-4.243a.5.5 0 0 1 .707 0l.707.707a.5.5 0 0 1 0 .707Z" /></svg>
                    Observaciones para Recojo
                </label>
                <textarea 
                    v-model="form.delivery_observations"
                    rows="2"
                    placeholder="Ej: Vendrá su hijo a recoger, llamar antes de venir..."
                    class="w-full bg-white dark:bg-secondary-900 border border-emerald-500/20 rounded-xl p-3 text-xs font-bold text-zinc-700 dark:text-white placeholder:text-zinc-300 dark:placeholder:text-secondary-600 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-400"
                ></textarea>
            </div>

            <!-- Otros Modos (Domicilio, Punto, Encomienda) -->
            <div v-if="['envio_domicilio', 'punto_encuentro', 'envio_encomienda'].includes(deliveryMode)" class="p-4 rounded-2xl bg-blue-500/5 border border-blue-500/10 space-y-4">
                <!-- Dirección / Punto -->
                <div v-if="deliveryMode !== 'envio_encomienda'">
                    <label class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2 block">
                        {{ deliveryMode === 'envio_domicilio' ? 'DIRECCIÓN DE ENVÍO *' : 'PUNTO DE ENCUENTRO *' }}
                    </label>
                    <textarea 
                        v-model="form.delivery_address"
                        rows="2"
                        placeholder="Indique la dirección detallada o referencia..."
                        class="w-full bg-white dark:bg-secondary-900 border border-blue-500/20 rounded-xl p-3 text-xs font-bold text-zinc-700 dark:text-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-400"
                    ></textarea>
                </div>

                <!-- Zona y Costo (Sólo Domicilio) -->
                <div v-if="deliveryMode === 'envio_domicilio'" class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2 block">ZONA / BARRIO</label>
                        <input v-model="form.delivery_zone" type="text" class="w-full bg-white dark:bg-secondary-900 border border-blue-500/20 rounded-xl px-3 py-2.5 text-xs font-bold text-zinc-700 dark:text-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-400" placeholder="Ej: Centro">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-2 block">COSTO ENVÍO *</label>
                        <input v-model="form.delivery_cost" type="number" class="w-full bg-white dark:bg-secondary-900 border border-rose-500/20 rounded-xl px-3 py-2.5 text-xs font-black text-zinc-700 dark:text-white focus:ring-4 focus:ring-rose-500/10 focus:border-rose-400 text-right" placeholder="0.00">
                    </div>
                </div>

                <!-- Contacto -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2 block">RECEPTOR</label>
                        <input v-model="form.delivery_contact_name" type="text" class="w-full bg-white dark:bg-secondary-900 border border-blue-500/20 rounded-xl px-3 py-2.5 text-xs font-bold text-zinc-700 dark:text-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-400" placeholder="Nombre">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2 block">TELÉFONO</label>
                        <input v-model="form.delivery_contact_phone" type="text" class="w-full bg-white dark:bg-secondary-900 border border-blue-500/20 rounded-xl px-3 py-2.5 text-xs font-bold text-zinc-700 dark:text-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-400" placeholder="Número">
                    </div>
                </div>

                <!-- Punto de Encuentro: Fecha/Hora -->
                <div v-if="deliveryMode === 'punto_encuentro'" class="grid grid-cols-2 gap-3 p-3 bg-white dark:bg-secondary-900 rounded-xl border border-blue-500/20">
                    <div>
                        <label class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 block">FECHA *</label>
                        <input v-model="form.delivery_date" type="date" class="w-full bg-zinc-50 dark:bg-secondary-800 border-zinc-100 dark:border-secondary-700 rounded-lg p-2 text-xs font-bold text-zinc-800 dark:text-white">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 block">HORA *</label>
                        <input v-model="form.delivery_time" type="time" class="w-full bg-zinc-50 dark:bg-secondary-800 border-zinc-100 dark:border-secondary-700 rounded-lg p-2 text-xs font-bold text-zinc-800 dark:text-white">
                    </div>
                </div>

                <!-- Encomienda -->
                <div v-if="deliveryMode === 'envio_encomienda'" class="space-y-3">
                    <div>
                        <label class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 block">EMPRESA DE TRANSPORTE *</label>
                        <input v-model="form.shipping_company" list="shipping_companies" class="w-full bg-white dark:bg-secondary-900 border border-blue-500/20 rounded-xl px-3 py-2.5 text-xs font-bold text-zinc-700 dark:text-white">
                        <datalist id="shipping_companies">
                            <option v-for="c in shippingHistory?.companies" :key="c" :value="c"></option>
                        </datalist>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 block">ORIGEN *</label>
                            <input v-model="form.shipping_origin" list="shipping_origins" class="w-full bg-white dark:bg-secondary-900 border border-blue-500/20 rounded-xl px-3 py-2.5 text-xs font-bold text-zinc-700 dark:text-white">
                            <datalist id="shipping_origins">
                                <option v-for="o in shippingHistory?.origins" :key="o" :value="o"></option>
                            </datalist>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 block">DESTINO *</label>
                            <input v-model="form.shipping_destination" list="shipping_destinations" class="w-full bg-white dark:bg-secondary-900 border border-blue-500/20 rounded-xl px-3 py-2.5 text-xs font-bold text-zinc-700 dark:text-white">
                            <datalist id="shipping_destinations">
                                <option v-for="d in shippingHistory?.destinations" :key="d" :value="d"></option>
                            </datalist>
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-2 block">COSTO ENCOMIENDA *</label>
                        <input v-model="form.delivery_cost" type="number" class="w-full bg-white dark:bg-secondary-900 border border-rose-500/20 rounded-xl px-3 py-2.5 text-xs font-black text-zinc-700 dark:text-white focus:ring-4 focus:ring-rose-500/10 focus:border-rose-400 text-right" placeholder="0.00">
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-widest mb-1 block">MAPS / UBICACIÓN</label>
                    <input v-model="form.delivery_map_url" type="url" placeholder="Pegue link de Google Maps" class="w-full bg-white dark:bg-secondary-900 border border-blue-500/20 rounded-xl px-3 py-2.5 text-xs font-bold text-zinc-700 dark:text-white">
                </div>
            </div>
        </div>
    </div>
</template>
