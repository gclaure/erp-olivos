<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    consumptionRequestId: {
        type: String,
        required: true,
    },
    warehouseId: {
        type: String,
        default: null,
    },
    detail: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'submitted']);

const searchQuery = ref('');
const searchResults = ref([]);
const isLoadingProducts = ref(false);
const selectedProduct = ref(null);
const isSearchOpen = ref(true);

// 'in_warehouse' = Sigue en almacén (preparado, nunca salió)
// 'return_required' = El solicitante lo tiene y debe devolverlo físicamente
// 'custom' = Desglose numérico manual avanzado
const physicalChoice = ref('in_warehouse');
const showCustomPhysical = ref(false);

const form = useForm({
    to_product_id: '',
    requested_quantity: 0,
    quantity_prepared: 0,
    quantity_physically_delivered: 0,
    reason: '',
});

const dispatchedBefore = computed(() => parseFloat(props.detail?.quantity_delivered || 0));
const hasPriorDispatch = computed(() => dispatchedBefore.value > 0.0001);
const originalQuantity = computed(() => parseFloat(props.detail?.quantity_requested || 0));

const fetchProducts = debounce(async (query) => {
    if (!props.detail) return;
    isLoadingProducts.value = true;
    try {
        const { data } = await axios.get(route('admin.api.selects.products'), {
            params: {
                search: query,
                warehouse_id: props.warehouseId,
                unit_of_measure_id: props.detail.unit_of_measure_id,
            },
        });
        searchResults.value = Array.isArray(data) ? data : [];
    } catch {
        searchResults.value = [];
    } finally {
        isLoadingProducts.value = false;
    }
}, 280);

const setPhysicalChoice = (choice) => {
    physicalChoice.value = choice;
    if (choice === 'in_warehouse') {
        showCustomPhysical.value = false;
        form.quantity_prepared = parseFloat(dispatchedBefore.value.toFixed(4));
        form.quantity_physically_delivered = 0;
    } else if (choice === 'return_required') {
        showCustomPhysical.value = false;
        form.quantity_prepared = 0;
        form.quantity_physically_delivered = parseFloat(dispatchedBefore.value.toFixed(4));
    } else if (choice === 'custom') {
        showCustomPhysical.value = true;
    }
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && props.detail) {
            form.reset();
            form.clearErrors();
            form.to_product_id = '';
            form.requested_quantity = parseFloat(Number(props.detail.quantity_requested || 0).toFixed(4));
            form.quantity_prepared = parseFloat(Number(props.detail.quantity_delivered || 0).toFixed(4));
            form.quantity_physically_delivered = 0;
            form.reason = '';
            selectedProduct.value = null;
            searchQuery.value = '';
            isSearchOpen.value = true;
            physicalChoice.value = 'in_warehouse';
            showCustomPhysical.value = false;
            fetchProducts('');
        }
    }
);

watch(searchQuery, (val) => {
    if (props.show && isSearchOpen.value) {
        fetchProducts(val);
    }
});

const selectProduct = (prod) => {
    selectedProduct.value = prod;
    form.to_product_id = prod.id;
    isSearchOpen.value = false;
};

const openProductSearch = () => {
    isSearchOpen.value = true;
    fetchProducts(searchQuery.value);
};

const physicalSum = computed(() => {
    const prep = parseFloat(form.quantity_prepared || 0);
    const phys = parseFloat(form.quantity_physically_delivered || 0);
    return parseFloat((prep + phys).toFixed(4));
});

const isPhysicalSumValid = computed(() => {
    if (!hasPriorDispatch.value) return true;
    return Math.abs(physicalSum.value - dispatchedBefore.value) < 0.0001;
});

const hasQuantityChanged = computed(() => {
    return Math.abs(parseFloat(form.requested_quantity || 0) - originalQuantity.value) >= 0.0001;
});

const isSameProduct = computed(() => {
    return Boolean(form.to_product_id && props.detail && form.to_product_id === props.detail.product_id);
});

const hasAnyChange = computed(() => {
    if (!form.to_product_id) return false;
    return !isSameProduct.value || hasQuantityChanged.value;
});

const costDifference = computed(() => {
    if (!selectedProduct.value || !props.detail) return 0;
    const fromCost = parseFloat(props.detail.unit_cost || 0);
    const toCost = parseFloat(selectedProduct.value.price || selectedProduct.value.catalog_price || 0);
    return parseFloat((toCost - fromCost).toFixed(2));
});

