import { ref } from 'vue';

const STORAGE_KEY = 'theme';
const modes = ['light', 'dark', 'system'];

const mode = ref('system');
let initialised = false;

const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

function apply() {
    const dark = mode.value === 'dark' || (mode.value === 'system' && prefersDark());
    document.documentElement.classList.toggle('dark', dark);
}

function init() {
    if (initialised || typeof window === 'undefined') return;
    initialised = true;

    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (modes.includes(saved)) mode.value = saved;
    } catch (e) {
        /* storage unavailable, keep default */
    }

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (mode.value === 'system') apply();
    });

    apply();
}

export function useTheme() {
    init();

    const set = (value) => {
        mode.value = value;
        try {
            localStorage.setItem(STORAGE_KEY, value);
        } catch (e) {}
        apply();
    };

    const cycle = () => set(modes[(modes.indexOf(mode.value) + 1) % modes.length]);

    return { mode, set, cycle };
}