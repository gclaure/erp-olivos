<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, Head, Link } from '@inertiajs/vue3';

const page = usePage();
const company = computed(() => page.props.company);
const appName = computed(() => page.props.appName);
const flash = computed(() => page.props.flash);

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login.store'), {
        preserveScroll: true,
        onFinish: () => form.reset('password'),
    });
};
import '../../../css/login.css';
</script>

<template>
    <Head title="Iniciar Sesión" />

    <div class="login-root">
        <!-- ... (paneles sin cambios) ... -->
        <div class="left-panel">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>

            <div class="dot-grid"></div>

            <div class="float-icon">
                <div class="float-icon-glow"></div>
                <span class="material-symbols-outlined">package_2</span>
            </div>

            <div class="left-content">
                <div class="brand">
                    <div class="brand-name">
                        {{ appName }} <span>Enterprise</span>
                    </div>
                </div>

                <div class="hero">
                    <div class="conveyor-icon">
                        <span class="material-symbols-outlined">conveyor_belt</span>
                    </div>
                    <h1 class="hero-title">
                        Optimice su Inventario con<br/>
                        <span class="gradient-text">Inteligencia Proactiva</span>
                    </h1>
                    <p class="hero-subtitle">
                        Gestión en tiempo real, análisis predictivo y control total de su cadena de suministro.
                    </p>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <div class="right-inner">
                <!-- Mobile brand -->
                <div class="mobile-brand">
                    <div class="mobile-brand-icon">
                        <img :src="company?.logo_url || '/img/logo-inventory.png'" 
                             class="w-full h-full object-contain" 
                             alt="Logo"
                             fetchpriority="high"
                             loading="eager">
                    </div>
                    <div class="mobile-brand-name">{{ appName }}</div>
                </div>

                <!-- Heading -->
                <div class="login-heading">
                    <h2 class="login-title">Iniciar Sesión</h2>
                    <p class="login-subtitle">Ingrese sus credenciales para continuar</p>
                </div>

                <!-- Session status / Errors -->
                <div v-if="flash.status" class="mb-4 font-medium text-sm text-green-600">
                    {{ flash.status }}
                </div>

                <div v-if="flash.error" class="mb-4 rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <span class="material-symbols-outlined text-red-400">error</span>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                {{ flash.error }}
                            </h3>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="login-form">
                    <!-- Email -->
                    <div class="field">
                        <label class="field-label" for="email">Correo Electrónico</label>
                        <div class="field-input-wrap">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">mail</span>
                            </span>
                            <input
                                id="email"
                                v-model="form.email"
                                class="field-input"
                                type="email"
                                placeholder="nombre@empresa.com"
                                required 
                                autofocus
                                autocomplete="email"
                            />
                        </div>
                        <div v-if="form.errors.email" class="field-error">
                            <span class="material-symbols-outlined">error</span>
                            <span>{{ form.errors.email }}</span>
                        </div>
                    </div>

                    <div class="field">
                        <label class="field-label" for="password">Contraseña</label>
                        <div class="field-input-wrap">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">lock</span>
                            </span>
                            <input
                                id="password"
                                v-model="form.password"
                                class="field-input"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="toggle-password"
                                aria-label="Mostrar/Ocultar contraseña"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); border: none; background: none; color: #6366f1; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 50; padding: 5px;"
                            >
                                <span class="material-symbols-outlined" style="font-size: 20px;">
                                    {{ showPassword ? 'visibility_off' : 'visibility' }}
                                </span>
                            </button>
                        </div>
                        <div v-if="form.errors.password" class="field-error">
                            <span class="material-symbols-outlined">error</span>
                            <span>{{ form.errors.password }}</span>
                        </div>
                    </div>

                    <!-- Remember + Forgot -->
                    <div class="form-row">
                        <div class="remember-wrap">
                            <input
                                id="remember"
                                v-model="form.remember"
                                class="remember-check"
                                type="checkbox"
                            />
                            <label class="remember-label" for="remember">
                                Mantener sesión iniciada
                            </label>
                        </div>
                        <Link v-if="page.props.canResetPassword" :href="route('password.request')" class="forgot-link">
                            ¿Olvidó su contraseña?
                        </Link>
                    </div>

                    <!-- Submit -->
                    <button class="submit-btn" :disabled="form.processing" type="submit">
                        <div v-if="form.processing" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Accediendo...</span>
                        </div>
                        <template v-else>
                            Acceder al Sistema
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </template>
                    </button>
                </form>
                <!-- Footer -->
                <div class="login-footer">
                    <div class="status-pill">
                        <div class="status-dot">
                            <div class="status-dot-ping"></div>
                            <div class="status-dot-core"></div>
                        </div>
                        <span class="status-text">Estado del Sistema: Operativo</span>
                    </div>

                    <p v-if="page.props.canRegister" class="register-row">
                        ¿No tienes cuenta?
                        <Link :href="route('register')" class="register-link">
                            Regístrate
                        </Link>
                    </p>

                    <div class="version-text">v3.5.0</div>
                </div>
            </div>
        </div>
    </div>
</template>
