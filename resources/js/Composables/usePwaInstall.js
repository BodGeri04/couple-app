import { ref } from 'vue';

const deferredPrompt = ref(null);
const installed = ref(false);
let initialised = false;

const isStandalone = () =>
    window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

const detectIos = () =>
    /iphone|ipad|ipod/i.test(navigator.userAgent) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

/** Called once from app.js: the browser can fire 'beforeinstallprompt' before any page exists. */
export function initPwaInstall() {
    if (initialised || typeof window === 'undefined') return;
    initialised = true;

    installed.value = isStandalone();

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt.value = event;
    });

    window.addEventListener('appinstalled', () => {
        installed.value = true;
        deferredPrompt.value = null;
    });
}

export function usePwaInstall() {
    const promptInstall = async () => {
        if (!deferredPrompt.value) return;
        deferredPrompt.value.prompt();
        await deferredPrompt.value.userChoice;
        deferredPrompt.value = null;
    };

    return {
        installed,
        canPrompt: deferredPrompt, // set when Chrome on Android offers installation
        isIos: detectIos(),
        promptInstall,
    };
}