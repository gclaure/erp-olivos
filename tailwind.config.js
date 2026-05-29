import typography from '@tailwindcss/typography';
import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'selector',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Instrument Sans', ...defaultTheme.fontFamily.sans],
                manrope: ['Manrope', 'sans-serif'],
            },
            colors: {
                brand: {
                    primary: '#b91c1c', /* red-700 (Crimson Heritage) */
                    primaryHover: '#991b1b', /* red-800 */
                    secondary: '#1e3a8a', /* blue-900 (Midnight Blue) */
                },
                // El color de acento índigo/púrpura de la imagen
                primary: {
                    50: '#f5f3ff',
                    100: '#ede9fe',
                    200: '#ddd6fe',
                    300: '#c4b5fd',
                    400: '#a78bfa',
                    500: '#8b5cf6',
                    600: '#5A4CFA', // Color exacto del botón "Nueva Unidad"
                    700: '#4c1d95',
                    800: '#4338ca',
                    900: '#312e81',
                    950: '#1e1b4b',
                },
                // La paleta de grises azulados para el modo oscuro
                secondary: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#333A48', // Bordes y divisores
                    800: '#222530', // Superficie de tarjetas y tablas
                    900: '#171923', // Fondo principal (Body)
                    950: '#0f172a',
                },
                zinc: {
                    50: '#fafafa',
                    100: '#f5f5f5',
                    200: '#e5e5e5',
                    300: '#d4d4d4',
                    400: '#a3a3a3',
                    500: '#737373',
                    600: '#525252',
                    700: '#404040',
                    800: '#262626',
                    900: '#171717',
                    950: '#0a0a0a',
                },

                /* ── Colores Semánticos (CSS Variables) ── */
                app: 'rgb(var(--color-background) / <alpha-value>)',
                surface: 'rgb(var(--color-surface) / <alpha-value>)',

                'primary-app': {
                    DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
                    hover: 'rgb(var(--color-primary-hover) / <alpha-value>)',
                    light: 'rgb(var(--color-primary-light) / <alpha-value>)',
                },

                'text-primary-app': 'rgb(var(--color-text-primary) / <alpha-value>)',
                'text-secondary-app': 'rgb(var(--color-text-secondary) / <alpha-value>)',
                'text-muted-app': 'rgb(var(--color-text-muted) / <alpha-value>)',

                'success-app': 'rgb(var(--color-success) / <alpha-value>)',
                'warning-app': 'rgb(var(--color-warning) / <alpha-value>)',
                'danger-app': 'rgb(var(--color-danger) / <alpha-value>)',
                'info-app': 'rgb(var(--color-info) / <alpha-value>)',
            },
        },
    },
    plugins: [forms, typography],
};
