import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';
import plugin from 'tailwindcss/plugin';

/*
 * Dark mode
 * ---------
 * Instead of adding `dark:` variants to every page, the palette colors used for backgrounds,
 * text and borders are served through CSS variables. Under `html.dark` each variable points to
 * the matching dark shade (e.g. bg-gray-50 -> gray-950, text-gray-900 -> gray-100,
 * bg-emerald-50 -> emerald-950), so existing light-theme markup turns dark automatically.
 * Areas that are already dark by design (sidebar, login side panel) carry `.theme-static`
 * to keep their light-theme values.
 */
const NEUTRALS = ['slate', 'gray', 'zinc'];
const ACCENTS = ['red', 'rose', 'orange', 'amber', 'yellow', 'green', 'emerald', 'teal', 'sky', 'blue', 'indigo', 'violet', 'purple'];
const SHADES = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];

// Dark-mode shade per utility kind; shades not listed keep their own value.
const DARK_SHADE = {
    bg: {
        neutral: { 50: 950, 100: 800, 200: 700, 300: 600, 400: 500, 600: 500, 700: 600, 800: 700, 900: 800 },
        accent: { 50: 950, 100: 900, 200: 800, 300: 700 },
    },
    text: {
        neutral: { 50: 900, 100: 800, 200: 700, 300: 600, 400: 500, 500: 400, 600: 400, 700: 300, 800: 200, 900: 100, 950: 50 },
        accent: { 600: 400, 700: 300, 800: 200, 900: 200, 950: 100 },
    },
    border: {
        neutral: { 50: 800, 100: 800, 200: 800, 300: 700, 400: 600, 600: 400, 700: 300, 800: 700, 900: 700 },
        accent: { 50: 950, 100: 900, 200: 800, 300: 700, 400: 600 },
    },
};

const rgb = (hex) => {
    const n = parseInt(hex.slice(1), 16);
    return `${(n >> 16) & 255} ${(n >> 8) & 255} ${n & 255}`;
};

const themeVars = (dark) => {
    const vars = {};
    for (const kind of Object.keys(DARK_SHADE)) {
        for (const name of [...NEUTRALS, ...ACCENTS]) {
            const group = NEUTRALS.includes(name) ? 'neutral' : 'accent';
            for (const shade of SHADES) {
                const target = dark ? (DARK_SHADE[kind][group][shade] ?? shade) : shade;
                vars[`--${kind}-${name}-${shade}`] = rgb(colors[name][target]);
            }
        }
    }
    vars['--bg-white'] = dark ? rgb(colors.gray[900]) : '255 255 255';
    return vars;
};

const themedPalette = (kind) => Object.fromEntries(
    [...NEUTRALS, ...ACCENTS].map((name) => [
        name,
        Object.fromEntries(SHADES.map((shade) => [shade, `rgb(var(--${kind}-${name}-${shade}) / <alpha-value>)`])),
    ]),
);

const themedBackground = { ...themedPalette('bg'), white: 'rgb(var(--bg-white) / <alpha-value>)' };

const darkTheme = plugin(({ addBase }) => {
    addBase({
        ':root, .theme-static': themeVars(false),
        '.dark': { ...themeVars(true), colorScheme: 'dark' },
        '.dark body': { backgroundColor: colors.gray[950], color: colors.gray[200] },
        '.dark :where(input:not([type="checkbox"]):not([type="radio"]), select, textarea)': {
            backgroundColor: colors.gray[900],
            borderColor: colors.gray[700],
            color: colors.gray[100],
        },
        '.dark :where([type="checkbox"], [type="radio"])': {
            backgroundColor: colors.gray[800],
            borderColor: colors.gray[600],
        },
    });
});

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            backgroundColor: themedBackground,
            gradientColorStops: themedBackground,
            textColor: themedPalette('text'),
            placeholderColor: themedPalette('text'),
            borderColor: themedPalette('border'),
            divideColor: themedPalette('border'),
            ringColor: themedPalette('border'),
        },
    },

    plugins: [forms, darkTheme],
};
