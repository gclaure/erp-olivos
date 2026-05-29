<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    tenant: Object,
    permissions: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    permissions: [],
});

const permissionLabels = {
    'create-purchases': 'Crear Compras',
    'create-sales': 'Crear Ventas',
    'manage-categories': 'Gestionar Categorías',
    'manage-clients': 'Gestionar Clientes',
    'manage-inventory': 'Gestionar Inventario',
    'manage-products': 'Gestionar Productos',
    'manage-providers': 'Gestionar Proveedores',
    'manage-purchases': 'Gestionar Compras',
    'manage-roles': 'Gestionar Roles',
    'manage-sales': 'Gestionar Ventas',
    'manage-deliveries': 'Gestionar Entregas',
    'manage-settings': 'Gestionar Configuración',
    'manage-users': 'Gestionar Usuarios',
    'manage-warehouses': 'Gestionar Almacenes',
    'view-reports': 'Ver Reportes',
    'manage-branches': 'Administrar sucursales',
    'pos-access': 'Acceso a Puntos de Venta',
    'manage-pos': 'Gestionar Puntos de Venta',
    'manage-transfers': 'Gestionar Transferencias',
    'manage-company': 'Gestionar Datos de Empresa',
};

const permissionDescriptions = {
    'create-purchases': 'Registra nuevas facturas de compra de mercancía.',
    'create-sales': 'Realiza ventas directas desde el Punto de Venta (POS).',
    'manage-categories': 'Organiza y clasifica productos en grupos lógicos.',
    'manage-clients': 'Administra la base de datos de compradores y sus créditos.',
    'manage-inventory': 'Controla stock, ajustes manuales y traslados (Kardex).',
    'manage-products': 'Crea y edita el catálogo de artículos y sus precios.',
    'manage-providers': 'Administra la información de los abastecedores.',
    'manage-purchases': 'Ver historial de compras y gestionar estados de pago.',
    'manage-roles': 'Define perfiles de seguridad y asigna permisos.',
    'manage-sales': 'Revisa facturación, cotizaciones y anulaciones.',
    'manage-deliveries': 'Administra la logística y despacho de ventas realizadas.',
    'manage-settings': 'Ajusta el perfil del usuario y preferencias del sistema.',
    'manage-users': 'Administra las cuentas de acceso de los empleados.',
    'manage-warehouses': 'Organiza los depósitos físicos de mercancía.',
    'view-reports': 'Accede a analíticas de ventas y rendimiento del negocio.',
    'manage-branches': 'Gestiona las sedes físicas y puntos de venta.',
    'pos-access': 'Habilita la interfaz táctil de ventas rápidas (Puntos de Venta).',
    'manage-pos': 'Administra los puntos de venta de la empresa.',
    'manage-transfers': 'Gestiona traslados de mercancía entre almacenes.',
    'manage-company': 'Modifica logo y configuración legal de la compañía.',
};

watch(() => props.show, (val) => {
    if (val) {
        form.reset();
        form.clearErrors();
    }
});

const submit = () => {
    form.post(route('superadmin.tenants.add-custom-role', props.tenant.id), {
        onSuccess: () => {
            form.reset();
            emit('close');
            Swal.fire({
                icon: 'success',
                title: 'Rol creado',
                text: 'El rol ha sido creado correctamente para esta empresa.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        },
    });
};

const deleteRole = (role) => {
    Swal.fire({
        title: '¿Eliminar Rol?',
        text: `¿Estás seguro de eliminar el rol "${role.name}" para esta empresa?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('superadmin.tenants.destroy-role', { 
                tenant: props.tenant.id, 
                role: role.id 
            }), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'El rol ha sido eliminado correctamente.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                },
                onError: (errors) => {
                    const message = Object.values(errors)[0] || 'No se pudo eliminar el rol.';
                    Swal.fire('Error', message, 'error');
                }
            });
        }
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200 text-left">
            <div class="p-6 border-b border-zinc-100 dark:border-gray-600 flex items-center justify-between">
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Añadir Nuevo Rol para {{ tenant?.name }}</h2>
                <button @click="emit('close')" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <form v-if="tenant" @submit.prevent="submit">
                <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                    <!-- Roles Existentes -->
                    <div v-if="tenant?.roles?.length" class="space-y-3">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Roles Existentes</label>
                        <div class="flex flex-wrap gap-2 p-4 bg-zinc-100 dark:bg-zinc-800/50 rounded-xl border border-dashed border-zinc-300 dark:border-gray-500">
                            <div v-for="role in tenant.roles" :key="role.id" 
                                 class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-700 rounded-lg border border-zinc-200 dark:border-gray-600 shadow-sm group">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ role.name }}</span>
                                <button v-if="!['Administrador', 'Vendedor'].includes(role.name)" 
                                        @click.prevent="deleteRole(role)" class="text-zinc-400 hover:text-rose-500 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 pt-6 border-t border-zinc-100 dark:border-gray-600">
                        <div class="flex items-center gap-2 text-violet-600 dark:text-violet-400 font-bold mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            <span class="text-sm uppercase tracking-wider">Añadir Nuevo Rol</span>
                        </div>
                        
                        <!-- Nombre del Rol -->
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nombre del Rol *</label>
                            <input v-model="form.name" type="text" placeholder="Ej: Supervisor, Auditor, etc." 
                                   class="w-full rounded-lg border-zinc-200 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-violet-500 focus:border-violet-500 transition-all" />
                            <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</p>
                        </div>

                        <!-- Permisos -->
                        <div class="space-y-3">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Seleccionar Permisos</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 bg-zinc-50 dark:bg-gray-700/50 rounded-xl border border-zinc-200 dark:border-gray-600">
                                <label v-for="permission in permissions" :key="permission.id" 
                                       class="flex items-start gap-3 p-3 rounded-xl hover:bg-white dark:hover:bg-gray-600 transition-all cursor-pointer group border border-transparent hover:border-zinc-200 dark:hover:border-gray-500">
                                    <input type="checkbox" v-model="form.permissions" :value="permission.name" 
                                           class="mt-1 rounded border-zinc-300 text-violet-600 focus:ring-violet-500 w-4 h-4">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-sm font-bold text-zinc-700 dark:text-zinc-200 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                                            {{ permissionLabels[permission.name] || permission.name }}
                                        </span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 leading-tight">
                                            {{ permissionDescriptions[permission.name] || 'Sin descripción disponible.' }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                            <p v-if="form.errors.permissions" class="text-xs text-rose-500 mt-1">{{ form.errors.permissions }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-zinc-50 dark:bg-gray-700/50 border-t border-zinc-100 dark:border-gray-600 flex justify-end gap-3">
                    <button type="button" @click="emit('close')" 
                            class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="form.processing" 
                            @click="submit"
                            class="px-6 py-2 bg-violet-600 text-white text-sm font-bold rounded-lg hover:bg-violet-700 shadow-lg shadow-violet-500/20 transition-all disabled:opacity-50">
                        {{ form.processing ? 'Creando...' : 'Crear Rol' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
