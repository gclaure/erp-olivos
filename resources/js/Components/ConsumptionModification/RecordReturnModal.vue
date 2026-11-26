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
    canRequestReturn: {
        type: Boolean,
        default: false,
    },
    canAdminCancel: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'updated']);

const returnedQuantity = ref(0);
const returnNotes = ref('');
const showCancelSection = ref(false);
const cancellationReason = ref('');
const isSubmitting = ref(false);

const pendingReturnQty = computed(() => parseFloat(props.modification?.pending_return_quantity || 0));

watch(
    () => [props.show, props.modification],
    ([isOpen, mod]) => {
        if (isOpen && mod) {
            returnedQuantity.value = parseFloat(Number(mod.pending_return_quantity || 0).toFixed(4));
            returnNotes.value = '';
            showCancelSection.value = false;
            cancellationReason.value = '';
        }
    },
    { immediate: true }
);

const canSubmitReturn = computed(() => {
    const qty = parseFloat(returnedQuantity.value || 0);
    return (
        props.canRequestReturn &&
        !isSubmitting.value &&
        qty > 0 &&
        qty <= pendingReturnQty.value + 0.0001
    );
});

const canSubmitCancel = computed(() => {
    return props.canAdminCancel && !isSubmitting.value && cancellationReason.value.trim().length >= 3;
});

const submitReturn = () => {
    if (!canSubmitReturn.value || !props.modification) return;
    isSubmitting.value = true;

    router.post(
        route('admin.consumption-requests.modifications.returns.store', {
            consumption_request: props.modification.consumption_request_id,
            modification: props.modification.id,
        }),
        {
            returned_quantity: parseFloat(returnedQuantity.value),
            return_notes: returnNotes.value.trim() || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
            },
            onSuccess: () => {
                const completesAll = Math.abs(parseFloat(returnedQuantity.value) - pendingReturnQty.value) < 0.0001;
                Swal.fire({
                    icon: 'success',
                    title: completesAll ? 'Devolución Completada' : 'Devolución Parcial Registrada',
                    text: completesAll
                        ? 'Se aplicó el producto sustituto y el detalle quedó habilitado para despacho.'
                        : 'El detalle permanece bloqueado hasta completar la devolución física restante.',
                    timer: 3200,
                    showConfirmButton: false,
                });
                emit('updated');
                emit('close');
            },
            onError: (errors) => {
                const msg = errors.error || Object.values(errors)[0] || 'No se pudo registrar la devolución.';
                Swal.fire('Validación', msg, 'error');
            },
        }
    );
};

