<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    modelValue: [String, Number, Object],
    options: {
        type: Array,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Seleccionar...'
    },
    label: {
        type: String,
        default: 'name'
    },
    valueKey: {
        type: String,
        default: 'id'
    },
    disabled: {
        type: Boolean,
        default: false
    },
    icon: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const dropdownRef = ref(null);

const selectedItem = computed(() => {
    return props.options.find(opt => opt[props.valueKey] === props.modelValue) || null;
});

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
};

const selectOption = (option) => {
    emit('update:modelValue', option ? option[props.valueKey] : null);
    emit('change', option);
    isOpen.value = false;
};

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
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <div 
            @click="toggleDropdown"
            :class="[
                'w-full px-4 py-3 bg-white dark:bg-gray-800 border rounded-2xl text-sm transition-all cursor-pointer flex items-center justify-between gap-2 shadow-sm',
                isOpen ? 'border-indigo-500 ring-4 ring-indigo-500/10' : 'border-zinc-200 dark:border-gray-700',
                disabled ? 'opacity-50 cursor-not-allowed' : 'hover:border-indigo-500'
            ]"
        >
            <div class="flex items-center gap-3 truncate">
                <span v-if="icon" class="material-symbols-outlined text-zinc-400 transition-colors text-lg" :class="isOpen ? 'text-indigo-500' : ''">
                    {{ icon }}
                </span>
                <span v-if="selectedItem" class="text-zinc-900 dark:text-white font-medium">
                    {{ selectedItem[label] }}
                </span>
                <span v-else class="text-zinc-400 dark:text-zinc-500">
                    {{ placeholder }}
                </span>
            </div>
            
            <span class="material-symbols-outlined text-zinc-400 transition-transform duration-300" :class="{ 'rotate-180': isOpen }">
                expand_more
            </span>
        </div>

        <!-- Dropdown Menu -->
        <div 
            v-if="isOpen"
            class="absolute z-[100] w-full mt-2 bg-white dark:bg-gray-800 border border-zinc-200 dark:border-gray-700 rounded-2xl shadow-2xl animate-in fade-in slide-in-from-top-2 duration-200 overflow-hidden"
        >
            <div class="max-h-60 overflow-y-auto p-1">
                <div 
                    v-for="(option, index) in options" 
                    :key="option[valueKey] || 'null-' + index"
                    @click="selectOption(option)"
                    :class="[
                        'px-4 py-2.5 rounded-xl text-sm cursor-pointer transition-all mb-0.5 flex items-center justify-between group',
                        modelValue === option[props.valueKey] 
                            ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-bold' 
                            : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50'
                    ]"
                >
                    <span>{{ option[label] }}</span>
                    <span v-if="modelValue === option[props.valueKey]" class="material-symbols-outlined text-sm">check</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-in {
    animation: slideIn 0.2s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