const canSubmit = computed(() => {
    return (
        !form.processing &&
        !!form.to_product_id &&
        parseFloat(form.requested_quantity || 0) > 0 &&
        isPhysicalSumValid.value &&
        hasAnyChange.value &&
        form.reason.trim().length >= 3
    );
});

const submit = () => {
    if (!canSubmit.value || !props.detail) return;

    const payload = {
        to_product_id: form.to_product_id,
        requested_quantity: parseFloat(form.requested_quantity),
        reason: form.reason.trim(),
        quantity_prepared: hasPriorDispatch.value ? parseFloat(form.quantity_prepared || 0) : null,
        quantity_physically_delivered: hasPriorDispatch.value ? parseFloat(form.quantity_physically_delivered || 0) : null,
    };

    form.transform(() => payload).post(
        route('admin.consumption-requests.modifications.store', {
            consumption_request: props.consumptionRequestId,
            detail: props.detail.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Solicitud Enviada',
                    text: 'La propuesta de sustitución quedó registrada para aprobación del Administrador.',
                    timer: 3000,
                    showConfirmButton: false,
                });
                emit('submitted');
                emit('close');
            },
            onError: (errors) => {
                const msg = errors.error || Object.values(errors)[0] || 'No se pudo registrar la sustitución.';
                Swal.fire({
                    icon: 'error',
                    title: 'Validación',
                    text: msg,
                });
            },
        }
    );
};
</script>

