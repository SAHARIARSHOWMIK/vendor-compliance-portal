/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Livewire/**/*.php',
    ],
    safelist: [
        'bg-emerald-50','text-emerald-700',
        'bg-red-50','text-red-700',
        'bg-amber-50','text-amber-700',
        'bg-orange-50','text-orange-700',
        'bg-indigo-50','text-indigo-700',
        'bg-sky-50','text-sky-700',
        'bg-slate-50','text-slate-700',
    ],
    theme: {
        extend: {
            boxShadow: {
                soft: '0 18px 45px -24px rgba(15, 23, 42, .25)',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
