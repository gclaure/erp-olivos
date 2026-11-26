<script setup>
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    order: {
        type: Object,
        default: null,
    },
    previousOrders: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    'close',
    'cancel-order',
    'select-order',
    'filter-by-customer',
]);

const activeLightboxImage = ref(null);

const allComplements = computed(() => {
    if (!props.order) return [];
    const list = [];
    (props.order.items || []).forEach((it) => {
        (it.complements || []).forEach((c) => list.push(c));
    });
    (props.order.complements || []).forEach((c) => {
        if (!c.order_item_id) list.push(c);
    });
    return list;
});

const formatQuantity = (qty) => {
    const num = Number(qty || 0);
    return Number.isInteger(num) ? String(num) : num.toFixed(2);
};

const cleanPhone = (phone) => {
    return String(phone || '').replace(/\D/g, '');
};

const getWhatsAppUrl = (phone, customerName, orderNumber) => {
    const raw = cleanPhone(phone);
    if (!raw) return '#';
    const text = encodeURIComponent(`Hola ${customerName || ''}, te contactamos de Pastelería Los Olivos con relación a tu Reserva #${orderNumber}.`);
    return `https://wa.me/${raw.length === 8 ? '591' + raw : raw}?text=${text}`;
};

const getInitials = (name) => {
    if (!name) return 'CL';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
};
</script>

