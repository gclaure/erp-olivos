<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    consumptionRequest: Object,
    isAdmin: {
        type: Boolean,
        default: false
    }
});

defineOptions({ layout: AdminLayout });

const request = computed(() => props.consumptionRequest.data);

const receivedQuantities = ref({});
const observations = ref({});

onMounted(() => {
    if (request.value && request.value.details) {
        request.value.details.forEach(item => {
            if (item.quantity_delivered > 0) {
                receivedQuantities.value[item.id] = parseFloat(item.quantity_delivered.toFixed(2));
            }
            observations.value[item.id] = item.observation || '';
        });
    }
});

// Determinar el color y etiqueta del estado
const statusBadgeClass = computed(() => {
    switch (request.value.status) {
        case 'pendiente':
            return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/10 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20';
        case 'aprobado':
            return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/10 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20';
        case 'observado':
            return 'bg-orange-50 text-orange-700 ring-1 ring-orange-600/10 dark:bg-orange-500/10 dark:text-orange-400 dark:ring-orange-500/20';
        case 'entregado':
            return 'bg-emerald-600 text-white font-black shadow-md shadow-emerald-500/20 dark:bg-emerald-500';
        case 'parcial':
            return 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/10 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20';
        case 'despachado':
            return 'bg-fuchsia-50 text-fuchsia-700 ring-1 ring-fuchsia-600/10 dark:bg-fuchsia-500/10 dark:text-fuchsia-400 dark:ring-fuchsia-500/20';
        case 'despachado_parcial':
            return 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/10 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/20';
        case 'cancelado':
            return 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20';
        default:
            return 'bg-slate-50 text-slate-700 ring-1 ring-slate-600/10 dark:bg-secondary-500/10 dark:text-secondary-400 dark:ring-secondary-500/20';
    }
});

const statusLabel = computed(() => {
    switch (request.value.status) {
        case 'pendiente': return 'Pendiente';
        case 'aprobado': return 'Aprobado';
        case 'observado': return 'Observado';
        case 'entregado': return 'Entregado';
        case 'parcial': return 'Despacho Parcial';
        case 'despachado': return 'Despachado (Por Recibir)';
        case 'despachado_parcial': return 'Despacho Parcial (Compra Solicitada)';
        case 'cancelado': return 'Cancelado';
        default: return request.value.status;
    }
});

// Comprobar si hay existencias suficientes para despachar al menos algo
const totalMissingQuantity = computed(() => {
    return request.value.details.reduce((acc, item) => {
        const pending = item.quantity_requested - item.quantity_delivered;
        const missing = pending > item.stock_available ? pending - item.stock_available : 0;
        return acc + missing;
    }, 0);
});

const totalDeliverableQuantity = computed(() => {
    return request.value.details.reduce((acc, item) => {
        const pending = item.quantity_requested - item.quantity_delivered;
        const deliverable = Math.min(pending, item.stock_available);
        return acc + deliverable;
    }, 0);
});

const isFullyStocked = computed(() => {
    return totalMissingQuantity.value === 0;
});

const hasAnyStockToDispatch = computed(() => {
    return totalDeliverableQuantity.value > 0;
});

// Acciones
const handleDispatch = () => {
    Swal.fire({
        title: '¿Confirmar Despacho?',
        text: isFullyStocked.value
            ? 'Se realizará la salida física de inventario para todos los insumos solicitados.'
            : 'Se despachará el stock físico disponible actualmente. El resto quedará como pendiente.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Despachar',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl mr-2 text-xs uppercase',
            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-4 rounded-xl text-xs uppercase dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-950'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.consumption-requests.dispatch', { consumption_request: request.value.id }), {}, {
                onSuccess: (page) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Despacho Registrado',
                        text: page.props.flash?.success || 'Los insumos se descontaron del inventario exitosamente.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    Swal.fire('Error', firstError, 'error');
                }
            });
        }
    });
};

