import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const v = (name) => `rgb(var(--c-${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Nunito Variable"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                canvas: v('bg'),
                surface: v('surface'),
                'surface-2': v('surface-2'),
                ink: v('ink'),
                'ink-soft': v('ink-soft'),
                line: v('line'),
                primary: v('primary'),
                accent: v('accent'),
                peach: v('peach'),
            },
            boxShadow: {
                soft: '0 6px 24px -10px rgb(var(--c-primary) / 0.35)',
                pop: '0 10px 24px -10px rgb(var(--c-primary) / 0.65)',
            },
        },
    },

    plugins: [forms],
};