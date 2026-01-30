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
            },
        },
    },

    plugins: [forms, flowbite],
};
