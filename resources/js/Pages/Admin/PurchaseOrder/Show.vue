<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    purchaseOrder: Object,
    isAdmin: Boolean,
});

const record = computed(() => props.purchaseOrder.data);

const showApproveModal = ref(false);

const approveForm = useForm({
    voucher_type: 'factura',
    voucher_type_number: '',
    payment_type: 'efectivo',
    due_date: '',
    down_payment: 0,
    receipt_file: null,
});

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

const handleFileUpload = (e) => {
    approveForm.receipt_file = e.target.files[0];
};

const openApproveModal = () => {
    approveForm.reset();
    showApproveModal.value = true;
};

const closeApproveModal = () => {
    showApproveModal.value = false;
    approveForm.reset();
};

const rejectOrder = () => {
    Swal.fire({
        title: '¿Rechazar esta solicitud?',
        text: 'La solicitud se marcará como Rechazada y no podrá ser procesada en el futuro.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, rechazar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'rounded-3xl dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800',
            title: 'text-zinc-900 dark:text-white font-bold',
            htmlContainer: 'text-zinc-500 dark:text-zinc-400',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.purchase-orders.cancel', record.value.id), {}, {
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Solicitud rechazada correctamente.',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            });
        }
    });
};

const approveOrder = () => {
    Swal.fire({
        title: '¿Aprobar esta solicitud administrativamente?',
        text: 'La solicitud se marcará como Aprobada. Quedará disponible para que el encargado de almacén registre la compra física con el comprobante correspondiente.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'rounded-3xl dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800',
            title: 'text-zinc-900 dark:text-white font-bold',
            htmlContainer: 'text-zinc-500 dark:text-zinc-400',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.purchase-orders.approve', record.value.id), {}, {
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Solicitud aprobada administrativamente.',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            });
        }
    });
};

const submitApprove = () => {
    approveForm.post(route('admin.purchase-orders.convert', record.value.id), {
        onSuccess: () => {
            closeApproveModal();
            Swal.fire({
                icon: 'success',
                title: '¡Compra Registrada!',
                text: 'La solicitud de compra ha sido convertida a compra física y el stock ha sido actualizado con éxito.',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    popup: 'rounded-3xl dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800',
                    title: 'text-zinc-900 dark:text-white font-bold',
                }
            });
        }
    });
};

</script>

