<script setup>
import { computed, ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    form: Object,
    clients: Array,
    loadingClients: Boolean,
    clientSearch: String,
    selectedClient: Object,
    defaultClient: Object
});

const emit = defineEmits(['update:clientSearch', 'open-client-modal', 'client-updated']);

const showDropdown = ref(false);
const isEditingPhone = ref(false);
const editPhoneValue = ref('');
const phoneInput = ref(null);

const startEditPhone = () => {
    editPhoneValue.value = internalSelectedClient.value?.phone || '';
    isEditingPhone.value = true;
    nextTick(() => {
        phoneInput.value?.focus();
    });
};

const cancelEditPhone = () => {
    isEditingPhone.value = false;
};

const savePhone = () => {
    const client = internalSelectedClient.value;
    if (!client || !client.id) return;

    router.put(route('admin.clients.update', client.id), {
        name: client.name,
        document_type: client.document_type,
        document_number: client.document_number,
        phone: editPhoneValue.value,
        is_active: true
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isEditingPhone.value = false;
            
            // Notificar al padre para actualizar el estado local
            const updatedClient = { ...client, phone: editPhoneValue.value };
            emit('client-updated', updatedClient);

            // Update the form delivery phone if it matches
            if (props.form.delivery_contact_phone === client.phone) {
                props.form.delivery_contact_phone = editPhoneValue.value;
            }
        }
    });
};

const selectClient = (client) => {
    props.form.client_id = client.id;
    emit('update:clientSearch', client.name);
    showDropdown.value = false;
    
    // Auto-fill delivery info
    if (!props.form.delivery_contact_name) props.form.delivery_contact_name = client.name;
    if (!props.form.delivery_contact_phone) props.form.delivery_contact_phone = client.phone;
};

const selectConsumidorFinal = () => {
    if (props.defaultClient) {
        selectClient(props.defaultClient);
    } else {
        props.form.client_id = null;
        emit('update:clientSearch', 'CONSUMIDOR FINAL');
    }
    showDropdown.value = false;
};

const formatMoney = (val) => parseFloat(val || 0).toFixed(2);

const internalSelectedClient = computed(() => {
    if (props.selectedClient) return props.selectedClient;
    return props.clients.find(c => c.id === props.form.client_id);
});
</script>

