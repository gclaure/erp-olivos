<script setup>
import { computed, defineAsyncComponent } from 'vue';
import { useForm, usePage, Head, Link } from '@inertiajs/vue3';

const AuthThreeCanvas = defineAsyncComponent(() => import('@/Components/AuthThreeCanvas.vue'));

defineProps({
    status: String,
});

const page = usePage();
const appName = computed(() => page.props.appName || 'ERP Los Olivos');

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Recuperar Contraseña" />

    <div class="min-h-screen w-full flex bg-surface dark:bg-secondary-950 font-sans selection:bg-[#73AC32] selection:text-white">
        <!-- ══ LEFT PANEL: BRAND SHOWCASE (Desktop >= 1024px) ══ -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-[52%] bg-zinc-950 dark:bg-black text-white p-8 xl:p-14 relative overflow-hidden flex-col justify-between border-r border-zinc-800/80">
            <!-- 3D Three.js Interactive Particles & Logistics Grid -->
            <AuthThreeCanvas />

            <!-- Background Glow Effects -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#73AC32]/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-[#44773C]/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:24px_24px]"></div>

            <!-- Top: Brand Logo Showcase (Prominent & High-Visibility) -->
            <div class="relative z-20">
                <div class="flex items-center gap-3">
                    <div class="h-20 sm:h-24 xl:h-28 w-auto max-w-[340px] flex items-center justify-start py-2">
                        <img 
                            src="/img/logo-dark.png" 
                            class="max-h-full max-w-full object-contain drop-shadow-md" 
                            :alt="appName"
                            fetchpriority="high"
                            loading="eager"
                        >
                    </div>
                </div>
            </div>

            <!-- Middle: Hero Statement & Value Propositions -->
            <div class="relative z-10 space-y-6 max-w-lg my-auto py-8">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-[#73AC32]/15 border border-[#73AC32]/30 text-[#A8C98A] rounded-full text-xs font-black uppercase tracking-wider shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-[#73AC32] animate-pulse"></span>
                    <span>Seguridad y Recuperación de Cuenta</span>
                </div>

                <h1 class="text-3xl xl:text-4xl font-black tracking-tight leading-tight text-white">
                    Restablece tu acceso de forma rápida y confiable.
                </h1>

                <p class="text-sm xl:text-base text-zinc-400 leading-relaxed font-medium">
                    Te enviaremos un enlace seguro a tu bandeja de entrada para que puedas generar una nueva clave y continuar con tus operaciones.
                </p>

                <!-- Value Highlights Grid -->
                <div class="grid grid-cols-3 gap-3 pt-4">
                    <div class="p-3.5 rounded-2xl bg-zinc-900/80 border border-zinc-800/80 backdrop-blur-sm">
                        <span class="material-symbols-outlined text-[#73AC32] text-xl">mark_email_read</span>
                        <div class="font-black text-xs text-white mt-1">Envío Rápido</div>
                        <div class="text-[10px] text-zinc-400 mt-0.5">Enlace al instante</div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-zinc-900/80 border border-zinc-800/80 backdrop-blur-sm">
                        <span class="material-symbols-outlined text-[#A8C98A] text-xl">encrypted</span>
                        <div class="font-black text-xs text-white mt-1">Acceso Seguro</div>
                        <div class="text-[10px] text-zinc-400 mt-0.5">Token encriptado</div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-zinc-900/80 border border-zinc-800/80 backdrop-blur-sm">
                        <span class="material-symbols-outlined text-[#73AC32] text-xl">support_agent</span>
                        <div class="font-black text-xs text-white mt-1">Soporte Directo</div>
                        <div class="text-[10px] text-zinc-400 mt-0.5">Asistencia 24/7</div>
                    </div>
                </div>
            </div>

            <!-- Bottom: System Status Pill & Version -->
            <div class="relative z-10 flex items-center justify-between border-t border-zinc-800/80 pt-4 text-xs">
                <div class="flex items-center gap-2 text-zinc-400">
                    <span class="w-2 h-2 rounded-full bg-[#73AC32] animate-ping"></span>
                    <span class="font-bold">Portal de Seguridad</span>
                </div>
            </div>
        </div>

        <!-- ══ RIGHT PANEL: RECOVERY FORM (Responsive Mobile, Tablet & Desktop) ══ -->
        <div class="flex-1 flex flex-col justify-center items-center p-5 sm:p-8 lg:p-12 overflow-y-auto relative bg-[#0d120c] text-white">
            <!-- Decorative Ambient Lighting & Grid -->
            <div class="absolute inset-0 opacity-[0.035] pointer-events-none bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:24px_24px]"></div>
            <div class="absolute -top-24 -right-24 w-80 h-80 bg-[#73AC32]/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-[#44773C]/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="w-full max-w-md space-y-6 relative z-10">
                <!-- Mobile / Tablet Brand Header (Seamless & Prominent) -->
                <div class="lg:hidden text-center space-y-2.5 pt-2 mb-2">
                    <div class="h-20 sm:h-24 w-auto max-w-[280px] mx-auto flex items-center justify-center">
                        <img 
                            src="/img/logo-dark.png" 
                            class="max-h-full max-w-full object-contain drop-shadow-xl" 
                            :alt="appName"
                            fetchpriority="high"
                            loading="eager"
                        >
                    </div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#73AC32]/10 border border-[#73AC32]/30 text-[#A8C98A] rounded-full text-[11px] font-black uppercase tracking-wider shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#73AC32] animate-pulse"></span>
                        <span>{{ appName }}</span>
                    </div>
                </div>

                <!-- Form Card Container (Dark Executive Glassmorphism) -->
                <div class="bg-[#141b12]/90 backdrop-blur-2xl rounded-3xl border border-zinc-800/90 dark:border-secondary-700/80 p-7 sm:p-9 shadow-2xl shadow-black/80 transition-all space-y-6">
                    <!-- Back Link -->
                    <div>
                        <Link 
                            :href="route('login')" 
                            class="inline-flex items-center gap-1.5 text-xs font-black text-[#A8C98A] hover:text-[#73AC32] transition-colors uppercase tracking-wider"
                        >
                            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                            <span>Volver al inicio de sesión</span>
                        </Link>
                    </div>

                    <!-- Heading -->
                    <div class="space-y-1.5">
                        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                            ¿Olvidaste tu contraseña?
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-400 font-medium leading-relaxed">
                            Ingresa tu correo electrónico registrado y te enviaremos instrucciones de recuperación.
                        </p>
                    </div>

                    <!-- Session Status Alert -->
                    <div v-if="status" class="p-3.5 rounded-2xl bg-[#44773C]/20 border border-[#73AC32]/40 text-[#A8C98A] text-xs font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-base text-[#73AC32]">check_circle</span>
                        <span>{{ status }}</span>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Email Field -->
                        <div class="space-y-1.5">
                            <label class="block text-[11px] font-black text-zinc-400 uppercase tracking-wider" for="email">
                                Correo Electrónico
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-500">
                                    <span class="material-symbols-outlined text-[18px]">mail</span>
                                </span>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="usuario@empresa.com"
                                    required
                                    autofocus
                                    autocomplete="email"
                                    class="w-full min-h-[46px] pl-10 pr-4 py-2.5 bg-[#090d08] border border-zinc-800/90 rounded-2xl text-sm font-semibold text-white placeholder-zinc-500 focus:ring-2 focus:ring-[#73AC32]/30 focus:border-[#73AC32] transition-all shadow-inner"
                                />
                            </div>
                            <p v-if="form.errors.email" class="text-xs font-bold text-rose-400 flex items-center gap-1 mt-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>
                                <span>{{ form.errors.email }}</span>
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full min-h-[48px] mt-3 flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-[#73AC32] to-[#44773C] hover:from-[#5C8C28] hover:to-[#365f30] text-white rounded-2xl font-black text-xs uppercase tracking-wider shadow-lg shadow-[#73AC32]/25 active:scale-[0.99] transition-all disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                        >
                            <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span v-if="form.processing">Enviando enlace...</span>
                            <template v-else>
                                <span>Enviar Instrucciones</span>
                                <span class="material-symbols-outlined text-[16px]">send</span>
                            </template>
                        </button>
                    </form>

                    <!-- Footer Help Link -->
                    <div class="pt-4 border-t border-zinc-800/80 text-center text-xs text-zinc-400">
                        ¿Necesitas ayuda adicional?
                        <a 
                            href="https://api.whatsapp.com/send/?phone=59172281455" 
                            target="_blank" 
                            class="font-black text-[#A8C98A] hover:text-[#73AC32] hover:underline ml-1 inline-flex items-center gap-0.5"
                        >
                            <span>Contactar a Soporte</span>
                            <span class="material-symbols-outlined text-[12px]">open_in_new</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
