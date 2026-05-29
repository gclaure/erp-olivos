<script setup>
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    tenant: Object,
    roles: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'admin',
});

const submit = () => {
    form.post(route('superadmin.tenants.store-user', props.tenant.id), {
        onSuccess: () => {
            closeModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Usuario creado y asignado',
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
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-zinc-200 dark:border-zinc-800">
                <!-- Header -->
                <div class="bg-white dark:bg-zinc-900 px-6 py-5 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0 border border-indigo-100 dark:border-indigo-800/30">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">Añadir Usuario</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 font-medium">Asignar a: <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ tenant?.name }}</span></p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Nombre Completo *</label>
                        <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Ej. Juan Perez">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Email de Acceso *</label>
                        <input v-model="form.email" type="email" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500" placeholder="usuario@empresa.com">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Contraseña Inicial *</label>
                        <input v-model="form.password" type="password" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500" placeholder="••••••••">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mb-1.5">Rol en la Empresa</label>
                        <select v-model="form.role" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="admin">Administrador (Full)</option>
                            <option value="user">Usuario (Ventas)</option>
                        </select>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-2">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-all">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="px-8 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-700 transition-all shadow-lg shadow-indigo-500/25 disabled:opacity-50">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