<template>
    <div class="relative">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[10px] font-black text-zinc-500 dark:text-secondary-400 uppercase tracking-widest flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-emerald-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                CLIENTE / RAZÓN SOCIAL <span class="text-rose-400">*</span>
            </p>
            <button 
                type="button"
                @click="$emit('open-client-modal')"
                class="text-[9px] font-bold bg-emerald-100 dark:bg-emerald-500/10 hover:bg-emerald-600 text-emerald-700 dark:text-emerald-400 hover:text-white px-2 py-1 rounded-md transition-colors flex items-center gap-1"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo Cliente
            </button>
        </div>

        <div v-if="form.client_id" class="space-y-3">
            <!-- Alerta de Deuda -->
            <div v-if="internalSelectedClient?.current_balance > 0" class="p-3 rounded-lg border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-200 flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-amber-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <span class="text-[11px] font-black uppercase tracking-wide">Aviso: Cliente con deuda activa</span>
                </div>
                <div class="text-[11px] font-medium ml-7">
                    El cliente tiene un saldo pendiente de <span class="font-black text-[13px] dark:text-white">Bs. {{ formatMoney(internalSelectedClient.current_balance) }}</span>.
                </div>
            </div>

            <!-- Card Cliente -->
            <div class="border border-emerald-400 dark:border-emerald-500/50 rounded-xl overflow-hidden bg-emerald-50/10 dark:bg-emerald-500/5 transition-all duration-300">
                <div class="flex items-center justify-between px-4 py-2 border-b border-zinc-200/50 dark:border-secondary-700/50">
                    <div class="flex items-center text-[10px] font-black text-zinc-800 dark:text-secondary-100 uppercase gap-2">
                        <div class="w-5 h-5 bg-emerald-500 text-white rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        </div>
                        Datos del Cliente
                    </div>
                    <button type="button" @click="form.client_id = null; $emit('update:clientSearch', '')" class="text-[9px] text-zinc-500 dark:text-secondary-400 bg-white/5 dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 px-3 py-1.5 rounded-lg font-bold uppercase hover:bg-zinc-100 dark:hover:bg-secondary-700 transition-all flex items-center gap-1.5 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 text-zinc-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Cambiar
                    </button>
                </div>

                <div v-if="!internalSelectedClient?.document_number && !internalSelectedClient?.phone" class="px-4 py-3 flex items-center gap-3 bg-white/40 dark:bg-secondary-800/40">
                    <div class="flex items-center gap-2 text-[11px] font-black text-zinc-700 dark:text-secondary-200 uppercase tracking-tight">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-emerald-500"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                        {{ internalSelectedClient?.name || 'CONSUMIDOR FINAL' }}
                    </div>
                    <div class="h-3 w-[1px] bg-zinc-300 dark:bg-secondary-700"></div>
                    <div class="text-[10px] font-bold text-zinc-400 dark:text-secondary-500 uppercase">S/N</div>
                </div>

                <div v-else class="p-4 space-y-2.5">
                    <div class="grid grid-cols-[100px_1fr] items-center gap-2 text-[11px]">
                        <div class="text-zinc-500 dark:text-secondary-500 font-bold flex items-center gap-2 uppercase tracking-tight">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 opacity-50"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                            Cliente:
                        </div>
                        <div class="font-black text-zinc-800 dark:text-white uppercase tracking-tight text-xs">{{ internalSelectedClient?.name }}</div>
                    </div>

                    <div class="grid grid-cols-[100px_1fr] items-center gap-2 text-[11px]">
                        <div class="text-zinc-500 dark:text-secondary-500 font-bold flex items-center gap-2 uppercase tracking-tight">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 opacity-50"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" /></svg>
                            Documento:
                        </div>
                        <div class="font-black text-zinc-800 dark:text-white uppercase tracking-tight">{{ internalSelectedClient?.document_number || 'S/D' }}</div>
                    </div>

                    <div class="grid grid-cols-[100px_1fr] items-center gap-2 text-[11px]">
                        <div class="text-zinc-500 dark:text-secondary-500 font-bold flex items-center gap-2 uppercase tracking-tight">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 opacity-50"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                            Teléfono:
                        </div>
                        <div class="flex items-center gap-2">
                            <div v-if="!isEditingPhone" class="flex items-center gap-2 group/phone">
                                <span class="px-2.5 py-1 bg-zinc-100 dark:bg-secondary-700/50 text-zinc-700 dark:text-secondary-200 font-black rounded-lg border border-zinc-200 dark:border-secondary-600/50 shadow-sm tracking-widest text-[12px] min-w-[120px] text-center">
                                    {{ internalSelectedClient?.phone || 'S/D' }}
                                </span>
                                <button @click="startEditPhone" type="button" class="p-1.5 text-zinc-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-md opacity-0 group-hover/phone:opacity-100 transition-all" title="Editar teléfono">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                    </svg>
                                </button>
                            </div>
                            <div v-else class="flex items-center gap-1.5 animate-in fade-in slide-in-from-left-2 duration-200">
                                <input 
                                    ref="phoneInput"
                                    v-model="editPhoneValue"
                                    @keyup.enter="savePhone"
                                    @keyup.esc="cancelEditPhone"
                                    type="text" 
                                    class="w-32 px-2 py-1 bg-white dark:bg-secondary-900 border border-blue-400 dark:border-blue-500 rounded-lg text-[12px] font-black text-zinc-800 dark:text-white focus:ring-4 focus:ring-blue-500/10 text-center tracking-widest"
                                    placeholder="Nro. Teléfono"
                                >
                                <button @click="savePhone" type="button" class="p-1.5 bg-emerald-500 text-white rounded-md hover:bg-emerald-600 shadow-sm transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </button>
                                <button @click="cancelEditPhone" type="button" class="p-1.5 bg-zinc-100 dark:bg-secondary-700 text-zinc-500 dark:text-secondary-400 rounded-md hover:bg-zinc-200 dark:hover:bg-secondary-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="relative">
            <div class="relative group">
                <div class="absolute inset-y-0 left-3.5 flex items-center text-zinc-400 dark:text-secondary-500 group-focus-within:text-emerald-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <input 
                    :value="clientSearch"
                    @input="e => { $emit('update:clientSearch', e.target.value); showDropdown = e.target.value.length > 0 }"
                    type="text" 
                    placeholder="BUSCAR CLIENTE POR NOMBRE O NIT..." 
                    class="w-full pl-10 pr-4 py-3 bg-zinc-50 dark:bg-secondary-900 border border-zinc-100 dark:border-secondary-700 rounded-xl text-xs font-bold text-zinc-700 dark:text-secondary-200 placeholder:text-zinc-300 dark:placeholder:text-secondary-600 focus:bg-white dark:focus:bg-secondary-900 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-500/10 transition-all uppercase"
                >
            </div>

            <!-- Dropdown Clientes -->
            <div v-if="showDropdown && clients.length > 0" class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-secondary-800 rounded-xl shadow-2xl border border-zinc-200 dark:border-secondary-700 max-h-48 overflow-y-auto z-[300] custom-scrollbar">
                <button 
                    type="button"
                    v-for="c in clients" 
                    :key="c.id"
                    @click="selectClient(c)"
                    class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 border-b border-zinc-100 dark:border-secondary-700 last:border-0 transition-colors"
                >
                    <span class="font-black text-xs text-zinc-800 dark:text-white block uppercase tracking-tight">{{ c.name }}</span>
                    <span class="text-[10px] text-zinc-500 dark:text-secondary-500 font-bold uppercase">{{ c.document_number || 'S/D' }}</span>
                </button>
            </div>

            <button 
                type="button"
                @click="selectConsumidorFinal"
                class="mt-3 w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-dashed border-zinc-300 dark:border-secondary-700 hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 text-zinc-500 dark:text-secondary-400 hover:text-emerald-700 dark:hover:text-emerald-400 transition-all group"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-zinc-400 dark:text-secondary-500 group-hover:text-emerald-500 transition-colors">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span class="text-[10px] font-black uppercase tracking-widest">Venta sin nombre (Consumidor Final)</span>
            </button>
        </div>
    </div>
</template>
