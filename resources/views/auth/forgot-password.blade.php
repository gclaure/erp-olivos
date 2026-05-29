<x-auth-layout>
<div class="fp-root">

    {{-- ══ LEFT PANEL ══ --}}
    <div class="fp-left">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>

        {{-- Decorative icons --}}
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
            {{-- Brand --}}
            <div class="brand">
                <div class="brand-icon">
                    <span class="material-symbols-outlined">inventory_2</span>
                </div>
                <div class="brand-name">
                    {{ config('app.name') }} <span>Enterprise</span>
                </div>
            </div>

            {{-- Hero --}}
            <div>
                <h1 class="hero-title">Recuperar Acceso</h1>
                <p class="hero-subtitle">
                    Le ayudaremos a restablecer su contraseña para que pueda volver a gestionar su inventario de inmediato.
                </p>
            </div>
        </div>
    </div>

    {{-- ══ RIGHT PANEL ══ --}}
    <div class="fp-right">

        {{-- Back link --}}
        <a class="back-link" href="{{ route('login') }}">
            <span class="material-symbols-outlined">arrow_back</span>
            Volver al inicio de sesión
        </a>

        {{-- Form --}}
        <div class="fp-form-wrap">

            {{-- Mobile brand --}}
            <div class="mobile-brand">
                <div class="mobile-brand-icon">
                    @if($logo = \App\Facades\CompanyFacade::getCompany()?->logo_path)
                        <img src="{{ Storage::url($logo) }}" class="w-full h-full object-contain" alt="Logo">
                    @else
                        <span class="material-symbols-outlined">inventory_2</span>
                    @endif
                </div>
                <div class="mobile-brand-name">{{ config('app.name') }}</div>
            </div>

            {{-- Heading --}}
            <div class="fp-heading">
                <h2 class="fp-title">¿Olvidó su contraseña?</h2>
                <p class="fp-subtitle">
                    Ingrese su correo electrónico registrado y le enviaremos instrucciones para crear una nueva.
                </p>
            </div>

            {{-- Session Status --}}
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="fp-form">
                @csrf

                {{-- Email --}}
                <div class="field">
                    <label class="field-label">Correo Electrónico</label>
                    <div class="field-input-wrap">
                        <span class="field-icon">
                            <span class="material-symbols-outlined">mail</span>
                        </span>
                        <input
                            class="field-input"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            placeholder="nombre@empresa.com"
                            required autofocus
                            autocomplete="email" />
                    </div>
                    @error('email')
                        <div class="field-error">
                            <span class="material-symbols-outlined">error</span>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Submit --}}
                <button class="submit-btn" type="submit" data-test="email-password-reset-link-button">
                    Enviar Instrucciones
                </button>
            </form>
        </div>

        {{-- Footer --}}
        <div class="fp-footer">
            <p>
                ¿Necesita ayuda?
                <a href="https://api.whatsapp.com/send/?phone=59172281455" target="_blank">Contacte a soporte técnico.</a>
            </p>
        </div>

    </div>

</div>
</x-auth-layout>