const submitAdminCancel = () => {
    if (!canSubmitCancel.value || !props.modification) return;
    isSubmitting.value = true;

    router.post(
        route('admin.consumption-requests.modifications.cancel', {
            consumption_request: props.modification.consumption_request_id,
            modification: props.modification.id,
        }),
        {
            cancellation_reason: cancellationReason.value.trim(),
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
            },
            onSuccess: () => {
                Swal.fire({
                    icon: 'info',
                    title: 'Modificación Cancelada',
                    text: 'El detalle restauró el producto original descontando las unidades físicamente devueltas.',
                    timer: 3200,
                    showConfirmButton: false,
                });
                emit('updated');
                emit('close');
            },
            onError: (errors) => {
                const msg = errors.error || Object.values(errors)[0] || 'Error al cancelar la modificación.';
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
        <div class="w-full sm:max-w-lg lg:max-w-2xl bg-white dark:bg-secondary-800 border border-zinc-200/80 dark:border-secondary-700 rounded-3xl shadow-2xl overflow-hidden my-auto">
            <!-- Header -->
            <div class="px-4 sm:px-6 py-4 border-b border-zinc-100 dark:border-secondary-700 flex items-center justify-between gap-3 bg-amber-50/70 dark:bg-secondary-900/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-lg">assignment_return</span>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-black text-zinc-900 dark:text-white uppercase tracking-tight">
                            Control de Devolución Física
                        </h2>
                        <p class="text-[11px] font-semibold text-zinc-500 dark:text-secondary-400">
                            El cambio a <strong>{{ modification.to_product?.name }}</strong> se aplicará al completar la devolución
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
                <!-- Resumen de Saldos de Devolución -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-center">
                    <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-secondary-900/60 border border-zinc-200 dark:border-secondary-700">
                        <span class="text-[10px] font-black uppercase text-zinc-400 block">Entregado Físicamente</span>
                        <span class="text-base font-black font-mono text-zinc-900 dark:text-white">
                            {{ modification.quantity_physically_delivered }} {{ modification.from_product?.unit_of_measure }}
                        </span>
                    </div>
                    <div class="p-3 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50">
                        <span class="text-[10px] font-black uppercase text-emerald-700 dark:text-emerald-400 block">Ya Devuelto</span>
                        <span class="text-base font-black font-mono text-emerald-700 dark:text-emerald-300">
                            {{ modification.total_returned_quantity }} {{ modification.from_product?.unit_of_measure }}
                        </span>
                    </div>
                    <div class="p-3 rounded-2xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50">
                        <span class="text-[10px] font-black uppercase text-amber-700 dark:text-amber-400 block">Saldo Pendiente</span>
                        <span class="text-base font-black font-mono text-amber-700 dark:text-amber-300">
                            {{ pendingReturnQty }} {{ modification.from_product?.unit_of_measure }}
                        </span>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-zinc-50 dark:bg-secondary-900/40 border border-zinc-200 dark:border-secondary-700 text-xs font-semibold text-zinc-700 dark:text-secondary-200">
                    Producto a recibir de vuelta en almacén: <strong class="uppercase text-zinc-900 dark:text-white">{{ modification.from_product?.name }}</strong>.
                    Una devolución parcial mantiene el detalle bloqueado (una nota no sustituye la entrega física).
                </div>

                <!-- Historial de devoluciones parciales previas -->
                <div v-if="modification.returns?.length" class="space-y-2">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-zinc-400 dark:text-secondary-400">
                        Entregas Físicas Registradas ({{ modification.returns.length }})
                    </h4>
                    <div class="divide-y divide-zinc-100 dark:divide-secondary-700 rounded-2xl border border-zinc-200 dark:border-secondary-700 bg-white dark:bg-secondary-900/30">
                        <div
                            v-for="ret in modification.returns"
                            :key="ret.id"
                            class="px-3.5 py-2.5 flex items-center justify-between gap-2 text-xs"
                        >
                            <div>
                                <span class="font-black font-mono text-emerald-700 dark:text-emerald-300">
                                    +{{ ret.returned_quantity }} {{ modification.from_product?.unit_of_measure }}
                                </span>
                                <span class="text-zinc-500 dark:text-secondary-400 ml-2">
                                    por {{ ret.returned_by_user?.name || 'Almacén' }}
                                </span>
                                <p v-if="ret.return_notes" class="text-[11px] text-zinc-500 italic mt-0.5">{{ ret.return_notes }}</p>
                            </div>
                            <span class="text-[10px] font-semibold text-zinc-400">{{ ret.returned_at_formatted }}</span>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Registro de Devolución (Almacén) -->
                <form v-if="canRequestReturn && pendingReturnQty > 0" @submit.prevent="submitReturn" class="space-y-3 pt-2 border-t border-zinc-100 dark:border-secondary-700">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-black uppercase tracking-wider text-zinc-700 dark:text-secondary-200">
                                    Cantidad Física Devuelta *
                                </label>
                                <button
                                    type="button"
                                    @click="returnedQuantity = pendingReturnQty"
                                    class="text-[10px] font-black uppercase text-primary-700 dark:text-primary-300 hover:underline"
                                >
                                    Usar saldo ({{ pendingReturnQty }})
                                </button>
                            </div>
                            <input
                                v-model.number="returnedQuantity"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="pendingReturnQty"
                                required
                                class="w-full h-11 px-3.5 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-sm font-black font-mono text-zinc-900 dark:text-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-zinc-700 dark:text-secondary-200 mb-1">
                                Nota de Recepción en Almacén (Opcional)
                            </label>
                            <input
                                v-model="returnNotes"
                                type="text"
                                maxlength="1000"
                                placeholder="Estado del insumo devuelto..."
                                class="w-full h-11 px-3.5 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-900 dark:text-white focus:border-primary-500 outline-none"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row gap-2 justify-end pt-2">
                        <button
                            type="button"
                            @click="emit('close')"
                            class="min-h-[44px] px-4 rounded-xl bg-white hover:bg-zinc-100 dark:bg-secondary-800 dark:hover:bg-secondary-700 text-zinc-700 dark:text-secondary-100 border border-zinc-200 dark:border-secondary-700 text-xs font-black uppercase tracking-wider transition-colors"
                        >
                            Cerrar
                        </button>
                        <button
                            type="submit"
                            :disabled="!canSubmitReturn"
                            class="min-h-[44px] px-5 rounded-xl bg-primary-600 hover:bg-primary-700 dark:bg-primary-600 dark:hover:bg-primary-500 disabled:opacity-50 text-white text-xs font-black uppercase tracking-wider transition-all shadow-md inline-flex items-center justify-center gap-2"
                        >
                            <span class="material-symbols-outlined text-lg">inventory</span>
                            <span>{{ isSubmitting ? 'Registrando...' : 'Confirmar Devolución Física' }}</span>
                        </button>
                    </div>
                </form>

                <!-- Opción Administrativa: Cancelar Modificación por Devolución Incompleta -->
                <div v-if="canAdminCancel" class="pt-3 border-t border-zinc-200/80 dark:border-secondary-700">
                    <button
                        type="button"
                        @click="showCancelSection = !showCancelSection"
                        class="text-xs font-black uppercase tracking-wider text-rose-600 dark:text-rose-400 inline-flex items-center gap-1 hover:underline"
                    >
                        <span class="material-symbols-outlined text-base">gavel</span>
                        <span>{{ showCancelSection ? 'Ocultar cancelación administrativa' : '¿La devolución física no se completará? Cancelar modificación (Admin)' }}</span>
                    </button>

                    <div v-if="showCancelSection" class="mt-3 p-3.5 rounded-2xl bg-rose-50/70 dark:bg-rose-950/25 border border-rose-200 dark:border-rose-900/50 space-y-2.5">
                        <p class="text-[11px] font-semibold text-rose-800 dark:text-rose-300">
                            Al cancelar administrativamente, el detalle volverá al estado normal conservando <strong>{{ modification.from_product?.name }}</strong> y su cantidad despachada quedará ajustada en <strong>{{ pendingReturnQty }} {{ modification.from_product?.unit_of_measure }}</strong> (descontando lo ya devuelto).
                        </p>
                        <textarea
                            v-model="cancellationReason"
                            rows="2"
                            placeholder="Motivo obligatorio de la cancelación administrativa *"
                            class="w-full p-3 rounded-xl border border-rose-200 dark:border-rose-800 bg-white dark:bg-secondary-900 text-xs font-semibold text-zinc-900 dark:text-white focus:border-rose-500 outline-none"
                        ></textarea>
                        <div class="flex justify-end">
                            <button
                                type="button"
                                :disabled="!canSubmitCancel"
                                @click="submitAdminCancel"
                                class="min-h-[40px] px-4 rounded-xl bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white text-xs font-black uppercase tracking-wider transition-all"
                            >
                                Confirmar Cancelación Administrativa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
