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
                    100: '#e1ecf5',
                    200: '#c3d9eb',
                    500: '#1e6091',
                    700: '#003366', // Azul oscuro institucional Electrocentro
                    900: '#001a33',
                }
            }
        },
    },

    plugins: [forms],
};
