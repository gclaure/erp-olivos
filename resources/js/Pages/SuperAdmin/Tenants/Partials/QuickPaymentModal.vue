<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    tenant: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    amount: '',
    payment_method: 'TRANSFER',
    reference: '',
    notes: '',
    paid_months: 1,
    gift_months: 0,
});

const submit = () => {
    form.post(route('superadmin.tenants.store-payment', props.tenant.id), {
        onSuccess: () => {
            closeModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Pago registrado correctamente',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const closeModal = () => {
    emit('close');
    form.reset();
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-start sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-zinc-200 dark:border-zinc-800">
                <!-- Header -->
                <div class="bg-white dark:bg-zinc-900 px-6 py-5 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0 border border-emerald-100 dark:border-emerald-800/30">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">Registrar Pago Rápido</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 font-medium">Empresa: <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ tenant?.name }}</span></p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Monto (Bs.)</label>
                            <input v-model="form.amount" type="number" step="0.01" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Método</label>
                            <select v-model="form.payment_method" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                                <option value="TRANSFER">Transferencia</option>
                                <option value="CASH">Efectivo</option>
                                <option value="QR">QR</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Meses Pagados</label>
                            <input v-model="form.paid_months" type="number" min="1" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Meses Regalo</label>
                            <input v-model="form.gift_months" type="number" min="0" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Referencia</label>
                        <input v-model="form.reference" type="text" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500" placeholder="Nro de comprobante">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Notas</label>
                        <textarea v-model="form.notes" rows="2" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500" placeholder="Información adicional..."></textarea>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-2">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-all">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="px-8 py-2.5 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-500/25 disabled:opacity-50">Registrar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
