<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import Swal from 'sweetalert2';
import debounce from 'lodash/debounce';

const props = defineProps({
    payments: Object,
    filters: Object,
    tenants: Array,
    statusOptions: Array,
    methodOptions: Array
});

// Filtros
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const handleSearch = debounce(() => {
    router.get(route('superadmin.payments.index'), {
        search: search.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch([search, statusFilter], () => {
    handleSearch();
});

// Modal Registrar Pago
const showModal = ref(false);
const form = useForm({
    tenant_id: '',
    amount: '',
    method: 'TRANSFER',
    reference: '',
    notes: '',
    status: 'PAID'
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    form.post(route('superadmin.payments.store'), {
        onSuccess: () => {
            showModal.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: 'success',
                title: 'Pago registrado correctamente.'
            });
        }
    });
};

const markAsPaid = (payment) => {
    router.post(route('superadmin.payments.mark-as-paid', payment.id), {}, {
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: 'success',
                title: 'Pago marcado como pagado.'
            });
        }
    });
};

const confirmDelete = (payment) => {
    Swal.fire({
        title: '¿Eliminar este pago?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#71717a',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('superadmin.payments.destroy', payment.id), {
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        icon: 'success',
                        title: 'Pago eliminado.'
                    });
                }
            });
        }
    });
};
</script>

<template>
    <SuperAdminLayout>
        <Head title="Gestión de Pagos" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">Gestión de Pagos</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Registra y gestiona pagos de suscripciones</p>
            </div>
            <button @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Registrar Pago
            </button>
        </div>

        <!-- Filtros -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 mb-6">
            <div class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="relative sm:col-span-1 lg:col-span-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        <input v-model="search"
                               type="text"
                               placeholder="Buscar empresa..."
                               class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-gray-500 rounded-lg text-sm bg-white dark:bg-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors">
                    </div>
                    <select v-model="statusFilter" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-gray-500 rounded-lg text-sm bg-white dark:bg-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors">
                        <option value="">Todos los Estados</option>
                        <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-surface rounded-xl border border-zinc-200 dark:border-gray-600 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-gray-600 border-b border-zinc-200 dark:border-gray-500">
                            <th class="px-4 sm:px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Empresa</th>
                            <th class="px-4 sm:px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Monto</th>
                            <th class="hidden sm:table-cell px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Método</th>
                            <th class="hidden md:table-cell px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 sm:px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Estado</th>
                            <th class="px-4 sm:px-6 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-600">
                        <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-zinc-50 dark:hover:bg-gray-600 transition-colors">
                            <td class="px-4 sm:px-6 py-4">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ payment.tenant.name }}</p>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 sm:hidden">{{ payment.method_label }}</p>
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ payment.amount_formatted }}</p>
                                <p v-if="payment.reference" class="text-[10px] text-zinc-400 dark:text-zinc-500 truncate max-w-[120px]">Ref: {{ payment.reference }}</p>
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4 text-zinc-500 dark:text-zinc-400">{{ payment.method_label }}</td>
                            <td class="hidden md:table-cell px-6 py-4 text-zinc-500 dark:text-zinc-400">{{ payment.paid_at }}</td>
                            <td class="px-4 sm:px-6 py-4 text-center">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', 
                                    payment.status_color === 'emerald' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400' :
                                    payment.status_color === 'amber' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400' :
                                    payment.status_color === 'red' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400' :
                                    'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300']">
                                    {{ payment.status_label }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button v-if="payment.status === 'PENDING'"
                                            @click="markAsPaid(payment)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        <span class="hidden sm:inline">Pagado</span>
                                    </button>
                                    <button @click="confirmDelete(payment)"
                                            class="p-2 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="payments.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mb-3"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75m0 3v.75m0 3v.75m0 3V15m0 3v.75M5.25 4.5h13.5A2.25 2.25 0 0 1 21 6.75v7.5a2.25 2.25 0 0 1-2.25 2.25H5.25a2.25 2.25 0 0 1-2.25-2.25V6.75A2.25 2.25 0 0 1 5.25 4.5Z" /></svg>
                                    <p class="text-zinc-500 dark:text-zinc-400 font-medium">No hay pagos registrados</p>
                                    <p class="text-sm text-zinc-400 dark:text-zinc-500 mt-1">Registra un nuevo pago para comenzar</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="payments.meta && payments.meta.last_page > 1" class="px-6 py-4 border-t border-zinc-200 dark:border-gray-600 bg-zinc-50 dark:bg-gray-600">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Mostrando {{ payments.meta.from }} a {{ payments.meta.to }} de {{ payments.meta.total }} resultados
                    </p>
                    <div class="flex gap-2">
                        <Link v-for="link in payments.meta.links" :key="link.label"
                              :href="link.url || '#'"
                              v-html="link.label"
                              :class="['px-3 py-1 text-xs rounded-md border transition-colors', 
                                link.active ? 'bg-violet-600 text-white border-violet-600' : 'bg-white dark:bg-gray-700 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-gray-600 hover:bg-zinc-50 dark:hover:bg-gray-600',
                                !link.url ? 'opacity-50 cursor-not-allowed' : '']"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Registrar Pago -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200 text-left">
                <div class="p-6 border-b border-zinc-100 dark:border-gray-600">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75m0 3v.75m0 3v.75m0 3V15m0 3v.75M5.25 4.5h13.5A2.25 2.25 0 0 1 21 6.75v7.5a2.25 2.25 0 0 1-2.25 2.25H5.25a2.25 2.25 0 0 1-2.25-2.25V6.75A2.25 2.25 0 0 1 5.25 4.5Z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">Registrar Pago</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-normal mt-1">Registra un pago de suscripción</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Empresa *</label>
                        <select v-model="form.tenant_id" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                            <option value="">Seleccionar empresa...</option>
                            <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-if="form.errors.tenant_id" class="text-xs text-rose-500 mt-1">{{ form.errors.tenant_id }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Monto (Bs.) *</label>
                            <input v-model="form.amount" type="number" step="0.01" min="0.01" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                            <p v-if="form.errors.amount" class="text-xs text-rose-500 mt-1">{{ form.errors.amount }}</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Método *</label>
                            <select v-model="form.method" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="CASH">Efectivo</option>
                                <option value="TRANSFER">Transferencia</option>
                                <option value="QR">QR</option>
                                <option value="CARD">Tarjeta</option>
                                <option value="OTHER">Otro</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Referencia</label>
                            <input v-model="form.reference" type="text" placeholder="Nro. comprobante" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Estado *</label>
                            <select v-model="form.status" class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="PAID">Pagado</option>
                                <option value="PENDING">Pendiente</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Notas (opcional)</label>
                        <textarea v-model="form.notes" rows="3" placeholder="Notas adicionales..." class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>
                </div>
                <div class="p-6 bg-zinc-50/50 dark:bg-gray-700/50 border-t border-zinc-100 dark:border-gray-600 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                    <button @click="showModal = false" class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-zinc-500 hover:bg-zinc-100 dark:hover:bg-gray-600 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button @click="submit" 
                            :disabled="form.processing"
                            class="w-full sm:w-auto px-8 py-2.5 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 transition-all disabled:opacity-50">
                        Registrar Pago
                    </button>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
