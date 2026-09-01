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
const isAdmin = computed(() => !!props.isAdmin);

const request = ref(props.consumptionRequest?.data || props.consumptionRequest || {});

watch(() => props.consumptionRequest, (newVal) => {
    request.value = newVal?.data || newVal || {};
}, { deep: true });

const isWarehouseRole = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    return roles.includes('Almacén') || roles.includes('almacen');
});

const isConsumidorRole = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    return roles.includes('Consumidor') || roles.includes('consumidor');
});

const canUserDispatch = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    const roles = user.roles || [];
    const hasRole = roles.includes('Almacén') || roles.includes('almacen');
    const hasStatus = ['aprobado', 'despachado_parcial'].includes(request.value?.status);
    return hasRole && hasStatus;
});

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
                    const previousStatus = request.value.status;
                    request.value = e.request;
                    const actionUserId = e.request.approved_by_user_id || e.request.dispatched_by_user_id || e.request.received_by_user_id || e.request.cancelled_by_user_id || e.request.observed_by_user_id;
                    const currentUserId = String(page.props.auth.user?.id);
                    const isOwnAction = actionUserId && String(actionUserId) === currentUserId;

                    if (!isOwnAction && e.action) {
                        const number = e.request.formatted_number || 'S/N';

                        if (e.action === 'approved' && (isAdmin.value || isConsumidorRole.value)) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: `Solicitud Aprobada #${number}`,
                                text: 'La solicitud fue aprobada por el administrador.',
                                showConfirmButton: false,
                                timer: 5000,
                                timerProgressBar: true
                            });
                        }

                        if (e.action === 'dispatched' && isConsumidorRole.value) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: `Solicitud Despachada #${number}`,
                                text: 'El almacén despachó los insumos. Ya puede confirmar la recepción.',
                                showConfirmButton: false,
                                timer: 5000,
                                timerProgressBar: true
                            });
                        }

                        if (e.action === 'received' && isAdmin.value) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: `Recepción Confirmada #${number}`,
                                text: `El consumidor ${e.request.received_by_user?.name || ''} confirmó la recepción.`,
                                showConfirmButton: false,
                                timer: 6000,
                                timerProgressBar: true
                            });
                        }

                        if (e.action === 'received' && isWarehouseRole.value) {
                            const detailedMsg = getDetailedMessage(e.request);
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: `Recepción Confirmada #${number}`,
                                text: detailedMsg,
                                showConfirmButton: false,
                                timer: 6000,
                                timerProgressBar: true
                            });
                        }

                        if (e.action === 'observed' && isConsumidorRole.value) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'info',
                                title: `Solicitud Observada #${number}`,
                                text: 'El administrador observó esta solicitud. Revise los comentarios.',
                                showConfirmButton: false,
                                timer: 5000,
                                timerProgressBar: true
                            });
                        }

                        if (e.action === 'cancelled' && (isWarehouseRole.value || isConsumidorRole.value)) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: `Solicitud Cancelada #${number}`,
                                text: 'El administrador canceló esta solicitud.',
                                showConfirmButton: false,
                                timer: 6000,
                                timerProgressBar: true
                            });
                        }

                        if (e.action === 'item_updated' && (isWarehouseRole.value || isConsumidorRole.value)) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'info',
                                title: `Cantidad Modificada #${number}`,
                                text: 'El administrador actualizó la cantidad solicitada de los insumos.',
                                showConfirmButton: false,
                                timer: 6000,
                                timerProgressBar: true
                            });
                        }
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
    return (request.value?.details || []).reduce((acc, item) => {
        const pending = item.quantity_requested - item.quantity_delivered;
        const missing = pending > item.stock_available ? pending - item.stock_available : 0;
        return acc + missing;
    }, 0);
});

