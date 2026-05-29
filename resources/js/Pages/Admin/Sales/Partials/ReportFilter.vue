<script setup>
import { ref } from 'vue';

const props = defineProps({
    initialDates: Object,
    users: Array,
    isAdmin: Boolean
});

const dateFrom = ref(props.initialDates.from);
const dateTo = ref(props.initialDates.to);
const userId = ref('');

const loadingPdf = ref(false);
const loadingExcel = ref(false);

const generatePdf = () => {
    loadingPdf.value = true;
    const url = route('admin.sales.report-pdf', {
        date_from: dateFrom.value,
        date_to: dateTo.value,
        user_id: userId.value
    });
    window.location.href = url;
    setTimeout(() => loadingPdf.value = false, 3000);
};

const generateExcel = () => {
    loadingExcel.value = true;
    const url = route('admin.sales.report-excel', {
        date_from: dateFrom.value,
        date_to: dateTo.value,
        user_id: userId.value
    });
    window.location.href = url;
    setTimeout(() => loadingExcel.value = false, 3000);
};

</script>

<template>
    <div class="bg-surface border border-zinc-200 dark:border-gray-600 rounded-xl p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 items-end">
            <div class="xl:col-span-1">
                <label class="block text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1 mb-1">Desde (Obligado)</label>
                <input type="date" v-model="dateFrom" class="w-full px-3 py-2 border border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" />
            </div>
            <div class="xl:col-span-1">
                <label class="block text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1 mb-1">Hasta (Obligado)</label>
                <input type="date" v-model="dateTo" class="w-full px-3 py-2 border border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" />
            </div>
            <div v-if="isAdmin" class="xl:col-span-1">
                <label class="block text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest ml-1 mb-1">Usuario (Opcional)</label>
                <select v-model="userId" class="w-full px-3 py-2 border border-zinc-300 dark:border-gray-500 dark:bg-gray-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none">
                    <option value="">Todos los usuarios</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                </select>
            </div>
            <div class="xl:col-span-2 flex flex-col sm:flex-row gap-2">
                <button @click="generatePdf" :disabled="loadingPdf"
                   class="w-full sm:w-auto inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-md border border-transparent bg-rose-600 text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all px-4 py-2 h-[38px] disabled:opacity-50">
                    <svg v-if="!loadingPdf" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <svg v-else class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Reporte PDF</span>
                </button>
                
                <button @click="generateExcel" :disabled="loadingExcel"
                   class="w-full sm:w-auto inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-md border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all px-4 py-2 h-[38px] disabled:opacity-50">
                    <svg v-if="!loadingExcel" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125h-7.5c-.621 0-1.125-.504-1.125-1.125m0 1.125v-1.5c0-.621.504-1.125 1.125-1.125m0 1.125h7.5m-7.5 0h-7.5m0-12.75h17.25m-17.25 0a1.125 1.125 0 0 0-1.125 1.125M3.375 5.625h7.5c.621 0 1.125.504 1.125 1.125m0-1.125v1.5c0 .621-.504 1.125-1.125 1.125m18.375-2.625a1.125 1.125 0 0 1 1.125 1.125M20.625 5.625h-7.5c-.621 0-1.125.504-1.125 1.125m0-1.125v1.5c0 .621.504 1.125 1.125 1.125m0-1.125h7.5m-7.5 0h-7.5" />
                    </svg>
                    <svg v-else class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Exportar Excel</span>
                </button>
            </div>
        </div>
    </div>
</template>
