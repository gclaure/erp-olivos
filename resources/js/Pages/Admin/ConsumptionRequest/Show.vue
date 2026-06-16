<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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

const page = usePage();
const isWarehouseRole = computed(() => {
    const roles = page.props.auth.user?.roles || [];
    return roles.includes('Almacén') || roles.includes('almacen');
});
const isConsumidorRole = computed(() => {
    const roles = page.props.auth.user?.roles || [];
    return roles.includes('Consumidor') || roles.includes('consumidor');
});
const canUserDispatch = computed(() => {
    const user = page.props.auth.user;
    if (!user) return false;
    const roles = user.roles || [];
    const hasRole = roles.includes('Almacén') || roles.includes('almacen') || 
                    roles.includes('Admin') || roles.includes('admin') || 
                    roles.includes('Administrador') || roles.includes('administrador') ||
                    !!user.is_super_admin;
    const hasStatus = ['pendiente', 'aprobado', 'despachado_parcial'].includes(request.value.status);
    return hasRole && hasStatus;
});

const request = ref(props.consumptionRequest.data);

watch(() => props.consumptionRequest.data, (newVal) => {
    request.value = newVal;
}, { deep: true });

const getDetailedMessage = (requestObj) => {
    const userName = requestObj.received_by_user?.name || 'Usuario';
    const number = requestObj.formatted_number || 'S/N';
    const requestedBy = requestObj.requested_by;

    const discrepancies = [];
    if (requestObj.details) {
        requestObj.details.forEach(detail => {
            const received = parseFloat(detail.quantity_received !== null ? detail.quantity_received : detail.quantity_delivered || 0);
            const requested = parseFloat(detail.quantity_requested || 0);
            const unitName = detail.unit_of_measure || '';

            const diff = received - requested;
            if (Math.abs(diff) >= 0.01) {
                const productName = detail.product_name || 'Producto';
                if (diff < 0) {
                    discrepancies.push(`${productName} (Faltó: ${Math.abs(diff).toFixed(2)} ${unitName})`);
                } else {
                    discrepancies.push(`${productName} (Entregado de más: ${Math.abs(diff).toFixed(2)} ${unitName})`);
                }
            }
        });
    }

    let msg = `El usuario ${userName} ha confirmado la recepción de la solicitud de consumo #${number} por el área de ${requestedBy}.`;
    if (discrepancies.length > 0) {
        msg += ` Con discrepancias: ${discrepancies.join(', ')}.`;
    } else {
        msg += ` Todo fue recibido conforme.`;
    }
    return msg;
};


const activeBranchId = computed(() => page.props.activeBranch?.id);
let currentSubscription = null;

const subscribeToBranch = (branchId) => {
    if (currentSubscription) {
        window.Echo.leave(`sucursal.${currentSubscription}`);
        currentSubscription = null;
    }

    if (window.Echo && branchId) {
        currentSubscription = branchId;
        window.Echo.private(`sucursal.${branchId}`)
            .listen('.consumption-request.updated', (e) => {
                if (e.request.id === request.value.id) {
                    request.value = e.request;
                    
                    if (isWarehouseRole.value && e.request.status === 'entregado') {
                        const detailedMsg = getDetailedMessage(e.request);
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: `Recepción confirmada #${e.request.formatted_number}`,
                            text: detailedMsg,
                            showConfirmButton: false,
                            timer: 6000,
                            timerProgressBar: true
                        });
                    }
                }
            });
    }
};

watch(activeBranchId, (newBranchId) => {
    subscribeToBranch(newBranchId);
}, { immediate: true });

onUnmounted(() => {
    if (window.Echo && currentSubscription) {
        window.Echo.leave(`sucursal.${currentSubscription}`);
    }
});

const receivedQuantities = ref({});
const dispatchQuantities = ref({});
const observations = ref({});
const dispatchObservations = ref({});

const initQuantities = () => {
    if (request.value && request.value.details) {
        request.value.details.forEach(item => {
            if (item.quantity_delivered > 0) {
                receivedQuantities.value[item.id] = parseFloat(item.quantity_delivered.toFixed(2));
            }
            observations.value[item.id] = item.observation || '';
            dispatchObservations.value[item.id] = item.observation || '';
            
            // Inicializar cantidades a despachar
            const pending = item.quantity_requested - item.quantity_delivered;
            if (pending > 0) {
                dispatchQuantities.value[item.id] = Math.max(0, Math.min(pending, item.stock_available));
            } else {
                dispatchQuantities.value[item.id] = 0;
            }
        });
    }
};

onMounted(() => {
    initQuantities();
});

watch(() => request.value, () => {
    initQuantities();
}, { deep: true });

