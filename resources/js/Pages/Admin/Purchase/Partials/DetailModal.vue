<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    record: Object,
});

const emit = defineEmits(['close']);

const details = ref([]);
const paginationMeta = ref({
    current_page: 1,
    last_page: 1,
    total: 0
});
const loading = ref(false);

// Limpiar notas de identificadores técnicos
const cleanedNotes = computed(() => {
    if (!props.record?.notes) return 'Sin notas.';
    return props.record.notes.replace(/\.?\s*Log ID:\s*[0-9a-f-]{36}/gi, '').trim() || 'Sin notas.';
});

const fetchDetails = async (page = 1) => {
    if (!props.record) return;
    loading.value = true;
    try {
        const response = await axios.get(route('admin.purchases.details', props.record.id), {
            params: { page }
        });
        details.value = response.data.data;
        paginationMeta.value = response.data.meta;
    } catch (error) {
        console.error('Error fetching purchase details:', error);
    } finally {
        loading.value = false;
    }
};

watch(() => props.record, (newVal) => {
    if (newVal) {
        fetchDetails(1);
    } else {
        details.value = [];
    }
}, { immediate: true });

const closeModal = () => {
    emit('close');
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-950/40 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="bg-white dark:bg-zinc-800 w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden border border-zinc-100 dark:border-zinc-700 animate-in zoom-in duration-300 flex flex-col max-h-[90vh]">
            <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 to-indigo-600"></div>
            
            <div class="p-6 overflow-y-auto">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-zinc-900 dark:text-white tracking-tight uppercase">Detalle de Compra</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Consulta la información completa del registro.</p>
                    </div>
                    <button @click="closeModal" class="text-zinc-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 p-2 rounded-xl transition-all">
                        <span class="material-symbols-outlined text-3xl leading-none">close</span>
                    </button>
                </div>

                <div v-if="record" class="space-y-6">
                    <!-- Metrics Summary Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 bg-zinc-50 dark:bg-zinc-900/50 p-5 rounded-2xl border border-zinc-100 dark:border-zinc-700">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Nro. PO</span>
                            <span class="text-sm font-black text-indigo-600 dark:text-indigo-400 font-mono">{{ record.purchase_number }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Fecha</span>
                            <span class="text-sm font-bold text-zinc-900 dark:text-white capitalize italic">{{ record.date_formatted }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Proveedor</span>
                            <span class="text-sm font-bold text-zinc-900 dark:text-white truncate">{{ record.provider?.name || '—' }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Almacén</span>
                            <span class="text-sm font-bold text-zinc-900 dark:text-white truncate">{{ record.warehouse?.name || '—' }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Registrado por</span>
                            <span class="text-sm font-bold text-zinc-900 dark:text-white truncate">{{ record.user?.name || '—' }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Estado</span>
                            <span v-if="record.status === 'cancelado'"
                                  class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400 border border-rose-200 dark:border-rose-800/30 w-fit">
                                Cancelado
                            </span>
                            <span v-else
                                  class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/30 w-fit">
                                Activo
                            </span>
                        </div>
                        <div v-if="record.status === 'cancelado'" class="col-span-1 md:col-span-2">
                            <span class="text-[10px] font-black text-rose-500 uppercase tracking-widest mb-1">Motivo de Cancelación</span>
                            <p class="text-xs font-medium text-rose-700 italic border-l-4 border-rose-500 pl-3 leading-relaxed">
                                "{{ record.cancellation_reason }}"
                            </p>
                        </div>
                        <div v-else class="col-span-1 md:col-span-2">
                            <span class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Notas Generales</span>
                            <p class="text-xs font-medium text-zinc-600 dark:text-zinc-400 leading-relaxed truncate">
                                {{ cleanedNotes }}
                            </p>
                        </div>
                    </div>

                    <!-- Products Table Block (Independent and w-full) -->
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-2xl overflow-hidden relative w-full bg-white dark:bg-zinc-800 shadow-sm">
                        <!-- Loading Overlay -->
                        <div v-if="loading" class="absolute inset-0 bg-white/60 dark:bg-zinc-800/60 backdrop-blur-[2px] z-20 flex items-center justify-center transition-all">
                            <div class="flex flex-col items-center gap-3">
                                <span class="material-symbols-outlined animate-spin text-4xl text-indigo-600">sync</span>
                                <span class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] animate-pulse">Consultando DB...</span>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left whitespace-nowrap">
                                <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
                                    <tr class="text-[10px] font-black text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">
                                        <th class="px-5 py-4">Producto</th>
                                        <th class="px-5 py-4 text-center">Cantidad</th>
                                        <th class="px-5 py-4 text-right">Costo Unit.</th>
                                        <th class="px-5 py-4 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                    <tr v-for="item in details" :key="item.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                                        <td class="px-5 py-4">
                                            <div class="flex flex-col max-w-[300px]">
                                                <span class="font-black text-zinc-900 dark:text-white tracking-tight truncate">{{ item.product_name }}</span>
                                                <span class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mt-1">{{ item.product_code }}</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-center font-bold text-zinc-700 dark:text-zinc-300">
                                            {{ formatNumber(item.quantity) }}
                                        </td>
                                        <td class="px-5 py-4 text-right font-bold text-zinc-600 dark:text-zinc-400">
                                            Bs. {{ formatNumber(item.unit_price) }}
                                        </td>
                                        <td class="px-5 py-4 text-right font-black text-zinc-900 dark:text-white text-base tracking-tighter">
                                            Bs. {{ formatNumber(item.subtotal) }}
                                        </td>
                                    </tr>
                                    <tr v-if="details.length === 0 && !loading">
                                        <td colspan="4" class="px-5 py-12 text-center text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-widest text-xs">
                                            No se encontraron productos
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-700">
                                    <tr>
                                        <td colspan="3" class="px-5 py-5 text-right text-zinc-600 dark:text-zinc-400 font-black uppercase text-[10px] tracking-widest">
                                            Total General de la Compra
                                        </td>
                                        <td class="px-5 py-5 text-right text-indigo-700 dark:text-indigo-400 font-black text-2xl tracking-tighter">
                                            Bs. {{ formatNumber(record.total) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Pagination Controls -->
                        <div v-if="paginationMeta.last_page > 1" class="px-5 py-3 bg-zinc-50/50 dark:bg-zinc-900/30 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                            <span class="text-[10px] font-black text-zinc-400 uppercase tracking-widest">
                                Página {{ paginationMeta.current_page }} de {{ paginationMeta.last_page }} ({{ paginationMeta.total }} items)
                            </span>
                            <div class="flex gap-2">
                                <button @click="fetchDetails(paginationMeta.current_page - 1)" 
                                        :disabled="paginationMeta.current_page === 1 || loading"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl border border-zinc-200 dark:border-zinc-700 hover:bg-white dark:hover:bg-zinc-800 disabled:opacity-30 disabled:pointer-events-none transition-all active:scale-95 shadow-sm">
                                    <span class="material-symbols-outlined text-xl">chevron_left</span>
                                </button>
                                <button @click="fetchDetails(paginationMeta.current_page + 1)" 
                                        :disabled="paginationMeta.current_page === paginationMeta.last_page || loading"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl border border-zinc-200 dark:border-zinc-700 hover:bg-white dark:hover:bg-zinc-800 disabled:opacity-30 disabled:pointer-events-none transition-all active:scale-95 shadow-sm">
                                    <span class="material-symbols-outlined text-xl">chevron_right</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button @click="closeModal"
                                class="px-8 py-3 text-[10px] font-black text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition-all border border-transparent hover:border-zinc-200 dark:hover:border-zinc-600 uppercase tracking-[0.2em] active:scale-95">
                            Cerrar Ventana
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overlay -->
        <div @click="closeModal" class="fixed inset-0 z-[-1]"></div>
    </div>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
