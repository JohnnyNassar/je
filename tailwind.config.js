import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import flowbite from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/flowbite/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Deep teal — matches the logo's garage-door background
                brand: {
                    50:  '#effafa',
                    100: '#d6f1f2',
                    200: '#b0e2e5',
                    300: '#7fcfd5',
                    400: '#4fb6bf',
                    500: '#329aa4',
                    600: '#287d88',
                    700: '#23636e',
                    800: '#1f525c',
                    900: '#0f4248',
                    950: '#08272c',
                },
                // Red accent — matches the "GARAGE SALE" red in the logo
                accent: {
                    50:  '#fef2f2',
                    100: '#fee2e2',
                    200: '#fecaca',
                    300: '#fca5a5',
                    400: '#f87171',
                    500: '#ef4444',
                    600: '#dc2626',
                    700: '#b91c1c',
                    800: '#991b1b',
                    900: '#7f1d1d',
                },
            },
            spacing: {
                '5.5': '22px',
                '6.5': '26px',
                '7.5': '30px',
                '8.5': '34px',
            },
            boxShadow: {
                'input': '0 3px 11px 0 rgba(15, 66, 72, 0.10)',
                'card': '0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04)',
                'card-hover': '0 8px 24px rgba(15, 66, 72, 0.12)',
            },
        },
    },

    plugins: [forms, flowbite],
};
