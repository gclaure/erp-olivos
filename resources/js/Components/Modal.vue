<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    zIndexClass: {
        type: String,
        default: 'z-[1000]',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

watch(
    () => props.show,
    (isOpen) => {
        if (typeof document !== 'undefined') {
            document.body.style.overflow = isOpen ? 'hidden' : '';
        }
    }
);

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});

const maxWidthClass = computed(() => {
    return (
        {
            sm: 'w-full sm:max-w-sm',
            md: 'w-full sm:max-w-md',
            lg: 'w-full sm:max-w-lg',
            xl: 'w-full sm:max-w-xl',
            '2xl': 'w-full sm:max-w-md lg:max-w-2xl',
            '3xl': 'w-full sm:max-w-xl lg:max-w-3xl',
            '4xl': 'w-full sm:max-w-2xl lg:max-w-4xl',
        }[props.maxWidth] || 'w-full sm:max-w-md lg:max-w-2xl'
    );
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-6 flex items-center justify-center"
                :class="zIndexClass"
            >
                <div
                    class="fixed inset-0 bg-secondary-950/75 backdrop-blur-sm transition-opacity"
                    @click="close"
                ></div>

                <div
                    class="relative z-10 bg-white dark:bg-secondary-800 rounded-3xl border border-zinc-200 dark:border-secondary-700 overflow-hidden transform transition-all"
                    :class="maxWidthClass"
                >
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
