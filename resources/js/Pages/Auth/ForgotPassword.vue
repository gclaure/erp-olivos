<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};

import '../../../css/forgot-password.css';
</script>

<template>
    <Head title="Recuperar Contraseña" />

    <div class="fp-root">
        <!-- ══ LEFT PANEL ══ -->
        <div class="fp-left">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>

            <!-- Decorative icons -->
            <div class="deco-icon deco-1">
                <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <div class="deco-icon deco-2">
                <span class="material-symbols-outlined">package_2</span>
            </div>
            <div class="deco-icon deco-3">
                <span class="material-symbols-outlined">deployed_code</span>
            </div>

            <div class="fp-left-content">
                <!-- Brand -->
                <div class="brand">
                    <div class="brand-icon">
                        <span class="material-symbols-outlined">inventory_2</span>
                    </div>
                    <div class="brand-name">
                        Tu Inventario <span>Enterprise</span>
                    </div>
                </div>

                <!-- Hero -->
                <div>
                    <h1 class="hero-title">Recuperar Acceso</h1>
                    <p class="hero-subtitle">
                        Le ayudaremos a restablecer su contraseña para que pueda volver a gestionar su inventario de inmediato.
                    </p>
                </div>
            </div>
        </div>

        <!-- ══ RIGHT PANEL ══ -->
        <div class="fp-right">
            <!-- Back link -->
            <Link class="back-link" :href="route('login')">
                <span class="material-symbols-outlined">arrow_back</span>
                Volver al inicio de sesión
            </Link>

            <!-- Form -->
            <div class="fp-form-wrap">
                <!-- Heading -->
                <div class="fp-heading">
                    <h2 class="fp-title">¿Olvidó su contraseña?</h2>
                    <p class="fp-subtitle">
                        Ingrese su correo electrónico registrado y le enviaremos instrucciones para crear una nueva.
                    </p>
                </div>

                <!-- Session Status -->
                <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="fp-form">
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
                                placeholder="nombre@empresa.com"
                                required 
                                autofocus
                                autocomplete="email" 
                            />
                        </div>
                        <div v-if="form.errors.email" class="field-error">
                            <span class="material-symbols-outlined">error</span>
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <!-- Submit -->
                    <button class="submit-btn" :disabled="form.processing" type="submit">
                        <span v-if="form.processing">Enviando...</span>
                        <span v-else>Enviar Instrucciones</span>
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="fp-footer">
                <p>
                    ¿Necesita ayuda?
                    <a href="https://api.whatsapp.com/send/?phone=59172281455" target="_blank">Contacte a soporte técnico.</a>
                </p>
            </div>
        </div>
    </div>
</template>
