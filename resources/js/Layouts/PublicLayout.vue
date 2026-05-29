<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { Link, usePage, Head } from '@inertiajs/vue3';
import CartDrawer from '@/Components/Public/CartDrawer.vue';
import BranchSelectorModal from '@/Pages/Public/Catalog/Partials/BranchSelectorModal.vue';

const { props } = usePage();
const company = props.company;
const settings = computed(() => usePage().props.ecommerceSettings || {});
const cartOpen = ref(false);

const dynamicStyles = computed(() => ({
    '--brand-primary': settings.value.primary_color || '#4f46e5',
    '--brand-primary-hover': settings.value.primary_color ? settings.value.primary_color + 'dd' : '#4338ca',
    '--brand-secondary': settings.value.secondary_color || '#0f172a',
    '--brand-secondary-dark': settings.value.tertiary_color || '#020617',
    '--brand-background': settings.value.background_color || '#0f172a',
    '--brand-card': settings.value.card_color || '#1e293b',
    '--brand-text': settings.value.text_color || '#f8fafc',
}));

const toggleCart = () => {
    cartOpen.value = !cartOpen.value;
};

// Escuchar evento global para abrir el carrito
const cartCount = computed(() => {
    const cart = usePage().props.cart;
    if (!cart || !cart.items) return 0;
    return Object.values(cart.items).reduce((acc, item) => acc + item.quantity, 0);
});

// Branch Logic
const branches = computed(() => usePage().props.ecommerceBranches || []);
const showBranchModal = ref(false);

const getStoredBranchId = () => {
    const stored = localStorage.getItem('selected_branch_id');
    if (stored && branches.value.some(b => b.id == stored)) {
        return parseInt(stored);
    }
    return null;
};

const selectedBranchId = ref(getStoredBranchId());

const activeBranch = computed(() => {
    if (selectedBranchId.value) {
        return branches.value.find(b => b.id === selectedBranchId.value);
    }
    return branches.value.find(b => b.is_main) || branches.value[0];
});

onMounted(() => {
    window.addEventListener('cart-open', () => {
        cartOpen.value = true;
    });

    if (branches.value.length > 1 && !localStorage.getItem('selected_branch_id')) {
        setTimeout(() => {
            showBranchModal.value = true;
        }, 1000);
    }
});

const handleBranchSelect = (id) => {
    selectedBranchId.value = id;
    localStorage.setItem('selected_branch_id', id);
    showBranchModal.value = false;
    window.dispatchEvent(new CustomEvent('branch-updated', { detail: id }));
};
</script>

