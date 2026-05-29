<script setup>
import { computed } from 'vue';

const props = defineProps({
    sale: Object,
});

const cleanPhone = (phone) => {
    return phone ? phone.replace(/\D/g, '') : '';
};
</script>

<template>
    <div class="p-3.5 bg-zinc-50 dark:bg-secondary-900/40 rounded-xl border border-zinc-100 dark:border-secondary-700 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-white dark:bg-secondary-800 flex items-center justify-center flex-shrink-0 border border-zinc-200/50 dark:border-secondary-700 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-primary-500">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
        </div>
        <div class="flex-1 min-w-0 flex items-center justify-between gap-4">
            <!-- Izquierda: Nombre y Documento -->
            <div>
                <span class="text-[9px] font-black text-zinc-400 dark:text-secondary-500 uppercase tracking-widest block mb-0.5">Receptor Final</span>
                <h3 class="text-sm font-extrabold text-zinc-900 dark:text-white uppercase truncate tracking-tight font-sans">
                    {{ sale.client?.name }}
                </h3>
                <div class="flex items-center gap-1.5 mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-zinc-400 opacity-60">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                    </svg>
                    <span class="text-[10px] font-bold text-zinc-500 dark:text-secondary-400">
                        {{ sale.client?.document_number ?? 'S/D' }}
                    </span>
                </div>
            </div>
            
            <!-- Derecha: Status y Contacto -->
            <div class="flex flex-col items-end gap-1.5 pt-1">
                <span class="text-[8px] px-2 py-0.5 bg-primary-100/50 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 rounded-md font-black uppercase tracking-tighter border border-primary-200/50 dark:border-primary-800/50 leading-none">
                    Registrado
                </span>
                <a v-if="sale.client?.phone" :href="`https://wa.me/${cleanPhone(sale.client.phone)}`" target="_blank" class="text-[10px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 font-bold hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.33.185.506.548.438.912l-1.323 7.126a3.375 3.375 0 0 1-3.315 2.76H8.25a3.375 3.375 0 0 1-3.315-2.76l-1.323-7.126a.75.75 0 0 1 .438-.912l16.2-7.5ZM8.25 19.5v-7.5M15.75 19.5v-7.5" />
                    </svg>
                    {{ sale.client?.phone }}
                </a>
            </div>
        </div>
    </div>
</template>
