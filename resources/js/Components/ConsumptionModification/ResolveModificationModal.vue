<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    modification: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'resolved']);

const mode = ref('approve'); // 'approve' | 'reject'
const resolutionNotes = ref('');
const lineDecisions = ref({});
const isSubmitting = ref(false);

const openLines = computed(() => props.modification?.open_purchase_lines || []);

watch(
    () => [props.show, props.modification],
    ([isOpen, mod]) => {
        if (isOpen && mod) {
            mode.value = 'approve';
            resolutionNotes.value = '';
            const map = {};
            (mod.open_purchase_lines || []).forEach((line) => {
                map[line.id] = {
                    decision: '',
                    notes: '',
                };
            });
            lineDecisions.value = map;
        }
    },
    { immediate: true }
);

const areAllPurchaseLinesResolved = computed(() => {
    for (const line of openLines.value) {
        const entry = lineDecisions.value[line.id];
        if (!entry || !['cancelar', 'mantener'].includes(entry.decision)) {
            return false;
        }
        if (entry.decision === 'mantener' && (!entry.notes || entry.notes.trim().length < 3)) {
            return false;
        }
    }
    return true;
});

const canApprove = computed(() => !isSubmitting.value && areAllPurchaseLinesResolved.value);
const canReject = computed(() => !isSubmitting.value && resolutionNotes.value.trim().length >= 3);

const submitApprove = () => {
    if (!canApprove.value || !props.modification) return;
    isSubmitting.value = true;

    const purchaseResolutions = openLines.value.map((line) => ({
        purchase_order_detail_id: line.id,
        decision: lineDecisions.value[line.id].decision,
        notes: (lineDecisions.value[line.id].notes || '').trim() || null,
    }));

    router.post(
        route('admin.consumption-requests.modifications.approve', {
            consumption_request: props.modification.consumption_request_id,
            modification: props.modification.id,
        }),
        {
            resolution_notes: resolutionNotes.value.trim() || null,
            purchase_resolutions: purchaseResolutions,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
            },
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Modificación Aprobada',
                    text:
                        props.modification.quantity_physically_delivered > 0
                            ? 'Quedó aprobada en espera de la devolución física completa.'
                            : 'Se actualizó el detalle al producto correcto.',
                    timer: 3000,
                    showConfirmButton: false,
                });
                emit('resolved');
                emit('close');
            },
            onError: (errors) => {
                const msg = errors.error || Object.values(errors)[0] || 'Error al aprobar la modificación.';
                Swal.fire('Validación', msg, 'error');
            },
        }
    );
};

const submitReject = () => {
    if (!canReject.value || !props.modification) return;
    isSubmitting.value = true;

    router.post(
        route('admin.consumption-requests.modifications.reject', {
            consumption_request: props.modification.consumption_request_id,
            modification: props.modification.id,
        }),
        {
            resolution_notes: resolutionNotes.value.trim(),
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
            },
            onSuccess: () => {
                Swal.fire({
                    icon: 'info',
                    title: 'Modificación Rechazada',
                    text: 'Se mantiene el producto original en la solicitud.',
                    timer: 2800,
                    showConfirmButton: false,
                });
                emit('resolved');
                emit('close');
            },
            onError: (errors) => {
                const msg = errors.error || Object.values(errors)[0] || 'Error al rechazar la modificación.';
                Swal.fire('Validación', msg, 'error');
            },
        }
    );
};
</script>

