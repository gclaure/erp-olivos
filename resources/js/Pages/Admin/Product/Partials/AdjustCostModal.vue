<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    product: {
        type: Object,
        default: null,
    },
    warehouses: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
    unit_cost: '',
    warehouse_id: '',
    reason: 'Corrección de costo de apertura / error de digitación',
});

watch(() => props.show, (newVal) => {
    if (newVal && props.product) {
        form.unit_cost = props.product.average_cost !== undefined && props.product.average_cost !== null 
            ? Number(props.product.average_cost).toFixed(4) 
            : '';
        form.warehouse_id = '';
        form.reason = 'Corrección de costo de apertura / error de digitación';
        form.clearErrors();
    }
});

const currentCostFormatted = computed(() => {
    if (!props.product) return '0.0000';
    return Number(props.product.average_cost || 0).toFixed(4);
});

const currentStockFormatted = computed(() => {
    if (!props.product) return '0.00';
    return Number(props.product.current_stock || 0).toFixed(2);
});

const newInventoryValue = computed(() => {
    const qty = Number(props.product?.current_stock || 0);
    const cost = Number(form.unit_cost || 0);
    return (qty * cost).toFixed(2);
});

const submit = () => {
    if (!props.product) return;

    if (form.unit_cost === '' || Number(form.unit_cost) < 0 || isNaN(Number(form.unit_cost))) {
        Swal.fire({
            title: 'Costo inválido',
            text: 'Debes ingresar un costo unitario numérico mayor o igual a 0.',
            icon: 'warning',
            confirmButtonColor: '#4f46e5',
        });
        return;
    }

    Swal.fire({
        title: '¿Confirmar ajuste de costo?',
        html: `El costo unitario de <b>${props.product.name}</b> se actualizará a <b>${form.unit_cost}</b>.<br><br><span class="text-xs text-zinc-500 dark:text-zinc-400">Nota: El stock físico de <b>${currentStockFormatted.value} ${props.product.unit_of_measure?.abbreviation || 'UND'}</b> no será modificado.</span>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, actualizar costo',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#71717a',
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(route('admin.products.adjust-cost', props.product.id), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    Swal.fire({
                        title: '¡Costo Actualizado!',
                        text: 'El costo unitario y la valorización contable han sido recalculados con éxito.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    emit('close');
                    emit('success');
                },
                onError: (errors) => {
                    const errorMsg = Object.values(errors).flat().join('<br>');
                    Swal.fire({
                        title: 'Error al ajustar costo',
                        html: errorMsg || 'Por favor verifica los datos ingresados.',
                        icon: 'error',
                        confirmButtonColor: '#4f46e5',
                    });
                }
            });
        }
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="emit('close')"></div>

        <!-- Modal Container -->
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-gray-800 text-left shadow-2xl transition-all w-full sm:max-w-lg border border-zinc-200 dark:border-gray-700">
                
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-5 border-b border-zinc-100 dark:border-gray-700/80 bg-zinc-50/50 dark:bg-gray-800/80">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-100 dark:border-indigo-800/50">
                            <span class="material-symbols-outlined text-xl">price_change</span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-white">Ajustar Costo Unitario</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Corrección contable sin alterar el stock físico</p>
                        </div>
                    </div>
                    <button 
                        @click="emit('close')"
                        type="button"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>

                <!-- Body -->
                <form @submit.prevent="submit" class="p-6 space-y-5">
                    
                    <!-- Product Info Card -->
                    <div v-if="product" class="p-4 rounded-2xl bg-zinc-50 dark:bg-gray-900/50 border border-zinc-200/80 dark:border-gray-700/80 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[10px] font-mono font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-zinc-200 dark:border-gray-700">
                                    {{ product.code || 'S/C' }}
                                </span>
                                <h4 class="text-sm font-bold text-zinc-900 dark:text-white mt-1.5">{{ product.name }}</h4>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/40">
                                {{ product.type_label || product.type }}
                            </span>
                        </div>

                        <!-- Readonly Stock & Current Cost Indicators -->
                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-zinc-200/60 dark:border-gray-700/60">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-400 dark:text-zinc-500 block">Stock Actual (Físico)</span>
                                <span class="text-sm font-black text-emerald-600 dark:text-emerald-400 flex items-center gap-1 mt-0.5">
                                    <span class="material-symbols-outlined text-[15px]">inventory_2</span>
                                    {{ currentStockFormatted }} <small class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold uppercase">{{ product.unit_of_measure?.abbreviation || 'UND' }}</small>
                                </span>
                                <span class="text-[9px] text-zinc-400 dark:text-zinc-500 block mt-0.5">Permanece 100% intacto</span>
                            </div>

                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-400 dark:text-zinc-500 block">Costo Actual Registrado</span>
                                <span class="text-sm font-black text-zinc-700 dark:text-zinc-200 flex items-center gap-1 mt-0.5 font-mono">
                                    {{ currentCostFormatted }}
                                </span>
                                <span class="text-[9px] text-zinc-400 dark:text-zinc-500 block mt-0.5">Costo unitario anterior</span>
                            </div>
                        </div>
                    </div>

                    <!-- Input: Nuevo Costo Unitario -->
                    <div>
                        <label for="new_unit_cost" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-2">
                            Nuevo Costo Unitario <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-2xl shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-zinc-400">
                                <span class="material-symbols-outlined text-[18px]">payments</span>
                            </div>
                            <input
                                id="new_unit_cost"
                                v-model="form.unit_cost"
                                type="number"
                                step="0.0001"
                                min="0"
                                required
                                placeholder="0.0000"
                                class="block w-full rounded-2xl border-zinc-200 dark:border-gray-700 bg-white dark:bg-gray-900 pl-11 pr-4 py-3 text-sm text-zinc-900 dark:text-white font-mono font-bold focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-400 transition-colors"
                            />
                        </div>
                        <p v-if="form.errors.unit_cost" class="mt-1.5 text-xs text-rose-500 font-medium">
                            {{ form.errors.unit_cost }}
                        </p>
                    </div>

                    <!-- Projected Inventory Valuation Box -->
                    <div class="p-3.5 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-indigo-700 dark:text-indigo-400 block">Nueva Valorización Total</span>
                            <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Stock ({{ currentStockFormatted }}) × Nuevo Costo</span>
                        </div>
                        <div class="text-right">
                            <span class="text-base font-black font-mono text-indigo-600 dark:text-indigo-400">
                                {{ newInventoryValue }}
                            </span>
                        </div>
                    </div>

                    <!-- Optional Warehouse Selection (if multiple) -->
                    <div v-if="warehouses && warehouses.length > 1">
                        <label for="warehouse_scope" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                            Aplicar en Almacén
                        </label>
                        <select
                            id="warehouse_scope"
                            v-model="form.warehouse_id"
                            class="block w-full rounded-2xl border-zinc-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-xs text-zinc-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todos los almacenes con existencias</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>

                    <!-- Input: Motivo / Justificación -->
                    <div>
                        <label for="adjust_reason" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                            Motivo / Justificación
                        </label>
                        <input
                            id="adjust_reason"
                            v-model="form.reason"
                            type="text"
                            placeholder="Ej: Corrección de costo de apertura"
                            class="block w-full rounded-2xl border-zinc-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-xs text-zinc-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                        />
                    </div>

                    <!-- Actions Footer -->
                    <div class="flex flex-col-reverse sm:flex-row gap-2.5 justify-end pt-3 border-t border-zinc-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="emit('close')"
                            :disabled="form.processing"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-zinc-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-500/25 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2"
                        >
                            <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span>
                            <span v-else class="material-symbols-outlined text-[16px]">check_circle</span>
                            <span>{{ form.processing ? 'Guardando...' : 'Guardar Nuevo Costo' }}</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>
