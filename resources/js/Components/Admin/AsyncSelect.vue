<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';

const props = defineProps({
    modelValue: [String, Number, Object],
    endpoint: {
        type: String,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Buscar...'
    },
    label: {
        type: String,
        default: 'name'
    },
    valueKey: {
        type: String,
        default: 'id'
    },
    initialData: {
        type: Object,
        default: null
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const search = ref('');
const options = ref([]);
const loading = ref(false);
const selectedItem = ref(props.initialData);
const dropdownRef = ref(null);

const fetchOptions = debounce(async (query) => {
    if (!query && !isOpen.value) return;
    
    loading.value = true;
    try {
        const response = await axios.get(props.endpoint, {
            params: { search: query }
        });
        options.value = response.data;
    } catch (error) {
        console.error('Error fetching options:', error);
    } finally {
        loading.value = false;
    }
}, 300);

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value && options.value.length === 0) {
        fetchOptions('');
    }
};

const selectOption = (option) => {
    selectedItem.value = option;
    isOpen.value = false;
    search.value = '';
    emit('update:modelValue', option ? option[props.valueKey] : null);
    emit('change', option);
};

const clearSelection = (e) => {
    e.stopPropagation();
    selectOption(null);
};

watch(search, (newQuery) => {
    fetchOptions(newQuery);
});

// Click outside to close
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

watch(() => props.initialData, (newVal) => {
    selectedItem.value = newVal;
}, { deep: true, immediate: true });

watch(() => props.modelValue, (newVal) => {
    if (newVal === null || newVal === undefined) {
        selectedItem.value = null;
    }
});

</script>

<template>
    <div class="relative" ref="dropdownRef">
        <div 
            @click="toggleDropdown"
            :class="[
                'w-full px-4 py-2.5 bg-white dark:bg-secondary-800 border rounded-xl text-sm transition-all cursor-pointer flex items-center justify-between gap-2 shadow-sm',
                isOpen ? 'border-indigo-500 ring-4 ring-indigo-500/10' : 'border-zinc-200 dark:border-secondary-700',
                disabled ? 'opacity-50 cursor-not-allowed' : ''
            ]"
        >
            <div class="truncate flex-1">
                <span v-if="selectedItem" class="text-zinc-900 dark:text-white font-medium">
                    {{ selectedItem[label] }}
                </span>
                <span v-else class="text-zinc-400 dark:text-secondary-500">
                    {{ placeholder }}
                </span>
            </div>
            
            <div class="flex items-center gap-1">
                <button 
                    v-if="selectedItem && !disabled" 
                    @click="clearSelection"
                    class="p-1 hover:bg-zinc-100 dark:hover:bg-secondary-700 rounded-full text-zinc-400 hover:text-rose-500 transition-colors"
                >
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
                <span class="material-symbols-outlined text-zinc-400 transition-transform duration-300" :class="{ 'rotate-180': isOpen }">
                    keyboard_arrow_down
                </span>
            </div>
        </div>

        <!-- Dropdown Menu -->
        <div 
            v-if="isOpen"
            class="absolute z-[100] w-full mt-2 bg-white dark:bg-secondary-800 border border-zinc-200 dark:border-secondary-700 rounded-xl shadow-2xl animate-in fade-in slide-in-from-top-2 duration-200"
        >
            <!-- Search Input -->
            <div class="p-2 border-b border-zinc-100 dark:border-secondary-700">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">search</span>
                    <input 
                        v-model="search"
                        type="text"
                        class="w-full pl-9 pr-4 py-2 bg-zinc-50 dark:bg-secondary-900 border-none rounded-lg text-xs focus:ring-0 dark:text-white"
                        placeholder="Escribe para buscar..."
                        @click.stop
                    >
                </div>
            </div>

            <!-- Options List -->
            <div class="max-h-60 overflow-y-auto custom-scrollbar p-1">
                <div v-if="loading" class="py-4 text-center">
                    <div class="inline-block animate-spin w-4 h-4 border-2 border-indigo-500 border-t-transparent rounded-full"></div>
                </div>
                
                <template v-else>
                    <div 
                        v-for="option in options" 
                        :key="option[valueKey]"
                        @click="selectOption(option)"
                        :class="[
                            'px-3 py-2 rounded-lg text-xs cursor-pointer transition-colors mb-0.5',
                            selectedItem?.[valueKey] === option[valueKey] 
                                ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-bold' 
                                : 'text-zinc-600 dark:text-secondary-300 hover:bg-zinc-50 dark:hover:bg-secondary-700'
                        ]"
                    >
                        {{ option[label] }}
                        <div v-if="option.code" class="text-[10px] text-zinc-400 dark:text-secondary-500 font-normal">
                            Cod: {{ option.code }}
                        </div>
                    </div>
                    
                    <div v-if="options.length === 0" class="py-8 text-center">
                        <span class="material-symbols-outlined text-zinc-300 dark:text-secondary-600 text-3xl mb-2">search_off</span>
                        <p class="text-xs text-zinc-400 dark:text-secondary-500">No se encontraron resultados</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>
