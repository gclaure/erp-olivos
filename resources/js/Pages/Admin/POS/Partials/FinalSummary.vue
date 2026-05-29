<script setup>
import { computed } from 'vue';

const props = defineProps({
    grossSubtotal: {
        type: Number,
        default: 0
    },
    totalDiscounts: {
        type: Number,
        default: 0
    },
    finalTotal: {
        type: Number,
        default: 0
    },
    deliveryCost: {
        type: Number,
        default: 0
    },
    deliveryMode: {
        type: String,
        default: 'venta_directa'
    },
    operationType: {
        type: String,
        default: 'sale'
    },
    paymentType: {
        type: String,
        default: 'efectivo'
    },
    downPayment: {
        type: Number,
        default: 0
    }
});

const formatMoney = (val) => {
    return parseFloat(val || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const hasShipping = computed(() => 
    (props.deliveryMode === 'envio_domicilio' || props.deliveryMode === 'envio_encomienda') && 
    props.deliveryCost > 0
);

const isCredit = computed(() => props.paymentType === 'credito' && props.operationType === 'sale');
const pendingBalance = computed(() => Math.max(0, props.finalTotal - (props.downPayment || 0)));
const displayTotal = computed(() => isCredit.value ? (props.downPayment || 0) : props.finalTotal);

</script>

<template>
    <div class="relative overflow-hidden bg-zinc-900 dark:bg-black rounded-3xl border border-zinc-800 shadow-2xl transition-all duration-500 group">
        <!-- Abstract Background Glow -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-emerald-500/10 rounded-full blur-[80px] group-hover:bg-emerald-500/20 transition-colors duration-1000"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-blue-500/10 rounded-full blur-[80px] group-hover:bg-blue-500/20 transition-colors duration-1000"></div>

        <div class="relative z-10 p-6 sm:p-8 space-y-5">
            <!-- Items Subtotal -->
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-1.5 h-6 bg-zinc-700 rounded-full"></div>
                    <span class="text-zinc-400 font-black text-[11px] uppercase tracking-[0.2em]">Subtotal Productos</span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-white font-black text-xl tracking-tighter">{{ formatMoney(grossSubtotal) }}</span>
                    <span class="text-[10px] font-black text-zinc-500">Bs.</span>
                </div>
            </div>

            <!-- Discounts Breakdown -->
            <div v-if="totalDiscounts > 0" class="flex justify-between items-center animate-in slide-in-from-right-4 duration-300">
                <div class="flex items-center gap-3">
                    <div class="w-1.5 h-6 bg-rose-500/50 rounded-full"></div>
                    <span class="text-rose-500 font-black text-[11px] uppercase tracking-[0.2em]">Descuentos Totales</span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-rose-500 font-black text-xl tracking-tighter">- {{ formatMoney(totalDiscounts) }}</span>
                    <span class="text-[10px] font-black text-rose-500/50">Bs.</span>
                </div>
            </div>

            <!-- Shipping Cost -->
            <div v-if="hasShipping" class="flex justify-between items-center animate-in slide-in-from-right-4 duration-300">
                <div class="flex items-center gap-3">
                    <div class="w-1.5 h-6 bg-blue-500/50 rounded-full"></div>
                    <span class="text-blue-400 font-black text-[11px] uppercase tracking-[0.2em]">Costo de Envío</span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-blue-400 font-black text-xl tracking-tighter">+ {{ formatMoney(deliveryCost) }}</span>
                    <span class="text-[10px] font-black text-blue-400/50">Bs.</span>
                </div>
            </div>

            <!-- Credit Breakdown if applicable -->
            <div v-if="isCredit" class="space-y-3 animate-in slide-in-from-right-4 duration-300">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 bg-amber-500/50 rounded-full"></div>
                        <span class="text-amber-500 font-black text-[11px] uppercase tracking-[0.2em]">Total de la Venta</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-white font-black text-xl tracking-tighter">{{ formatMoney(finalTotal) }}</span>
                        <span class="text-[10px] font-black text-zinc-500">Bs.</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 bg-orange-500/50 rounded-full"></div>
                        <span class="text-orange-400 font-black text-[11px] uppercase tracking-[0.2em]">Saldo a Crédito</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-orange-400 font-black text-xl tracking-tighter">- {{ formatMoney(pendingBalance) }}</span>
                        <span class="text-[10px] font-black text-orange-400/50">Bs.</span>
                    </div>
                </div>
            </div>

            <!-- Decorative Divider -->
            <div class="relative py-2">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-zinc-800"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-zinc-900 dark:bg-black px-4 text-[10px] font-black text-zinc-600 uppercase tracking-widest">Resumen Final</span>
                </div>
            </div>

            <!-- Grand Total -->
            <div class="flex justify-between items-end pt-2">
                <div class="space-y-1">
                    <span class="block text-emerald-500/50 font-black text-[9px] uppercase tracking-[0.3em] leading-none">
                        {{ operationType === 'quotation' ? 'Monto Estimado' : (isCredit ? 'Inicial a Pagar' : 'Importe Total') }}
                    </span>
                    <h4 class="text-white font-black text-2xl sm:text-3xl tracking-tighter uppercase leading-none">
                        {{ operationType === 'quotation' ? 'TOTAL COTIZADO:' : (isCredit ? 'ADELANTO:' : 'A PAGAR:') }}
                    </h4>
                </div>
                <div class="text-right">
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl sm:text-5xl font-black text-emerald-400 tracking-tighter drop-shadow-[0_0_20px_rgba(52,211,153,0.3)]">
                            {{ formatMoney(displayTotal) }}
                        </span>
                        <span class="text-lg font-black text-emerald-500/50 tracking-tighter">Bs.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inner Border for Depth -->
        <div class="absolute inset-0 border border-white/5 rounded-3xl pointer-events-none"></div>
    </div>
</template>

<style scoped>
.animate-in {
    animation-fill-mode: forwards;
}

@keyframes slide-in-right {
    from { transform: translateX(20px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.slide-in-from-right-4 {
    animation: slide-in-right 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