<template>
    <div
        v-if="show && detail"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-sm overflow-y-auto"
        @click.self="emit('close')"
    >
        <div class="w-full sm:max-w-xl lg:max-w-2xl bg-white dark:bg-secondary-800 border border-zinc-200/80 dark:border-secondary-700 rounded-3xl shadow-2xl overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
            <!-- Header -->
            <div class="px-5 sm:px-6 py-4 border-b border-zinc-100 dark:border-secondary-700 flex items-center justify-between gap-3 bg-zinc-50/70 dark:bg-secondary-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-primary-100/70 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800/50 flex items-center justify-center flex-shrink-0 shadow-sm">
                        <span class="material-symbols-outlined text-xl">swap_horiz</span>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-black text-zinc-900 dark:text-white uppercase tracking-tight">
                            Cambio de Producto en Entrega
                        </h2>
                        <p class="text-[11px] font-semibold text-zinc-500 dark:text-secondary-400">
                            Reemplazar un insumo por otro. Requiere aprobación del Administrador.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="min-h-[44px] min-w-[44px] rounded-xl flex items-center justify-center text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-secondary-700 transition-colors"
                    title="Cerrar modal"
                >
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="submit" class="p-4 sm:p-6 space-y-5 max-h-[82vh] overflow-y-auto">
                <!-- ============================================================ -->
                <!-- PASO 1: EL PRODUCTO A REEMPLAZAR (¿QUÉ PASA CON ÉL?) -->
                <!-- ============================================================ -->
                <div class="p-4 rounded-2xl bg-rose-50/50 dark:bg-rose-950/20 border border-rose-200/80 dark:border-rose-900/40 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-rose-800 dark:text-rose-300">
                            <span class="w-5 h-5 rounded-full bg-rose-200 dark:bg-rose-900/60 text-rose-800 dark:text-rose-200 flex items-center justify-center text-[11px] font-black">1</span>
                            Producto a Reemplazar (Retorna o se Cancela)
                        </span>
                        <span class="text-[10px] font-mono font-bold text-zinc-500 dark:text-secondary-400">
                            CÓD: {{ detail.product_code || 'S/C' }}
                        </span>
                    </div>

                    <!-- Ficha del Producto Original -->
                    <div class="bg-white/80 dark:bg-secondary-900/80 p-3 rounded-xl border border-rose-200/60 dark:border-rose-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="text-sm font-black text-zinc-900 dark:text-white uppercase">
                                {{ detail.product_name }}
                            </p>
                            <p class="text-[11px] text-zinc-500 dark:text-secondary-400">
                                Cantidad pedida: <strong class="text-zinc-800 dark:text-zinc-200">{{ originalQuantity }} {{ detail.unit_of_measure }}</strong>
                                <span v-if="hasPriorDispatch" class="ml-2 font-semibold text-amber-600 dark:text-amber-400">
                                    · Despachado previamente: {{ dispatchedBefore }} {{ detail.unit_of_measure }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Pregunta física directa: Solo si ya hubo despacho previo -->
                    <div v-if="hasPriorDispatch" class="space-y-2 pt-1">
                        <label class="block text-xs font-black text-zinc-800 dark:text-zinc-200">
                            ¿Dónde está físicamente este producto ahora mismo?
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <!-- Opción A: Sigue en almacén -->
                            <button
                                type="button"
                                @click="setPhysicalChoice('in_warehouse')"
                                class="min-h-[52px] p-3 rounded-xl border text-left flex items-start gap-3 transition-all"
                                :class="physicalChoice === 'in_warehouse' && !showCustomPhysical
                                    ? 'bg-white dark:bg-secondary-800 border-primary-600 dark:border-primary-500 ring-2 ring-primary-500/30 shadow-sm'
                                    : 'bg-white/60 dark:bg-secondary-900/60 border-zinc-200 dark:border-secondary-700 hover:bg-white text-zinc-700 dark:text-secondary-300'"
                            >
                                <span class="material-symbols-outlined text-xl text-primary-600 dark:text-primary-400 mt-0.5">storefront</span>
                                <div>
                                    <strong class="block text-xs uppercase text-zinc-900 dark:text-white">Sigue en Almacén</strong>
                                    <span class="text-[10px] text-zinc-500 dark:text-secondary-400 leading-tight block">
                                        Nunca salió (estaba preparado). Se queda en almacén.
                                    </span>
                                </div>
                            </button>

                            <!-- Opción B: El solicitante lo devolverá -->
                            <button
                                type="button"
                                @click="setPhysicalChoice('return_required')"
                                class="min-h-[52px] p-3 rounded-xl border text-left flex items-start gap-3 transition-all"
                                :class="physicalChoice === 'return_required' && !showCustomPhysical
                                    ? 'bg-white dark:bg-secondary-800 border-amber-500 dark:border-amber-400 ring-2 ring-amber-500/30 shadow-sm'
                                    : 'bg-white/60 dark:bg-secondary-900/60 border-zinc-200 dark:border-secondary-700 hover:bg-white text-zinc-700 dark:text-secondary-300'"
                            >
                                <span class="material-symbols-outlined text-xl text-amber-600 dark:text-amber-400 mt-0.5">assignment_return</span>
                                <div>
                                    <strong class="block text-xs uppercase text-zinc-900 dark:text-white">El solicitante lo devolverá</strong>
                                    <span class="text-[10px] text-zinc-500 dark:text-secondary-400 leading-tight block">
                                        Salió con el usuario. Requerirá registrar devolución.
                                    </span>
                                </div>
                            </button>
                        </div>

                        <!-- Opción discreta para ajuste manual (casos mixtos) -->
                        <div class="text-right">
                            <button
                                type="button"
                                @click="showCustomPhysical = !showCustomPhysical; physicalChoice = 'custom'"
                                class="text-[10px] font-bold text-zinc-500 hover:text-zinc-800 dark:text-secondary-400 dark:hover:text-white underline inline-flex items-center gap-1"
                            >
                                <span class="material-symbols-outlined text-[13px]">tune</span>
                                {{ showCustomPhysical ? 'Ocultar desglose manual' : '¿Fue una entrega mixta? Ajustar cantidades' }}
                            </button>
                        </div>

                        <!-- Campos numéricos si eligió ajuste manual -->
                        <div v-if="showCustomPhysical" class="p-3 rounded-xl bg-white dark:bg-secondary-900 border border-zinc-200 dark:border-secondary-700 space-y-2">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-zinc-600 dark:text-secondary-300 mb-1">
                                        En almacén (no salió):
                                    </label>
                                    <input
                                        v-model.number="form.quantity_prepared"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full h-9 px-2 rounded-lg border border-zinc-200 dark:border-secondary-700 text-xs font-mono font-bold"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-zinc-600 dark:text-secondary-300 mb-1">
                                        Entregado (devolverá):
                                    </label>
                                    <input
                                        v-model.number="form.quantity_physically_delivered"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full h-9 px-2 rounded-lg border border-zinc-200 dark:border-secondary-700 text-xs font-mono font-bold"
                                    />
                                </div>
                            </div>
                            <p class="text-[10px] font-bold" :class="isPhysicalSumValid ? 'text-emerald-600' : 'text-rose-600'">
                                Suma: {{ physicalSum }} / {{ dispatchedBefore }} {{ detail.unit_of_measure }}
                                ({{ isPhysicalSumValid ? 'Correcto' : 'Debe igualar la cantidad despachada' }})
                            </p>
                        </div>
                    </div>

                    <!-- Mensaje si no había despacho previo -->
                    <div v-else class="text-[11px] text-emerald-700 dark:text-emerald-400 font-medium flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">check_circle</span>
                        <span>Este producto aún no había salido de almacén. No requiere registrar devolución física.</span>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- PASO 2: EL PRODUCTO NUEVO QUE SE ENTREGARÁ -->
                <!-- ============================================================ -->
                <div class="p-4 rounded-2xl bg-primary-50/40 dark:bg-primary-950/20 border border-primary-200/80 dark:border-primary-800/40 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-primary-800 dark:text-primary-300">
                            <span class="w-5 h-5 rounded-full bg-primary-200 dark:bg-primary-900/60 text-primary-800 dark:text-primary-200 flex items-center justify-center text-[11px] font-black">2</span>
                            Producto Nuevo que se Entregará
                        </span>
                        <span class="text-[10px] font-bold text-primary-700 dark:text-primary-400 bg-primary-100/60 dark:bg-primary-950 px-2 py-0.5 rounded-md">
                            Misma unidad: {{ detail.unit_of_measure }}
                        </span>
                    </div>

                    <!-- Si el producto nuevo YA ESTÁ SELECCIONADO -->
                    <div v-if="selectedProduct && !isSearchOpen" class="bg-white dark:bg-secondary-900 p-3.5 rounded-xl border-2 border-primary-500 space-y-3 shadow-sm">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[9px] font-black uppercase tracking-wider text-primary-600 dark:text-primary-400 block">
                                    Producto Seleccionado para Entregar:
                                </span>
                                <h4 class="text-sm font-black text-zinc-900 dark:text-white uppercase">
                                    {{ selectedProduct.name }}
                                </h4>
                                <p class="text-[10px] font-mono text-zinc-500 dark:text-secondary-400">
                                    CÓD: {{ selectedProduct.code || 'S/C' }} · Costo ref.: Bs {{ Number(selectedProduct.price || selectedProduct.catalog_price || 0).toFixed(2) }}
                                    <span v-if="Math.abs(costDifference) >= 0.01" class="font-bold ml-1" :class="costDifference > 0 ? 'text-amber-600' : 'text-emerald-600'">
                                        ({{ costDifference > 0 ? '+' : '' }}Bs {{ costDifference.toFixed(2) }})
                                    </span>
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="openProductSearch"
                                class="px-3 py-1.5 rounded-lg bg-zinc-100 dark:bg-secondary-800 hover:bg-primary-50 text-xs font-black uppercase text-primary-700 dark:text-primary-300 inline-flex items-center gap-1 transition-colors"
                            >
                                <span class="material-symbols-outlined text-sm">sync</span>
                                Cambiar
                            </button>
                        </div>

                        <!-- Cantidad a entregar y Stock -->
                        <div class="pt-2 border-t border-zinc-100 dark:border-secondary-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-black text-zinc-800 dark:text-zinc-200">
                                    Cantidad a entregar:
                                </label>
                                <div class="w-32">
                                    <input
                                        v-model.number="form.requested_quantity"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        required
                                        class="w-full h-10 px-3 rounded-lg border-2 border-primary-500 text-sm font-black font-mono text-zinc-900 dark:text-white text-center focus:ring-2 focus:ring-primary-500/30 outline-none"
                                    />
                                </div>
                                <span class="text-xs font-bold font-mono text-zinc-600 dark:text-secondary-300">
                                    {{ detail.unit_of_measure }}
                                </span>
                            </div>

                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black font-mono"
                                :class="selectedProduct.stock > 0 ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' : 'bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300'"
                            >
                                <span class="w-2 h-2 rounded-full mr-1.5" :class="selectedProduct.stock > 0 ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                Stock en almacén: {{ selectedProduct.stock }} {{ selectedProduct.unit }}
                            </span>
                        </div>
                    </div>

                    <!-- Si se está buscando producto nuevo -->
                    <div v-else class="space-y-2">
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                autofocus
                                placeholder="Escribe el nombre del producto nuevo que vas a entregar..."
                                class="w-full h-12 pl-10 pr-10 rounded-xl border-2 border-primary-300 dark:border-primary-700 bg-white dark:bg-secondary-900 text-xs font-semibold text-zinc-900 dark:text-white focus:border-primary-600 outline-none shadow-sm"
                            />
                            <span class="material-symbols-outlined text-xl text-primary-600 absolute left-3 top-3">search</span>
                            <button
                                v-if="selectedProduct"
                                type="button"
                                @click="isSearchOpen = false"
                                class="absolute right-2.5 top-2.5 px-2 py-1 rounded text-[10px] font-bold text-zinc-500 hover:bg-zinc-100"
                            >
                                Cancelar
                            </button>
                        </div>

                        <!-- Lista de resultados -->
                        <div class="max-h-48 overflow-y-auto rounded-xl border border-zinc-200 dark:border-secondary-700 divide-y divide-zinc-100 dark:divide-secondary-800 bg-white dark:bg-secondary-900 shadow-sm">
                            <div v-if="isLoadingProducts" class="p-4 text-center text-xs font-semibold text-zinc-500 dark:text-secondary-400 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined animate-spin text-base text-primary-600">progress_activity</span>
                                Buscando insumos...
                            </div>
                            <div v-else-if="searchResults.length === 0" class="p-4 text-center text-xs text-zinc-500 dark:text-secondary-400">
                                No se encontraron productos activos con unidad <strong>{{ detail.unit_of_measure }}</strong>.
                            </div>
                            <button
                                v-for="prod in searchResults"
                                :key="prod.id"
                                type="button"
                                @click="selectProduct(prod)"
                                class="w-full px-4 py-2.5 text-left flex items-center justify-between gap-3 hover:bg-primary-50/70 dark:hover:bg-primary-950/40 transition-colors"
                            >
                                <div>
                                    <p class="text-xs font-black text-zinc-900 dark:text-white uppercase">
                                        {{ prod.name }}
                                        <span v-if="prod.id === detail.product_id" class="ml-1 text-[9px] font-bold text-amber-600">(ACTUAL)</span>
                                    </p>
                                    <p class="text-[10px] font-mono text-zinc-500 dark:text-secondary-400">
                                        CÓD: {{ prod.code || 'S/C' }} · Costo: Bs {{ Number(prod.price || prod.catalog_price || 0).toFixed(2) }}
                                    </p>
                                </div>
                                <span
                                    class="text-[10px] font-black font-mono px-2 py-0.5 rounded-lg flex-shrink-0"
                                    :class="prod.stock > 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300'"
                                >
                                    Stock: {{ prod.stock }} {{ prod.unit }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- PASO 3: MOTIVO DEL CAMBIO -->
                <!-- ============================================================ -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black uppercase tracking-wider text-zinc-700 dark:text-secondary-200 flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-zinc-200 dark:bg-secondary-700 text-zinc-700 dark:text-secondary-200 flex items-center justify-center text-[11px] font-black">3</span>
                        Motivo del Cambio *
                    </label>
                    <textarea
                        v-model="form.reason"
                        rows="2"
                        maxlength="1000"
                        required
                        placeholder="Ej.: El solicitante se equivocó de medida y requiere base en lugar de caja..."
                        class="w-full p-3 rounded-xl border border-zinc-200 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900 text-xs font-semibold text-zinc-900 dark:text-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none resize-y"
                    ></textarea>

                    <p v-if="form.to_product_id && !hasAnyChange" class="text-[11px] font-bold text-rose-600 dark:text-rose-400">
                        Selecciona un producto diferente al original o ajusta la cantidad.
                    </p>
                </div>

                <!-- Footer -->
                <div class="flex flex-col-reverse sm:flex-row gap-2.5 justify-end pt-3 border-t border-zinc-100 dark:border-secondary-700">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="min-h-[44px] px-4 rounded-xl bg-white hover:bg-zinc-100 dark:bg-secondary-800 dark:hover:bg-secondary-700 text-zinc-700 dark:text-secondary-100 border border-zinc-200 dark:border-secondary-700 text-xs font-black uppercase tracking-wider transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="min-h-[44px] px-6 rounded-xl bg-primary-600 hover:bg-primary-700 dark:bg-primary-600 dark:hover:bg-primary-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-black uppercase tracking-wider transition-all shadow-md inline-flex items-center justify-center gap-2 group active:scale-[0.99]"
                    >
                        <span class="material-symbols-outlined text-base">send</span>
                        <span>{{ form.processing ? 'Enviando...' : 'Solicitar Cambio de Producto' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