<template>
    <Modal :show="show" maxWidth="3xl" zIndexClass="z-[1000]" @close="emit('close')">
        <div class="bg-white dark:bg-secondary-800 text-zinc-900 dark:text-white max-h-[90vh] flex flex-col rounded-3xl overflow-hidden shadow-2xl">
            <!-- Estado de carga -->
            <div v-if="loading" class="py-24 flex flex-col items-center justify-center gap-3">
                <span class="material-symbols-outlined text-4xl text-primary-500 animate-spin">sync</span>
                <p class="text-xs font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400">
                    Cargando detalle de la reserva...
                </p>
            </div>

            <template v-else-if="order">
                <!-- Header Atenuado con Jerarquía Visual Clara -->
                <div class="px-5 py-4 sm:px-6 sm:py-5 bg-zinc-50/80 dark:bg-secondary-900/60 border-b border-zinc-200/80 dark:border-secondary-700/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div
                            class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm flex-shrink-0"
                            :class="order.is_cancelled
                                ? 'bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400'
                                : 'bg-primary-600 text-white'"
                        >
                            <span class="material-symbols-outlined text-2xl">
                                {{ order.is_cancelled ? 'cancel' : 'receipt_long' }}
                            </span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-lg bg-primary-600 text-white text-xs font-black uppercase tracking-wider">
                                    Reserva #{{ order.formatted_number }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border shadow-xs"
                                    :class="order.is_cancelled
                                        ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/60'
                                        : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="order.is_cancelled ? 'bg-rose-500' : 'bg-emerald-500'"></span>
                                    {{ order.is_cancelled ? 'Anulado' : 'Registrado' }}
                                </span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-secondary-400 font-semibold mt-1">
                                Registrado el {{ order.registered_at_formatted || order.registered_at }}
                                <template v-if="order.user?.name"> · Por <span class="text-zinc-700 dark:text-secondary-200 font-bold">{{ order.user.name }}</span></template>
                            </p>
                        </div>
                    </div>

                    <!-- Botones de Acción Superiores -->
                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <button
                            v-if="!order.is_cancelled"
                            type="button"
                            @click="emit('cancel-order', order)"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-xl bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50 text-xs font-black uppercase tracking-wider transition-all"
                            title="Anular esta reserva"
                        >
                            <span class="material-symbols-outlined text-base">block</span>
                            <span>Anular</span>
                        </button>

                        <a
                            :href="route('admin.orders.receipt-pdf', order.id)"
                            target="_blank"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-xl bg-zinc-800 hover:bg-zinc-900 dark:bg-secondary-700 dark:hover:bg-secondary-600 text-white text-xs font-black uppercase tracking-wider transition-all shadow-xs"
                            title="Imprimir comanda técnica"
                        >
                            <span class="material-symbols-outlined text-base">print</span>
                            <span>Imprimir</span>
                        </a>

                        <button
                            type="button"
                            @click="emit('close')"
                            class="w-9 h-9 rounded-xl flex items-center justify-center text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-200/60 dark:hover:bg-secondary-700 transition-colors"
                            title="Cerrar modal"
                        >
                            <span class="material-symbols-outlined text-xl">close</span>
                        </button>
                    </div>
                </div>

                <!-- Cuerpo Desplazable -->
                <div class="p-5 sm:p-6 overflow-y-auto custom-scrollbar space-y-6 flex-1">
                    <!-- Banner de Reserva Anulada (Prioridad Superior) -->
                    <div
                        v-if="order.is_cancelled"
                        class="p-4 rounded-2xl bg-rose-50/90 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 flex items-start gap-3.5"
                    >
                        <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/60 text-rose-600 dark:text-rose-300 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-xl">cancel</span>
                        </div>
                        <div class="space-y-1 flex-1">
                            <div class="text-xs font-black uppercase tracking-wider text-rose-700 dark:text-rose-300">
                                Reserva Anulada
                            </div>
                            <p class="text-xs sm:text-sm text-zinc-700 dark:text-secondary-200 font-medium">
                                <span class="font-bold text-zinc-900 dark:text-white">Motivo:</span>
                                {{ order.cancellation_reason || 'Sin motivo especificado' }}
                            </p>
                            <p v-if="order.cancelled_at" class="text-[11px] text-zinc-500 dark:text-secondary-400">
                                Fecha de anulación: {{ order.cancelled_at }}
                                <template v-if="order.cancelled_by?.name"> · Registrado por {{ order.cancelled_by.name }}</template>
                            </p>
                        </div>
                    </div>

                    <!-- Tarjetas de Cliente y Entrega (Diseño 2 Columnas) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- 1. Tarjeta Cliente -->
                        <div class="p-4 sm:p-5 rounded-2xl bg-zinc-50 dark:bg-secondary-900/60 border border-zinc-200/70 dark:border-secondary-700 flex flex-col justify-between gap-3">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black text-zinc-400 dark:text-secondary-400 uppercase tracking-widest flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm text-primary-500">person</span>
                                        Datos del Cliente
                                    </span>
                                    <button
                                        v-if="order.customer?.id"
                                        type="button"
                                        @click="emit('filter-by-customer', order.customer)"
                                        class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-wider text-primary-600 dark:text-primary-400 hover:underline"
                                    >
                                        <span class="material-symbols-outlined text-xs">history</span>
                                        {{ order.customer.orders_count || 1 }} pedidos
                                    </button>
                                </div>

                                <div class="flex items-center gap-3 mt-1">
                                    <div class="w-10 h-10 rounded-xl bg-primary-600 text-white flex items-center justify-center font-black text-sm shadow-xs flex-shrink-0">
                                        {{ getInitials(order.customer?.full_name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm sm:text-base font-black text-zinc-900 dark:text-white uppercase truncate">
                                            {{ order.customer?.full_name }}
                                        </div>
                                        <div class="text-xs font-bold text-zinc-500 dark:text-secondary-400 mt-0.5">
                                            Celular: {{ order.customer?.phone || 'No registrado' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Acciones rápidas de contacto con el cliente -->
                            <div v-if="order.customer?.phone" class="flex items-center gap-2 pt-2 border-t border-zinc-200/60 dark:border-secondary-700/60">
                                <a
                                    :href="`tel:${cleanPhone(order.customer.phone)}`"
                                    class="inline-flex items-center justify-center gap-1 h-8 px-3 rounded-lg bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 text-[11px] font-bold text-zinc-700 dark:text-secondary-200 hover:bg-zinc-100 dark:hover:bg-secondary-700 transition-colors"
                                >
                                    <span class="material-symbols-outlined text-sm text-primary-500">call</span>
                                    Llamar
                                </a>
                                <a
                                    :href="getWhatsAppUrl(order.customer.phone, order.customer.full_name, order.formatted_number)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center justify-center gap-1 h-8 px-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors"
                                >
                                    <span class="material-symbols-outlined text-sm text-emerald-600">chat</span>
                                    WhatsApp
                                </a>
                            </div>
                        </div>

                        <!-- 2. Tarjeta Logística y Entrega -->
                        <div class="p-4 sm:p-5 rounded-2xl bg-zinc-50 dark:bg-secondary-900/60 border border-zinc-200/70 dark:border-secondary-700 space-y-3">
                            <span class="text-[10px] font-black text-zinc-400 dark:text-secondary-400 uppercase tracking-widest flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-primary-500">local_shipping</span>
                                Programación y Entrega
                            </span>

                            <div class="space-y-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-base">event</span>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-secondary-400">
                                            Fecha Programada
                                        </div>
                                        <div class="text-xs sm:text-sm font-black text-zinc-900 dark:text-white">
                                            {{ order.delivery_date_formatted || order.delivery_date }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-base">schedule</span>
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <div>
                                            <div class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-secondary-400">
                                                Hora y Modalidad
                                            </div>
                                            <div class="text-xs sm:text-sm font-black text-zinc-900 dark:text-white flex items-center gap-1.5">
                                                <span>{{ order.delivery_time }}</span>
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border"
                                                    :class="order.delivery_mode === 'delivery'
                                                        ? 'bg-sky-50 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-800/60'
                                                        : 'bg-primary-50 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 border-primary-200 dark:border-primary-800/60'"
                                                >
                                                    {{ order.delivery_mode_label }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="order.delivery_address" class="flex items-start gap-2.5 pt-1">
                                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span class="material-symbols-outlined text-base">location_on</span>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-secondary-400">
                                            Dirección de Despacho
                                        </div>
                                        <div class="text-xs font-semibold text-zinc-800 dark:text-secondary-200">
                                            {{ order.delivery_address }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Productos Solicitados (Comanda Técnica) -->
                    <div class="space-y-3.5">
                        <div class="flex items-center justify-between border-b border-zinc-200 dark:border-secondary-700 pb-2.5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-primary-600 dark:text-primary-400 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">bakery_dining</span>
                                Productos Solicitados ({{ order.items?.length || 0 }})
                            </h3>
                            <span class="text-[10px] font-black uppercase tracking-wider text-zinc-400 dark:text-secondary-400">
                                Especificaciones Técnicas
                            </span>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="(item, idx) in order.items"
                                :key="item.id || idx"
                                class="rounded-2xl border border-zinc-200/80 dark:border-secondary-700 bg-white dark:bg-secondary-900/40 p-4 sm:p-5 space-y-4 shadow-xs"
                            >
                                <!-- Encabezado del Producto -->
                                <div class="flex items-start justify-between gap-3 border-b border-zinc-100 dark:border-secondary-800 pb-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-xl bg-primary-600 text-white text-xs font-black shadow-xs">
                                            {{ formatQuantity(item.quantity) }} un.
                                        </span>
                                        <div>
                                            <div class="text-sm sm:text-base font-black text-zinc-900 dark:text-white uppercase">
                                                {{ item.product_name }}
                                            </div>
                                            <div v-if="item.portions_or_size" class="text-xs font-bold text-primary-600 dark:text-primary-400">
                                                Tamaño / Porciones: {{ item.portions_or_size }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Especificaciones Técnicas (Chips / Badges modernos) -->
                                <div v-if="item.details?.length" class="space-y-1.5">
                                    <div class="text-[10px] font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400">
                                        Características y Masas
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="detail in item.details"
                                            :key="detail.id"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-50 dark:bg-secondary-800/80 border border-zinc-200/70 dark:border-secondary-700 text-xs text-zinc-700 dark:text-secondary-200"
                                        >
                                            <span class="font-black text-zinc-900 dark:text-white">{{ detail.label }}:</span>
                                            <span class="font-semibold">{{ detail.value }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Decoración Personalizada -->
                                <div v-if="item.custom_description" class="rounded-xl border border-primary-200 dark:border-primary-900/50 bg-primary-50/50 dark:bg-primary-950/30 p-3.5 space-y-1">
                                    <div class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-primary-700 dark:text-primary-300">
                                        <span class="material-symbols-outlined text-sm">palette</span>
                                        Decoración Solicitada
                                    </div>
                                    <p class="text-xs sm:text-sm text-zinc-800 dark:text-secondary-200 whitespace-pre-line leading-relaxed">
                                        {{ item.custom_description }}
                                    </p>
                                </div>

                                <!-- Observaciones Adicionales -->
                                <div v-if="item.observations" class="rounded-xl border border-zinc-200/80 dark:border-secondary-700 bg-zinc-50/80 dark:bg-secondary-800/40 p-3.5 space-y-1">
                                    <div class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-zinc-500 dark:text-secondary-400">
                                        <span class="material-symbols-outlined text-sm">chat_bubble_outline</span>
                                        Observaciones de Taller
                                    </div>
                                    <p class="text-xs sm:text-sm text-zinc-700 dark:text-secondary-300 whitespace-pre-line">
                                        {{ item.observations }}
                                    </p>
                                </div>

                                <!-- Referencia y Fotografías WebP -->
                                <div v-if="item.reference_notes || item.attachments?.length" class="space-y-2.5 pt-1 border-t border-zinc-100 dark:border-secondary-800/60">
                                    <div v-if="item.reference_notes">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 text-amber-800 dark:text-amber-300 text-xs font-black uppercase tracking-wider">
                                            <span class="material-symbols-outlined text-sm">photo_camera</span>
                                            Referencia: {{ item.reference_notes }}
                                        </span>
                                    </div>

                                    <!-- Galería con Overlay Elegante y Lightbox -->
                                    <div v-if="item.attachments?.length" class="space-y-1.5">
                                        <div class="text-[10px] font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400">
                                            Fotografías de Referencia ({{ item.attachments.length }})
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                            <button
                                                v-for="att in item.attachments"
                                                :key="att.id"
                                                type="button"
                                                @click="activeLightboxImage = att.file_url"
                                                class="group relative rounded-2xl overflow-hidden border border-zinc-200 dark:border-secondary-700 bg-zinc-100 dark:bg-secondary-900 aspect-square focus:outline-hidden focus:ring-2 focus:ring-primary-500"
                                                title="Hacer clic para ampliar imagen"
                                            >
                                                <img
                                                    :src="att.file_url"
                                                    :alt="att.original_name || 'Fotografía de referencia'"
                                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                                    loading="lazy"
                                                />
                                                <div class="absolute inset-0 bg-black/35 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-black/75 text-white text-[10px] font-black uppercase tracking-wider backdrop-blur-xs">
                                                        <span class="material-symbols-outlined text-xs">zoom_in</span>
                                                        Ampliar
                                                    </span>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Complementos Adicionales -->
                    <div v-if="allComplements.length" class="space-y-2.5">
                        <div class="flex items-center justify-between border-b border-zinc-200 dark:border-secondary-700 pb-2">
                            <h3 class="text-xs font-black uppercase tracking-widest text-primary-600 dark:text-primary-400 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">extension</span>
                                Complementos y Accesorios ({{ allComplements.length }})
                            </h3>
                            <span class="text-[10px] font-black uppercase tracking-wider text-zinc-400 dark:text-secondary-400">
                                Responsable
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div
                                v-for="comp in allComplements"
                                :key="comp.id"
                                class="p-3 rounded-xl border border-zinc-200/80 dark:border-secondary-700 bg-white dark:bg-secondary-900/40 flex items-center justify-between gap-3"
                            >
                                <span class="text-xs font-bold text-zinc-800 dark:text-secondary-200 truncate">
                                    {{ comp.description }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider flex-shrink-0 border"
                                    :class="comp.provider_type === 'business'
                                        ? 'bg-primary-50 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 border-primary-200 dark:border-primary-800/40'
                                        : 'bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800/40'"
                                >
                                    <span class="material-symbols-outlined text-xs">
                                        {{ comp.provider_type === 'business' ? 'store' : 'person' }}
                                    </span>
                                    {{ comp.provider_label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones de la Reserva -->
                    <div v-if="order.general_notes" class="space-y-2">
                        <h3 class="text-xs font-black uppercase tracking-widest text-primary-600 dark:text-primary-400 border-b border-zinc-200 dark:border-secondary-700 pb-2 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base">sticky_note_2</span>
                            Observaciones Generales de la Reserva
                        </h3>
                        <div class="p-4 rounded-2xl bg-zinc-50 dark:bg-secondary-900/60 border border-zinc-200/80 dark:border-secondary-700 text-xs sm:text-sm text-zinc-800 dark:text-secondary-200 whitespace-pre-line leading-relaxed">
                            {{ order.general_notes }}
                        </div>
                    </div>

                    <!-- Pedidos Anteriores del Cliente -->
                    <div v-if="previousOrders?.length" class="space-y-2.5 pt-2">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs text-primary-500">history</span>
                            Otras Reservas Registradas de {{ order.customer?.full_name }} ({{ previousOrders.length }})
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <button
                                v-for="prev in previousOrders"
                                :key="prev.id"
                                type="button"
                                @click="emit('select-order', prev.id)"
                                class="flex items-center justify-between p-3 rounded-2xl border border-zinc-200 dark:border-secondary-700 hover:border-primary-500 dark:hover:border-primary-500 bg-white dark:bg-secondary-900/50 dark:hover:bg-secondary-700/60 transition-colors text-left group"
                            >
                                <div>
                                    <div class="text-xs font-black text-primary-600 dark:text-primary-400 group-hover:underline">
                                        Reserva #{{ prev.formatted_number }}
                                    </div>
                                    <div class="text-[11px] text-zinc-500 dark:text-secondary-400">
                                        Entrega: {{ prev.delivery_date_formatted }} · {{ prev.delivery_time }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border"
                                        :class="prev.is_cancelled
                                            ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/40'
                                            : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/40'"
                                    >
                                        {{ prev.is_cancelled ? 'Anulado' : 'Registrado' }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer Atenuado y Accesible -->
                <div class="p-4 sm:p-5 bg-zinc-50/90 dark:bg-secondary-900/60 border-t border-zinc-200/80 dark:border-secondary-700 flex flex-col-reverse sm:flex-row justify-end items-stretch sm:items-center gap-2.5">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="h-11 px-5 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 text-xs font-black uppercase tracking-widest text-zinc-600 dark:text-secondary-300 hover:bg-zinc-100 dark:hover:bg-secondary-700 transition-colors"
                    >
                        Cerrar
                    </button>
                    <a
                        :href="route('admin.orders.receipt-pdf', { order: order.id, download: 1 })"
                        class="inline-flex items-center justify-center gap-2 h-11 px-6 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-black uppercase tracking-widest transition-all shadow-md active:scale-95"
                    >
                        <span class="material-symbols-outlined text-base">download</span>
                        <span>Descargar Comprobante PDF</span>
                    </a>
                </div>
            </template>
        </div>

        <!-- Visor Lightbox de fotografía WebP (Nivel 2: z-[1100]) -->
        <Teleport to="body">
            <div
                v-if="activeLightboxImage"
                @click="activeLightboxImage = null"
                class="fixed inset-0 z-[1100] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
            >
                <div class="relative max-w-4xl max-h-[90vh]">
                    <img
                        :src="activeLightboxImage"
                        alt="Fotografía de referencia ampliada"
                        class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain border border-white/10"
                    />
                    <button
                        type="button"
                        @click.stop="activeLightboxImage = null"
                        class="absolute -top-3 -right-3 sm:-top-4 sm:-right-4 w-10 h-10 rounded-full bg-secondary-900 text-white hover:bg-secondary-800 border border-white/20 flex items-center justify-center shadow-lg transition-transform hover:scale-105"
                        title="Cerrar ampliación"
                    >
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>
            </div>
        </Teleport>
    </Modal>
</template>