const handleReceive = () => {
    // Validar cantidades recolectadas de la tabla
    const payload = {};
    const obsPayload = {};
    let hasError = false;
    let errorMsg = '';
    
    request.value.details.forEach((item) => {
        if (item.quantity_delivered > 0) {
            const rawVal = receivedQuantities.value[item.id];
            if (rawVal === undefined || isNaN(rawVal) || rawVal < 0) {
                hasError = true;
                errorMsg = `La cantidad recibida para ${item.product_name} no es válida y no puede ser negativa.`;
            } else {
                const finalQty = parseFloat(rawVal.toFixed(2));
                payload[item.id] = finalQty;

                // Si la cantidad es diferente a la solicitada, la observación es obligatoria
                const isDifferent = Math.abs(finalQty - parseFloat(item.quantity_requested.toFixed(2))) >= 0.01;
                const obs = observations.value[item.id] ? observations.value[item.id].trim() : '';

                if (isDifferent) {
                    if (!obs || obs.length < 3) {
                        hasError = true;
                        errorMsg = `Debe ingresar una observación/motivo de al menos 3 caracteres para la diferencia de cantidad en el producto: ${item.product_name}.`;
                    } else {
                        obsPayload[item.id] = obs;
                    }
                }
            }
        }
    });

    if (hasError) {
        Swal.fire({
            icon: 'warning',
            title: 'Validación de Recepción',
            text: errorMsg
        });
        return;
    }

    Swal.fire({
        title: '¿Confirmar Recepción?',
        text: 'Al confirmar, declararás que el área solicitante recibió físicamente las cantidades especificadas directamente en la tabla y se cerrará este ciclo de requisición.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Recepcionar',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl mr-2 text-xs uppercase',
            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-4 rounded-xl text-xs uppercase dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-950'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.consumption-requests.receive', { consumption_request: request.value.id }), {
                received_quantities: payload,
                observations: obsPayload
            }, {
                onSuccess: (page) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Recepción Registrada',
                        text: page.props.flash?.success || 'Los insumos se marcaron como recibidos exitosamente.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    Swal.fire('Error', firstError, 'error');
                }
            });
        }
    });
};

const handleGeneratePurchaseOrder = () => {
    Swal.fire({
        title: '¿Generar Solicitud de Compra?',
        text: 'Se preparará una solicitud de compra con las cantidades faltantes exactas para cubrir esta requisición.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Sí, Generar',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl mr-2 text-xs uppercase',
            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-4 rounded-xl text-xs uppercase dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-950'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.consumption-requests.generate-purchase-order', { consumption_request: request.value.id }), {}, {
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    Swal.fire('Error', firstError, 'error');
                }
            });
        }
    });
};

const handleCancel = () => {
    Swal.fire({
        title: '¿Cancelar Solicitud?',
        text: 'Esta acción no se puede deshacer y la requisición se marcará como cancelada.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, Cancelar',
        cancelButtonText: 'Volver',
        customClass: {
            confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-4 rounded-xl mr-2 text-xs uppercase',
            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-4 rounded-xl text-xs uppercase dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-950'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.consumption-requests.cancel', { consumption_request: request.value.id }), {}, {
                onSuccess: (page) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Solicitud Cancelada',
                        text: page.props.flash?.success || 'La requisición fue anulada.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    Swal.fire('Error', firstError, 'error');
                }
            });
        }
    });
};

const handleApprove = () => {
    Swal.fire({
        title: '¿Aprobar Solicitud?',
        text: 'Se aprobará administrativamente esta solicitud de consumo interno para que pueda ser despachada por el almacén.',
        input: 'textarea',
        inputLabel: 'Notas / Observaciones de Aprobación (Opcional)',
        inputPlaceholder: 'Escriba aquí alguna nota u observación sobre la aprobación si es necesario...',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Aprobar',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl mr-2 text-xs uppercase',
            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-4 rounded-xl text-xs uppercase dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-950'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.consumption-requests.approve', { consumption_request: request.value.id }), {
                observation_notes: result.value
            }, {
                onSuccess: (page) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Solicitud Aprobada',
                        text: page.props.flash?.success || 'La solicitud fue aprobada exitosamente.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    Swal.fire('Error', firstError, 'error');
                }
            });
        }
    });
};
</script>

