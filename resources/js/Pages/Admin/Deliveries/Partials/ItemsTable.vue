<script setup>
const props = defineProps({
    details: Array,
});

const formatMoney = (val) => {
    return parseFloat(val).toFixed(2);
};
</script>

<template>
    <div class="space-y-2">
        <p class="text-[10px] font-bold text-zinc-400 dark:text-secondary-400 uppercase tracking-widest px-1 text-left">Items para Despacho</p>
        <div class="overflow-hidden rounded-xl border border-zinc-100 dark:border-secondary-600 bg-zinc-50/30 dark:bg-secondary-700/30">
            <div class="max-h-[400px] overflow-y-auto custom-scrollbar">
                <table class="w-full text-left text-[11px] bg-transparent border-collapse">
                    <thead class="bg-zinc-100 dark:bg-secondary-800 border-b border-zinc-100 dark:border-secondary-700 uppercase sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2.5 font-bold text-zinc-500 dark:text-secondary-400 tracking-widest">Descripción</th>
                            <th class="px-3 py-2.5 font-bold text-zinc-500 dark:text-secondary-400 tracking-widest text-right w-16">Cant.</th>
                            <th class="px-3 py-2.5 font-bold text-zinc-500 dark:text-secondary-400 tracking-widest text-right w-20">P. Unit.</th>
                            <th class="px-3 py-2.5 font-bold text-zinc-500 dark:text-secondary-400 tracking-widest text-right w-24">Descuento</th>
                            <th class="px-3 py-2.5 font-bold text-zinc-500 dark:text-secondary-400 tracking-widest text-right w-24">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-secondary-700/50 text-left">
                        <tr v-for="detail in details" :key="detail.id" class="dark:text-secondary-300 hover:bg-zinc-100/50 dark:hover:bg-secondary-700/50 transition-colors">
                            <td class="px-3 py-3 font-medium uppercase">
                                <div class="font-bold text-zinc-900 dark:text-white" :title="detail.product_name">
                                    {{ detail.product_name }}
                                </div>
                            </td>
                            <td class="px-3 py-3 font-bold text-zinc-900 dark:text-white text-right">
                                {{ detail.quantity }}
                            </td>
                            <td class="px-3 py-3 text-right text-zinc-600 dark:text-secondary-400">
                                {{ formatMoney(detail.unit_price) }}
                            </td>
                            <td class="px-3 py-3 text-right text-rose-500 font-medium">
                                -{{ formatMoney(detail.discount_amount) }}
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-zinc-900 dark:text-white">
                                {{ formatMoney(detail.subtotal) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
