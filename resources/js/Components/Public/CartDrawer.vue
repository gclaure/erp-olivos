<script setup>
import { ref, watch, computed } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean
});

const emit = defineEmits(['close']);

const { props: pageProps } = usePage();
const cart = computed(() => usePage().props.cart);

const form = useForm({
    customer_name: '',
    customer_ci: ''
});

const companySlug = computed(() => usePage().props.company_slug);

const updateQuantity = (id, change) => {
    router.post(route('public.cart.update', { company_slug: companySlug.value, productId: id }), { change }, {
        preserveScroll: true,
        preserveState: true
    });
};

const removeItem = (id) => {
    router.post(route('public.cart.remove', { company_slug: companySlug.value, productId: id }), {}, {
        preserveScroll: true,
        preserveState: true
    });
};

const sendOrder = () => {
    form.post(route('public.cart.send', { 
        company_slug: companySlug.value,
        branch_id: localStorage.getItem('selected_branch_id')
    }), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash.success_data?.whatsapp_url) {
                window.open(page.props.flash.success_data.whatsapp_url, '_blank');
            }
        }
    });
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2 }).format(num);
};

// Escuchar cambios en flash para notificaciones si fuera necesario
</script>

<template>
    <div v-show="show" class="fixed inset-0 z-[100] overflow-hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 overflow-hidden">
            <!-- Overlay -->
            <transition
                enter-active-class="ease-in-out duration-500"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in-out duration-500"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="show" @click="emit('close')" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm transition-opacity"></div>
            </transition>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                <transition
                    enter-active-class="transform transition ease-in-out duration-500 sm:duration-700"
                    enter-from-class="translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transform transition ease-in-out duration-500 sm:duration-700"
                    leave-from-class="translate-x-0"
                    leave-to-class="translate-x-full"
                >
                    <div v-show="show" class="pointer-events-auto w-screen max-w-md">
                        <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-zinc-900 shadow-2xl">
                            <div class="flex-1 overflow-y-auto px-4 py-8 sm:px-6">
                                <!-- Header -->
                                <div class="flex items-start justify-between border-b border-zinc-100 dark:border-zinc-800 pb-6 mb-8">
                                    <h2 class="text-2xl font-black font-outfit text-zinc-900 dark:text-white">
                                        Mi Carrito
                                        <span class="text-zinc-400 font-medium text-sm ml-2">({{ Object.keys(cart.items).length }} ítems)</span>
                                    </h2>
                                    <div class="ml-3 flex h-7 items-center">
                                        <button type="button" @click="emit('close')" class="relative -m-2 p-2 text-zinc-400 hover:text-zinc-500">
                                            <span class="material-symbols-outlined w-6 h-6">close</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Cart Items -->
                                <div class="space-y-6">
                                    <div v-for="(item, id) in cart.items" :key="id" class="flex items-center gap-4 group">
                                        <div class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-50 dark:border-zinc-800">
                                            <img v-if="item.image" :src="item.image" class="h-full w-full object-cover group-hover:scale-110 transition-transform duration-500">
                                            <div v-else class="w-full h-full flex items-center justify-center text-zinc-300">
                                                <span class="material-symbols-outlined text-4xl">image</span>
                                            </div>
                                        </div>

                                        <div class="flex flex-1 flex-col">
                                            <div class="flex justify-between text-sm font-bold text-zinc-900 dark:text-white mb-1">
                                                <h3 class="line-clamp-1">{{ item.name }}</h3>
                                                <p class="ml-4 tabular-nums">Bs. {{ formatNumber(item.price * item.quantity) }}</p>
                                            </div>
                                            <p class="text-[10px] text-zinc-400 uppercase tracking-widest font-black mb-3">{{ item.code }}</p>
                                            
                                            <div class="flex items-center justify-between text-sm">
                                                <!-- Quantity Controls -->
                                                <div class="flex items-center bg-zinc-50 dark:bg-zinc-800 rounded-xl overflow-hidden border border-zinc-100 dark:border-zinc-800">
                                                    <button @click="updateQuantity(id, -1)" class="px-2 py-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                                                        <span class="material-symbols-outlined text-[14px] text-zinc-400">remove</span>
                                                    </button>
                                                    <span class="px-3 font-black tabular-nums text-zinc-700 dark:text-zinc-200">{{ item.quantity }}</span>
                                                    <button @click="updateQuantity(id, 1)" class="px-2 py-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                                                        <span class="material-symbols-outlined text-[14px] text-zinc-400">add</span>
                                                    </button>
                                                </div>

                                                <button @click="removeItem(id)" class="font-bold text-rose-500 hover:text-rose-600 uppercase text-[10px] tracking-widest">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="Object.keys(cart.items).length === 0" class="py-20 text-center">
                                        <div class="w-16 h-16 bg-zinc-50 dark:bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-4 text-zinc-300">
                                            <span class="material-symbols-outlined text-4xl">shopping_cart</span>
                                        </div>
                                        <p class="text-zinc-500 font-medium">Su carrito está vacío</p>
                                        <button @click="emit('close')" class="mt-4 text-indigo-600 font-black uppercase text-[10px] tracking-widest hover:underline">Seguir comprando</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Checkout Section -->
                            <div v-if="Object.keys(cart.items).length > 0" class="border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/20 px-4 py-8 sm:px-6 space-y-6">
                                
                                <!-- Customer Form -->
                                <div class="space-y-4">
                                    <h4 class="font-outfit font-black text-xs text-zinc-400 uppercase tracking-[0.2em]">Sus Datos</h4>
                                    <div class="grid grid-cols-1 gap-4">
                                        <div class="space-y-1">
                                            <label class="block text-xs font-bold text-zinc-500">Nombre Completo</label>
                                            <input v-model="form.customer_name" type="text" placeholder="Ej: Juan Pérez" class="w-full bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-2xl px-4 py-2 text-sm text-zinc-900 dark:text-white focus:ring-brand-primary focus:border-brand-primary">
                                            <div v-if="form.errors.customer_name" class="text-xs text-red-500">{{ form.errors.customer_name }}</div>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-bold text-zinc-500">Cédula de Identidad (CI)</label>
                                            <input v-model="form.customer_ci" type="text" placeholder="Ej: 1234567 LP" class="w-full bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded-2xl px-4 py-2 text-sm text-zinc-900 dark:text-white focus:ring-brand-primary focus:border-brand-primary">
                                            <div v-if="form.errors.customer_ci" class="text-xs text-red-500">{{ form.errors.customer_ci }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="flex justify-between text-base font-bold text-zinc-900 dark:text-white">
                                        <p class="font-outfit text-lg">Total del Pedido</p>
                                        <p class="text-2xl font-black text-indigo-600 font-outfit tabular-nums">Bs. {{ formatNumber(cart.total) }}</p>
                                    </div>

                                    <div class="mt-6">
                                        <button @click="sendOrder" :disabled="form.processing"
                                                class="w-full flex items-center justify-center rounded-3xl border border-transparent bg-emerald-600 px-6 py-4 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-emerald-200 dark:shadow-emerald-900/10 hover:bg-emerald-700 transition-all active:scale-95 group disabled:opacity-50">
                                            <svg class="w-6 h-6 mr-3 transition-transform group-hover:scale-125" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.414 0 .018 5.393 0 12.03c0 2.123.551 4.197 1.595 6.03L0 24l6.111-1.605A11.75 11.75 0 0012.046 24c6.634 0 12.034-5.4 12.037-12.037.001-3.213-1.248-6.233-3.518-8.514z"/></svg>
                                            Enviar pedido por WhatsApp
                                        </button>
                                    </div>
                                    <div class="mt-4 flex justify-center text-center text-xs text-zinc-500">
                                        <button type="button" @click="emit('close')" class="font-bold text-zinc-400 hover:text-zinc-600 uppercase tracking-widest">
                                            Seguir comprando
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </div>
</template>
