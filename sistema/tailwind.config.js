import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Segoe UI Variable"', '"Segoe UI"', ...defaultTheme.fontFamily.sans],
                mono: ['"Cascadia Code"', 'Consolas', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                brand: {
                    50: '#eef5fc',
                    100: '#d8e9f8',
                    200: '#b3d4f1',
                    300: '#7eb7e6',
                    400: '#3f91d6',
                    500: '#1672c2',
                    600: '#0057a8', // Azul eléctrico Electrocentro
                    700: '#004182',
                    800: '#003266',
                    900: '#00244d', // Navy institucional
                    950: '#001836', // Sidebar profundo
                },
                energy: {
                    50: '#effaf0',
                    100: '#dcf4de',
                    500: '#3ba62d', // Verde señal del emblema Electrocentro
                    600: '#2d8821',
                    700: '#236b1a',
                },
                canvas: '#f4f6f9',
            },
            boxShadow: {
                panel: '0 1px 2px rgba(0, 24, 54, 0.05), 0 1px 1px rgba(0, 24, 54, 0.03)',
                elevated: '0 8px 24px -6px rgba(0, 24, 54, 0.08), 0 2px 6px -2px rgba(0, 24, 54, 0.04)',
            },
        },
    },

    plugins: [forms],
};
