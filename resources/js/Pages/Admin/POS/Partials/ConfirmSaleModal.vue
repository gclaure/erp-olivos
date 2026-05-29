<script setup>
import { ref, computed, watch } from 'vue';
import FinalSummary from './FinalSummary.vue';
import ClientSection from './ClientSection.vue';
import PaymentSection from './PaymentSection.vue';
import DeliverySection from './DeliverySection.vue';
import PaymentStatus from './PaymentStatus.vue';

const props = defineProps({
    show: Boolean,
    grossSubtotal: Number,
    totalDiscounts: Number,
    finalTotal: Number,
    processing: Boolean,
    form: Object,
    clients: Array,
    selectedClient: Object,
    loadingClients: Boolean,
    clientSearch: String,
    shippingHistory: Object,
    isFixedDiscount: Boolean,
    globalDiscount: [Number, String],
    defaultClient: Object,
    permissions: Array,
});

const emit = defineEmits([
    'close', 
    'confirm', 
    'update:clientSearch', 
    'open-client-modal',
    'update:global-discount',
    'client-updated'
]);

const amountReceived = ref(0);
const operationType = computed(() => props.form.operation_type);

// Reset local state when modal closes
watch(() => props.show, (val) => {
    if (!val) {
        amountReceived.value = 0;
    }
});

const selectedClientObj = computed(() => {
    if (props.selectedClient) return props.selectedClient;
    return props.clients.find(c => c.id === props.form.client_id);
});

const creditAnalysis = computed(() => {
    const client = selectedClientObj.value;
    if (!client) return { exceeded: false };

    const limit = parseFloat(client.credit_limit) || 0;
    const balance = parseFloat(client.current_balance) || 0;
    const creditUsed = Math.max(0, props.finalTotal - (parseFloat(props.form.down_payment) || 0));
    const newBalance = balance + creditUsed;
    
    const unlimited = limit === 0;
    const available = unlimited ? 0 : limit - newBalance;
    return { exceeded: !unlimited && available < 0 };
});

const canConfirm = computed(() => {
    if (props.processing || !props.form.client_id) return false;
    
    if (operationType.value === 'sale') {
        if (props.form.payment_type === 'credito' && creditAnalysis.value.exceeded) return false;
    }
    
    return true;
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[1000] flex sm:items-center justify-center p-0 sm:p-4 animate-in fade-in duration-300">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('close')"></div>
            
            <div class="relative bg-white dark:bg-secondary-800 sm:rounded-2xl shadow-2xl w-full h-full sm:h-auto sm:max-w-2xl flex flex-col sm:max-h-[90vh] border border-zinc-200 dark:border-secondary-700 animate-in zoom-in-95 duration-300">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 border-b border-zinc-200 dark:border-secondary-700 flex-shrink-0 bg-white dark:bg-secondary-800 sm:rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div :class="operationType === 'sale' ? 'bg-emerald-500' : 'bg-blue-500'" class="w-8 h-8 rounded-xl flex items-center justify-center text-white shadow-lg">
                            <svg v-if="operationType === 'sale'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <h3 class="text-xs font-black uppercase tracking-widest text-zinc-800 dark:text-white">
                            {{ operationType === 'sale' ? 'Confirmar Venta' : 'Generar Proforma' }}
                        </h3>
                    </div>
                    <button @click="$emit('close')" class="text-zinc-300 dark:text-secondary-500 hover:text-rose-500 dark:hover:text-rose-400 transition-all w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50 dark:hover:bg-rose-500/10">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-4 sm:px-6 py-5 space-y-5 flex-1 overflow-y-auto custom-scrollbar bg-white dark:bg-secondary-800 transition-colors">
                    
                    <!-- CLIENTE -->
                    <ClientSection 
                        :form="form"
                        :clients="clients"
                        :loading-clients="loadingClients"
                        :client-search="clientSearch"
                        :selected-client="selectedClient"
                        :default-client="defaultClient"
                        @update:client-search="v => $emit('update:clientSearch', v)"
                        @open-client-modal="$emit('open-client-modal')"
                        @client-updated="v => $emit('client-updated', v)"
                    />

                    <!-- PAGO Y CRÉDITO -->
                    <PaymentSection 
                        :form="form"
                        :operation-type="operationType"
                        :is-fixed-discount="isFixedDiscount"
                        :global-discount="globalDiscount"
                        :final-total="finalTotal"
                        :selected-client="selectedClient"
                        :clients="clients"
                        :default-client="defaultClient"
                        @update:global-discount="v => $emit('update:global-discount', v)"
                    />

                    <!-- ENTREGA -->
                    <DeliverySection 
                        v-if="operationType === 'sale'"
                        :form="form"
                        :shipping-history="shippingHistory"
                        :default-client="defaultClient"
                        :permissions="permissions"
                    />

                    <!-- RESUMEN FINAL -->
                    <FinalSummary 
                        :gross-subtotal="grossSubtotal" 
                        :total-discounts="totalDiscounts"
                        :final-total="finalTotal"
                        :delivery-cost="parseFloat(form.delivery_cost)"
                        :delivery-mode="form.delivery_mode"
                        :operation-type="operationType"
                        :payment-type="form.payment_type"
                        :down-payment="parseFloat(form.down_payment) || 0"
                    />

                    <!-- PAGO Y CAMBIO -->
                    <PaymentStatus 
                        v-if="operationType === 'sale'"
                        :form="form"
                        :final-total="finalTotal"
                        :operation-type="operationType"
                        @update:amount-received="v => amountReceived = v"
                    />
                </div>

                <!-- Footer -->
                <div class="p-6 border-t border-zinc-100 dark:border-secondary-700 bg-zinc-50 dark:bg-secondary-900/50 flex items-center justify-end gap-3 sm:rounded-b-2xl transition-colors">
                    <button @click="$emit('close')" class="px-6 py-3 text-xs font-bold text-zinc-400 dark:text-secondary-500 uppercase hover:text-zinc-600 dark:hover:text-secondary-300 transition-colors">Cancelar</button>
                    
                    <button 
                        v-if="operationType === 'quotation'"
                        @click="$emit('confirm', { type: 'quotation' })"
                        :disabled="processing || !form.client_id"
                        class="px-8 py-3 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-xl font-bold text-xs uppercase disabled:opacity-50 transition-all shadow-lg shadow-blue-500/20"
                    >
                        Guardar Proforma
                    </button>

                    <button 
                        v-else
                        @click="$emit('confirm', { amount_received: amountReceived || finalTotal })"
                        :disabled="!canConfirm"
                        class="px-8 py-3 bg-emerald-600 dark:bg-emerald-500 hover:bg-emerald-700 dark:hover:bg-emerald-600 text-white rounded-xl font-bold text-xs uppercase disabled:opacity-50 transition-all shadow-lg shadow-emerald-500/20"
                    >
                        Confirmar Venta
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