const getRemainingStock = (item) => {
    if (!canUserDispatch.value) return item.stock_available;
    const dispatchVal = parseFloat(dispatchQuantities.value[item.id]) || 0;
    return Math.max(0, parseFloat((item.stock_available - dispatchVal).toFixed(2)));
};

// Dictado por Voz (Speech Recognition) para Motivos de Despacho
const activeListeningId = ref(null);
let speechRecognitionObj = null;
let silenceTimer = null;
let baseText = '';
let currentVoiceField = 'dispatch'; // 'dispatch' o 'diff'

const resetSilenceTimer = () => {
    if (silenceTimer) {
        clearTimeout(silenceTimer);
    }
    silenceTimer = setTimeout(() => {
        if (speechRecognitionObj && activeListeningId.value) {
            speechRecognitionObj.stop();
        }
    }, 4000); // 4 segundos de inactividad
};

const initSpeechRecognition = () => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        return null;
    }
    const rec = new SpeechRecognition();
    rec.continuous = true;
    rec.interimResults = true;
    rec.lang = 'es-ES';
    rec.maxAlternatives = 1;

    rec.onstart = () => {
        resetSilenceTimer();
    };

    rec.onerror = (e) => {
        console.error('Speech recognition error', e);
        activeListeningId.value = null;
        if (silenceTimer) clearTimeout(silenceTimer);
        
        if (e.error !== 'no-speech') {
            Swal.fire({
                icon: 'error',
                title: 'Error de Dictado',
                text: 'No se pudo acceder al micrófono o no se detectó voz.',
                timer: 2000,
                showConfirmButton: false
            });
        }
    };

    rec.onend = () => {
        activeListeningId.value = null;
        if (silenceTimer) clearTimeout(silenceTimer);
    };

    rec.onresult = (event) => {
        resetSilenceTimer();
        
        let sessionTranscript = '';
        for (let i = 0; i < event.results.length; ++i) {
            sessionTranscript += event.results[i][0].transcript;
        }

        const space = baseText ? ' ' : '';
        if (activeListeningId.value) {
            if (currentVoiceField === 'diff') {
                observations.value[activeListeningId.value] = (baseText + space + sessionTranscript).toUpperCase();
            } else {
                dispatchObservations.value[activeListeningId.value] = (baseText + space + sessionTranscript).toUpperCase();
            }
        }
    };

    return rec;
};

