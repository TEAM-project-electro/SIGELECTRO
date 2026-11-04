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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#f0f5fa',
                    100: '#e0ecf6',
                    200: '#b9d5ec',
                    300: '#84b5de',
                    400: '#4a90cd',
                    500: '#2270b2',
                    600: '#145593',
                    700: '#003366', // Azul institucional oficial Electrocentro
                    800: '#08284d',
                    900: '#0b1d36', // Sidebar navy profundo
                    950: '#071223',
                }
            }
        },
    },

    plugins: [forms],
};
