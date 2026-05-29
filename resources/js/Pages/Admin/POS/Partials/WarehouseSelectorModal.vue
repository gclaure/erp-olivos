<script setup>
defineProps({
    show: Boolean,
    warehouses: Array,
});
defineEmits(['close', 'select']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[1000] flex items-center justify-center p-4 animate-in fade-in duration-300">
            <div class="absolute inset-0 bg-zinc-900/60 dark:bg-black/80 backdrop-blur-sm" @click="$emit('close')"></div>
            
            <div class="relative bg-white dark:bg-secondary-800 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col border border-zinc-100 dark:border-secondary-700 animate-in zoom-in-95 duration-300 transition-colors">
                <div class="p-4 sm:p-6 border-b border-zinc-100 dark:border-secondary-700 flex items-center justify-between bg-zinc-50 dark:bg-secondary-800/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5-1.5-1.5-.545m-1.5.545v9.143M3 16.035v3.965m6-12.748a6.016 6.016 0 0 0 3 0c.666-.164 1.324-.35 1.983-.558L18 4.5l-6-2.25-6 2.25 1.017.342c.659.207 1.317.394 1.983.558Z" />
                            </svg>
                        </div>
                        <h2 class="text-base font-black text-zinc-800 dark:text-secondary-50 uppercase tracking-tight">Seleccionar Almacén</h2>
                    </div>
                    <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-full text-zinc-300 hover:bg-zinc-100 dark:hover:bg-secondary-700 hover:text-rose-500 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-4 sm:p-6 space-y-3 bg-white dark:bg-secondary-800">
                    <button 
                        v-for="warehouse in warehouses" 
                        :key="warehouse.id"
                        @click="$emit('select', warehouse.id)"
                        class="w-full flex items-center justify-between p-4 bg-zinc-50 dark:bg-secondary-900 border border-zinc-100 dark:border-secondary-700 hover:border-emerald-500 dark:hover:border-emerald-500/50 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-2xl transition-all group"
                    >
                        <div class="flex items-center gap-4 text-left">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-secondary-800 flex items-center justify-center text-zinc-400 dark:text-secondary-600 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 transition-colors border border-zinc-100 dark:border-secondary-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-zinc-800 dark:text-secondary-100 uppercase tracking-tight">{{ warehouse.name }}</h3>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-secondary-500 uppercase tracking-widest mt-0.5">{{ warehouse.location || 'Ubicación General' }}</p>
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-zinc-300 dark:text-secondary-600 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 transition-colors">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
