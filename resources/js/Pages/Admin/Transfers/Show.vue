<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    transfer: Object,
    canApprove: Boolean,
    canDispatch: Boolean,
    canReceive: Boolean,
    canCancel: Boolean,
});

const showReceiveModal = ref(false);
const showDispatchModal = ref(false);
const showRejectModal = ref(false);
const rejectionReason = ref('');
const receivedQuantities = ref({});
const shippedQuantities = ref({});

// Inicializar cantidades
props.transfer.details.forEach(detail => {
    receivedQuantities.value[detail.id] = Number(detail.quantity_sent);
    shippedQuantities.value[detail.id] = Number(detail.quantity_requested);
});

const confirmDispatch = () => {
    router.post(route('admin.transfers.dispatch', props.transfer.id), {
        shipped_quantities: shippedQuantities.value
    }, {
        onSuccess: () => {
            showDispatchModal.value = false;
            Swal.fire('¡Despachado!', 'La transferencia ha sido despachada y el Kardex de salida registrado.', 'success');
        }
    });
};

const approve = () => {
    Swal.fire({
        title: '¿Aprobar Transferencia?',
        text: "La transferencia pasará a estado 'Aprobada' para que el origen pueda despachar.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.transfers.approve', props.transfer.id), {}, {
                onSuccess: () => {
                    Swal.fire('Éxito', 'Transferencia aprobada correctamente.', 'success');
                }
            });
        }
    });
};

const reject = () => {
    if (rejectionReason.value.length < 5) {
        Swal.fire('Error', 'Debe ingresar un motivo de rechazo válido (mín. 5 caracteres).', 'error');
        return;
    }

    router.post(route('admin.transfers.reject', props.transfer.id), {
        reason: rejectionReason.value
    }, {
        onSuccess: () => {
            showRejectModal.value = false;
            Swal.fire('Rechazada', 'La transferencia ha sido rechazada.', 'success');
        }
    });
};

const cancel = () => {
    Swal.fire({
        title: '¿Cancelar Transferencia?',
        text: "¿Estás seguro que deseas anular esta solicitud?",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.transfers.cancel', props.transfer.id), {}, {
                onSuccess: () => {
                    Swal.fire('Cancelada', 'Transferencia anulada.', 'success');
                }
            });
        }
    });
};

const confirmReceive = () => {
    router.post(route('admin.transfers.receive', props.transfer.id), {
        received_quantities: receivedQuantities.value
    }, {
        onSuccess: () => {
            showReceiveModal.value = false;
            Swal.fire('¡Recibido!', 'El stock ha sido incrementado en el destino y el Kardex de entrada registrado.', 'success');
        }
    });
};

const grandTotal = computed(() => {
    return props.transfer.details.reduce((acc, detail) => {
        return acc + (detail.quantity_sent * detail.cost);
    }, 0);
});

const getTimelineStatus = (status) => {
    const statusOrder = ['DRAFT', 'PENDING', 'APPROVED', 'IN_TRANSIT', 'COMPLETED'];
    const currentIndex = statusOrder.indexOf(props.transfer.status.toUpperCase());
    const stepIndex = statusOrder.indexOf(status.toUpperCase());
    
    if (props.transfer.status === 'REJECTED' || props.transfer.status === 'CANCELLED') {
        return false;
    }
    
    return currentIndex >= stepIndex;
};

const getProgressBarColor = (detailId) => {
    const rec = Number(receivedQuantities.value[detailId] || 0);
    const sent = Number(props.transfer.details.find(d => d.id === detailId).quantity_sent);
    if (rec === sent) return 'bg-emerald-500';
    if (rec < sent) return 'bg-amber-500';
    return 'bg-rose-500';
};

const getProgressWidth = (detailId) => {
    const rec = Number(receivedQuantities.value[detailId] || 0);
    const sent = Number(props.transfer.details.find(d => d.id === detailId).quantity_sent);
    return sent > 0 ? Math.min(100, (rec / sent) * 100) : 0;
};
</script>

