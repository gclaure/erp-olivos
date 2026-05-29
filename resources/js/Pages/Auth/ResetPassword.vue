<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Head, usePage } from '@inertiajs/vue3';

const props = defineProps({
    email: String,
    token: String,
    status: String,
});

const page = usePage();
const appName = computed(() => page.props.appName || 'Tu Inventario');
const company = computed(() => page.props.company);

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

// Password Strength Logic
const strengthScore = ref(0);
const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e'];

watch(() => form.password, (val) => {
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    strengthScore.value = score;
});

const strengthColor = computed(() => {
    if (strengthScore.value === 0) return '#e2e8f0';
    return colors[strengthScore.value - 1];
});

import '../../../css/reset-password.css';
</script>

<template>
    <Head title="Restablecer Contraseña" />

    <div class="rp-root">
        <!-- ══ LEFT PANEL ══ -->
        <div class="rp-left">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>

            <div class="deco-icon deco-1">
                <span class="material-symbols-outlined">lock_reset</span>
            </div>
            <div class="deco-icon deco-2">
                <span class="material-symbols-outlined">shield_lock</span>
            </div>
            <div class="deco-icon deco-3">
                <span class="material-symbols-outlined">key</span>
            </div>

            <div class="rp-left-content">
                <div class="brand">
                    <div class="brand-icon">
                        <span class="material-symbols-outlined">inventory_2</span>
                    </div>
                    <div class="brand-name">
                        {{ appName }} <span>Enterprise</span>
                    </div>
                </div>
                <div>
                    <h1 class="hero-title">
                        Crea tu nueva<br/>
                        <span class="gradient-text">Contraseña</span><br/>
                        Segura
                    </h1>
                    <p class="hero-subtitle">
                        Elige una contraseña robusta para proteger tu acceso al sistema de inventario empresarial.
                    </p>
                </div>
            </div>
        </div>

        <!-- ══ RIGHT PANEL ══ -->
        <div class="rp-right">
            <!-- Back link -->
            <a class="back-link" :href="route('login')">
                <span class="material-symbols-outlined">arrow_back</span>
                Volver al inicio de sesión
            </a>

            <div class="rp-form-wrap">
                <!-- Mobile brand -->
                <div class="mobile-brand">
                    <div class="mobile-brand-icon">
                        <img v-if="company?.logo_path" :src="'/storage/' + company.logo_path" class="w-full h-full object-contain" alt="Logo">
                        <span v-else class="material-symbols-outlined">inventory_2</span>
                    </div>
                    <div class="mobile-brand-name">{{ appName }}</div>
                </div>

                <!-- Heading -->
                <div class="rp-heading">
                    <h2 class="rp-title">Restablecer Contraseña</h2>
                    <p class="rp-subtitle">Ingresa y confirma tu nueva contraseña para recuperar el acceso.</p>
                </div>

                <!-- Session Status -->
                <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="rp-form">
                    <!-- Email -->
                    <div class="field">
                        <label class="field-label">Correo Electrónico</label>
                        <div class="field-input-wrap">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">mail</span>
                            </span>
                            <input
                                v-model="form.email"
                                class="field-input"
                                type="email"
                                required
                                autocomplete="email"
                                placeholder="nombre@empresa.com"
                                readonly
                            />
                        </div>
                        <div v-if="form.errors.email" class="field-error">
                            <span class="material-symbols-outlined">error</span>
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="field">
                        <label class="field-label">Nueva Contraseña</label>
                        <div class="field-input-wrap">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">lock</span>
                            </span>
                            <input
                                v-model="form.password"
                                class="field-input"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="toggle-password"
                            >
                                <span class="material-symbols-outlined">
                                    {{ showPassword ? 'visibility_off' : 'visibility' }}
                                </span>
                            </button>
                        </div>
                        <!-- Strength bar -->
                        <div class="strength-bar">
                            <div v-for="i in 4" :key="i" 
                                class="strength-seg" 
                                :style="{ background: i <= strengthScore ? strengthColor : '#e2e8f0' }"
                            ></div>
                        </div>
                        <div v-if="form.errors.password" class="field-error">
                            <span class="material-symbols-outlined">error</span>
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="field">
                        <label class="field-label">Confirmar Contraseña</label>
                        <div class="field-input-wrap">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">lock_open</span>
                            </span>
                            <input
                                v-model="form.password_confirmation"
                                class="field-input"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                class="toggle-password"
                            >
                                <span class="material-symbols-outlined">
                                    {{ showPasswordConfirmation ? 'visibility_off' : 'visibility' }}
                                </span>
                            </button>
                        </div>
                        <div v-if="form.errors.password_confirmation" class="field-error">
                            <span class="material-symbols-outlined">error</span>
                            {{ form.errors.password_confirmation }}
                        </div>
                    </div>

                    <!-- Info pill -->
                    <div class="info-pill">
                        <span class="material-symbols-outlined">info</span>
                        <p>Usa al menos 8 caracteres combinando letras mayúsculas, minúsculas, números y símbolos.</p>
                    </div>

                    <!-- Submit -->
                    <button class="submit-btn" :disabled="form.processing" type="submit">
                        <template v-if="form.processing">Restableciendo...</template>
                        <template v-else>
                            Restablecer Contraseña
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </template>
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="rp-footer">
                <p>
                    ¿Necesita ayuda?
                    <a href="https://api.whatsapp.com/send/?phone=59172281455" target="_blank">Contacte a soporte técnico.</a>
                </p>
            </div>
        </div>
    </div>
</template>