const toggleSpeechRecognition = (itemId, field = 'dispatch') => {
    if (!speechRecognitionObj) {
        speechRecognitionObj = initSpeechRecognition();
    }

    if (!speechRecognitionObj) {
        Swal.fire({
            icon: 'warning',
            title: 'No Compatible',
            text: 'El dictado por voz no es soportado por este navegador. Pruebe usando Chrome o Safari.',
            timer: 3000,
            showConfirmButton: false
        });
        return;
    }

    if (activeListeningId.value === itemId && currentVoiceField === field) {
        speechRecognitionObj.stop();
    } else {
        if (activeListeningId.value) {
            speechRecognitionObj.stop();
        }
        activeListeningId.value = itemId;
        currentVoiceField = field;
        if (field === 'diff') {
            baseText = observations.value[itemId] || '';
        } else {
            baseText = dispatchObservations.value[itemId] || '';
        }
        speechRecognitionObj.start();
    }
};

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
    // Validar cantidades recolectadas de la tabla/vista móvil
    const payload = {};
    let hasError = false;
    let errorMsg = '';
    let totalToDispatch = 0;
    
    request.value.details.forEach((item) => {
        const pending = item.quantity_requested - item.quantity_delivered;
        if (pending > 0) {
            const rawVal = dispatchQuantities.value[item.id];
            if (rawVal === undefined || isNaN(rawVal) || rawVal < 0) {
                hasError = true;
                errorMsg = `La cantidad a despachar para ${item.product_name} no es válida y no puede ser negativa.`;
            } else if (rawVal > pending) {
                hasError = true;
                errorMsg = `La cantidad a despachar para ${item.product_name} (${rawVal}) no puede superar la cantidad pendiente (${pending.toFixed(2)}).`;
            } else if (rawVal > item.stock_available) {
                hasError = true;
                errorMsg = `La cantidad a despachar para ${item.product_name} (${rawVal}) no puede superar el stock disponible (${item.stock_available.toFixed(2)}).`;
            } else {
                const finalQty = parseFloat(rawVal.toFixed(2));
                payload[item.id] = finalQty;
                totalToDispatch += finalQty;
            }
        }
    });

    if (hasError) {
        Swal.fire({
            icon: 'warning',
            title: 'Validación de Despacho',
            text: errorMsg
        });
        return;
    }

    if (totalToDispatch === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Validación de Despacho',
            text: 'Debe ingresar al menos una cantidad mayor a 0 para despachar.'
        });
        return;
    }

    Swal.fire({
        title: '¿Confirmar Despacho?',
        text: 'Se registrará la salida física del inventario para las cantidades especificadas.',
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
            router.post(route('admin.consumption-requests.dispatch', { consumption_request: request.value.id }), {
                quantities: payload
            }, {
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
        text: 'Al confirmar, declararás que el área solicitante recibió físicamente las cantidades especificadas directamente en la tabla y se cerrará este ciclo de solicitud.',
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
        text: 'Se preparará una solicitud de compra con las cantidades faltantes exactas para cubrir esta solicitud.',
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
        text: 'Esta acción no se puede deshacer y la solicitud se marcará como cancelada.',
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
                        text: page.props.flash?.success || 'La solicitud fue anulada.',
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

// Visualizador de Imagen / Galería Lightbox
const showImagePreview = ref(false);
const previewImages = ref([]);
const previewName = ref('');
const currentImageIndex = ref(0);

const openGallery = (images, name) => {
    if (!images || images.length === 0) return;
    previewImages.value = Array.isArray(images) ? images : [images];
    previewName.value = name;
    currentImageIndex.value = 0;
    showImagePreview.value = true;
};

const nextImage = () => {
    currentImageIndex.value = (currentImageIndex.value + 1) % previewImages.value.length;
};

const prevImage = () => {
    currentImageIndex.value = (currentImageIndex.value - 1 + previewImages.value.length) % previewImages.value.length;
};
</script>

<template>
    <Head :title="`Solicitud de ${request.requested_by}`" />

    <div class="space-y-6 w-full py-2">
        <!-- HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-zinc-900 dark:text-secondary-50 tracking-tight uppercase">
                        Solicitud #{{ request.formatted_number || 'S/N' }}
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
                    Imprimir Solicitud
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
                    
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-secondary-700">
                            <thead class="bg-zinc-50 dark:bg-secondary-900/50">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 text-left text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Producto / Especificaciones</th>
                                    <th scope="col" class="px-6 py-3.5 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Almacén</th>
                                    <th scope="col" class="px-6 py-3.5 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Solicitado</th>
                                    <th v-if="request.status === 'despachado' || request.status === 'despachado_parcial' || request.status === 'entregado'" scope="col" class="px-6 py-3.5 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Recibido</th>
                                    <th v-if="canUserDispatch" scope="col" class="px-6 py-3.5 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Despachando</th>
                                    <th scope="col" class="px-6 py-3.5 class text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Stock Físico</th>
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
                                        <div class="flex items-start gap-3">
                                            <!-- Imagen del Producto -->
                                            <div 
                                                class="w-14 h-14 rounded-xl bg-zinc-100 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700/60 overflow-hidden flex-shrink-0 flex items-center justify-center shadow-sm"
                                                :class="{ 'cursor-pointer hover:border-indigo-500/50 transition-colors': item.product_image_url || (item.product_images && item.product_images.length > 0) }"
                                                @click="openGallery(item.product_images || item.product_image_url, item.product_name)"
                                            >
                                                <img 
                                                    v-if="item.product_image_url" 
                                                    :src="item.product_image_url" 
                                                    :alt="item.product_name"
                                                    class="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                                                />
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-zinc-400 dark:text-secondary-600">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                </svg>
                                            </div>
                                            <!-- Información del Producto -->
                                            <div class="space-y-1 text-left flex-1 min-w-0">
                                                <div>
                                                    <p class="text-xs font-black text-zinc-800 dark:text-secondary-100 uppercase tracking-tight leading-tight">
                                                        {{ item.product_name }}
                                                    </p>
                                                    <p class="text-[9px] text-zinc-400 dark:text-secondary-500 font-bold uppercase tracking-wider mt-0.5">
                                                        Cód: {{ item.product_code }}
                                                    </p>
                                                </div>
                                                <p v-if="item.product_description" class="text-[10px] text-zinc-500 dark:text-secondary-400 italic leading-snug">
                                                    {{ item.product_description }}
                                                </p>
                                                <div class="flex flex-col gap-1.5 pt-2 text-[9px] max-w-md">
                                                    <!-- Fila 1: Datos Logísticos (Ubicación) -->
                                                    <div class="flex flex-wrap items-center gap-1.5">
                                                        <!-- UBICACIÓN (SI O SI DESTACADA EN VERDE) -->
                                                        <span class="px-2 py-0.5 rounded-md font-black tracking-wider uppercase bg-emerald-600 text-white flex items-center gap-0.5 shadow-sm shadow-emerald-500/10">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                                                <path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9.5a7 7 0 1 0-14 0c0 2.993 1.698 5.488 3.363 7.087.829.799 1.655 1.381 2.274 1.765.31.193.57.337.757.433.111.057.2.099.281.14l.018.008.006.003ZM10 12a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" clip-rule="evenodd" />
                                                            </svg>
                                                            Ubicación: {{ item.product_location || 'No Asignada' }}
                                                        </span>
                                                    </div>
 
                                                    <!-- Fila 2: Datos Comerciales y Formato (Marca, Categorías y Empaque) -->
                                                    <div class="flex flex-wrap items-center gap-1.5">
                                                        <!-- MARCA -->
                                                        <span v-if="item.product_brand" class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 border border-indigo-100/30 dark:border-indigo-900/20">
                                                            Marca: {{ item.product_brand }}
                                                        </span>
 
                                                        <!-- CATEGORÍAS -->
                                                        <span v-for="cat in item.product_categories" :key="cat" class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-zinc-100 dark:bg-secondary-900 text-zinc-500 dark:text-secondary-450 border border-zinc-200 dark:border-secondary-800">
                                                            Cat: {{ cat }}
                                                        </span>
 
                                                        <!-- FORMATO DE EMPAQUE -->
                                                        <span class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-amber-50/50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border border-amber-100/30 dark:border-amber-900/20">
                                                            {{ item.product_package_name && item.product_units_per_package > 1 
                                                                ? `Empaque: ${item.product_package_name} (contiene ${item.product_units_per_package} ${item.unit_of_measure})` 
                                                                : `Formato: Individual (Despacho por ${item.unit_of_measure})` }}
                                                        </span>
 
                                                        <!-- CONTROL DE VENCIMIENTO -->
                                                        <span v-if="item.product_has_expiration" class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-455 border border-rose-100/30 dark:border-rose-900/20 flex items-center gap-0.5">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-rose-500">
                                                                <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                                                            </svg>
                                                            Expirable
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 border border-blue-100/30 dark:border-blue-900/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 text-blue-500">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h1.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-4.5 1.636m0 0V21m-4.5-12h3.25M11.25 3v1.5m0 0h5.25m-5.25 0v1.5m5.25-1.5v1.5m-5.25 0h5.25" />
                                            </svg>
                                            {{ request.warehouse_name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="text-xs font-bold text-zinc-700 dark:text-secondary-300">
                                            {{ item.quantity_requested }} {{ item.unit_of_measure }}
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
                                                <div v-if="Math.abs((receivedQuantities[item.id] ?? 0) - item.quantity_requested) >= 0.01" class="mt-1.5 w-full max-w-[210px] flex items-center gap-1">
                                                    <textarea 
                                                        rows="2"
                                                        placeholder="Motivo de diferencia..." 
                                                        v-model="observations[item.id]" 
                                                        class="flex-1 text-center rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-950/20 text-[10px] font-bold p-1.5 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-amber-850 dark:text-amber-200 placeholder-amber-400 resize-y min-h-[40px]"
                                                    ></textarea>
                                                    <button 
                                                        type="button"
                                                        @click="toggleSpeechRecognition(item.id, 'diff')"
                                                        class="w-7 h-7 flex-shrink-0 rounded-lg flex items-center justify-center transition-all duration-300 shadow-sm"
                                                        :class="activeListeningId === item.id && currentVoiceField === 'diff' ? 'bg-rose-500 text-white animate-pulse' : 'bg-amber-100 hover:bg-amber-200 text-amber-850 dark:bg-amber-950/40 dark:hover:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800'"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                                                        </svg>
                                                    </button>
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
                                    <td v-if="canUserDispatch" class="px-6 py-4 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="flex items-center justify-center gap-1">
                                                <input 
                                                    type="number" 
                                                    step="0.01" 
                                                    min="0" 
                                                    v-model.number="dispatchQuantities[item.id]" 
                                                    class="w-20 text-right rounded-lg border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-bold p-1 focus:ring-0 focus:border-indigo-500 text-zinc-800 dark:text-secondary-100"
                                                />
                                                <span class="text-[10px] font-black text-zinc-400 dark:text-secondary-500">{{ item.unit_of_measure }}</span>
                                            </div>
                                            <!-- Entrada de Observación Obligatoria si supera el stock o lo pendiente -->
                                            <div v-if="(dispatchQuantities[item.id] ?? 0) > (item.quantity_requested - item.quantity_delivered) || (dispatchQuantities[item.id] ?? 0) > item.stock_available" class="mt-1.5 w-full max-w-[210px] flex items-center gap-1">
                                                <textarea 
                                                    rows="2"
                                                    placeholder="Motivo de sobre-despacho..." 
                                                    v-model="dispatchObservations[item.id]" 
                                                    class="flex-1 text-center rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-950/20 text-[10px] font-bold p-1.5 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-amber-850 dark:text-amber-200 placeholder-amber-400 resize-y min-h-[40px]"
                                                ></textarea>
                                                <button 
                                                    type="button"
                                                    @click="toggleSpeechRecognition(item.id)"
                                                    class="w-7 h-7 flex-shrink-0 rounded-lg flex items-center justify-center transition-all duration-300 shadow-sm"
                                                    :class="activeListeningId === item.id && currentVoiceField === 'dispatch' ? 'bg-rose-500 text-white animate-pulse' : 'bg-amber-100 hover:bg-amber-200 text-amber-850 dark:bg-amber-950/40 dark:hover:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800'"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="text-xs font-semibold" :class="getRemainingStock(item) > 0 ? 'text-zinc-700 dark:text-secondary-300' : 'text-rose-500 dark:text-rose-400 font-bold'">
                                            {{ getRemainingStock(item) }} {{ item.unit_of_measure }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span 
                                            v-if="item.quantity_delivered >= item.quantity_requested && request.status === 'entregado'"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-[11px] font-black uppercase tracking-widest bg-emerald-600 text-white shadow-lg shadow-emerald-500/25 dark:bg-emerald-500 dark:shadow-emerald-950/40"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4.12-5.671Z" clip-rule="evenodd" />
                                            </svg>
                                            ENTREGADO
                                        </span>
                                        <span 
                                            v-else-if="item.quantity_delivered >= item.quantity_requested"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-[11px] font-black uppercase tracking-widest bg-fuchsia-600 text-white shadow-lg shadow-fuchsia-500/25 dark:bg-fuchsia-500 dark:shadow-fuchsia-950/40"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124l-.321-5.128a1.125 1.125 0 0 0-1.122-1.053H14.5a1.5 1.5 0 0 0-1.5 1.5v4.5m10.5-3H12" />
                                            </svg>
                                            DESPACHADO
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

                    <!-- Vista Móvil en Tarjetas -->
                    <div class="md:hidden divide-y divide-zinc-100 dark:divide-secondary-700/50">
                        <div 
                            v-for="item in request.details" 
                            :key="'mob-' + item.id"
                            class="p-4 space-y-4 bg-white dark:bg-secondary-800"
                        >
                            <!-- Fila Superior: Imagen, Nombre y Código -->
                            <div class="flex items-start gap-3">
                                <div 
                                    class="w-14 h-14 rounded-xl bg-zinc-100 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700/60 overflow-hidden flex-shrink-0 flex items-center justify-center shadow-sm"
                                    :class="{ 'cursor-pointer hover:border-indigo-500/50 transition-colors': item.product_image_url || (item.product_images && item.product_images.length > 0) }"
                                    @click="openGallery(item.product_images || item.product_image_url, item.product_name)"
                                >
                                    <img 
                                        v-if="item.product_image_url" 
                                        :src="item.product_image_url" 
                                        :alt="item.product_name"
                                        class="w-full h-full object-cover"
                                    />
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-zinc-400 dark:text-secondary-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[9px] text-zinc-400 dark:text-secondary-500 font-bold uppercase tracking-wider">
                                            Cód: {{ item.product_code }}
                                        </span>
                                        <!-- Badge de Estado del Item -->
                                        <div>
                                            <span 
                                                v-if="item.quantity_delivered >= item.quantity_requested && request.status === 'entregado'"
                                                class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-widest bg-emerald-600 text-white"
                                            >
                                                ENTREGADO
                                            </span>
                                            <span 
                                                v-else-if="item.quantity_delivered >= item.quantity_requested"
                                                class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-widest bg-fuchsia-600 text-white"
                                            >
                                                DESPACHADO
                                            </span>
                                            <span 
                                                v-else-if="item.stock_available >= (item.quantity_requested - item.quantity_delivered)"
                                                class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400"
                                            >
                                                DISPONIBLE
                                            </span>
                                            <span 
                                                v-else-if="item.stock_available > 0"
                                                class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400"
                                            >
                                                STOCK PARCIAL
                                            </span>
                                            <span 
                                                v-else
                                                class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400"
                                            >
                                                SIN STOCK
                                            </span>
                                        </div>
                                    </div>
                                    <h4 class="text-xs font-black text-zinc-800 dark:text-secondary-100 uppercase tracking-tight leading-tight mt-0.5">
                                        {{ item.product_name }}
                                    </h4>
                                    <p v-if="item.product_description" class="text-[9px] text-zinc-500 dark:text-secondary-400 italic leading-snug mt-0.5">
                                        {{ item.product_description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Fila de Atributos Verticales/Horizontales Compactos -->
                            <div class="flex flex-col gap-1.5 text-[9px]">
                                <!-- Fila 1: Datos Logísticos (Ubicación y Almacén) -->
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-md font-black tracking-wider uppercase bg-emerald-600 text-white flex items-center gap-0.5 shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                            <path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9.5a7 7 0 1 0-14 0c0 2.993 1.698 5.488 3.363 7.087.829.799 1.655 1.381 2.274 1.765.31.193.57.337.757.433.111.057.2.099.281.14l.018.008.006.003ZM10 12a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" clip-rule="evenodd" />
                                        </svg>
                                        Ubicación: {{ item.product_location || 'No Asignada' }}
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 border border-blue-100/30 dark:border-blue-900/20 flex items-center gap-0.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-blue-500">
                                            <path d="M4 3a2 2 0 1 0 0 4h12a2 2 0 1 0 0-4H4Z" />
                                            <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8Zm5 3a1 1 0 0 1 1-1h2a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                                        </svg>
                                        Almacén: {{ request.warehouse_name }}
                                    </span>
                                </div>

                                <!-- Fila 2: Datos Comerciales y Formato (Marca, Categorías y Empaque) -->
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span v-if="item.product_brand" class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 border border-indigo-100/30 dark:border-indigo-900/20">
                                        Marca: {{ item.product_brand }}
                                    </span>
                                    <span v-for="cat in item.product_categories" :key="'mob-cat-'+cat" class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-zinc-100 dark:bg-secondary-900 text-zinc-500 dark:text-secondary-450 border border-zinc-200 dark:border-secondary-800">
                                        Cat: {{ cat }}
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-amber-50/50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border border-amber-100/30 dark:border-amber-900/20">
                                        {{ item.product_package_name && item.product_units_per_package > 1 
                                            ? `Empaque: ${item.product_package_name} (contiene ${item.product_units_per_package} ${item.unit_of_measure})` 
                                            : `Formato: Individual (Despacho por ${item.unit_of_measure})` }}
                                    </span>
                                    <span v-if="item.product_has_expiration" class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-455 border border-rose-100/30 dark:border-rose-900/20 flex items-center gap-0.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-rose-500">
                                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                                        </svg>
                                        Expirable
                                    </span>
                                </div>
                            </div>

                            <!-- Fila de Detalles, Cantidades y Stocks -->
                            <div class="grid grid-cols-3 gap-2 pt-2 border-t border-zinc-100 dark:border-secondary-700/50 text-center">
                                <div class="flex flex-col">
                                    <span class="text-[9px] text-zinc-400 dark:text-secondary-500 font-bold uppercase tracking-wider">Solicitado</span>
                                    <span class="text-xs font-black text-zinc-700 dark:text-secondary-300 mt-0.5">
                                        {{ item.quantity_requested }} {{ item.unit_of_measure }}
                                    </span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[9px] text-zinc-400 dark:text-secondary-500 font-bold uppercase tracking-wider">Stock Físico</span>
                                    <span class="text-xs font-black mt-0.5" :class="getRemainingStock(item) > 0 ? 'text-zinc-700 dark:text-secondary-300' : 'text-rose-500 dark:text-rose-400 font-bold'">
                                        {{ getRemainingStock(item) }} {{ item.unit_of_measure }}
                                    </span>
                                </div>
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-[9px] text-zinc-400 dark:text-secondary-500 font-bold uppercase tracking-wider mb-0.5">Despachado</span>
                                    <span 
                                        v-if="item.quantity_delivered > 0"
                                        class="text-xs font-black text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ item.quantity_delivered }} {{ item.unit_of_measure }}
                                    </span>
                                    <span v-else class="text-zinc-400 dark:text-secondary-600">—</span>
                                </div>
                            </div>

                            <!-- Sección de Despacho (Si el usuario puede despachar) -->
                            <div 
                                v-if="canUserDispatch"
                                class="pt-3 border-t border-dashed border-zinc-100 dark:border-secondary-700/50 bg-zinc-50/50 dark:bg-secondary-900/10 p-2.5 rounded-xl"
                            >
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wide">Cantidad a Despachar:</span>
                                        <div class="flex items-center gap-1.5">
                                            <input 
                                                type="number" 
                                                step="0.01" 
                                                min="0" 
                                                v-model.number="dispatchQuantities[item.id]" 
                                                class="w-24 text-right rounded-lg border border-zinc-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 text-xs font-black p-1.5 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-zinc-800 dark:text-secondary-100"
                                            />
                                            <span class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase">{{ item.unit_of_measure }}</span>
                                        </div>
                                    </div>
                                    <!-- Entrada de Observación Obligatoria si supera el stock o lo pendiente -->
                                    <div v-if="(dispatchQuantities[item.id] ?? 0) > (item.quantity_requested - item.quantity_delivered) || (dispatchQuantities[item.id] ?? 0) > item.stock_available" class="w-full mt-1.5 flex items-center gap-1.5">
                                        <textarea 
                                            rows="2"
                                            placeholder="Describa el motivo del sobre-despacho..." 
                                            v-model="dispatchObservations[item.id]" 
                                            class="flex-1 text-left rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-950/20 text-[10px] font-bold p-2 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-amber-850 dark:text-amber-200 placeholder-amber-450 resize-y min-h-[44px]"
                                        ></textarea>
                                        <button 
                                            type="button"
                                            @click="toggleSpeechRecognition(item.id)"
                                            class="w-9 h-9 flex-shrink-0 rounded-lg flex items-center justify-center transition-all duration-300 shadow-sm"
                                            :class="activeListeningId === item.id && currentVoiceField === 'dispatch' ? 'bg-rose-500 text-white animate-pulse' : 'bg-amber-100 hover:bg-amber-200 text-amber-850 dark:bg-amber-950/40 dark:hover:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800'"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Recepción (Si se requiere interactividad en móviles) -->
                            <div 
                                v-if="(request.status === 'despachado' || request.status === 'despachado_parcial' || request.status === 'entregado')"
                                class="pt-3 border-t border-dashed border-zinc-100 dark:border-secondary-700/50 bg-zinc-50/50 dark:bg-secondary-900/10 p-2.5 rounded-xl"
                            >
                                <div v-if="request.status === 'despachado' || request.status === 'despachado_parcial'">
                                    <div class="flex flex-col gap-2" v-if="item.quantity_delivered > 0">
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wide">Cantidad Recibida:</span>
                                            <div class="flex items-center gap-1.5">
                                                <input 
                                                    type="number" 
                                                    step="0.01" 
                                                    min="0" 
                                                    v-model.number="receivedQuantities[item.id]" 
                                                    class="w-24 text-right rounded-lg border border-zinc-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 text-xs font-black p-1.5 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-zinc-800 dark:text-secondary-100"
                                                />
                                                <span class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase">{{ item.unit_of_measure }}</span>
                                            </div>
                                        </div>
                                        <!-- Entrada de Observación Obligatoria por Discrepancia -->
                                        <div v-if="Math.abs((receivedQuantities[item.id] ?? 0) - item.quantity_requested) >= 0.01" class="w-full flex items-center gap-1">
                                            <textarea 
                                                rows="2"
                                                placeholder="Describa el motivo de la discrepancia..." 
                                                v-model="observations[item.id]" 
                                                class="flex-1 text-left rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-950/20 text-[10px] font-bold p-2 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-amber-850 dark:text-amber-200 placeholder-amber-450 resize-y min-h-[44px]"
                                            ></textarea>
                                            <button 
                                                type="button"
                                                @click="toggleSpeechRecognition(item.id, 'diff')"
                                                class="w-8 h-8 flex-shrink-0 rounded-lg flex items-center justify-center transition-all duration-300 shadow-sm"
                                                :class="activeListeningId === item.id && currentVoiceField === 'diff' ? 'bg-rose-500 text-white animate-pulse' : 'bg-amber-100 hover:bg-amber-200 text-amber-850 dark:bg-amber-950/40 dark:hover:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800'"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="flex flex-col gap-1 items-center">
                                    <div class="flex items-center gap-1">
                                        <span class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-wider">Recibido:</span>
                                        <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">
                                            {{ item.quantity_received !== null ? item.quantity_received.toFixed(2) : '—' }} {{ item.unit_of_measure }}
                                        </span>
                                    </div>
                                    <!-- Renderizado de la observación en modo lectura -->
                                    <span v-if="item.observation" class="text-[9px] text-orange-600 dark:text-orange-400 font-bold italic block text-left w-full whitespace-normal leading-tight mt-1 bg-orange-500/5 p-2 rounded-lg border border-orange-500/10">
                                        Obs: {{ item.observation }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AVISO PARA ALMACENERO: PREPARAR PEDIDO -->
                <div 
                    v-if="request.status === 'pendiente' && isWarehouseRole"
                    class="p-4 bg-blue-500/10 border border-blue-500/20 rounded-2xl flex items-start gap-3 transition-colors duration-300"
                >
                    <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-blue-800 dark:text-blue-400 uppercase tracking-wide">Solicitud Lista para Preparar</h4>
                        <p class="text-[11px] text-blue-700/90 dark:text-blue-500/90 mt-1 uppercase font-bold tracking-tight leading-relaxed">
                            Esta solicitud está pendiente de despacho. Prepare los insumos y presione el botón <strong>Despachar</strong> cuando estén listos.
                        </p>
                    </div>
                </div>

                <!-- AVISO PARA CONSUMIDOR: ESPERANDO DESPACHO -->
                <div 
                    v-if="request.status === 'pendiente' && isConsumidorRole"
                    class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-start gap-3 transition-colors duration-300"
                >
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-amber-800 dark:text-amber-400 uppercase tracking-wide">En Espera de Despacho</h4>
                        <p class="text-[11px] text-amber-700/90 dark:text-amber-500/90 mt-1 uppercase font-bold tracking-tight leading-relaxed">
                            Su solicitud fue registrada exitosamente. El almacenero está preparando los insumos para el despacho.
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
                                {{ request.user.name }} ({{ request.requested_by }})
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

                    <!-- Recepcionar stock (Solo rol Consumidor) -->
                    <button 
                        v-if="isConsumidorRole && (request.status === 'despachado' || request.status === 'despachado_parcial')"
                        @click="handleReceive"
                        class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors shadow-md hover:shadow-indigo-500/10 flex items-center justify-center gap-2 mb-3"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" />
                        </svg>
                        Confirmar Recepción
                    </button>

                    <!-- Comprar faltantes (Solo si falta stock, pendiente o aprobado) -->
                    <button 
                        v-if="!isConsumidorRole && (request.status === 'pendiente' || request.status === 'aprobado' || request.status === 'despachado_parcial') && !isFullyStocked"
                        @click="handleGeneratePurchaseOrder"
                        class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors shadow-md flex items-center justify-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        Solicitar Compra de Faltantes
                    </button>

                    <!-- Despachar stock (desde pendiente, aprobado o parcial) -->
                    <button 
                        v-if="canUserDispatch && hasAnyStockToDispatch"
                        @click="handleDispatch"
                        class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors shadow-md hover:shadow-emerald-500/10 flex items-center justify-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Despachar Stock
                    </button>

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

        <!-- Image Gallery Lightbox -->
        <div v-if="showImagePreview" 
             class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-zinc-950/85 backdrop-blur-xl"
             @keydown.escape="showImagePreview = false">
            
            <div class="absolute top-0 left-0 w-full p-4 sm:p-8 flex justify-between items-start z-[10000]">
                <div class="px-5 py-2.5 sm:px-6 sm:py-3 bg-white/10 backdrop-blur-xl border border-white/20 rounded-full shadow-2xl flex items-center gap-3 transform transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-indigo-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25" />
                    </svg>
                    <h3 class="text-sm sm:text-base font-semibold tracking-wide text-white max-w-[150px] sm:max-w-md truncate">{{ previewName }}</h3>
                    <span v-if="previewImages.length > 1" class="flex items-center justify-center bg-white/20 text-white text-[10px] sm:text-xs px-2.5 py-1 rounded-full font-mono font-bold border border-white/10 shadow-inner">
                        {{ currentImageIndex + 1 }} / {{ previewImages.length }}
                    </span>
                </div>
                <button @click="showImagePreview = false" class="group flex items-center justify-center gap-2 px-5 py-2.5 sm:px-6 sm:py-3 bg-rose-600/90 hover:bg-rose-600 text-white backdrop-blur-xl rounded-full shadow-2xl transition-all duration-300 z-[10000] border border-white/20">
                    <span class="text-xs sm:text-sm font-bold tracking-wider uppercase">Cerrar</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <button v-if="previewImages.length > 1" @click.stop="prevImage" class="absolute left-4 sm:left-6 top-1/2 -translate-y-1/2 w-12 h-12 sm:w-16 sm:h-16 bg-white/5 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-xl transition-all duration-300 border border-white/10 z-[10000] shadow-2xl">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
            <button v-if="previewImages.length > 1" @click.stop="nextImage" class="absolute right-4 sm:right-6 top-1/2 -translate-y-1/2 w-12 h-12 sm:w-16 sm:h-16 bg-white/5 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-xl transition-all duration-300 border border-white/10 z-[10000] shadow-2xl">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
            
            <div class="flex-1 w-full max-w-7xl flex items-center justify-center p-4 sm:p-24 relative overflow-hidden" @click="showImagePreview = false">
                <div v-for="(img, index) in previewImages" :key="index"
                     v-show="currentImageIndex === index" 
                     class="absolute inset-0 flex items-center justify-center p-4 sm:p-12 transition-opacity duration-500">
                    <img :src="img" :alt="previewName" class="max-w-full max-h-[85vh] sm:max-h-[75vh] object-contain drop-shadow-2xl rounded-2xl ring-1 ring-white/10">
                </div>
            </div>
            
            <div v-if="previewImages.length > 1" class="absolute bottom-6 sm:bottom-10 left-1/2 -translate-x-1/2 z-[10000] max-w-full px-4">
                <div class="flex items-center justify-start sm:justify-center gap-2 sm:gap-4 p-2 sm:p-3 bg-zinc-900/60 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-2xl overflow-x-auto scrollbar-hide">
                    <button v-for="(img, index) in previewImages" :key="'thumb-'+index"
                             @click.stop="currentImageIndex = index" 
                             class="relative flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 rounded-lg overflow-hidden transition-all duration-300" 
                             :class="currentImageIndex === index ? 'ring-2 ring-indigo-400 ring-offset-2 ring-offset-zinc-950 scale-110' : 'opacity-40 hover:opacity-100 border border-white/20'">
                        <img :src="img" class="w-full h-full object-cover">
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