const totalDeliverableQuantity = computed(() => {
    return (request.value?.details || []).reduce((acc, item) => {
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

// Stepper del Ciclo de Vida Operativo
const lifecycleSteps = computed(() => {
    const s = request.value?.status;
    const isCancelled = s === 'cancelado';
    const isObserved = s === 'observado';
    const isApproved = !!request.value?.approved_by_user || ['aprobado', 'despachado', 'despachado_parcial', 'entregado'].includes(s);
    const isDispatched = !!request.value?.dispatched_by_user || ['despachado', 'despachado_parcial', 'entregado'].includes(s);
    const isDelivered = s === 'entregado';

    return [
        {
            key: 'created',
            step: '01',
            title: 'Solicitud Creada',
            actor: request.value?.user?.name || 'Solicitante',
            time: request.value?.date_formatted ? `${request.value.date_formatted}${request.value.created_at_time ? ' ' + request.value.created_at_time : ''}` : null,
            status: 'completed',
            badge: 'Registrada',
            badgeClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/30'
        },
        {
            key: 'approval',
            step: '02',
            title: isCancelled ? 'Solicitud Cancelada' : (isObserved ? 'Solicitud Observada' : 'Aprobación'),
            actor: isCancelled ? (request.value?.cancelled_by_user?.name || 'Usuario') : (isObserved ? (request.value?.observed_by_user?.name || 'Administrador') : (request.value?.approved_by_user?.name || 'Administrador')),
            time: isCancelled ? request.value?.cancelled_at_formatted : (isObserved ? request.value?.observed_at_formatted : (request.value?.approved_at_formatted || 'En espera de aprobación')),
            status: isCancelled ? 'danger' : (isObserved ? 'warning' : (isApproved ? 'completed' : 'current')),
            badge: isCancelled ? 'Cancelado' : (isObserved ? 'Observado' : (isApproved ? 'Aprobado' : 'En Espera')),
            badgeClass: isCancelled 
                ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-200/50 dark:border-rose-800/30' 
                : (isObserved 
                    ? 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400 border border-orange-200/50 dark:border-orange-800/30' 
                    : (isApproved 
                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/30' 
                        : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/30'))
        },
        {
            key: 'dispatch',
            step: '03',
            title: (s === 'despachado_parcial' || s === 'parcial') ? 'Despacho Parcial' : 'Despacho de Almacén',
            actor: request.value?.dispatched_by_user?.name || (isApproved ? (isWarehouseRole.value ? 'Tu área (Almacén)' : 'Almacén Central') : 'En espera'),
            time: request.value?.dispatched_at_formatted || (isApproved ? 'Listo para preparar' : 'Pendiente'),
            status: isDispatched ? ((s === 'despachado_parcial' || s === 'parcial') ? 'warning' : 'completed') : (isApproved ? 'current' : 'pending'),
            badge: isDispatched ? ((s === 'despachado_parcial' || s === 'parcial') ? 'Parcial' : 'Despachado') : (isApproved ? 'En Preparación' : 'Pendiente'),
            badgeClass: isDispatched 
                ? ((s === 'despachado_parcial' || s === 'parcial') 
                    ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-800/30' 
                    : 'bg-fuchsia-50 text-fuchsia-700 dark:bg-fuchsia-500/10 dark:text-fuchsia-400 border border-fuchsia-200/50 dark:border-fuchsia-800/30') 
                : (isApproved ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-200/50 animate-pulse' : 'bg-zinc-100 text-zinc-600 dark:bg-secondary-800 dark:text-secondary-400 border border-zinc-200 dark:border-secondary-700')
        },
        {
            key: 'reception',
            step: '04',
            title: 'Recepción en Destino',
            actor: request.value?.received_by_user?.name || (isDispatched ? request.value?.requested_by : 'En espera'),
            time: request.value?.received_at_formatted || (isDispatched ? 'Pendiente de entrega' : 'Pendiente'),
            status: isDelivered ? 'completed' : (isDispatched ? 'current' : 'pending'),
            badge: isDelivered ? 'Entregado' : (isDispatched ? 'Por Recibir' : 'Pendiente'),
            badgeClass: isDelivered 
                ? 'bg-emerald-600 text-white font-black shadow-sm' 
                : (isDispatched 
                    ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border border-amber-200/50 animate-pulse' 
                    : 'bg-zinc-100 text-zinc-600 dark:bg-secondary-800 dark:text-secondary-400 border border-zinc-200 dark:border-secondary-700')
        }
    ];
});

// Acción Principal Móvil
const primaryMobileAction = computed(() => {
    const s = request.value?.status;
    if (isAdmin.value && (s === 'pendiente' || s === 'observado')) {
        return { type: 'approve', label: 'Aprobar Solicitud', icon: 'check', class: 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/30' };
    }
    if (canUserDispatch.value && hasAnyStockToDispatch.value) {
        return { type: 'dispatch', label: 'Despachar Stock', icon: 'local_shipping', class: 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/30' };
    }
    if (isConsumidorRole.value && (s === 'despachado' || s === 'despachado_parcial')) {
        return { type: 'receive', label: 'Confirmar Recepción', icon: 'inventory_2', class: 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-600/30' };
    }
    if (!isConsumidorRole.value && (s === 'pendiente' || s === 'aprobado' || s === 'despachado_parcial') && !isFullyStocked.value) {
        return { type: 'purchase', label: 'Comprar Faltantes', icon: 'shopping_cart', class: 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-600/30' };
    }
    if (isAdmin.value && s === 'aprobado') {
        return { type: 'cancel', label: 'Cancelar Solicitud', icon: 'close', class: 'bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400' };
    }
    return null;
});

const executeMobileAction = (type) => {
    switch (type) {
        case 'approve': handleApprove(); break;
        case 'dispatch': handleDispatch(); break;
        case 'receive': handleReceive(); break;
        case 'purchase': handleGeneratePurchaseOrder(); break;
        case 'cancel': handleCancel(); break;
    }
};

// Acciones
const handleDispatch = () => {
    const payload = {};
    const obsPayload = {};
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
                const obs = (dispatchObservations.value[item.id] || '').trim().slice(0, 500);
                if (obs.length > 0) {
                    obsPayload[item.id] = obs;
                }
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
        html: `
            <p class="text-sm text-zinc-600 dark:text-secondary-300 text-left mb-4" style="font-family: inherit;">Se registrará la salida física del inventario para las cantidades especificadas.</p>
            <div class="text-left">
                <label class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1">Observación general (opcional)</label>
                <textarea id="swal-dispatch-obs" rows="3" maxlength="500" placeholder="Nota sobre el despacho..." class="w-full rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-zinc-700 dark:text-secondary-300 placeholder-zinc-400 dark:placeholder-secondary-500 resize-y" style="min-height: 60px;"></textarea>
                <div class="text-right mt-1"><span id="swal-dispatch-obs-counter" class="text-[8px] font-bold text-zinc-300 dark:text-secondary-600">0/500</span></div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Despachar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-3xl',
            title: 'text-zinc-900 dark:text-white font-black',
            htmlContainer: 'text-zinc-600 dark:text-secondary-300',
            confirmButton: 'bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl mr-2 text-xs uppercase',
            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-4 rounded-xl text-xs uppercase dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-950'
        },
        buttonsStyling: false,
        didOpen: () => {
            const ta = document.getElementById('swal-dispatch-obs');
            const counter = document.getElementById('swal-dispatch-obs-counter');
            if (ta && counter) {
                ta.addEventListener('input', () => {
                    counter.textContent = `${ta.value.length}/500`;
                    counter.classList.toggle('text-amber-500', ta.value.length > 450);
                });
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const ta = document.getElementById('swal-dispatch-obs');
            const dispatchObs = (ta ? ta.value : '').trim().slice(0, 500);

            router.post(route('admin.consumption-requests.dispatch', { consumption_request: request.value.id }), {
                quantities: payload,
                observations: obsPayload,
                dispatch_observation: dispatchObs
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
        html: `
            <p class="text-sm text-zinc-600 dark:text-secondary-300 text-left mb-4" style="font-family: inherit;">Al confirmar, declararás que el área solicitante recibió físicamente las cantidades especificadas y se cerrará este ciclo de solicitud.</p>
            <div class="text-left">
                <label class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1">Observación (opcional)</label>
                <textarea id="swal-receive-obs" rows="3" maxlength="500" placeholder="Nota sobre la recepción..." class="w-full rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-zinc-700 dark:text-secondary-300 placeholder-zinc-400 dark:placeholder-secondary-500 resize-y" style="min-height: 60px;"></textarea>
                <div class="text-right mt-1"><span id="swal-receive-obs-counter" class="text-[8px] font-bold text-zinc-300 dark:text-secondary-600">0/500</span></div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Recepcionar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-3xl',
            title: 'text-zinc-900 dark:text-white font-black',
            confirmButton: 'bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl mr-2 text-xs uppercase',
            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-4 rounded-xl text-xs uppercase dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-950'
        },
        buttonsStyling: false,
        didOpen: () => {
            const ta = document.getElementById('swal-receive-obs');
            const counter = document.getElementById('swal-receive-obs-counter');
            if (ta && counter) {
                ta.addEventListener('input', () => {
                    counter.textContent = `${ta.value.length}/500`;
                    counter.classList.toggle('text-amber-500', ta.value.length > 450);
                });
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const ta = document.getElementById('swal-receive-obs');
            const receiveObs = (ta ? ta.value : '').trim().slice(0, 500);
            const receiveObsPayload = {};
            if (receiveObs.length > 0) {
                Object.keys(payload).forEach((detailId) => {
                    receiveObsPayload[detailId] = receiveObs;
                });
            }

            router.post(route('admin.consumption-requests.receive', { consumption_request: request.value.id }), {
                received_quantities: payload,
                observations: obsPayload,
                receive_observations: receiveObsPayload
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
            popup: 'bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-3xl',
            title: 'text-zinc-900 dark:text-white font-black',
            htmlContainer: 'text-zinc-600 dark:text-secondary-300',
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
        html: `
            <p class="text-sm text-zinc-600 dark:text-secondary-300 text-left mb-4" style="font-family: inherit;">Esta acción no se puede deshacer y la solicitud se marcará como cancelada.</p>
            <div class="text-left">
                <label class="block text-[9px] font-black text-rose-500 dark:text-rose-400 uppercase tracking-widest mb-1.5">Motivo de Cancelación * (Mínimo 5 caracteres)</label>
                <textarea id="swal-cancel-obs" rows="3" maxlength="500" placeholder="Escriba el motivo de la cancelación aquí..." class="w-full rounded-2xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold p-3.5 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-zinc-700 dark:text-secondary-300 placeholder-zinc-400 dark:placeholder-secondary-500 resize-y" style="min-height: 80px;"></textarea>
                <div class="text-right mt-1"><span id="swal-cancel-obs-counter" class="text-[8px] font-bold text-zinc-300 dark:text-secondary-600">0/500</span></div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, Cancelar',
        cancelButtonText: 'Volver',
        customClass: {
            popup: 'bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-3xl',
            title: 'text-zinc-900 dark:text-white font-black',
            confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-4 rounded-xl mr-2 text-xs uppercase',
            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-4 rounded-xl text-xs uppercase dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-950'
        },
        buttonsStyling: false,
        preConfirm: () => {
            const ta = document.getElementById('swal-cancel-obs');
            const val = (ta ? ta.value : '').trim();
            if (!val || val.length < 5) {
                Swal.showValidationMessage('El motivo de la cancelación es obligatorio (mínimo 5 caracteres).');
                return false;
            }
            return val;
        },
        didOpen: () => {
            const ta = document.getElementById('swal-cancel-obs');
            const counter = document.getElementById('swal-cancel-obs-counter');
            if (ta && counter) {
                ta.addEventListener('input', () => {
                    counter.textContent = `${ta.value.length}/500`;
                    counter.classList.toggle('text-amber-500', ta.value.length > 450);
                });
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.consumption-requests.cancel', { consumption_request: request.value.id }), {
                cancellation_notes: result.value
            }, {
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

const handleEditQuantity = (item) => {
    Swal.fire({
        title: 'Modificar Cantidad Solicitada',
        html: `
            <div class="text-left space-y-3" style="font-family: inherit;">
                <p class="text-xs text-zinc-600 dark:text-secondary-300 font-semibold">
                    Producto: <span class="font-black text-zinc-900 dark:text-white uppercase">${item.product_name}</span>
                </p>
                <div>
                    <label class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1">
                        Nueva Cantidad Solicitada (${item.unit_of_measure}) *
                    </label>
                    <input 
                        id="swal-edit-qty" 
                        type="number" 
                        step="0.01" 
                        min="0.01" 
                        value="${item.quantity_requested}" 
                        class="w-full rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-sm font-bold p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-zinc-800 dark:text-secondary-100"
                    />
                </div>
                <div>
                    <label class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1">
                        Motivo / Observación (Opcional)
                    </label>
                    <textarea 
                        id="swal-edit-notes" 
                        rows="2" 
                        maxlength="500" 
                        placeholder="Explique el motivo del cambio..." 
                        class="w-full rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-zinc-700 dark:text-secondary-300 placeholder-zinc-400 dark:placeholder-secondary-500 resize-y"
                    ></textarea>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Guardar Cambio',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-3xl',
            title: 'text-zinc-900 dark:text-white font-black',
            confirmButton: 'bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl mr-2 text-xs uppercase',
            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-4 rounded-xl text-xs uppercase dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-950'
        },
        buttonsStyling: false,
        preConfirm: () => {
            const inputQty = document.getElementById('swal-edit-qty');
            const inputNotes = document.getElementById('swal-edit-notes');
            const qtyVal = parseFloat(inputQty ? inputQty.value : '0');
            if (isNaN(qtyVal) || qtyVal <= 0) {
                Swal.showValidationMessage('La cantidad solicitada debe ser un número mayor a 0.');
                return false;
            }
            return {
                quantity_requested: qtyVal,
                modification_notes: (inputNotes ? inputNotes.value : '').trim()
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            router.put(route('admin.consumption-requests.update-detail-quantity', {
                consumption_request: request.value.id,
                detail: item.id
            }), result.value, {
                onSuccess: (page) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cantidad Actualizada',
                        text: page.props.flash?.success || 'La cantidad solicitada se modificó correctamente.',
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
        html: `
            <p class="text-sm text-zinc-600 dark:text-secondary-300 text-left mb-4" style="font-family: inherit;">Se aprobará administrativamente esta solicitud de consumo interno para que pueda ser despachada por el almacén.</p>
            <div class="text-left">
                <label class="block text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mb-1.5">Notas / Observaciones de Aprobación (Opcional)</label>
                <textarea id="swal-approve-obs" rows="3" maxlength="500" placeholder="Escriba aquí alguna nota u observación sobre la aprobación si es necesario..." class="w-full rounded-2xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold p-3.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-zinc-700 dark:text-secondary-300 placeholder-zinc-400 dark:placeholder-secondary-500 resize-y" style="min-height: 80px;"></textarea>
                <div class="text-right mt-1"><span id="swal-approve-obs-counter" class="text-[8px] font-bold text-zinc-300 dark:text-secondary-600">0/500</span></div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Aprobar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-3xl',
            title: 'text-zinc-900 dark:text-white font-black',
            confirmButton: 'bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl mr-2 text-xs uppercase',
            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-4 rounded-xl text-xs uppercase dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-950'
        },
        buttonsStyling: false,
        didOpen: () => {
            const ta = document.getElementById('swal-approve-obs');
            const counter = document.getElementById('swal-approve-obs-counter');
            if (ta && counter) {
                ta.addEventListener('input', () => {
                    counter.textContent = `${ta.value.length}/500`;
                    counter.classList.toggle('text-amber-500', ta.value.length > 450);
                });
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const ta = document.getElementById('swal-approve-obs');
            const approveObs = (ta ? ta.value : '').trim().slice(0, 500);

            router.post(route('admin.consumption-requests.approve', { consumption_request: request.value.id }), {
                observation_notes: approveObs
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
    <Head :title="`Solicitud #${request.formatted_number || 'S/N'} - ${request.requested_by}`" />

    <div class="space-y-6 w-full py-2 pb-24 lg:pb-6">
        <!-- BREADCRUMB & TOP ACTIONS -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-zinc-200/60 dark:border-secondary-800 pb-3">
            <nav class="flex items-center gap-2 text-[11px] font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-wider">
                <Link :href="route('admin.dashboard')" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Inicio</Link>
                <span>/</span>
                <Link :href="route('admin.consumption-requests.index')" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Solicitudes de Consumo</Link>
                <span>/</span>
                <span class="text-zinc-800 dark:text-secondary-200">#{{ request.formatted_number || 'S/N' }}</span>
            </nav>

            <div class="flex items-center gap-2">
                <a 
                    :href="route('admin.consumption-requests.print', { consumption_request: request.id })" 
                    target="_blank"
                    class="inline-flex items-center justify-center h-9 px-3.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all gap-1.5 shadow-sm shadow-indigo-500/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0a2.25 2.25 0 0 1-2.24 2.156H8.58A2.25 2.25 0 0 1 6.34 18m11.32 0h-11.32m11.32 0a2.25 2.25 0 0 0 2.25-2.25V9A2.25 2.25 0 0 0 15 6.75h-2.25V4.5a2.25 2.25 0 0 0-2.25-2.25h-3a2.25 2.25 0 0 0-2.25 2.25v2.25H3A2.25 2.25 0 0 0 .75 9v6.75a2.25 2.25 0 0 0 2.25 2.25h1.36m3.93 0h3.14m1.36 0h1.36M6.75 22.5h10.5" />
                    </svg>
                    <span>Imprimir</span>
                </a>
                
                <Link 
                    :href="route('admin.consumption-requests.index')" 
                    class="inline-flex items-center justify-center h-9 px-3.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-secondary-800 dark:hover:bg-secondary-700 text-zinc-700 dark:text-secondary-300 rounded-xl text-xs font-black uppercase tracking-wider transition-colors border border-zinc-200/80 dark:border-secondary-700 gap-1.5"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Listado</span>
                </Link>
            </div>
        </div>

        <!-- EXECUTIVE HERO HEADER -->
        <div class="bg-white dark:bg-secondary-800 rounded-3xl p-5 sm:p-6 border border-zinc-200/70 dark:border-secondary-700/80 shadow-sm transition-all duration-300 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-800/40 font-mono">
                            CONSUMO INTERNO
                        </span>
                        <span 
                            v-if="request.status === 'entregado'"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-xl text-xs font-black uppercase tracking-widest bg-emerald-600 text-white shadow-md shadow-emerald-600/20"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
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

                    <h1 class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-secondary-50 tracking-tight font-mono">
                        Solicitud #{{ request.formatted_number || 'S/N' }}
                    </h1>
                </div>

                <!-- METADATA CHIPS BAR -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <!-- Área Solicitante -->
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-50 dark:bg-secondary-900/60 border border-zinc-200/60 dark:border-secondary-700">
                        <span class="material-symbols-outlined text-[16px] text-indigo-500">meeting_room</span>
                        <span class="font-black text-zinc-800 dark:text-secondary-100 uppercase tracking-tight">{{ request.requested_by }}</span>
                    </div>

                    <!-- Almacén Origen -->
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-50 dark:bg-secondary-900/60 border border-zinc-200/60 dark:border-secondary-700">
                        <span class="material-symbols-outlined text-[16px] text-blue-500">store</span>
                        <span class="font-bold text-zinc-700 dark:text-secondary-200 uppercase tracking-tight">{{ request.warehouse_name }}</span>
                    </div>

                    <!-- Fecha -->
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-50 dark:bg-secondary-900/60 border border-zinc-200/60 dark:border-secondary-700">
                        <span class="material-symbols-outlined text-[16px] text-zinc-400">calendar_today</span>
                        <span class="font-semibold text-zinc-600 dark:text-secondary-300">{{ request.date_formatted }}</span>
                    </div>

                    <!-- Solicitante Usuario -->
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-50 dark:bg-secondary-900/60 border border-zinc-200/60 dark:border-secondary-700">
                        <span class="material-symbols-outlined text-[16px] text-zinc-400">person</span>
                        <span class="font-semibold text-zinc-600 dark:text-secondary-300">{{ request.user?.name }}</span>
                    </div>
                </div>
            </div>

            <!-- STEPPER DEL CICLO DE VIDA OPERATIVO -->
            <div class="pt-4 border-t border-zinc-100 dark:border-secondary-700/60">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-3">
                    <div 
                        v-for="st in lifecycleSteps" 
                        :key="st.key"
                        class="p-3 rounded-2xl border transition-all duration-300 flex flex-col justify-between gap-1.5 relative overflow-hidden"
                        :class="[
                            st.status === 'completed' ? 'bg-emerald-500/5 dark:bg-emerald-500/10 border-emerald-500/30 dark:border-emerald-500/20' : '',
                            st.status === 'current' ? 'bg-indigo-500/5 dark:bg-indigo-500/10 border-indigo-500/40 ring-1 ring-indigo-500/20 shadow-sm' : '',
                            st.status === 'warning' ? 'bg-orange-500/5 dark:bg-orange-500/10 border-orange-500/40' : '',
                            st.status === 'danger' ? 'bg-rose-500/5 dark:bg-rose-500/10 border-rose-500/40' : '',
                            st.status === 'pending' ? 'bg-zinc-50/50 dark:bg-secondary-900/20 border-zinc-200/60 dark:border-secondary-700/50 opacity-60' : ''
                        ]"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[9px] font-mono font-black text-zinc-400 dark:text-secondary-500 tracking-wider">PASO {{ st.step }}</span>
                            <span :class="['text-[8px] font-black uppercase px-2 py-0.5 rounded-md tracking-wider', st.badgeClass]">
                                {{ st.badge }}
                            </span>
                        </div>

                        <div>
                            <h4 class="text-xs font-black text-zinc-900 dark:text-secondary-100 uppercase tracking-tight leading-tight">
                                {{ st.title }}
                            </h4>
                            <p v-if="st.actor" class="text-[10px] font-bold text-zinc-500 dark:text-secondary-400 truncate mt-0.5">
                                {{ st.actor }}
                            </p>
                            <p v-if="st.time" class="text-[9px] text-zinc-400 dark:text-secondary-500 font-semibold mt-0.5">
                                {{ st.time }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT: 2-COLUMN LAYOUT -->
        <div class="flex flex-col lg:flex-row gap-6 items-start">
            <!-- COLUMNA IZQUIERDA: INSUMOS Y ALERTAS (70%) -->
            <div class="w-full lg:w-[68%] xl:w-[72%] space-y-4">
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
                        <p class="text-[11px] text-blue-700/90 dark:text-blue-500/90 mt-0.5 uppercase font-bold tracking-tight leading-relaxed">
                            Esta solicitud está pendiente de despacho. Prepare los insumos y presione el botón <strong>Despachar</strong> cuando estén listos.
                        </p>
                    </div>
                </div>

                <!-- AVISO PARA CONSUMIDOR: PENDIENTE DE APROBACIÓN -->
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
                        <h4 class="text-xs font-black text-amber-800 dark:text-amber-400 uppercase tracking-wide">Pendiente de Aprobación</h4>
                        <p class="text-[11px] text-amber-700/90 dark:text-amber-500/90 mt-0.5 uppercase font-bold tracking-tight leading-relaxed">
                            Su solicitud fue registrada exitosamente. Está a la espera de la aprobación del Administrador.
                        </p>
                    </div>
                </div>

                <!-- AVISO PARA CONSUMIDOR: EN ESPERA DE DESPACHO -->
                <div 
                    v-if="request.status === 'aprobado' && isConsumidorRole"
                    class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-start gap-3 transition-colors duration-300"
                >
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-amber-800 dark:text-amber-400 uppercase tracking-wide">En Espera de Despacho</h4>
                        <p class="text-[11px] text-amber-700/90 dark:text-amber-500/90 mt-0.5 uppercase font-bold tracking-tight leading-relaxed">
                            Su solicitud fue aprobada. El almacenero está preparando los insumos para el despacho.
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
                        <p class="text-[11px] text-orange-700/90 dark:text-orange-500/90 mt-0.5 uppercase font-bold tracking-tight leading-relaxed">
                            Esta solicitud ha sido observada y se encuentra en pausa. Por favor revise los comentarios detallados en la ficha lateral.
                        </p>
                    </div>
                </div>

                <!-- ALERTA DE STOCK INSUFICIENTE / PARCIAL -->
                <div 
                    v-if="!isConsumidorRole && !isFullyStocked && (request.status === 'pendiente' || request.status === 'aprobado' || request.status === 'observado' || request.status === 'parcial')"
                    class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-start gap-3 transition-colors duration-300"
                >
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-xs font-black text-amber-800 dark:text-amber-400 uppercase tracking-wide">Insumos Faltantes Detectados</h4>
                        <p class="text-[11px] text-amber-700/90 dark:text-amber-500/90 mt-0.5 uppercase font-bold tracking-tight leading-relaxed">
                            No hay stock físico suficiente para despachar la totalidad de esta solicitud. Puede generar la orden de compra de faltantes directamente.
                        </p>
                    </div>
                </div>

                <!-- CONTENEDOR DE INSUMOS SOLICITADOS -->
                <div class="bg-white dark:bg-secondary-800 rounded-3xl border border-zinc-200/70 dark:border-secondary-700/80 shadow-sm overflow-hidden transition-all duration-300">
                    <div class="px-5 py-4 border-b border-zinc-100 dark:border-secondary-700 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-black text-zinc-900 dark:text-secondary-100 uppercase tracking-widest">
                                Insumos Solicitados
                            </h3>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-black bg-zinc-100 dark:bg-secondary-700 text-zinc-600 dark:text-secondary-300">
                                {{ request.details?.length || 0 }} {{ request.details?.length === 1 ? 'Ítem' : 'Ítems' }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- TABLA DESKTOP / TABLET LANDSCAPE (>= 768px) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-secondary-700">
                            <thead class="bg-zinc-50/80 dark:bg-secondary-900/50">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-left text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Producto / Especificaciones</th>
                                    <th scope="col" class="px-4 py-3 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Almacén</th>
                                    <th scope="col" class="px-4 py-3 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Solicitado</th>
                                    <th v-if="request.status === 'despachado' || request.status === 'despachado_parcial' || request.status === 'entregado'" scope="col" class="px-4 py-3 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Recibido</th>
                                    <th v-if="canUserDispatch" scope="col" class="px-4 py-3 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">A Despachar</th>
                                    <th v-if="!isConsumidorRole" scope="col" class="px-4 py-3 text-center text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Stock Físico</th>
                                    <th scope="col" class="px-5 py-3 text-right text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50 bg-white dark:bg-secondary-800">
                                <tr 
                                    v-for="item in request.details" 
                                    :key="item.id"
                                    class="hover:bg-zinc-50/50 dark:hover:bg-secondary-700/10 transition-colors"
                                >
                                    <td class="px-5 py-4">
                                        <div class="flex items-start gap-3">
                                            <!-- Imagen del Producto -->
                                            <div 
                                                class="w-14 h-14 rounded-2xl bg-zinc-100 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700/60 overflow-hidden flex-shrink-0 flex items-center justify-center shadow-inner group relative"
                                                :class="{ 'cursor-pointer hover:border-indigo-500/50 transition-colors': item.product_image_url || (item.product_images && item.product_images.length > 0) }"
                                                @click="openGallery(item.product_images || item.product_image_url, item.product_name)"
                                            >
                                                <img 
                                                    v-if="item.product_image_url" 
                                                    :src="item.product_image_url" 
                                                    :alt="item.product_name"
                                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                                />
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-zinc-400 dark:text-secondary-600">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                </svg>
                                                <div v-if="item.product_image_url" class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-white text-[18px]">zoom_in</span>
                                                </div>
                                            </div>

                                            <!-- Información del Producto -->
                                            <div class="space-y-1 text-left flex-1 min-w-0">
                                                <div>
                                                    <p class="text-xs font-black text-zinc-900 dark:text-secondary-50 uppercase tracking-tight leading-tight">
                                                        {{ item.product_name }}
                                                    </p>
                                                    <p class="text-[9px] text-zinc-400 dark:text-secondary-500 font-mono font-bold uppercase tracking-wider mt-0.5">
                                                        CÓD: {{ item.product_code }}
                                                    </p>
                                                </div>
                                                <p v-if="item.product_description" class="text-[10px] text-zinc-500 dark:text-secondary-400 italic leading-snug">
                                                    {{ item.product_description }}
                                                </p>
                                                
                                                <!-- Fila de Atributos: Ubicación, Marca, Formato, Vencimiento -->
                                                <div class="flex flex-wrap items-center gap-1.5 pt-1.5 text-[9px]">
                                                    <!-- UBICACIÓN DESTACADA EN VERDE ESMERALDA -->
                                                    <span class="px-2 py-0.5 rounded-md font-black tracking-wider uppercase bg-emerald-600 text-white flex items-center gap-1 shadow-sm shadow-emerald-600/20">
                                                        <span class="material-symbols-outlined text-[13px]">location_on</span>
                                                        Ubicación: {{ item.product_location || 'No Asignada' }}
                                                    </span>

                                                    <!-- MARCA -->
                                                    <span v-if="item.product_brand" class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-800/30">
                                                        Marca: {{ item.product_brand }}
                                                    </span>

                                                    <!-- CATEGORÍAS -->
                                                    <span v-for="cat in item.product_categories" :key="cat" class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-zinc-100 dark:bg-secondary-900 text-zinc-600 dark:text-secondary-400 border border-zinc-200 dark:border-secondary-800">
                                                        {{ cat }}
                                                    </span>

                                                    <!-- FORMATO DE EMPAQUE -->
                                                    <span class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/30">
                                                        {{ item.product_package_name && item.product_units_per_package > 1 
                                                            ? `Empaque: ${item.product_package_name} (${item.product_units_per_package} ${item.unit_of_measure})` 
                                                            : `Unidad Individual (${item.unit_of_measure})` }}
                                                    </span>

                                                    <!-- CONTROL DE VENCIMIENTO -->
                                                    <span v-if="item.product_has_expiration" class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 border border-rose-200/50 dark:border-rose-800/30 flex items-center gap-0.5">
                                                        <span class="material-symbols-outlined text-[13px]">event_busy</span>
                                                        Expirable
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- ALMACÉN -->
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 border border-blue-100/40 dark:border-blue-900/30 font-mono">
                                            {{ request.warehouse_name }}
                                        </span>
                                    </td>

                                    <!-- CANTIDAD SOLICITADA -->
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <span class="text-xs font-black font-mono text-zinc-900 dark:text-secondary-100">
                                                {{ item.quantity_requested }}
                                            </span>
                                            <span class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase">{{ item.unit_of_measure }}</span>
                                            <button 
                                                v-if="isAdmin && (request.status === 'pendiente' || request.status === 'observado')"
                                                @click="handleEditQuantity(item)"
                                                type="button"
                                                class="p-1 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors ml-0.5"
                                                title="Editar cantidad solicitada"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">edit</span>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- RECEPCIÓN (SI CORRESPONDE) -->
                                    <td v-if="request.status === 'despachado' || request.status === 'despachado_parcial' || request.status === 'entregado'" class="px-4 py-4 text-center">
                                        <div v-if="isConsumidorRole && (request.status === 'despachado' || request.status === 'despachado_parcial')">
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
                                            <span class="text-xs font-black font-mono" :class="item.quantity_received !== null && item.quantity_received > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-400 dark:text-secondary-500'">
                                                {{ item.quantity_received !== null ? Number(item.quantity_received).toFixed(2) : '0.00' }} {{ item.unit_of_measure }}
                                            </span>
                                            <!-- Renderizado de la observación en modo lectura -->
                                            <div 
                                                v-if="item.receive_observation || item.observation" 
                                                :title="item.receive_observation || item.observation"
                                                class="text-[10px] text-amber-800 dark:text-amber-400 font-semibold italic block text-left max-w-[240px] whitespace-normal leading-relaxed mt-1 bg-amber-500/5 dark:bg-amber-500/10 p-2 rounded-xl border border-amber-200/60 dark:border-amber-800/40 shadow-sm"
                                            >
                                                <div class="font-black uppercase text-[8px] text-amber-600 dark:text-amber-500 tracking-wider mb-0.5">Observación:</div>
                                                <p class="line-clamp-3 hover:line-clamp-none cursor-pointer leading-snug">{{ item.receive_observation || item.observation }}</p>
                                            </div>
                                        </span>
                                    </td>

                                    <!-- A DESPACHAR (INMUTABLE / BLOQUEADO) -->
                                    <td v-if="canUserDispatch" class="px-4 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-black font-mono bg-zinc-100 dark:bg-secondary-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200/80 dark:border-secondary-700 shadow-inner">
                                                {{ Number(dispatchQuantities[item.id] || 0).toFixed(2) }}
                                            </span>
                                            <span class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase">{{ item.unit_of_measure }}</span>
                                        </div>
                                    </td>

                                    <!-- STOCK FÍSICO -->
                                    <td v-if="!isConsumidorRole" class="px-4 py-4 text-center whitespace-nowrap">
                                        <span class="text-xs font-black font-mono" :class="getRemainingStock(item) > 0 ? 'text-zinc-700 dark:text-secondary-300' : 'text-rose-500 dark:text-rose-400'">
                                            {{ getRemainingStock(item) }}
                                        </span>
                                        <span class="text-[10px] font-black text-zinc-400 dark:text-secondary-500 uppercase ml-1">{{ item.unit_of_measure }}</span>
                                    </td>

                                    <!-- BADGE DE ESTADO DEL ITEM -->
                                    <td class="px-5 py-4 whitespace-nowrap text-right">
                                        <span 
                                            v-if="item.quantity_delivered >= item.quantity_requested && request.status === 'entregado'"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest bg-emerald-600 text-white shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4.12-5.671Z" clip-rule="evenodd" />
                                            </svg>
                                            ENTREGADO
                                        </span>
                                        <span 
                                            v-else-if="item.quantity_delivered >= item.quantity_requested"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest bg-fuchsia-600 text-white shadow-sm"
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

                    <!-- VISTA MÓVIL EN TARJETAS (< 768px) -->
                    <div class="md:hidden divide-y divide-zinc-100 dark:divide-secondary-700/50">
                        <div 
                            v-for="item in request.details" 
                            :key="'mob-' + item.id"
                            class="p-4 space-y-3.5 bg-white dark:bg-secondary-800"
                        >
                            <!-- Fila Superior: Imagen, Título, Código y Estado -->
                            <div class="flex items-start gap-3">
                                <div 
                                    class="w-14 h-14 rounded-2xl bg-zinc-100 dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700/60 overflow-hidden flex-shrink-0 flex items-center justify-center shadow-inner relative"
                                    :class="{ 'cursor-pointer': item.product_image_url || (item.product_images && item.product_images.length > 0) }"
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

                                <div class="flex-1 min-w-0 space-y-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[9px] text-zinc-400 dark:text-secondary-500 font-mono font-bold uppercase tracking-wider">
                                            CÓD: {{ item.product_code }}
                                        </span>
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

                                    <h4 class="text-xs font-black text-zinc-900 dark:text-secondary-100 uppercase tracking-tight leading-tight">
                                        {{ item.product_name }}
                                    </h4>
                                    <p v-if="item.product_description" class="text-[9px] text-zinc-500 dark:text-secondary-400 italic leading-snug">
                                        {{ item.product_description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Chips de Atributos Móviles -->
                            <div class="flex flex-wrap items-center gap-1.5 text-[9px]">
                                <span class="px-2 py-0.5 rounded-md font-black tracking-wider uppercase bg-emerald-600 text-white flex items-center gap-0.5 shadow-sm">
                                    <span class="material-symbols-outlined text-[13px]">location_on</span>
                                    {{ item.product_location || 'Ubicación S/N' }}
                                </span>
                                <span v-if="item.product_brand" class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 border border-indigo-200/50">
                                    {{ item.product_brand }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded font-black tracking-wider uppercase bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 border border-amber-200/50">
                                    {{ item.product_package_name || item.unit_of_measure }}
                                </span>
                            </div>

                            <!-- Grid 2x2 de Métricas en Móvil -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-zinc-100 dark:border-secondary-700/50 bg-zinc-50/50 dark:bg-secondary-900/30 p-2.5 rounded-2xl text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-[8px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-wider">Solicitado</span>
                                    <div class="inline-flex items-center gap-1 mt-0.5">
                                        <span class="text-xs font-black font-mono text-zinc-800 dark:text-secondary-100">
                                            {{ item.quantity_requested }} {{ item.unit_of_measure }}
                                        </span>
                                        <button 
                                            v-if="isAdmin && (request.status === 'pendiente' || request.status === 'observado')"
                                            @click="handleEditQuantity(item)"
                                            type="button"
                                            class="p-0.5 rounded text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                                            title="Editar cantidad"
                                        >
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                        </button>
                                    </div>
                                </div>

                                <div v-if="!isConsumidorRole" class="flex flex-col">
                                    <span class="text-[8px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-wider">Stock Físico</span>
                                    <span class="text-xs font-black font-mono mt-0.5" :class="getRemainingStock(item) > 0 ? 'text-zinc-700 dark:text-secondary-300' : 'text-rose-500 dark:text-rose-400'">
                                        {{ getRemainingStock(item) }} {{ item.unit_of_measure }}
                                    </span>
                                </div>

                                <div v-if="canUserDispatch" class="flex flex-col">
                                    <span class="text-[8px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-wider">A Despachar</span>
                                    <span class="text-xs font-black font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">
                                        {{ Number(dispatchQuantities[item.id] || 0).toFixed(2) }} {{ item.unit_of_measure }}
                                    </span>
                                </div>

                                <div v-if="request.status === 'despachado' || request.status === 'despachado_parcial' || request.status === 'entregado'" class="flex flex-col">
                                    <span class="text-[8px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-wider">Recibido</span>
                                    <span class="text-xs font-black font-mono mt-0.5" :class="item.quantity_received !== null && item.quantity_received > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-400 dark:text-secondary-500'">
                                        {{ item.quantity_received !== null ? Number(item.quantity_received).toFixed(2) : '0.00' }} {{ item.unit_of_measure }}
                                    </span>
                                </div>
                            </div>

                            <!-- Input de Recepción Móvil si corresponde -->
                            <div 
                                v-if="(request.status === 'despachado' || request.status === 'despachado_parcial') && isConsumidorRole && item.quantity_delivered > 0"
                                class="pt-2 border-t border-dashed border-zinc-200 dark:border-secondary-700 space-y-2"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[9px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-wider">Cantidad a Recibir:</span>
                                    <div class="flex items-center gap-1">
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

                                <div v-if="Math.abs((receivedQuantities[item.id] ?? 0) - item.quantity_requested) >= 0.01" class="flex items-center gap-1.5">
                                    <textarea 
                                        rows="2"
                                        placeholder="Motivo de discrepancia..." 
                                        v-model="observations[item.id]" 
                                        class="flex-1 text-left rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-950/20 text-[10px] font-bold p-2 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-amber-850 dark:text-amber-200 placeholder-amber-450 resize-y min-h-[44px]"
                                    ></textarea>
                                    <button 
                                        type="button"
                                        @click="toggleSpeechRecognition(item.id, 'diff')"
                                        class="w-9 h-9 flex-shrink-0 rounded-lg flex items-center justify-center transition-all duration-300 shadow-sm"
                                        :class="activeListeningId === item.id && currentVoiceField === 'diff' ? 'bg-rose-500 text-white animate-pulse' : 'bg-amber-100 hover:bg-amber-200 text-amber-850 dark:bg-amber-950/40 dark:hover:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800'"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: FICHA OPERATIVA Y ACCIONES DESKTOP (30% Sticky) -->
            <div class="w-full lg:w-[32%] xl:w-[28%] space-y-4 lg:sticky lg:top-4">
                <!-- FICHA OPERATIVA -->
                <div class="bg-white dark:bg-secondary-800 rounded-3xl border border-zinc-200/70 dark:border-secondary-700/80 shadow-sm p-5 space-y-4 transition-all duration-300">
                    <h3 class="text-xs font-black text-zinc-900 dark:text-secondary-100 uppercase tracking-widest border-b border-zinc-100 dark:border-secondary-700 pb-3 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-indigo-500">assignment</span>
                        Ficha Operativa
                    </h3>
                    
                    <div class="space-y-3">
                        <!-- Solicitud Info -->
                        <div class="p-3.5 bg-zinc-50 dark:bg-secondary-900/40 rounded-2xl border border-zinc-100 dark:border-secondary-700/60 space-y-2">
                            <span class="block text-[8px] font-black text-indigo-500 dark:text-indigo-400 uppercase tracking-widest border-b border-zinc-200/40 dark:border-secondary-700/30 pb-1">Registro Inicial</span>
                            <div class="text-xs space-y-1">
                                <p class="text-[10px] font-bold text-zinc-400 uppercase">Área Solicitante:</p>
                                <p class="font-black text-zinc-800 dark:text-secondary-100 uppercase">{{ request.requested_by }}</p>
                                <p class="text-[10px] font-bold text-zinc-400 uppercase pt-1">Fecha & Hora:</p>
                                <p class="font-semibold text-zinc-700 dark:text-secondary-300">{{ request.date_formatted }} {{ request.created_at_time || '' }}</p>
                            </div>
                        </div>

                        <!-- Aprobación / Cancelación / Observación -->
                        <div v-if="request.status === 'cancelado'" class="p-3.5 bg-rose-50/70 dark:bg-rose-950/20 rounded-2xl border border-rose-200/60 dark:border-rose-900/40 space-y-1.5">
                            <span class="block text-[8px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest">Cancelación</span>
                            <p class="text-xs font-black text-rose-700 dark:text-rose-300 uppercase">{{ request.cancelled_by_user?.name || 'Usuario' }}</p>
                            <p class="text-[10px] font-semibold text-rose-600/80">{{ request.cancelled_at_formatted }}</p>
                            <p v-if="request.cancellation_notes" class="text-[10px] font-medium text-rose-800 dark:text-rose-300 italic pt-1">{{ request.cancellation_notes }}</p>
                        </div>

                        <div v-else-if="request.approved_by_user" class="p-3.5 bg-emerald-50/70 dark:bg-emerald-950/20 rounded-2xl border border-emerald-200/60 dark:border-emerald-900/40 space-y-1">
                            <span class="block text-[8px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Aprobación</span>
                            <p class="text-xs font-black text-emerald-700 dark:text-emerald-300 uppercase">{{ request.approved_by_user.name }}</p>
                            <p class="text-[10px] font-semibold text-emerald-600/80">{{ request.approved_at_formatted }}</p>
                        </div>

                        <div v-if="request.observed_by_user" class="p-3.5 bg-orange-50/70 dark:bg-orange-950/20 rounded-2xl border border-orange-200/60 dark:border-orange-900/40 space-y-1">
                            <span class="block text-[8px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest">Observación de Admin</span>
                            <p class="text-xs font-black text-orange-700 dark:text-orange-300 uppercase">{{ request.observed_by_user.name }}</p>
                            <p class="text-[10px] font-semibold text-orange-600/80">{{ request.observed_at_formatted }}</p>
                            <p v-if="request.observation_notes" class="text-[10px] font-medium text-orange-800 dark:text-orange-300 italic pt-1">{{ request.observation_notes }}</p>
                        </div>

                        <!-- Despacho -->
                        <div v-if="request.dispatched_by_user" class="p-3.5 bg-indigo-50/70 dark:bg-indigo-950/20 rounded-2xl border border-indigo-200/60 dark:border-indigo-900/40 space-y-1">
                            <span class="block text-[8px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Despacho</span>
                            <p class="text-xs font-black text-indigo-700 dark:text-indigo-300 uppercase">{{ request.dispatched_by_user.name }}</p>
                            <p class="text-[10px] font-semibold text-indigo-600/80">{{ request.dispatched_at_formatted }}</p>
                        </div>

                        <!-- Recepción -->
                        <div v-if="request.received_by_user" class="p-3.5 bg-emerald-50/70 dark:bg-emerald-950/20 rounded-2xl border border-emerald-200/60 dark:border-emerald-900/40 space-y-1">
                            <span class="block text-[8px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Recepción Final</span>
                            <p class="text-xs font-black text-emerald-700 dark:text-emerald-300 uppercase">{{ request.received_by_user.name }}</p>
                            <p class="text-[10px] font-semibold text-emerald-600/80">{{ request.received_at_formatted }}</p>
                        </div>
                    </div>
                </div>

                <!-- ACCIONES PANEL DESKTOP -->
                <div 
                    v-if="request.status === 'pendiente' || request.status === 'aprobado' || request.status === 'observado' || request.status === 'parcial' || request.status === 'despachado' || request.status === 'despachado_parcial'"
                    class="bg-white dark:bg-secondary-800 rounded-3xl border border-zinc-200/70 dark:border-secondary-700/80 shadow-sm p-5 space-y-3.5 transition-all duration-300"
                >
                    <h3 class="text-xs font-black text-zinc-900 dark:text-secondary-100 uppercase tracking-widest border-b border-zinc-100 dark:border-secondary-700 pb-3 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-indigo-500">touch_app</span>
                        Acciones Operativas
                    </h3>

                    <!-- Aprobar Solicitud (Admin) -->
                    <button 
                        v-if="isAdmin && (request.status === 'pendiente' || request.status === 'observado')"
                        @click="handleApprove"
                        class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span>Aprobar Solicitud</span>
                    </button>

                    <!-- Despachar stock (Almacén) -->
                    <button 
                        v-if="canUserDispatch && hasAnyStockToDispatch"
                        @click="handleDispatch"
                        class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>Despachar Stock</span>
                    </button>

                    <!-- Confirmar Recepción (Consumidor) -->
                    <button 
                        v-if="isConsumidorRole && (request.status === 'despachado' || request.status === 'despachado_parcial')"
                        @click="handleReceive"
                        class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-md shadow-indigo-600/20 flex items-center justify-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" />
                        </svg>
                        <span>Confirmar Recepción</span>
                    </button>

                    <!-- Comprar faltantes (Almacén/Admin si falta stock) -->
                    <button 
                        v-if="!isConsumidorRole && (request.status === 'pendiente' || request.status === 'aprobado' || request.status === 'despachado_parcial') && !isFullyStocked"
                        @click="handleGeneratePurchaseOrder"
                        class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-md flex items-center justify-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        <span>Comprar Faltantes</span>
                    </button>

                    <!-- Cancelar solicitud (Administrador en pendiente, observado o aprobado) -->
                    <button 
                        v-if="isAdmin && (request.status === 'pendiente' || request.status === 'observado' || request.status === 'aprobado')"
                        @click="handleCancel"
                        class="w-full py-3 px-4 bg-rose-50 hover:bg-rose-100 text-rose-600 dark:bg-rose-950/30 dark:text-rose-400 rounded-2xl text-xs font-black uppercase tracking-wider transition-colors flex items-center justify-center gap-2 border border-rose-200/50 dark:border-rose-900/30"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>Cancelar Solicitud</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- STICKY BOTTOM ACTION BAR PARA MÓVILES (< 1024px) -->
        <div 
            v-if="primaryMobileAction"
            class="lg:hidden fixed bottom-0 left-0 right-0 z-30 p-3 bg-white/95 dark:bg-secondary-900/95 backdrop-blur-xl border-t border-zinc-200/80 dark:border-secondary-800 shadow-2xl flex items-center gap-2"
        >
            <button 
                @click="executeMobileAction(primaryMobileAction.type)"
                :class="['flex-1 min-h-[44px] py-2.5 px-4 rounded-2xl text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2 shadow-lg', primaryMobileAction.class]"
            >
                <span class="material-symbols-outlined text-[18px]">{{ primaryMobileAction.icon }}</span>
                <span>{{ primaryMobileAction.label }}</span>
            </button>

            <a 
                :href="route('admin.consumption-requests.print', { consumption_request: request.id })" 
                target="_blank"
                class="min-h-[44px] px-3 bg-zinc-100 hover:bg-zinc-200 dark:bg-secondary-800 dark:hover:bg-secondary-700 text-zinc-700 dark:text-secondary-300 rounded-2xl flex items-center justify-center border border-zinc-200/80 dark:border-secondary-700 shadow-sm"
                title="Imprimir Solicitud"
            >
                <span class="material-symbols-outlined text-[20px]">print</span>
            </a>
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
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
            <button v-if="previewImages.length > 1" @click.stop="nextImage" class="absolute right-4 sm:right-6 top-1/2 -translate-y-1/2 w-12 h-12 sm:w-16 sm:h-16 bg-white/5 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-xl transition-all duration-300 border border-white/10 z-[10000] shadow-2xl">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
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
