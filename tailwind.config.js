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
            },
        },
    },

    plugins: [forms, flowbite],
};
