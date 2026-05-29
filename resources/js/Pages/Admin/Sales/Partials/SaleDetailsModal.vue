<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    sale: Object
});

const emit = defineEmits(['close', 'print']);

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-900/40 backdrop-blur-sm overflow-y-auto custom-scrollbar">
        <div class="bg-white dark:bg-gray-800 w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden my-auto border border-zinc-100 dark:border-gray-700">
            <div v-if="sale">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-gray-600 flex items-center gap-4 text-left">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">
                            Detalle de Venta #{{ sale.formatted_number }}
                            <span v-if="!sale.is_active" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400 uppercase">
                                Anulada
                            </span>
                        </h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 font-normal">Información completa de la transacción</p>
                    </div>
                    <button @click="$emit('close')" class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 overflow-y-auto max-h-[70vh] custom-scrollbar">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div>
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Cliente</p>
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200 uppercase">{{ sale.client?.name || 'S/N' }}</p>
                            <p v-if="sale.client?.document_number" class="text-[10px] text-zinc-500 dark:text-zinc-400 font-mono mt-0.5">
                                {{ sale.client.document_type }}: {{ sale.client.document_number }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Almacén</p>
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200 uppercase">{{ sale.warehouse?.name || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Caja</p>
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200 uppercase">{{ sale.point_of_sale?.name || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-1">Fecha</p>
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200 capitalize italic">{{ sale.formatted_date }}</p>
                        </div>
                    </div>

                    <div class="border border-zinc-200 dark:border-gray-600 rounded-xl overflow-hidden mb-6">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                                <tr>
                                    <th class="px-4 py-2 text-left font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Producto</th>
                                    <th class="px-4 py-2 text-center font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Cant.</th>
                                    <th class="px-4 py-2 text-right font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">P. Unit.</th>
                                    <th class="px-4 py-2 text-right font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Desc.</th>
                                    <th class="px-4 py-2 text-right font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                                <tr v-for="detail in sale.details" :key="detail.id">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-zinc-900 dark:text-white">{{ detail.product_name }}</div>
                                        <div class="text-[10px] text-zinc-400 dark:text-zinc-500 font-mono">{{ detail.product_code }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-zinc-700 dark:text-zinc-300 font-bold">
                                        {{ formatMoney(detail.quantity) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-zinc-700 dark:text-zinc-300">
                                        {{ formatMoney(detail.price) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-rose-500 dark:text-rose-400 font-medium">
                                        {{ formatMoney(detail.discount) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-zinc-900 dark:text-white font-bold">
                                        {{ formatMoney(detail.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-zinc-50 dark:bg-gray-600 border-t border-zinc-200 dark:border-gray-500">
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-right font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-tight">Subtotal:</td>
                                    <td class="px-4 py-2 text-right font-bold text-zinc-900 dark:text-white">{{ formatMoney(sale.subtotal) }} Bs.</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-right font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-tight">Descuento Global:</td>
                                    <td class="px-4 py-2 text-right font-bold text-rose-600 dark:text-rose-400">{{ formatMoney(sale.global_discount) }} Bs.</td>
                                </tr>
                                <tr class="bg-indigo-50/50 dark:bg-indigo-900/10">
                                    <td colspan="4" class="px-4 py-3 text-right font-black text-indigo-900 dark:text-indigo-300 uppercase tracking-widest text-base">Total Venta:</td>
                                    <td class="px-4 py-3 text-right font-bold text-indigo-700 dark:text-indigo-400 text-lg">{{ formatMoney(sale.total) }} Bs.</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div v-if="!sale.is_active && sale.reason_cancel" class="p-4 bg-rose-50 dark:bg-rose-900/30 rounded-xl border border-rose-100 dark:border-rose-900/50 mb-6">
                        <p class="text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-1">Motivo de Anulación</p>
                        <p class="text-sm text-rose-800 dark:text-rose-300 font-medium">{{ sale.reason_cancel }}</p>
                    </div>

                    <div v-if="sale.notes" class="p-4 bg-amber-50 dark:bg-amber-900/30 rounded-xl border border-amber-100 dark:border-amber-900/50 mb-6">
                        <p class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest mb-1">Notas / Observaciones</p>
                        <p class="text-sm text-amber-800 dark:text-amber-300 font-medium">{{ sale.notes }}</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 flex items-center justify-end gap-3 border-t border-zinc-100 bg-zinc-50/50 dark:bg-gray-700 dark:border-gray-600">
                    <button @click="$emit('close')" class="px-6 py-2 text-sm font-bold text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-gray-600 rounded-lg transition-colors">
                        Cerrar
                    </button>
                    <button @click="$emit('print', sale.id)" class="px-6 py-2 text-sm font-bold bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg transition-colors flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231a1.125 1.125 0 0 1-1.12-1.227L6.34 18m11.318-4.171a41.07 41.07 0 0 0-11.318 0m11.318 0c.232-.23.442-.47.63-.718m-11.948.718c-.23-.23-.442-.47-.63-.718m12.578-.625a1.875 1.875 0 0 0-1.875-1.875h-.375V4.312c0-.621-.504-1.125-1.125-1.125H8.938c-.621 0-1.125.504-1.125 1.125v5.063h-.375a1.875 1.875 0 0 0-1.875 1.875v.375c0 .156.015.31.044.458m12.166 0c.03-.149.044-.303.044-.458m-11.411 0c.03-.149.044-.303.044-.458" />
                        </svg>
                        Imprimir Factura
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
}
</style>