<template>
    <Head :title="`Requisición de ${request.requested_by}`" />

    <div class="space-y-6 w-full py-2">
        <!-- HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-zinc-900 dark:text-secondary-50 tracking-tight uppercase">
                        Requisición #{{ request.formatted_number || 'S/N' }}
                    </h1>
                    <span 
                        v-if="request.status === 'entregado'"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-black uppercase tracking-widest bg-emerald-600 text-white shadow-lg shadow-emerald-500/30 dark:bg-emerald-500 dark:shadow-emerald-950/50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4.12-5.671Z" clip-rule="evenodd" />
                        </svg>
                        ENTREGADO
                    </span>
                    <span 
                        v-else
                        :class="['px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider', statusBadgeClass]"
                    >
                        {{ statusLabel }}
                    </span>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                <a 
                    :href="route('admin.consumption-requests.print', { consumption_request: request.id })" 
                    target="_blank"
                    class="inline-flex items-center justify-center h-10 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors gap-2 w-full sm:w-auto shadow-md shadow-indigo-500/10"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0a2.25 2.25 0 0 1-2.24 2.156H8.58A2.25 2.25 0 0 1 6.34 18m11.32 0h-11.32m11.32 0a2.25 2.25 0 0 0 2.25-2.25V9A2.25 2.25 0 0 0 15 6.75h-2.25V4.5a2.25 2.25 0 0 0-2.25-2.25h-3a2.25 2.25 0 0 0-2.25 2.25v2.25H3A2.25 2.25 0 0 0 .75 9v6.75a2.25 2.25 0 0 0 2.25 2.25h1.36m3.93 0h3.14m1.36 0h1.36M6.75 22.5h10.5" />
                    </svg>
                    Imprimir Requisición
                </a>
                
                <Link 
                    :href="route('admin.consumption-requests.index')" 
                    class="inline-flex items-center justify-center h-10 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-secondary-900 dark:hover:bg-secondary-950 text-slate-700 dark:text-secondary-300 rounded-xl text-xs font-black uppercase tracking-wider transition-colors border border-slate-200/60 dark:border-secondary-800 gap-2 w-full sm:w-auto"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Regresar al Listado
                </Link>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6 items-start">
            <!-- PANEL PRINCIPAL: TABLA DE INSUMOS -->
            <div class="w-full lg:w-[70%] space-y-4">
                <div class="bg-white dark:bg-secondary-800 rounded-2xl border border-zinc-200/60 dark:border-secondary-700 shadow-sm overflow-hidden transition-all duration-300">
                    <div class="px-5 py-4 border-b border-zinc-100 dark:border-secondary-700">
                        <h3 class="text-xs font-black text-zinc-800 dark:text-secondary-100 uppercase tracking-widest">
                            Insumos Solicitados
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-secondary-700">
                            <thead class="bg-zinc-50 dark:bg-secondary-900/50">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 text-left text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Código/Nombre</th>
                                    <th scope="col" class="px-6 py-3.5 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Solicitado</th>
                                    <th scope="col" class="px-6 py-3.5 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Despachado</th>
                                    <th v-if="request.status === 'despachado' || request.status === 'despachado_parcial' || request.status === 'entregado'" scope="col" class="px-6 py-3.5 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Recibido</th>
                                    <th scope="col" class="px-6 py-3.5 class text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Stock Físico</th>
                                    <th scope="col" class="px-6 py-3.5 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Faltante</th>
                                    <th scope="col" class="px-6 py-3.5 text-right text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50 bg-white dark:bg-secondary-800">
                                <tr 
                                    v-for="item in request.details" 
                                    :key="item.id"
                                    class="hover:bg-zinc-50/50 dark:hover:bg-secondary-700/10 transition-colors"
                                  >
                                    <td class="px-6 py-4">
                                        <p class="text-xs font-black text-zinc-800 dark:text-secondary-100 uppercase tracking-tight">{{ item.product_name }}</p>
                                        <p class="text-[9px] text-zinc-400 dark:text-secondary-500 font-bold uppercase tracking-wider mt-0.5">{{ item.product_code }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="text-xs font-bold text-zinc-700 dark:text-secondary-300">
                                            {{ item.quantity_requested }} {{ item.unit_of_measure }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="text-xs font-bold" :class="item.quantity_delivered > 0 ? 'text-emerald-600 dark:text-emerald-400 font-black' : 'text-zinc-400 dark:text-secondary-600'">
                                            {{ item.quantity_delivered }} {{ item.unit_of_measure }}
                                        </span>
                                    </td>
                                    <td v-if="request.status === 'despachado' || request.status === 'despachado_parcial' || request.status === 'entregado'" class="px-6 py-4 text-center">
                                        <div v-if="request.status === 'despachado' || request.status === 'despachado_parcial'">
                                            <div class="flex flex-col items-center gap-1" v-if="item.quantity_delivered > 0">
                                                <div class="flex items-center justify-center gap-1">
                                                    <input 
                                                        type="number" 
                                                        step="0.01" 
                                                        min="0" 
                                                        v-model.number="receivedQuantities[item.id]" 
                                                        class="w-20 text-right rounded-lg border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-bold p-1 focus:ring-0 focus:border-indigo-500 text-zinc-800 dark:text-secondary-100"
                                                    />
                                                    <span class="text-[10px] font-black text-zinc-400 dark:text-secondary-500">{{ item.unit_of_measure }}</span>
                                                </div>
                                                <!-- Entrada de Observación Obligatoria por Discrepancia -->
                                                <div v-if="Math.abs((receivedQuantities[item.id] ?? 0) - item.quantity_requested) >= 0.01" class="mt-1.5 w-full max-w-[180px]">
                                                    <input 
                                                        type="text" 
                                                        placeholder="Motivo de diferencia..." 
                                                        v-model="observations[item.id]" 
                                                        class="w-full text-center rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-950/20 text-[10px] font-bold p-1 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-amber-800 dark:text-amber-200"
                                                    />
                                                </div>
                                            </div>
                                            <span v-else class="text-zinc-400 dark:text-secondary-600">—</span>
                                        </div>
                                        <span v-else class="flex flex-col items-center justify-center gap-1">
                                            <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">
                                                {{ item.quantity_received !== null ? item.quantity_received.toFixed(2) : '—' }} {{ item.unit_of_measure }}
                                            </span>
                                            <!-- Renderizado de la observación en modo lectura -->
                                            <span v-if="item.observation" class="text-[10px] text-orange-600 dark:text-orange-400 font-bold italic block text-center max-w-[180px] whitespace-normal leading-tight mt-1 bg-orange-500/5 p-1.5 rounded-xl border border-orange-500/10">
                                                Obs: {{ item.observation }}
                                            </span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="text-xs font-semibold" :class="item.stock_available > 0 ? 'text-zinc-700 dark:text-secondary-300' : 'text-rose-500 dark:text-rose-400 font-bold'">
                                            {{ item.stock_available }} {{ item.unit_of_measure }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="text-xs font-bold" :class="(item.quantity_requested - item.quantity_delivered) > item.stock_available ? 'text-rose-600 dark:text-rose-400 font-black' : 'text-zinc-400 dark:text-secondary-600'">
                                            {{ Math.max(0, (item.quantity_requested - item.quantity_delivered) - item.stock_available) }} {{ item.unit_of_measure }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span 
                                            v-if="item.quantity_delivered >= item.quantity_requested"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-[11px] font-black uppercase tracking-widest bg-emerald-600 text-white shadow-lg shadow-emerald-500/25 dark:bg-emerald-500 dark:shadow-emerald-950/40"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4.12-5.671Z" clip-rule="evenodd" />
                                            </svg>
                                            ENTREGADO
                                        </span>
                                        <span 
                                            v-else-if="item.stock_available >= (item.quantity_requested - item.quantity_delivered)"
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/10 dark:bg-emerald-500/10 dark:text-emerald-400"
                                        >
                                            DISPONIBLE
                                        </span>
                                        <span 
                                            v-else-if="item.stock_available > 0"
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 ring-1 ring-amber-600/10 dark:bg-amber-500/10 dark:text-amber-400"
                                        >
                                            STOCK PARCIAL
                                        </span>
                                        <span 
                                            v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400"
                                        >
                                            SIN STOCK
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ALERTA DE APROBACIÓN PENDIENTE -->
                <div 
                    v-if="request.status === 'pendiente'"
                    class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-start gap-3 transition-colors duration-300"
                >
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-amber-800 dark:text-amber-400 uppercase tracking-wide">Aprobación Pendiente</h4>
                        <p class="text-[11px] text-amber-700/90 dark:text-amber-500/90 mt-1 uppercase font-bold tracking-tight leading-relaxed">
                            Esta solicitud de consumo interno debe ser aprobada por el administrador antes de poder realizar el despacho físico de los insumos.
                        </p>
                    </div>
                </div>

                <!-- ALERTA DE SOLICITUD OBSERVADA -->
                <div 
                    v-if="request.status === 'observado'"
                    class="p-4 bg-orange-500/10 border border-orange-500/20 rounded-2xl flex items-start gap-3 transition-colors duration-300"
                >
                    <div class="w-8 h-8 rounded-lg bg-orange-500/20 flex items-center justify-center text-orange-600 dark:text-orange-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-orange-800 dark:text-orange-400 uppercase tracking-wide">Solicitud Observada por el Administrador</h4>
                        <p class="text-[11px] text-orange-700/90 dark:text-orange-500/90 mt-1 uppercase font-bold tracking-tight leading-relaxed">
                            Esta solicitud ha sido observada y se encuentra en pausa. Por favor revise los comentarios detallados en el panel lateral.
                        </p>
                    </div>
                </div>

                <!-- ALERTA DE STOCK INSUFICIENTE / PARCIAL -->
                <div 
                    v-if="!isFullyStocked && (request.status === 'pendiente' || request.status === 'aprobado' || request.status === 'observado' || request.status === 'parcial')"
                    class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-start gap-3 transition-colors duration-300"
                >
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-amber-800 dark:text-amber-400 uppercase tracking-wide">Insumos Faltantes Detectados</h4>
                        <p class="text-[11px] text-amber-700/90 dark:text-amber-500/90 mt-1 uppercase font-bold tracking-tight leading-relaxed">
                            No hay stock físico suficiente para despachar la totalidad de esta solicitud. Debe solicitar la compra de los insumos faltantes. El despacho estará bloqueado hasta que se reponga el inventario.
                        </p>
                    </div>
                </div>
            </div>

            <!-- PANEL LATERAL: INFORMACIÓN GENERAL -->
            <div class="w-full lg:w-[30%] space-y-4">
                <div class="bg-white dark:bg-secondary-800 rounded-2xl border border-zinc-200/60 dark:border-secondary-700 shadow-sm p-5 space-y-4 transition-all duration-300">
                    <h3 class="text-xs font-black text-zinc-800 dark:text-secondary-100 uppercase tracking-widest border-b border-zinc-100 dark:border-secondary-700 pb-3">
                        Ficha Operativa
                    </h3>
                    
                    <div class="space-y-3.5">
                        <div>
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Área Solicitante</span>
                            <span class="text-xs font-black text-zinc-800 dark:text-secondary-100 uppercase tracking-tight mt-0.5 block">
                                {{ request.requested_by }}
                            </span>
                        </div>
                        
                        <div>
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Almacén Origen</span>
                            <span class="text-xs font-bold text-zinc-700 dark:text-secondary-300 uppercase tracking-tight mt-0.5 block">
                                {{ request.warehouse.name }}
                            </span>
                        </div>
                        
                        <div>
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Fecha de Registro</span>
                            <span class="text-xs font-semibold text-zinc-700 dark:text-secondary-300 uppercase tracking-tight mt-0.5 block">
                                {{ request.date_formatted }}
                            </span>
                        </div>
                        
                        <div>
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Registrado Por</span>
                            <span class="text-xs font-semibold text-zinc-700 dark:text-secondary-300 uppercase tracking-tight mt-0.5 block">
                                {{ request.user.name }}
                            </span>
                        </div>

                        <!-- Aprobación -->
                        <div v-if="request.approved_by_user">
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Aprobado Por</span>
                            <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-tight mt-0.5 block">
                                {{ request.approved_by_user.name }}
                            </span>
                        </div>

                        <div v-if="request.approved_by_user && request.approved_at_formatted">
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Fecha de Aprobación</span>
                            <span class="text-xs font-semibold text-zinc-700 dark:text-secondary-300 uppercase tracking-tight mt-0.5 block">
                                {{ request.approved_at_formatted }}
                            </span>
                        </div>

                        <!-- Observación -->
                        <div v-if="request.observed_by_user">
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Observado Por</span>
                            <span class="text-xs font-black text-orange-600 dark:text-orange-400 uppercase tracking-tight mt-0.5 block">
                                {{ request.observed_by_user.name }}
                            </span>
                        </div>

                        <div v-if="request.observed_by_user && request.observed_at_formatted">
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Fecha de Observación</span>
                            <span class="text-xs font-semibold text-zinc-700 dark:text-secondary-300 uppercase tracking-tight mt-0.5 block">
                                {{ request.observed_at_formatted }}
                            </span>
                        </div>

                        <div v-if="request.observation_notes">
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Comentarios de Observación</span>
                            <span class="text-xs font-semibold text-orange-700 dark:text-orange-300 uppercase tracking-tight mt-1 block bg-orange-500/5 p-2.5 rounded-xl border border-orange-500/10 whitespace-pre-line leading-relaxed">
                                {{ request.observation_notes }}
                            </span>
                        </div>

                        <div v-if="request.status !== 'pendiente' && request.status !== 'observado' && request.status !== 'cancelado'">
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Despachado Por</span>
                            <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-tight mt-0.5 block">
                                {{ request.dispatched_by_user ? request.dispatched_by_user.name : 'N/A' }}
                            </span>
                        </div>

                        <div v-if="request.status !== 'pendiente' && request.status !== 'observado' && request.status !== 'cancelado'">
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Fecha de Despacho</span>
                            <span class="text-xs font-semibold text-zinc-700 dark:text-secondary-300 uppercase tracking-tight mt-0.5 block">
                                {{ request.dispatched_at_formatted || 'N/A' }}
                            </span>
                        </div>
                        
                        <div v-if="request.status === 'entregado'">
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Recepcionado Por</span>
                            <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-tight mt-0.5 block">
                                {{ request.received_by_user ? request.received_by_user.name : 'N/A' }}
                            </span>
                        </div>
                        
                        <div v-if="request.status === 'entregado'">
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Fecha de Recepción</span>
                            <span class="text-xs font-semibold text-zinc-700 dark:text-secondary-300 uppercase tracking-tight mt-0.5 block">
                                {{ request.received_at_formatted || 'N/A' }}
                            </span>
                        </div>
                        
                        <div v-if="request.notes">
                            <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Notas / Observaciones</span>
                            <span class="text-xs font-semibold text-zinc-600 dark:text-secondary-400 uppercase tracking-tight mt-1 block bg-slate-50 dark:bg-secondary-900 p-2.5 rounded-xl border border-slate-100 dark:border-secondary-950 whitespace-pre-line leading-relaxed">
                                {{ request.notes }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ACCIONES PANEL -->
                <div 
                    v-if="request.status === 'pendiente' || request.status === 'aprobado' || request.status === 'observado' || request.status === 'parcial' || request.status === 'despachado' || request.status === 'despachado_parcial'"
                    class="bg-white dark:bg-secondary-800 rounded-2xl border border-zinc-200/60 dark:border-secondary-700 shadow-sm p-5 space-y-4 transition-all duration-300"
                >
                    <!-- ACCIONES DE ADMINISTRADOR -->
                    <div v-if="isAdmin && (request.status === 'pendiente' || request.status === 'observado')" class="space-y-3 pb-4 border-b border-zinc-100 dark:border-secondary-700">
                        <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1">Acciones de Administración</span>
                        
                        <!-- Aprobar Solicitud -->
                        <button 
                            @click="handleApprove"
                            class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors shadow-md hover:shadow-emerald-500/10 flex items-center justify-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Aprobar Solicitud
                        </button>
                    </div>

                    <h3 class="text-xs font-black text-zinc-800 dark:text-secondary-100 uppercase tracking-widest border-b border-zinc-100 dark:border-secondary-700 pb-3 mb-2">
                        Acciones de Almacén
                    </h3>

                    <!-- Recepcionar stock (Área solicitante) -->
                    <button 
                        v-if="request.status === 'despachado' || request.status === 'despachado_parcial'"
                        @click="handleReceive"
                        class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors shadow-md hover:shadow-indigo-500/10 flex items-center justify-center gap-2 mb-3"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" />
                        </svg>
                        Confirmar Recepción
                    </button>

                    <!-- Comprar faltantes (Solo si falta stock y está aprobada) -->
                    <button 
                        v-if="(request.status === 'aprobado' || request.status === 'despachado_parcial') && !isFullyStocked"
                        @click="handleGeneratePurchaseOrder"
                        class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors shadow-md flex items-center justify-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        Solicitar Compra de Faltantes
                    </button>

                    <!-- Despachar stock (Solo si está completo y aprobado) -->
                    <button 
                        v-if="(request.status === 'aprobado' || request.status === 'despachado_parcial') && isFullyStocked && hasAnyStockToDispatch"
                        @click="handleDispatch"
                        class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors shadow-md hover:shadow-emerald-500/10 flex items-center justify-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Despachar Stock
                    </button>

                    <!-- Alerta visual de aprobación requerida si no está aprobada -->
                    <div 
                        v-if="request.status === 'pendiente' || request.status === 'observado'"
                        class="text-center p-3 bg-zinc-50 dark:bg-secondary-900 border border-zinc-200/50 dark:border-secondary-950 rounded-xl"
                    >
                        <span class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest">Despacho Bloqueado</span>
                        <span class="text-[10px] font-black text-amber-600 dark:text-amber-500 uppercase tracking-wider mt-1 block leading-tight">
                            Requiere aprobación del administrador para operar
                        </span>
                    </div>

                    <!-- Cancelar solicitud -->
                    <button 
                        v-if="request.status === 'pendiente' || request.status === 'aprobado' || request.status === 'observado'"
                        @click="handleCancel"
                        class="w-full py-3 px-4 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-black uppercase tracking-wider transition-colors flex items-center justify-center gap-2 border border-rose-100"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Cancelar Solicitud
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
