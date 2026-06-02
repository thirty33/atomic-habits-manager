import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import flowbite from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
        './app/ViewModels/**/*.php',
        './app/Services/Frontend/**/*.php',
        './node_modules/flowbite/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                lato: ['Lato', ...defaultTheme.fontFamily.sans],
                // === Redesign 2026 — coexists with legacy families above ===
                body: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Instrument Serif"', 'Georgia', 'serif'],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                sidebar: {
                    bg: 'rgb(var(--color-sidebar-bg) / <alpha-value>)',
                    border: 'rgb(var(--color-sidebar-border) / <alpha-value>)',
                    text: 'rgb(var(--color-sidebar-text) / <alpha-value>)',
                    muted: 'rgb(var(--color-sidebar-text-muted) / <alpha-value>)',
                    hover: 'rgb(var(--color-sidebar-hover-bg) / <alpha-value>)',
                    active: 'rgb(var(--color-sidebar-active-text) / <alpha-value>)',
                    separator: 'rgb(var(--color-sidebar-separator) / <alpha-value>)',
                },
                page: {
                    bg: 'rgb(var(--color-page-bg) / <alpha-value>)',
                    heading: 'rgb(var(--color-heading) / <alpha-value>)',
                    border: 'rgb(var(--color-content-border) / <alpha-value>)',
                    content: 'rgb(var(--color-content-bg) / <alpha-value>)',
                    muted: 'rgb(var(--color-text-muted) / <alpha-value>)',
                },
                btn: {
                    primary: 'rgb(var(--color-btn-primary) / <alpha-value>)',
                    'primary-hover': 'rgb(var(--color-btn-primary-hover) / <alpha-value>)',
                    secondary: 'rgb(var(--color-btn-secondary) / <alpha-value>)',
                    'secondary-hover': 'rgb(var(--color-btn-secondary-hover) / <alpha-value>)',
                    info: 'rgb(var(--color-btn-info) / <alpha-value>)',
                    'info-hover': 'rgb(var(--color-btn-info-hover) / <alpha-value>)',
                    danger: 'rgb(var(--color-btn-danger) / <alpha-value>)',
                    'danger-hover': 'rgb(var(--color-btn-danger-hover) / <alpha-value>)',
                    success: 'rgb(var(--color-btn-success) / <alpha-value>)',
                    'success-hover': 'rgb(var(--color-btn-success-hover) / <alpha-value>)',
                },

                // === Redesign 2026 — coexists with legacy palette above ===
                brand: {
                    50: 'rgb(var(--color-brand-50) / <alpha-value>)',
                    100: 'rgb(var(--color-brand-100) / <alpha-value>)',
                    200: 'rgb(var(--color-brand-200) / <alpha-value>)',
                    300: 'rgb(var(--color-brand-300) / <alpha-value>)',
                    500: 'rgb(var(--color-brand-500) / <alpha-value>)',
                    600: 'rgb(var(--color-brand-600) / <alpha-value>)',
                    700: 'rgb(var(--color-brand-700) / <alpha-value>)',
                    800: 'rgb(var(--color-brand-800) / <alpha-value>)',
                    900: 'rgb(var(--color-brand-900) / <alpha-value>)',
                },
                paper: 'rgb(var(--color-paper) / <alpha-value>)',
                'page-cream': 'rgb(var(--color-page-cream) / <alpha-value>)',
                card: 'rgb(var(--color-card) / <alpha-value>)',
                ink: {
                    400: 'rgb(var(--color-ink-400) / <alpha-value>)',
                    500: 'rgb(var(--color-ink-500) / <alpha-value>)',
                    700: 'rgb(var(--color-ink-700) / <alpha-value>)',
                    900: 'rgb(var(--color-ink-900) / <alpha-value>)',
                },
                line: {
                    100: 'rgb(var(--color-line-100) / <alpha-value>)',
                    200: 'rgb(var(--color-line-200) / <alpha-value>)',
                    300: 'rgb(var(--color-line-300) / <alpha-value>)',
                },
                warning: 'rgb(var(--color-warning) / <alpha-value>)',
                'success-2': 'rgb(var(--color-success-2) / <alpha-value>)',
                'danger-2': 'rgb(var(--color-danger-2) / <alpha-value>)',
                'info-2': 'rgb(var(--color-info-2) / <alpha-value>)',
            },
        },
    },

    plugins: [forms, flowbite],
};