<template>
    <div :style="dynamicStyles" class="min-h-screen flex flex-col font-sans antialiased bg-brand-background text-brand-text selection:bg-brand-primary selection:text-white">
        <!-- Crimson Accent Top Bar -->
        <div class="h-1 bg-brand-primary w-full fixed top-0 z-[60]"></div>

        <!-- Translucent Premium Header -->
        <header :style="{ 
                    backgroundColor: (settings.secondary_color || '#0f172a') + 'cc',
                    borderColor: (settings.text_color || '#ffffff') + '15'
                }" 
                class="sticky top-0 z-50 backdrop-blur-md border-b transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Brand Section -->
                    <div class="flex-shrink-0 flex items-center">
                        <Link :href="route('public.catalog', { company_slug: props.company_slug })" class="group flex flex-col items-start hover:scale-105 transition-transform duration-300">
                            <img v-if="settings.logo_url" :src="settings.logo_url" :alt="settings.store_name" class="h-12 w-auto object-contain">
                            <span v-else class="text-3xl font-black tracking-widest text-brand-text font-manrope uppercase leading-none group-hover:text-brand-primary transition-colors">
                                {{ settings.store_name || 'KING\'S' }}<span class="text-brand-primary">{{ settings.store_name ? '' : 'BRAND' }}</span>
                            </span>
                        </Link>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-4 sm:space-x-8">
                        <!-- Branch Selector trigger -->
                        <button v-if="branches.length > 1" 
                                @click="showBranchModal = true" 
                                class="hidden md:flex items-center gap-2 px-4 py-2 bg-white/5 border border-white/10 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-brand-primary hover:border-brand-primary transition-all group/branch">
                            <span class="material-symbols-outlined text-sm text-brand-primary group-hover/branch:text-white transition-colors">location_on</span>
                            <span class="opacity-70 group-hover/branch:opacity-100">{{ activeBranch?.name || 'Sucursal' }}</span>
                        </button>

                        <button @click="cartOpen = true" class="relative group p-2 text-brand-text opacity-70 hover:opacity-100 hover:text-brand-primary transition-all">
                            <span class="material-symbols-outlined scale-110">shopping_cart</span>
                            <span v-if="cartCount > 0" class="absolute -top-1 -right-1 bg-brand-primary text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center shadow-lg animate-bounce">
                                {{ cartCount }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Section -->
        <main class="flex-grow">
            <slot />
        </main>

        <!-- Dynamic Premium Footer -->
        <footer :style="{ 
                    backgroundColor: settings.secondary_color || '#0f172a',
                    borderColor: (settings.text_color || '#ffffff') + '15'
                }" 
                class="py-20 border-t mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-16 sm:gap-12">
                    <!-- Brand Detail -->
                    <div class="space-y-8 col-span-1">
                        <div class="flex flex-col">
                            <span class="text-xl font-black text-brand-text font-manrope uppercase tracking-tighter">{{ settings.store_name || 'KING\'S BRAND' }}</span>
                            <span class="text-[10px] font-bold text-brand-text opacity-40 uppercase tracking-widest">{{ settings.store_slogan || 'Premium Goods' }}</span>
                        </div>
                        <p class="text-sm text-brand-text opacity-60 leading-relaxed max-w-xs">
                            {{ settings.store_description || 'Definiendo el estándar de calidad y elegancia. Curaduría de piezas únicas para el conocedor moderno.' }}
                        </p>
                    </div>

                    <!-- Value Propositions -->
                    <div class="col-span-1 md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-x-12 gap-y-10">
                        <div class="flex items-start gap-5 group">
                            <div :style="{ backgroundColor: (settings.text_color || '#ffffff') + '05', borderColor: (settings.text_color || '#ffffff') + '10' }"
                                 class="w-10 h-10 rounded-lg flex items-center justify-center border group-hover:border-brand-primary transition-colors">
                                <span class="material-symbols-outlined text-brand-primary scale-90">verified</span>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-text mb-2">Garantía Total</h4>
                                <p class="text-[11px] text-brand-text opacity-50 leading-relaxed italic">Productos seleccionados bajo los más altos estándares de calidad para asegurar tu satisfacción.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-5 group">
                            <div :style="{ backgroundColor: (settings.text_color || '#ffffff') + '05', borderColor: (settings.text_color || '#ffffff') + '10' }"
                                 class="w-10 h-10 rounded-lg flex items-center justify-center border group-hover:border-brand-primary transition-colors">
                                <span class="material-symbols-outlined text-brand-primary scale-90">local_shipping</span>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-text mb-2">Envíos Nacionales</h4>
                                <p class="text-[11px] text-brand-text opacity-50 leading-relaxed italic">Llegamos a donde estés con logística rápida, segura y seguimiento en tiempo real de tu pedido.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-5 group">
                            <div :style="{ backgroundColor: (settings.text_color || '#ffffff') + '05', borderColor: (settings.text_color || '#ffffff') + '10' }"
                                 class="w-10 h-10 rounded-lg flex items-center justify-center border group-hover:border-brand-primary transition-colors">
                                <span class="material-symbols-outlined text-brand-primary scale-90">support_agent</span>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-text mb-2">Soporte Experto</h4>
                                <p class="text-[11px] text-brand-text opacity-50 leading-relaxed italic">Nuestro equipo está listo para asesorarte personalmente y resolver todas tus dudas antes de comprar.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-5 group">
                            <div :style="{ backgroundColor: (settings.text_color || '#ffffff') + '05', borderColor: (settings.text_color || '#ffffff') + '10' }"
                                 class="w-10 h-10 rounded-lg flex items-center justify-center border group-hover:border-brand-primary transition-colors">
                                <span class="material-symbols-outlined text-brand-primary scale-90">chat_bubble</span>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-text mb-2">Pedido Directo</h4>
                                <p class="text-[11px] text-brand-text opacity-50 leading-relaxed italic">Facilitamos tu compra conectándote al instante con nosotros para una atención ágil y sin complicaciones.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div :style="{ borderColor: (settings.text_color || '#ffffff') + '10' }" 
                     class="mt-20 pt-10 border-t flex flex-col md:flex-row justify-between items-center gap-8 text-[11px] text-brand-text opacity-40 font-bold uppercase tracking-[0.2em]">
                    <span>© {{ new Date().getFullYear() }} {{ settings.store_name }}. Todos los derechos reservados.</span>
                    <div class="flex space-x-10">
                        <a v-if="company?.instagram" :href="company.instagram" target="_blank" class="hover:text-brand-primary transition-colors">Instagram</a>
                        <a v-if="company?.facebook" :href="company.facebook" target="_blank" class="hover:text-brand-primary transition-colors">Facebook</a>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Cart Side Panel Component -->
        <CartDrawer :show="cartOpen" @close="cartOpen = false" />

        <!-- Branch Selector Modal Global -->
        <BranchSelectorModal 
            :show="showBranchModal"
            :branches="branches"
            :ecommerceSettings="settings"
            @select="handleBranchSelect"
            @close="showBranchModal = false"
        />
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap');

body {
    background-color: var(--brand-secondary);
}

/* Dynamic Scrollbar */
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: var(--brand-secondary); }
::-webkit-scrollbar-thumb { background: var(--brand-primary); border-radius: 10px; opacity: 0.5; }
::-webkit-scrollbar-thumb:hover { background: var(--brand-primary); }

.font-manrope {
    font-family: 'Manrope', sans-serif;
}

.bg-brand-secondary {
    background-color: var(--brand-secondary);
}

.bg-brand-secondary-dark, .bg-brand-tertiary {
    background-color: var(--brand-secondary-dark);
}

.bg-brand-background {
    background-color: var(--brand-background);
}

.bg-brand-card {
    background-color: var(--brand-card);
}

.bg-brand-primary {
    background-color: var(--brand-primary);
}

.text-brand-primary {
    color: var(--brand-primary);
}

.text-brand-text {
    color: var(--brand-text);
}

.border-brand-primary {
    border-color: var(--brand-primary);
}

.selection\:bg-brand-primary::selection {
    background-color: var(--brand-primary);
}

.hover\:text-brand-primary:hover {
    color: var(--brand-primary);
}

.group:hover .group-hover\:text-brand-primary {
    color: var(--brand-primary);
}
</style>