<template>
    <div
        v-if="show && modification"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-sm overflow-y-auto"
        @click.self="emit('close')"
    >
        <div class="w-full sm:max-w-xl lg:max-w-3xl bg-white dark:bg-secondary-800 border border-zinc-200/80 dark:border-secondary-700 rounded-3xl shadow-2xl overflow-hidden my-auto">
            <!-- Header -->
            <div class="px-4 sm:px-6 py-4 border-b border-zinc-100 dark:border-secondary-700 flex items-center justify-between gap-3 bg-zinc-50/70 dark:bg-secondary-900/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800/40 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-lg">fact_check</span>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-black text-zinc-900 dark:text-white uppercase tracking-tight">
                            Resolver Solicitud de Modificación
                        </h2>
                        <p class="text-[11px] font-semibold text-zinc-500 dark:text-secondary-400">
                            Consumo #{{ modification.consumption_request?.formatted_number || 'S/N' }} · Área: {{ modification.consumption_request?.requested_by }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="min-h-[44px] min-w-[44px] rounded-xl flex items-center justify-center text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-secondary-700 transition-colors"
                >
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-4 max-h-[82vh] overflow-y-auto">
                <!-- Comparativa Producto Actual vs Producto Solicitado -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="p-3.5 rounded-2xl bg-zinc-50 dark:bg-secondary-900/60 border border-zinc-200 dark:border-secondary-700 space-y-1">
                        <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400">Producto Actual</span>
                        <p class="text-xs sm:text-sm font-black text-zinc-900 dark:text-white uppercase">
                            {{ modification.from_product?.name }}
                        </p>
                        <p class="text-[11px] font-mono text-zinc-600 dark:text-secondary-300">
                            Cant. original: <strong>{{ modification.original_quantity }} {{ modification.from_product?.unit_of_measure }}</strong>
                        </p>
                        <p class="text-[10px] font-mono text-zinc-500 dark:text-secondary-400">
                            Costo unit.: Bs {{ Number(modification.from_product?.unit_cost || 0).toFixed(2) }}
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-primary-50/70 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800/50 space-y-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-primary-700 dark:text-primary-300">Producto Solicitado</span>
                            <span
                                v-if="Math.abs(modification.cost_difference || 0) >= 0.01"
                                class="px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50"
                            >
                                Dif. costo: {{ modification.cost_difference > 0 ? '+' : '' }}Bs {{ Number(modification.cost_difference).toFixed(2) }}
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm font-black text-zinc-900 dark:text-white uppercase">
                            {{ modification.to_product?.name }}
                        </p>
                        <p class="text-[11px] font-mono text-zinc-800 dark:text-secondary-100">
                            Cant. solicitada: <strong>{{ modification.requested_quantity }} {{ modification.to_product?.unit_of_measure }}</strong>
                            <span v-if="modification.has_quantity_change" class="ml-1 text-amber-600 dark:text-amber-400 font-bold">(Modificada)</span>
                        </p>
                        <p class="text-[10px] font-mono text-zinc-500 dark:text-secondary-400">
                            Costo unit.: Bs {{ Number(modification.to_product?.unit_cost || 0).toFixed(2) }}
                        </p>
                    </div>
                </div>

                <!-- Motivo y Situación Física -->
                <div class="p-3.5 rounded-2xl bg-zinc-50 dark:bg-secondary-900/50 border border-zinc-200/80 dark:border-secondary-700 space-y-2 text-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-bold text-zinc-500 dark:text-secondary-400">
                            Propuesto por: <strong class="text-zinc-900 dark:text-white uppercase">{{ modification.requested_by_user?.name }}</strong> ({{ modification.created_at_formatted }})
                        </span>
                        <div class="flex flex-wrap items-center gap-1.5 font-mono text-[10px] font-black">
                            <span class="px-2 py-0.5 rounded-lg bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 text-zinc-700 dark:text-secondary-200">
                                En almacén: {{ modification.quantity_prepared }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded-lg border"
                                :class="modification.quantity_physically_delivered > 0 ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300' : 'bg-white dark:bg-secondary-800 border-zinc-200 dark:border-secondary-700 text-zinc-700 dark:text-secondary-200'"
                            >
                                Entregadas físicamente: {{ modification.quantity_physically_delivered }}
                            </span>
                        </div>
                    </div>
                    <p class="text-zinc-800 dark:text-secondary-100 font-semibold italic">
                        "{{ modification.reason }}"
                    </p>
                    <p v-if="modification.quantity_physically_delivered > 0" class="text-[11px] font-bold text-amber-700 dark:text-amber-400">
                        Al aprobar, el detalle conservará el producto original bloqueado hasta que el almacenero confirme la devolución física de las {{ modification.quantity_physically_delivered }} unidades.
                    </p>
                </div>

                <!-- Contexto: Otros productos de la solicitud -->
                <div
                    v-if="modification.consumption_request?.other_details?.length"
                    class="p-3.5 rounded-2xl bg-white dark:bg-secondary-900/30 border border-zinc-200/70 dark:border-secondary-700 space-y-2"
                >
                    <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400 block">
                        Demás Productos de la Solicitud ({{ modification.consumption_request.other_details.length }}) — No se verán afectados
                    </span>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="od in modification.consumption_request.other_details"
                            :key="od.id"
                            class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-zinc-50 dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 text-zinc-700 dark:text-secondary-200"
                        >
                            {{ od.product_name }} ({{ od.quantity_delivered }}/{{ od.quantity_requested }} {{ od.unit_of_measure }})
                        </span>
                    </div>
                </div>

                <!-- Advertencia de Compras Legadas sin FK -->
                <div
                    v-if="modification.has_legacy_unlinked_orders_warning"
                    class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 text-xs text-amber-800 dark:text-amber-300 font-semibold"
                >
                    Aviso: Se detectaron órdenes de compra previas sin vínculo estructurado en este almacén que mencionan esta solicitud en sus notas. Verifíquelas manualmente en Solicitudes de Compra si corresponde.
                </div>

                <!-- Selector de Acción: Aprobar vs Rechazar -->
                <div class="grid grid-cols-2 gap-2 p-1 rounded-2xl bg-zinc-100 dark:bg-secondary-900">
                    <button
                        type="button"
                        @click="mode = 'approve'"
                        class="min-h-[40px] rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                        :class="mode === 'approve' ? 'bg-primary-600 text-white shadow-sm' : 'text-zinc-600 dark:text-secondary-300'"
                    >
                        Autorizar Cambio
                    </button>
                    <button
                        type="button"
                        @click="mode = 'reject'"
                        class="min-h-[40px] rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                        :class="mode === 'reject' ? 'bg-rose-600 text-white shadow-sm' : 'text-zinc-600 dark:text-secondary-300'"
                    >
                        Rechazar Cambio
                    </button>
                </div>

                <!-- Resolución Obligatoria de Líneas de Compra Abiertas (Solo en modo Aprobar) -->
                <div
                    v-if="mode === 'approve' && openLines.length > 0"
                    class="p-4 rounded-2xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/60 space-y-3"
                >
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-amber-900 dark:text-amber-300">
                            Resolución Obligatoria de Compras Asociadas ({{ openLines.length }})
                        </h4>
                        <p class="text-[11px] font-semibold text-amber-800 dark:text-amber-400">
                            Defina qué hacer con cada línea abierta del producto original antes de aprobar.
                        </p>
                    </div>

                    <div
                        v-for="line in openLines"
                        :key="line.id"
                        class="p-3.5 rounded-xl bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 space-y-2.5"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                            <div>
                                <p class="font-black text-zinc-900 dark:text-white uppercase">
                                    Proveedor: {{ line.provider_name }} · {{ line.quantity }} {{ modification.from_product?.unit_of_measure }} (Bs {{ Number(line.subtotal).toFixed(2) }})
                                </p>
                                <p class="text-[10px] font-semibold text-zinc-500 dark:text-secondary-400">
                                    Estado orden: <strong class="uppercase">{{ line.order_status }}</strong> ·
                                    {{
                                        line.other_active_lines_count > 0
                                            ? `Comparte orden con ${line.other_active_lines_count} línea(s) adicional(es) que seguirán activas`
                                            : 'Única línea activa (si cancela, la orden pasará a cancelada)'
                                    }}
                                </p>
                            </div>
                            <span
                                v-if="line.order_status === 'aprobada'"
                                class="px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                            >
                                Orden ya aprobada: coordinar con proveedor
                            </span>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2">
                            <button
                                type="button"
                                @click="lineDecisions[line.id].decision = 'cancelar'"
                                class="flex-1 min-h-[38px] px-3 rounded-xl text-xs font-black uppercase border transition-all"
                                :class="
                                    lineDecisions[line.id]?.decision === 'cancelar'
                                        ? 'bg-rose-600 text-white border-rose-600'
                                        : 'bg-zinc-50 dark:bg-secondary-900 text-zinc-700 dark:text-secondary-200 border-zinc-200 dark:border-secondary-700'
                                "
                            >
                                Cancelar esta línea
                            </button>
                            <button
                                type="button"
                                @click="lineDecisions[line.id].decision = 'mantener'"
                                class="flex-1 min-h-[38px] px-3 rounded-xl text-xs font-black uppercase border transition-all"
                                :class="
                                    lineDecisions[line.id]?.decision === 'mantener'
                                        ? 'bg-primary-600 text-white border-primary-600'
                                        : 'bg-zinc-50 dark:bg-secondary-900 text-zinc-700 dark:text-secondary-200 border-zinc-200 dark:border-secondary-700'
                                "
                            >
                                Mantener compra (producto original)
                            </button>
                        </div>

                        <div v-if="lineDecisions[line.id]?.decision === 'mantener'">
                            <input
                                v-model="lineDecisions[line.id].notes"
                                type="text"
                                placeholder="Nota obligatoria: ¿por qué se mantiene esta línea de compra para almacén? *"
                                class="w-full h-10 px-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-900 dark:text-white focus:border-primary-500 outline-none"
                            />
                        </div>
                    </div>
                </div>

                <!-- Nota de Resolución -->
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-zinc-700 dark:text-secondary-200 mb-1">
                        {{ mode === 'reject' ? 'Motivo del Rechazo *' : 'Nota de Resolución (Opcional)' }}
                    </label>
                    <textarea
                        v-model="resolutionNotes"
                        rows="2"
                        maxlength="1000"
                        :placeholder="mode === 'reject' ? 'Indique por qué se rechaza la sustitución...' : 'Comentario opcional de autorización...'"
                        class="w-full p-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-900 dark:text-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none resize-y"
                    ></textarea>
                </div>

                <!-- Footer -->
                <div class="flex flex-col-reverse sm:flex-row gap-2 justify-end pt-3 border-t border-zinc-100 dark:border-secondary-700">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="min-h-[44px] px-4 rounded-xl bg-white hover:bg-zinc-100 dark:bg-secondary-800 dark:hover:bg-secondary-700 text-zinc-700 dark:text-secondary-100 border border-zinc-200 dark:border-secondary-700 text-xs font-black uppercase tracking-wider transition-colors"
                    >
                        Cerrar
                    </button>

                    <button
                        v-if="mode === 'approve'"
                        type="button"
                        :disabled="!canApprove"
                        @click="submitApprove"
                        class="min-h-[44px] px-5 rounded-xl bg-primary-600 hover:bg-primary-700 dark:bg-primary-600 dark:hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-black uppercase tracking-wider transition-all shadow-md inline-flex items-center justify-center gap-2"
                    >
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                        <span>{{ isSubmitting ? 'Procesando...' : 'Aprobar Modificación' }}</span>
                    </button>

                    <button
                        v-else
                        type="button"
                        :disabled="!canReject"
                        @click="submitReject"
                        class="min-h-[44px] px-5 rounded-xl bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-black uppercase tracking-wider transition-all shadow-md inline-flex items-center justify-center gap-2"
                    >
                        <span class="material-symbols-outlined text-lg">cancel</span>
                        <span>{{ isSubmitting ? 'Procesando...' : 'Confirmar Rechazo' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
