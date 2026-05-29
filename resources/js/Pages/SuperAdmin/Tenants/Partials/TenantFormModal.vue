<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    tenant: Object,
    plans: Array,
    vendedores: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    nit: '',
    business_name: '',
    email: '',
    phone: '',
    phone_secondary: '',
    address: '',
    status: 'ACTIVE',
    trial_months: 1,
    selectedPlanId: '',
    vendedor_id: '',
});

watch(() => props.tenant, (newTenant) => {
    if (newTenant) {
        form.name = newTenant.name || '';
        form.nit = newTenant.nit || '';
        form.business_name = newTenant.business_name || '';
        form.email = newTenant.email || '';
        form.phone = newTenant.phone || '';
        form.phone_secondary = newTenant.phone_secondary || '';
        form.address = newTenant.address || '';
        form.status = newTenant.status || 'ACTIVE';
        form.trial_months = newTenant.trial_months || 1;
        form.selectedPlanId = newTenant.plan_id || '';
        form.vendedor_id = newTenant.vendedor_id || '';
    } else {
        form.reset();
    }
}, { immediate: true });

const submit = () => {
    if (props.tenant) {
        form.put(route('superadmin.tenants.update', props.tenant.id), {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Empresa actualizada',
                    showConfirmButton: false,
                    timer: 3000
                });
            },
            onError: (errors) => {
                console.error('Error:', errors);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: 'Corrige los errores en el formulario.',
                    confirmButtonColor: '#7c3aed'
                });
            }
        });
    } else {
        form.post(route('superadmin.tenants.store'), {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Empresa creada',
                    showConfirmButton: false,
                    timer: 3000
                });
            },
            onError: (errors) => {
                console.error('Error:', errors);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: 'Corrige los errores en el formulario.',
                    confirmButtonColor: '#7c3aed'
                });
            }
        });
    }
};

const closeModal = () => {
    emit('close');
    form.reset();
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-start sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-zinc-200 dark:border-zinc-800">
                <!-- Header -->
                <div class="bg-white dark:bg-zinc-900 px-6 py-5 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center flex-shrink-0 border border-violet-100 dark:border-violet-800/30">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-9h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">
                                {{ tenant ? 'Editar Empresa' : 'Nueva Empresa' }}
                            </h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 font-medium">
                                {{ tenant ? 'Actualiza los datos de la empresa' : 'Registra una nueva empresa en el sistema' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div class="space-y-4">
                        <!-- Nombre -->
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Nombre de la Empresa *</label>
                            <input v-model="form.name" type="text" placeholder="Nombre comercial" 
                                   class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                   :class="{'border-red-500': form.errors.name}">
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500 font-medium">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- NIT -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">NIT *</label>
                                <input v-model="form.nit" type="text" placeholder="NIT de la empresa"
                                       class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                       :class="{'border-red-500': form.errors.nit}">
                                <p v-if="form.errors.nit" class="mt-1 text-xs text-red-500 font-medium">{{ form.errors.nit }}</p>
                            </div>
                            <!-- Razón Social -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Representante Legal *</label>
                                <input v-model="form.business_name" type="text" placeholder="Razón social"
                                       class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                       :class="{'border-red-500': form.errors.business_name}">
                                <p v-if="form.errors.business_name" class="mt-1 text-xs text-red-500 font-medium">{{ form.errors.business_name }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Email *</label>
                                <input v-model="form.email" type="email" placeholder="Email de contacto"
                                       class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                       :class="{'border-red-500': form.errors.email}">
                                <p v-if="form.errors.email" class="mt-1 text-xs text-red-500 font-medium">{{ form.errors.email }}</p>
                            </div>
                            <!-- Teléfono -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Teléfono 1</label>
                                <input v-model="form.phone" type="text" placeholder="Celular o teléfono"
                                       class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                       :class="{'border-red-500': form.errors.phone}">
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500 font-medium">{{ form.errors.phone }}</p>
                            </div>
                            <!-- Teléfono Secundario -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Teléfono 2 (Opcional)</label>
                                <input v-model="form.phone_secondary" type="text" placeholder="Otro número de contacto"
                                       class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                       :class="{'border-red-500': form.errors.phone_secondary}">
                                <p v-if="form.errors.phone_secondary" class="mt-1 text-xs text-red-500 font-medium">{{ form.errors.phone_secondary }}</p>
                            </div>
                        </div>

                        <!-- Dirección -->
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Dirección (Casa Matriz)</label>
                            <input v-model="form.address" type="text" placeholder="Dirección completa"
                                   class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                   :class="{'border-red-500': form.errors.address}">
                            <p v-if="form.errors.address" class="mt-1 text-xs text-red-500 font-medium">{{ form.errors.address }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Estado -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Estado</label>
                                <select v-model="form.status" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all">
                                    <option value="ACTIVE">Activo</option>
                                    <option value="TRIAL">Prueba</option>
                                    <option value="SUSPENDED">Suspendido</option>
                                    <option value="CANCELLED">Cancelado</option>
                                </select>
                            </div>
                            <!-- Meses de prueba (solo si es TRIAL) -->
                            <div v-if="form.status === 'TRIAL'">
                                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Meses de Prueba *</label>
                                <input v-model="form.trial_months" type="number" min="1"
                                       class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                       :class="{'border-red-500': form.errors.trial_months}">
                                <p v-if="form.errors.trial_months" class="mt-1 text-xs text-red-500 font-medium">{{ form.errors.trial_months }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Plan -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Plan Asignado</label>
                                <select v-model="form.selectedPlanId" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                        :class="{'border-red-500': form.errors.selectedPlanId}">
                                    <option value="">Seleccionar plan...</option>
                                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                                </select>
                                <p v-if="form.errors.selectedPlanId" class="mt-1 text-xs text-red-500 font-medium">{{ form.errors.selectedPlanId }}</p>
                            </div>
                            <!-- Vendedor -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Vendedor / Asesor</label>
                                <select v-model="form.vendedor_id" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                        :class="{'border-red-500': form.errors.vendedor_id}">
                                    <option value="">Seleccionar asesor...</option>
                                    <option v-for="v in vendedores" :key="v.id" :value="v.id">{{ v.name }}</option>
                                </select>
                                <p v-if="form.errors.vendedor_id" class="mt-1 text-xs text-red-500 font-medium">{{ form.errors.vendedor_id }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-2">
                        <button type="button" @click="closeModal"
                                class="px-5 py-2.5 text-sm font-bold text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing"
                                class="px-8 py-2.5 bg-violet-600 text-white text-sm font-bold rounded-xl hover:bg-violet-700 transition-all shadow-lg shadow-violet-500/25 disabled:opacity-50">
                            {{ tenant ? 'Guardar Cambios' : 'Crear Empresa' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
