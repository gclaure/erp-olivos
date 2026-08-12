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
                // Acento de marca — verde olivo en claro, índigo en dark (mode-aware)
                primary: {
                    50: 'rgb(var(--color-primary-50) / <alpha-value>)',
                    100: 'rgb(var(--color-primary-100) / <alpha-value>)',
                    200: 'rgb(var(--color-primary-200) / <alpha-value>)',
                    300: 'rgb(var(--color-primary-300) / <alpha-value>)',
                    400: 'rgb(var(--color-primary-400) / <alpha-value>)',
                    500: 'rgb(var(--color-primary-500) / <alpha-value>)',
                    600: 'rgb(var(--color-primary-600) / <alpha-value>)',
                    700: 'rgb(var(--color-primary-700) / <alpha-value>)',
                    800: 'rgb(var(--color-primary-800) / <alpha-value>)',
                    900: 'rgb(var(--color-primary-900) / <alpha-value>)',
                    950: 'rgb(var(--color-primary-950) / <alpha-value>)',
                },
                // Escala índigo por defecto de Tailwind, re-mapeada por variables
                indigo: {
                    50: 'rgb(var(--color-indigo-50) / <alpha-value>)',
                    100: 'rgb(var(--color-indigo-100) / <alpha-value>)',
                    200: 'rgb(var(--color-indigo-200) / <alpha-value>)',
                    300: 'rgb(var(--color-indigo-300) / <alpha-value>)',
                    400: 'rgb(var(--color-indigo-400) / <alpha-value>)',
                    500: 'rgb(var(--color-indigo-500) / <alpha-value>)',
                    600: 'rgb(var(--color-indigo-600) / <alpha-value>)',
                    700: 'rgb(var(--color-indigo-700) / <alpha-value>)',
                    800: 'rgb(var(--color-indigo-800) / <alpha-value>)',
                    900: 'rgb(var(--color-indigo-900) / <alpha-value>)',
                    950: 'rgb(var(--color-indigo-950) / <alpha-value>)',
                },
                // Escala de estructura (slate en claro, olivo oscuro en dark) — mode-aware
                secondary: {
                    50: 'rgb(var(--color-secondary-50) / <alpha-value>)',
                    100: 'rgb(var(--color-secondary-100) / <alpha-value>)',
                    200: 'rgb(var(--color-secondary-200) / <alpha-value>)',
                    300: 'rgb(var(--color-secondary-300) / <alpha-value>)',
                    400: 'rgb(var(--color-secondary-400) / <alpha-value>)',
                    500: 'rgb(var(--color-secondary-500) / <alpha-value>)',
                    600: 'rgb(var(--color-secondary-600) / <alpha-value>)',
                    700: 'rgb(var(--color-secondary-700) / <alpha-value>)',
                    800: 'rgb(var(--color-secondary-800) / <alpha-value>)',
                    900: 'rgb(var(--color-secondary-900) / <alpha-value>)',
                    950: 'rgb(var(--color-secondary-950) / <alpha-value>)',
                },
                // Neutros — cálidos en claro, zinc actual en dark (mode-aware)
                zinc: {
                    50: 'rgb(var(--color-zinc-50) / <alpha-value>)',
                    100: 'rgb(var(--color-zinc-100) / <alpha-value>)',
                    200: 'rgb(var(--color-zinc-200) / <alpha-value>)',
                    300: 'rgb(var(--color-zinc-300) / <alpha-value>)',
                    400: 'rgb(var(--color-zinc-400) / <alpha-value>)',
                    500: 'rgb(var(--color-zinc-500) / <alpha-value>)',
                    600: 'rgb(var(--color-zinc-600) / <alpha-value>)',
                    700: 'rgb(var(--color-zinc-700) / <alpha-value>)',
                    800: 'rgb(var(--color-zinc-800) / <alpha-value>)',
                    900: 'rgb(var(--color-zinc-900) / <alpha-value>)',
                    950: 'rgb(var(--color-zinc-950) / <alpha-value>)',
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