<template>
    <Head :title="'Detalle de Transferencia ' + (transfer.number || '#DRAFT')" />

    <AdminLayout>
        <template #breadcrumbs>
            <div class="flex items-center gap-2 text-sm text-zinc-500">
                <Link :href="route('admin.dashboard')" class="hover:text-violet-600 transition-colors">Dashboard</Link>
                <span>/</span>
                <Link :href="route('admin.transfers.index')" class="hover:text-violet-600 transition-colors">Transferencias</Link>
                <span>/</span>
                <span class="text-zinc-900 dark:text-zinc-100 font-medium">Detalle</span>
            </div>
        </template>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.transfers.index')" 
                      class="p-2 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-all">
                    <span class="material-symbols-outlined text-[24px]">arrow_back</span>
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Transferencia {{ transfer.number || '#DRAFT' }}</h1>
                        <span :class="[
                            'px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider',
                            transfer.status === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                            transfer.status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' :
                            transfer.status === 'approved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                            transfer.status === 'draft' ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400' :
                            transfer.status === 'in_transit' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400' :
                            'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                        ]">
                            {{ transfer.status }}
                        </span>
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 font-medium italic">
                        Registrada el <span class="capitalize">{{ new Date(transfer.created_at).toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</span> 
                        a las {{ new Date(transfer.created_at).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) }} por {{ transfer.user.name }}
                    </p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <Link v-if="transfer.status === 'draft'" 
                      :href="route('admin.transfers.edit', transfer.id)" 
                      class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-violet-500/25">
                    <span class="material-symbols-outlined text-[20px]">edit</span>
                    Editar Borrador
                </Link>

                <template v-if="transfer.status === 'pending'">
                    <button v-if="canApprove" @click="approve"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-emerald-500/25">
                        <span class="material-symbols-outlined text-[20px]">check</span>
                        Aprobar Solicitud
                    </button>
                    <button v-if="canApprove" @click="showRejectModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-zinc-900 border border-rose-200 dark:border-rose-900/30 text-rose-600 dark:text-rose-400 text-sm font-bold rounded-xl hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all">
                        <span class="material-symbols-outlined text-[20px]">cancel</span>
                        Rechazar
                    </button>
                    <button v-if="canCancel" @click="cancel"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-rose-500/25">
                        <span class="material-symbols-outlined text-[20px]">delete</span>
                        Anular Solicitud
                    </button>
                </template>

                <button v-if="canDispatch" @click="showDispatchModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-violet-500/25">
                    <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                    Despachar Productos
                </button>

                <button v-if="canReceive" @click="showReceiveModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-emerald-500/25">
                    <span class="material-symbols-outlined text-[20px]">download</span>
                    Confirmar Recepción
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <div class="lg:col-span-3 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Almacén Origen -->
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 p-8 shadow-sm">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500 dark:text-zinc-400 border border-zinc-200/50 dark:border-zinc-700/50">
                                <span class="material-symbols-outlined text-[28px]">home</span>
                            </div>
                            <div>
                                <p class="text-[11px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Almacén Origen</p>
                                <p class="text-xl font-black text-zinc-900 dark:text-white">{{ transfer.origin_warehouse.name }}</p>
                            </div>
                        </div>
                        <div class="space-y-3 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400">Sucursal:</span>
                                <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ transfer.origin_branch.name }}</span>
                            </div>
                            <div v-if="transfer.shipped_at" class="flex justify-between items-center text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400">Despachado:</span>
                                <span class="font-bold text-zinc-900 dark:text-zinc-100 capitalize">{{ new Date(transfer.shipped_at).toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' }) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Almacén Destino -->
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 p-8 shadow-sm">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/30">
                                <span class="material-symbols-outlined text-[28px]">location_on</span>
                            </div>
                            <div>
                                <p class="text-[11px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Almacén Destino</p>
                                <p class="text-xl font-black text-zinc-900 dark:text-white">{{ transfer.destination_warehouse.name }}</p>
                            </div>
                        </div>
                        <div class="space-y-3 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400">Sucursal:</span>
                                <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ transfer.destination_branch.name }}</span>
                            </div>
                            <div v-if="transfer.received_at" class="flex justify-between items-center text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400">Recibido:</span>
                                <span class="font-bold text-zinc-900 dark:text-zinc-100 capitalize">{{ new Date(transfer.received_at).toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' }) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Productos -->
                <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden font-sans">
                    <div class="px-8 py-6 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
                        <h2 class="font-black text-zinc-900 dark:text-white tracking-tight uppercase text-sm">Productos en la transferencia</h2>
                        <span class="px-3 py-1 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 text-[10px] font-bold text-zinc-500 uppercase tracking-widest">{{ transfer.details.length }} Ítems</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-zinc-50/30 dark:bg-zinc-800/20 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-widest text-[10px] border-b border-zinc-100 dark:border-zinc-800">
                                    <th class="px-8 py-4 text-left">Producto</th>
                                    <th class="px-8 py-4 text-center">Solicitada</th>
                                    <th class="px-8 py-4 text-center">Despachada</th>
                                    <th class="px-8 py-4 text-center">Recibida</th>
                                    <th class="px-8 py-4 text-right">Costo Unit.</th>
                                    <th class="px-8 py-4 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <tr v-for="detail in transfer.details" :key="detail.id"
                                    class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-zinc-900 dark:text-white">{{ detail.product.name }}</span>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ detail.product.code }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-center font-medium text-zinc-500 dark:text-zinc-400 italic">{{ Number(detail.quantity_requested).toFixed(2) }}</td>
                                    <td class="px-8 py-5 text-center">
                                        <span v-if="['in_transit', 'completed'].includes(transfer.status)" :class="[
                                            'font-bold',
                                            Number(detail.quantity_sent) < Number(detail.quantity_requested) ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-white'
                                        ]">
                                            {{ Number(detail.quantity_sent).toFixed(2) }}
                                        </span>
                                        <span v-else class="text-zinc-300 dark:text-zinc-700">—</span>
                                    </td>
                                    <td class="px-8 py-5 text-center">
                                        <span v-if="['completed'].includes(transfer.status)" :class="[
                                            'font-black',
                                            Number(detail.quantity_received) < Number(detail.quantity_sent) ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'
                                        ]">
                                            {{ Number(detail.quantity_received).toFixed(2) }}
                                        </span>
                                        <span v-else class="text-zinc-300 dark:text-zinc-700">—</span>
                                    </td>
                                    <td class="px-8 py-5 text-right text-zinc-500 dark:text-zinc-400 font-mono">{{ Number(detail.cost).toFixed(4) }}</td>
                                    <td class="px-8 py-5 text-right font-black text-zinc-900 dark:text-white">{{ Number(detail.quantity_sent * detail.cost).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-zinc-50/50 dark:bg-zinc-800/50 border-t-2 border-zinc-100 dark:border-zinc-800">
                                    <td colspan="5" class="px-8 py-6 text-right font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest text-[11px]">Valor total de transferencia</td>
                                    <td class="px-8 py-6 text-right font-black text-xl text-violet-600 dark:text-white">Bs. {{ grandTotal.toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div v-if="transfer.notes" class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 p-8 shadow-sm">
                    <h2 class="text-[11px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">info</span>
                        Notas / Observaciones
                    </h2>
                    <p class="text-zinc-700 dark:text-zinc-300 leading-relaxed text-sm italic">"{{ transfer.notes }}"</p>
                </div>

                <!-- Historial de Auditoría -->
                <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 p-8 shadow-sm">
                    <h2 class="text-sm font-black text-zinc-900 dark:text-white mb-8 tracking-tight uppercase">Historial de Trazabilidad</h2>
                    <div class="space-y-6">
                        <div v-for="history in transfer.histories" :key="history.id" class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[16px] text-zinc-500">{{ 
                                    history.action === 'solicitud' ? 'add_circle' : 
                                    history.action === 'aprobacion' ? 'verified' : 
                                    history.action === 'rechazo' ? 'block' : 
                                    history.action === 'despacho' ? 'local_shipping' : 
                                    history.action === 'recepcion' ? 'download' : 'info'
                                }}</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-xs font-black uppercase text-zinc-900 dark:text-white tracking-wider">{{ history.action }}</p>
                                    <span class="text-[10px] text-zinc-400 font-bold">{{ new Date(history.created_at).toLocaleString('es-ES') }}</span>
                                </div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ history.metadata?.notes }}</p>
                                <p class="text-[10px] text-zinc-400 mt-1">Usuario: {{ history.user.name }} | IP: {{ history.ip_address }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <!-- Timeline Visual -->
                <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 p-8 shadow-sm">
                    <h2 class="text-sm font-black text-zinc-900 dark:text-white mb-8 tracking-tight">Línea de Vida</h2>
                    <div class="space-y-8 relative">
                        <div class="absolute left-3.5 top-2 bottom-2 w-0.5 bg-zinc-100 dark:bg-zinc-800"></div>

                        <div v-for="step in [
                            { label: 'Borrador', status: 'DRAFT' },
                            { label: 'Pendiente', status: 'PENDING' },
                            { label: 'Aprobado', status: 'APPROVED' },
                            { label: 'En Tránsito', status: 'IN_TRANSIT' },
                            { label: 'Completado', status: 'COMPLETED' }
                        ]" :key="step.status" class="flex items-center gap-6 relative z-10">
                            <div :class="[
                                'w-7 h-7 rounded-full flex items-center justify-center transition-all duration-500 border-4 shadow-sm',
                                getTimelineStatus(step.status) 
                                    ? 'bg-violet-600 border-violet-100 dark:border-violet-900/30 text-white' 
                                    : 'bg-white dark:bg-zinc-900 border-zinc-100 dark:border-zinc-800 text-zinc-300 dark:text-zinc-700'
                            ]">
                                <span v-if="getTimelineStatus(step.status)" class="material-symbols-outlined text-[14px]">check</span>
                                <div v-else class="w-1.5 h-1.5 rounded-full bg-zinc-300 dark:bg-zinc-700"></div>
                            </div>
                            <span :class="[
                                'text-xs font-black uppercase tracking-widest transition-colors duration-500',
                                getTimelineStatus(step.status) ? 'text-zinc-900 dark:text-white' : 'text-zinc-300 dark:text-zinc-600'
                            ]">{{ step.label }}</span>
                        </div>
                    </div>
                    
                    <div v-if="transfer.status === 'rejected'" class="mt-8 p-4 bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-900/30 rounded-2xl">
                        <p class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-2">Transferencia Rechazada</p>
                        <p class="text-xs text-rose-700 dark:text-rose-300 italic">"{{ transfer.rejection_reason }}"</p>
                    </div>
                </div>

                <!-- Audit Quick Trail -->
                <div class="bg-zinc-900 rounded-3xl p-8 shadow-2xl text-white border border-zinc-800">
                    <h2 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 mb-8 border-b border-zinc-800 pb-4">Responsabilidades</h2>
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="mt-1 p-1.5 bg-zinc-800 rounded-lg text-zinc-400">
                                <span class="material-symbols-outlined text-[16px]">person</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Solicitado por</p>
                                <p class="text-sm font-black">{{ transfer.user.name }}</p>
                            </div>
                        </div>

                        <div v-if="transfer.approver" class="flex items-start gap-4">
                            <div class="mt-1 p-1.5 bg-emerald-900/30 rounded-lg text-emerald-400 border border-emerald-500/20">
                                <span class="material-symbols-outlined text-[16px]">verified</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Aprobado por</p>
                                <p class="text-sm font-black text-emerald-400">{{ transfer.approver.name }}</p>
                            </div>
                        </div>

                        <div v-if="transfer.shipper" class="flex items-start gap-4">
                            <div class="mt-1 p-1.5 bg-violet-900/30 rounded-lg text-violet-400 border border-violet-500/20">
                                <span class="material-symbols-outlined text-[16px]">local_shipping</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Despachado por</p>
                                <p class="text-sm font-black">{{ transfer.shipper.name }}</p>
                            </div>
                        </div>

                        <div v-if="transfer.receiver" class="flex items-start gap-4">
                            <div class="mt-1 p-1.5 bg-emerald-900/30 rounded-lg text-emerald-400 border border-emerald-500/20">
                                <span class="material-symbols-outlined text-[16px]">download</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Recibido por</p>
                                <p class="text-sm font-black">{{ transfer.receiver.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Despacho -->
        <div v-if="showDispatchModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/80 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden border border-zinc-200 dark:border-zinc-800 animate-in zoom-in-95 duration-200">
                <div class="p-8 bg-zinc-50 dark:bg-zinc-950/50 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 w-12 h-12 rounded-2xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-800/30 shadow-sm">
                            <span class="material-symbols-outlined text-[28px]">local_shipping</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-zinc-900 dark:text-white leading-none tracking-tight uppercase">Proceso de Despacho</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-3 leading-relaxed font-medium">
                                Ingrese las cantidades que está enviando físicamente. 
                                <span class="text-violet-700 dark:text-violet-400 font-black italic">Se descontará este stock del almacén origen.</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="max-h-[50vh] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-white dark:bg-zinc-900 text-zinc-400 dark:text-zinc-600 font-black border-b border-zinc-100 dark:border-zinc-800 uppercase tracking-[0.15em] text-[9px]">
                            <tr>
                                <th class="px-8 py-5 text-left">Producto / Solicitado</th>
                                <th class="px-8 py-5 text-right w-44">Cantidad a Despachar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800">
                            <tr v-for="detail in transfer.details" :key="detail.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="font-black text-zinc-900 dark:text-white tracking-tight text-base">{{ detail.product.name }}</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[10px] text-zinc-400 dark:text-zinc-600 font-mono font-bold tracking-widest">{{ detail.product.code }}</span>
                                            <span class="w-1 h-1 rounded-full bg-zinc-200 dark:bg-zinc-700"></span>
                                            <span class="text-[11px] font-black text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/20 px-2 py-0.5 rounded-lg">Solicitado: {{ Number(detail.quantity_sent).toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col items-end gap-3">
                                        <div class="relative w-32">
                                            <input v-model="shippedQuantities[detail.id]" type="number" step="0.0001"
                                                   class="w-full pl-3 pr-10 py-2.5 text-right font-black font-mono bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl text-zinc-900 dark:text-white focus:ring-2 focus:ring-violet-500 transition-all outline-none" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-zinc-400 uppercase tracking-widest pointer-events-none">und</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-8 border-t border-zinc-100 dark:border-zinc-800 flex flex-col sm:flex-row justify-end items-center gap-4 bg-zinc-50/50 dark:bg-zinc-950/50">
                    <button @click="showDispatchModal = false" class="w-full sm:w-auto px-6 py-3 text-zinc-500 font-bold hover:text-zinc-700 transition-colors">
                        Cancelar
                    </button>
                    <button @click="confirmDispatch"
                            class="w-full sm:w-auto flex items-center justify-center gap-2 px-8 py-3 bg-violet-600 hover:bg-violet-700 text-white text-sm font-black rounded-xl transition-all shadow-lg shadow-violet-500/25">
                        <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                        Confirmar Despacho
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Recepción -->
        <div v-if="showReceiveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/80 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden border border-zinc-200 dark:border-zinc-800 animate-in zoom-in-95 duration-200">
                <div class="p-8 bg-zinc-50 dark:bg-zinc-950/50 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/30 shadow-sm">
                            <span class="material-symbols-outlined text-[28px]">download</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-zinc-900 dark:text-white leading-none tracking-tight uppercase">Proceso de Recepción</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-3 leading-relaxed font-medium">
                                Verifique físicamente los productos. 
                                <span class="text-emerald-700 dark:text-emerald-400 font-black italic">Confirme las cantidades que realmente llegaron.</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="max-h-[50vh] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-white dark:bg-zinc-900 text-zinc-400 dark:text-zinc-600 font-black border-b border-zinc-100 dark:border-zinc-800 uppercase tracking-[0.15em] text-[9px]">
                            <tr>
                                <th class="px-8 py-5 text-left">Producto / Enviado</th>
                                <th class="px-8 py-5 text-right w-44">Cantidad Recibida</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800">
                            <tr v-for="detail in transfer.details" :key="detail.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="font-black text-zinc-900 dark:text-white tracking-tight text-base">{{ detail.product.name }}</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[10px] text-zinc-400 dark:text-zinc-600 font-mono font-bold tracking-widest">{{ detail.product.code }}</span>
                                            <span class="w-1 h-1 rounded-full bg-zinc-200 dark:bg-zinc-700"></span>
                                            <span class="text-[11px] font-black text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/20 px-2 py-0.5 rounded-lg">Solicitado: {{ Number(detail.quantity_requested).toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col items-end gap-3">
                                        <div class="relative w-32">
                                            <input v-model="receivedQuantities[detail.id]" type="number" step="0.0001"
                                                   class="w-full pl-3 pr-10 py-2.5 text-right font-black font-mono bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 transition-all outline-none" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-zinc-400 uppercase tracking-widest pointer-events-none">und</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden shadow-inner">
                                            <div :class="['h-full transition-all duration-700 ease-out', getProgressBarColor(detail.id)]" 
                                                 :style="{ width: getProgressWidth(detail.id) + '%' }"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-8 border-t border-zinc-100 dark:border-zinc-800 flex flex-col sm:flex-row justify-end items-center gap-4 bg-zinc-50/50 dark:bg-zinc-950/50">
                    <button @click="showReceiveModal = false" class="w-full sm:w-auto px-6 py-3 text-zinc-500 font-bold hover:text-zinc-700 transition-colors">
                        Cancelar
                    </button>
                    <button @click="confirmReceive"
                            class="w-full sm:w-auto flex items-center justify-center gap-2 px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-black rounded-xl transition-all shadow-lg shadow-emerald-500/25">
                        <span class="material-symbols-outlined text-[20px]">check_circle</span>
                        Confirmar Recepción
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Rechazo -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/80 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden border border-zinc-200 dark:border-zinc-800 animate-in zoom-in-95 duration-200">
                <div class="p-8">
                    <h3 class="text-xl font-black text-zinc-900 dark:text-white uppercase tracking-tight mb-4">Rechazar Transferencia</h3>
                    <p class="text-sm text-zinc-500 mb-6 font-medium">Por favor, indique el motivo por el cual no se puede procesar esta transferencia.</p>
                    
                    <textarea v-model="rejectionReason" 
                              rows="4" 
                              placeholder="Ej: Stock dañado en origen, productos incorrectos..."
                              class="w-full p-4 bg-zinc-50 dark:bg-zinc-800 border-none rounded-2xl text-zinc-900 dark:text-white focus:ring-2 focus:ring-rose-500 transition-all outline-none text-sm"></textarea>
                    
                    <div class="flex justify-end gap-3 mt-8">
                        <button @click="showRejectModal = false" class="px-6 py-2.5 text-sm font-bold text-zinc-500 hover:text-zinc-700 transition-colors">
                            Cancelar
                        </button>
                        <button @click="reject" 
                                class="px-8 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-black rounded-xl transition-all shadow-lg shadow-rose-500/25">
                            Confirmar Rechazo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