<template>
    <Head title="Detalle de Solicitud de Compra" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div class="text-left">
                <div class="flex items-center gap-2">
                    <span v-if="record.status === 'pendiente'" 
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 border border-amber-200 dark:border-amber-800/30 animate-pulse">
                        Pendiente de Aprobación
                    </span>
                    <span v-else-if="record.status === 'aprobada'" 
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-sky-100 dark:bg-sky-900/30 text-sky-800 dark:text-sky-400 border border-sky-200 dark:border-sky-800/30">
                        Aprobada - Pendiente de Compra
                    </span>
                    <span v-else-if="record.status === 'completada'" 
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/30">
                        Compra Completada
                    </span>
                    <span v-else-if="record.status === 'cancelada'" 
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400 border border-rose-200 dark:border-rose-800/30">
                        Rechazada / Cancelada
                    </span>
                </div>
                <h1 class="text-2xl font-black text-zinc-900 dark:text-white leading-tight tracking-tight uppercase mt-2">Detalle de Solicitud</h1>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Revisión general y procesamiento administrativo de solicitud de existencias.</p>
            </div>
            <Link :href="route('admin.purchase-orders.index')" 
                  class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 text-xs font-black uppercase tracking-widest rounded-2xl border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-750 transition-all shadow-sm active:scale-95">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Volver
            </Link>
        </div>

        <!-- Panel Administrativo (Fase 1: Pendiente e isAdmin) -->
        <div v-if="record.status === 'pendiente' && isAdmin" 
             class="bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 rounded-3xl p-6 mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-sm animate-in fade-in duration-300">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0 shadow-inner">
                    <span class="material-symbols-outlined text-2xl">gavel</span>
                </div>
                <div>
                    <h3 class="text-sm font-black text-indigo-900 dark:text-indigo-300 uppercase tracking-wide">Acción Administrativa Requerida</h3>
                    <p class="text-xs text-indigo-700/80 dark:text-indigo-400/80 mt-1 leading-relaxed font-semibold">
                        Esta solicitud de compra está pendiente de autorización. Como administrador, valide los costos y cantidades estimadas antes de aprobar administrativamente la solicitud.
                    </p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <button type="button" @click="rejectOrder"
                        class="px-6 py-3 border border-rose-200 dark:border-rose-900/50 text-rose-600 dark:text-rose-400 text-xs font-black uppercase tracking-widest rounded-2xl bg-rose-50/50 dark:bg-rose-950/10 hover:bg-rose-600 hover:text-white transition-all active:scale-95 text-center">
                    Rechazar Solicitud
                </button>
                <button type="button" @click="approveOrder"
                        class="px-6 py-3 bg-indigo-600 dark:bg-indigo-500 hover:bg-indigo-700 dark:hover:bg-indigo-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-md active:scale-95 text-center flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">check_circle</span>
                    Aprobar Solicitud
                </button>
            </div>
        </div>

        <!-- Panel de Almacén/Compras (Fase 2: Aprobada) -->
        <div v-if="record.status === 'aprobada'" 
             class="bg-sky-50 dark:bg-sky-950/20 border border-sky-100 dark:border-sky-900/30 rounded-3xl p-6 mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-sm animate-in fade-in duration-300">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-sky-600/10 text-sky-600 dark:text-sky-400 flex items-center justify-center flex-shrink-0 shadow-inner">
                    <span class="material-symbols-outlined text-2xl">local_shipping</span>
                </div>
                <div>
                    <h3 class="text-sm font-black text-sky-900 dark:text-sky-300 uppercase tracking-wide">Fase 2: Registro de Compra Física</h3>
                    <p class="text-xs text-sky-700/80 dark:text-sky-400/80 mt-1 leading-relaxed font-semibold">
                        Esta orden ha sido aprobada administrativamente por el administrador. Ingrese el comprobante físico una vez recibidos los productos para completar el inventario y actualizar el stock.
                    </p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <button type="button" @click="openApproveModal"
                        class="px-6 py-3 bg-sky-600 dark:bg-sky-500 hover:bg-sky-700 dark:hover:bg-sky-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-md active:scale-95 text-center flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">receipt_long</span>
                    Registrar Compra Real
                </button>
            </div>
        </div>

        <!-- Grid Principal (Dos Columnas) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Columna Izquierda: Detalles del Pedido e Items (2/3 de ancho en desktop) -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Card de Detalles de Productos -->
                <div class="bg-surface dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 shadow-sm relative overflow-hidden">
                    <div class="h-1.5 w-full bg-gradient-to-r from-zinc-400 to-zinc-500 rounded-t-3xl"></div>
                    <div class="p-5 border-b border-zinc-100 dark:border-zinc-800 flex items-center gap-3 bg-zinc-50/50 dark:bg-zinc-900/50">
                        <span class="material-symbols-outlined text-zinc-500 dark:text-zinc-400">shopping_cart</span>
                        <div>
                            <h2 class="text-sm font-black text-zinc-900 dark:text-white uppercase tracking-tight">Detalle de Solicitud</h2>
                            <p class="text-[10px] text-zinc-400 uppercase tracking-widest font-black mt-0.5">Productos solicitados y costos proyectados</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-zinc-50/30 dark:bg-zinc-900/30 border-b border-zinc-100 dark:border-zinc-800">
                                <tr class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">
                                    <th class="px-6 py-4">Código</th>
                                    <th class="px-6 py-4">Producto</th>
                                    <th class="px-6 py-4 text-center">Unidad</th>
                                    <th class="px-6 py-4 text-center">Cantidad</th>
                                    <th class="px-6 py-4 text-right">Costo Unit. (Est)</th>
                                    <th class="px-6 py-4 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <tr v-for="item in record.details" :key="item.id"
                                    class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                                    <td class="px-6 py-4 font-bold text-zinc-400 dark:text-zinc-500">
                                        {{ item.product_code || '—' }}
                                    </td>
                                    <td class="px-6 py-4 font-black text-zinc-900 dark:text-white">
                                        {{ item.product_name }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-semibold text-zinc-500 dark:text-zinc-400">
                                        {{ item.unit_of_measure || 'Unidades' }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-black text-zinc-900 dark:text-white">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-zinc-500 dark:text-zinc-400 font-semibold">
                                        Bs. {{ formatNumber(item.unit_price) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-zinc-950 dark:text-white">
                                        Bs. {{ formatNumber(item.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totales -->
                    <div class="p-6 bg-zinc-50/50 dark:bg-zinc-900/50 border-t border-zinc-100 dark:border-zinc-800 flex justify-end items-center">
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Total Presupuestado (Est.):</span>
                            <span class="text-lg font-black text-zinc-950 dark:text-white">Bs. {{ formatNumber(record.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card de Notas -->
                <div v-if="record.notes" 
                     class="bg-surface dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 shadow-sm relative overflow-hidden p-6">
                    <h3 class="text-xs font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">sticky_note_2</span>
                        Notas / Observaciones del Solicitante
                    </h3>
                    <p class="text-xs text-zinc-700 dark:text-zinc-300 leading-relaxed font-semibold">
                        {{ record.notes }}
                    </p>
                </div>
            </div>

            <!-- Columna Derecha: Metadatos y Datos del Proveedor (1/3 de ancho en desktop) -->
            <div class="space-y-6">
                
                <!-- Card de Información de Cabecera -->
                <div class="bg-surface dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 shadow-sm relative overflow-hidden p-6">
                    <h3 class="text-xs font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-6 flex items-center gap-2 border-b border-zinc-100 dark:border-zinc-850 pb-3">
                        <span class="material-symbols-outlined text-base">info</span>
                        Información General
                    </h3>

                    <div class="space-y-4">
                        <!-- Fecha -->
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Fecha de Solicitud</span>
                            <span class="text-xs font-black text-zinc-900 dark:text-white capitalize mt-1">{{ record.date_formatted }}</span>
                        </div>

                        <hr class="border-zinc-100 dark:border-zinc-850" />

                        <!-- Solicitante -->
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Usuario Solicitante</span>
                            <div class="flex items-center gap-2.5 mt-2">
                                <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 flex items-center justify-center font-bold text-xs uppercase shadow-inner border border-zinc-200 dark:border-zinc-700">
                                    {{ record.user?.name ? record.user.name.charAt(0) : 'U' }}
                                </div>
                                <span class="text-xs font-bold text-zinc-900 dark:text-white">{{ record.user?.name || '—' }}</span>
                            </div>
                        </div>

                        <hr class="border-zinc-100 dark:border-zinc-850" />

                        <!-- Almacén Destino -->
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Almacén Destino</span>
                            <div class="flex items-center gap-2.5 mt-1.5 text-zinc-900 dark:text-white">
                                <span class="material-symbols-outlined text-zinc-400 text-lg">warehouse</span>
                                <span class="text-xs font-bold">{{ record.warehouse?.name || '—' }}</span>
                            </div>
                        </div>

                        <hr class="border-zinc-100 dark:border-zinc-850" />

                        <!-- Proveedor Proyectado -->
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Proveedor Proyectado</span>
                            <div class="flex items-center gap-2.5 mt-1.5 text-zinc-900 dark:text-white">
                                <span class="material-symbols-outlined text-zinc-400 text-lg">local_shipping</span>
                                <span class="text-xs font-bold">{{ record.provider?.name || '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card del Estado de la Transacción -->
                <div class="bg-surface dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 shadow-sm relative overflow-hidden p-6">
                    <h3 class="text-xs font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-4 flex items-center gap-2 border-b border-zinc-100 dark:border-zinc-850 pb-3">
                        <span class="material-symbols-outlined text-base">verified</span>
                        Estado del Flujo
                    </h3>

                    <div class="space-y-4">
                        <div v-if="record.status === 'pendiente'" class="p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30 rounded-2xl">
                            <div class="flex gap-3">
                                <span class="material-symbols-outlined text-amber-500 text-xl flex-shrink-0">hourglass_empty</span>
                                <div class="text-xs">
                                    <p class="font-black text-amber-900 dark:text-amber-300 uppercase tracking-wide">Esperando Aprobación</p>
                                    <p class="text-amber-700/90 dark:text-amber-400/90 mt-1 leading-relaxed font-semibold">
                                        Esta solicitud no ha sido evaluada por el administrador y aún no compromete inventarios ni fondos.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="record.status === 'aprobada'" class="p-4 bg-sky-50 dark:bg-sky-950/20 border border-sky-100 dark:border-sky-900/30 rounded-2xl">
                            <div class="flex gap-3">
                                <span class="material-symbols-outlined text-sky-500 text-xl flex-shrink-0">gavel</span>
                                <div class="text-xs">
                                    <p class="font-black text-sky-900 dark:text-sky-300 uppercase tracking-wide">Aprobación Administrativa</p>
                                    <p class="text-sky-700/90 dark:text-sky-400/90 mt-1 leading-relaxed font-semibold">
                                        La solicitud fue autorizada por el administrador. Está pendiente de que Almacén registre el comprobante físico de compra para ingresar los productos al inventario.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="record.status === 'completada'" class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 rounded-2xl">
                            <div class="flex gap-3">
                                <span class="material-symbols-outlined text-emerald-500 text-xl flex-shrink-0">check_circle</span>
                                <div class="text-xs">
                                    <p class="font-black text-emerald-900 dark:text-emerald-300 uppercase tracking-wide">Compra Completada</p>
                                    <p class="text-emerald-700/90 dark:text-emerald-400/90 mt-1 leading-relaxed font-semibold">
                                        La solicitud fue convertida exitosamente a una compra física real. El stock de los productos ha sido incrementado y se generaron los movimientos de Kardex correspondientes.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="record.status === 'cancelada'" class="p-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/30 rounded-2xl">
                            <div class="flex gap-3">
                                <span class="material-symbols-outlined text-rose-500 text-xl flex-shrink-0">cancel</span>
                                <div class="text-xs">
                                    <p class="font-black text-rose-900 dark:text-rose-300 uppercase tracking-wide">Rechazada por Administración</p>
                                    <p class="text-rose-700/90 dark:text-rose-400/90 mt-1 leading-relaxed font-semibold">
                                        Esta solicitud fue desestimada y cancelada permanentemente.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL DE CONVERSIÓN A COMPRA REAL -->
        <div v-if="showApproveModal" 
             class="fixed inset-0 z-[60] overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeApproveModal"></div>

            <div class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-zinc-900 text-left shadow-2xl transition-all w-full sm:max-w-lg border border-zinc-200 dark:border-zinc-800 z-10 animate-in zoom-in-95 duration-200">
                <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-t-3xl"></div>
                
                <!-- Header del Modal -->
                <div class="bg-white dark:bg-zinc-900 px-6 py-5 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0 border border-indigo-100 dark:border-indigo-800/30 shadow-inner">
                            <span class="material-symbols-outlined text-2xl">receipt_long</span>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-zinc-900 dark:text-white leading-tight uppercase tracking-tight">Convertir a Compra Real</h3>
                            <p class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mt-1">Valide e ingrese los datos del comprobante físico de la compra</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitApprove" class="p-6 space-y-6">
                    <!-- Errores locales del formulario -->
                    <div v-if="Object.keys(approveForm.errors).length > 0" 
                         class="p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-2xl flex items-start gap-3 text-rose-600 dark:text-rose-400">
                        <span class="material-symbols-outlined text-lg">error</span>
                        <div class="text-xs font-semibold leading-relaxed">
                            <ul class="list-disc pl-3">
                                <li v-for="(err, key) in approveForm.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Tipo de Comprobante -->
                        <div>
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Tipo Comprobante *</label>
                            <select v-model="approveForm.voucher_type" required
                                    class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs font-semibold text-zinc-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
                                <option value="factura">Factura</option>
                                <option value="recibo">Recibo de Compra</option>
                                <option value="boleta">Boleta</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <!-- Número de Comprobante -->
                        <div>
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Nº Comprobante / Factura *</label>
                            <input v-model="approveForm.voucher_type_number" type="text" required placeholder="Ej: 154823"
                                   @input="approveForm.errors.voucher_type_number = null"
                                   class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs font-semibold text-zinc-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
                        </div>
                    </div>

                    <!-- Tipo de Pago -->
                    <div>
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-2.5 ml-1">Método de Pago *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label :class="{'border-indigo-500 bg-indigo-50/30 dark:bg-indigo-950/20 text-indigo-700 dark:text-indigo-400': approveForm.payment_type === 'efectivo'}"
                                   class="flex items-center justify-center gap-2.5 py-3 border border-zinc-200 dark:border-zinc-800 rounded-2xl cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 text-xs font-black uppercase tracking-widest text-zinc-500 dark:text-zinc-400 transition-all">
                                <input type="radio" value="efectivo" v-model="approveForm.payment_type" class="hidden">
                                <span class="material-symbols-outlined text-lg">payments</span>
                                Contado (Efectivo)
                            </label>
                            <label :class="{'border-indigo-500 bg-indigo-50/30 dark:bg-indigo-950/20 text-indigo-700 dark:text-indigo-400': approveForm.payment_type === 'credito'}"
                                   class="flex items-center justify-center gap-2.5 py-3 border border-zinc-200 dark:border-zinc-800 rounded-2xl cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 text-xs font-black uppercase tracking-widest text-zinc-500 dark:text-zinc-400 transition-all">
                                <input type="radio" value="credito" v-model="approveForm.payment_type" class="hidden">
                                <span class="material-symbols-outlined text-lg">calendar_month</span>
                                Crédito
                            </label>
                        </div>
                    </div>

                    <!-- Campos de Crédito con Animación/Visualización Reactiva -->
                    <div v-if="approveForm.payment_type === 'credito'" 
                         class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-zinc-50/50 dark:bg-zinc-900/50 rounded-2xl border border-zinc-150 dark:border-zinc-850 animate-in slide-in-from-top-3 duration-250">
                        
                        <!-- Fecha de Vencimiento -->
                        <div>
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Fecha Vencimiento *</label>
                            <input v-model="approveForm.due_date" type="date" required
                                   class="w-full px-4 py-2.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs font-semibold text-zinc-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
                        </div>

                        <!-- Adelanto en Efectivo -->
                        <div>
                            <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Adelanto Inicial</label>
                            <div class="relative">
                                <input v-model.number="approveForm.down_payment" type="number" step="any" min="0" :max="record.total"
                                       class="w-full pl-7 pr-3 py-2.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs font-black text-zinc-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
                                <span class="absolute left-3 top-3 text-[10px] font-black text-zinc-400">Bs</span>
                            </div>
                        </div>
                    </div>

                    <!-- Documento Adjunto (Opcional) -->
                    <div>
                        <label class="block text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5 ml-1">Comprobante Digital / Foto (Opcional)</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-zinc-200 dark:border-zinc-800 border-dashed rounded-2xl bg-zinc-50/50 dark:bg-zinc-900/50">
                            <div class="space-y-1 text-center">
                                <span class="material-symbols-outlined text-3xl text-zinc-450 dark:text-zinc-650">upload_file</span>
                                <div class="flex text-xs text-zinc-600 dark:text-zinc-450">
                                    <label class="relative cursor-pointer rounded-md font-black text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus-within:outline-none">
                                        <span>Subir comprobante</span>
                                        <input type="file" @change="handleFileUpload" class="sr-only" accept="image/*,application/pdf">
                                    </label>
                                    <p class="pl-1">o arrastre el archivo</p>
                                </div>
                                <p class="text-[10px] text-zinc-400 dark:text-zinc-500">PDF, PNG, JPG hasta 5MB</p>
                                <p v-if="approveForm.receipt_file" class="text-xs font-bold text-emerald-600 mt-2 flex items-center justify-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                    {{ approveForm.receipt_file.name }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer del Modal -->
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                        <button type="button" @click="closeApproveModal"
                                class="px-5 py-2.5 text-xs font-black uppercase tracking-widest text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800/60 rounded-xl transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="approveForm.processing"
                                class="px-8 py-2.5 bg-indigo-600 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/25 disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="approveForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Confirmar Aprobación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
