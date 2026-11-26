<script setup>
defineProps({
    modifications: {
        type: Array,
        default: () => [],
    },
});

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'pendiente':
            return 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50';
        case 'aprobada':
            return 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/50';
        case 'aplicada':
            return 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50';
        case 'rechazada':
        case 'cancelada':
            return 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50';
        default:
            return 'bg-zinc-100 dark:bg-secondary-800 text-zinc-700 dark:text-secondary-300';
    }
};
</script>

<template>
    <div
        v-if="modifications.length > 0"
        class="bg-white dark:bg-secondary-800 rounded-3xl border border-zinc-200/80 dark:border-secondary-700 shadow-sm overflow-hidden"
    >
        <div class="px-5 py-4 border-b border-zinc-100 dark:border-secondary-700 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg text-primary-600 dark:text-primary-400">history</span>
                <h3 class="text-xs font-black text-zinc-900 dark:text-white uppercase tracking-widest">
                    Historial Inmutable de Modificaciones de Detalle
                </h3>
            </div>
            <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black font-mono bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 border border-primary-200/60 dark:border-primary-800/40">
                {{ modifications.length }} {{ modifications.length === 1 ? 'Registro' : 'Registros' }}
            </span>
        </div>

        <div class="divide-y divide-zinc-100 dark:divide-secondary-700/60">
            <div v-for="mod in modifications" :key="mod.id" class="p-4 sm:p-5 space-y-2.5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="font-black text-zinc-700 dark:text-secondary-200 uppercase">
                            {{ mod.from_product?.name }} ({{ mod.original_quantity }} {{ mod.from_product?.unit_of_measure }})
                        </span>
                        <span class="material-symbols-outlined text-sm text-primary-600 dark:text-primary-400">arrow_forward</span>
                        <span class="font-black text-primary-700 dark:text-primary-300 uppercase">
                            {{ mod.to_product?.name }} ({{ mod.requested_quantity }} {{ mod.to_product?.unit_of_measure }})
                        </span>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider self-start sm:self-center" :class="getStatusBadgeClass(mod.status)">
                        {{ mod.status_label }}
                    </span>
                </div>

                <p class="text-xs text-zinc-600 dark:text-secondary-300 italic">
                    Motivo: "{{ mod.reason }}"
                </p>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] font-semibold text-zinc-500 dark:text-secondary-400">
                    <span>Solicitó: <strong class="text-zinc-800 dark:text-secondary-200">{{ mod.requested_by_user?.name }}</strong> ({{ mod.created_at_formatted }})</span>
                    <span v-if="mod.quantity_dispatched_before_modification > 0">
                        Físico previo: {{ mod.quantity_prepared }} almacén / {{ mod.quantity_physically_delivered }} entregadas
                    </span>
                    <span v-if="mod.resolved_by_user">
                        Resolvió: <strong class="text-zinc-800 dark:text-secondary-200">{{ mod.resolved_by_user.name }}</strong> ({{ mod.resolved_at_formatted }})
                    </span>
                    <span v-if="mod.cancelled_by_user">
                        Canceló: <strong class="text-rose-600 dark:text-rose-400">{{ mod.cancelled_by_user.name }}</strong> ({{ mod.cancelled_at_formatted }})
                    </span>
                </div>

                <p v-if="mod.resolution_notes" class="text-[11px] font-semibold text-zinc-700 dark:text-secondary-200 bg-zinc-50 dark:bg-secondary-900/50 px-3 py-1.5 rounded-xl">
                    Nota de resolución: {{ mod.resolution_notes }}
                </p>

                <p v-if="mod.cancellation_reason" class="text-[11px] font-semibold text-rose-700 dark:text-rose-300 bg-rose-50/70 dark:bg-rose-950/30 px-3 py-1.5 rounded-xl">
                    Motivo de cancelación administrativa: {{ mod.cancellation_reason }}
                </p>

                <div v-if="mod.returns?.length" class="flex flex-wrap gap-1.5 pt-1">
                    <span
                        v-for="ret in mod.returns"
                        :key="ret.id"
                        class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-800/40"
                    >
                        Devolución: {{ ret.returned_quantity }} {{ mod.from_product?.unit_of_measure }} ({{ ret.returned_by_user?.name }}, {{ ret.returned_at_formatted }})
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
