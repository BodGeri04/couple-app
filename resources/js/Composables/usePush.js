import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

// loading | unsupported | needs-install | denied | off | on
const state = ref('loading');
const busy = ref(false);
const message = ref('');
const detail = ref('');
const diagnostics = ref({});

const isIos =
    /iphone|ipad|ipod/i.test(navigator.userAgent) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

const isStandalone = () =>
    window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

const toKey = (base64) => {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([...raw].map((char) => char.charCodeAt(0)));
};

export function usePush() {
    const page = usePage();
    const publicKey = computed(() => page.props.push?.publicKey);

    const refresh = async () => {
        detail.value = '';
        diagnostics.value = {
            ios: isIos,
            standalone: isStandalone(),
            secureContext: window.isSecureContext,
            publicKey: Boolean(publicKey.value),
            serviceWorker: 'serviceWorker' in navigator,
            pushManager: 'PushManager' in window,
            notification: 'Notification' in window,
            permission: 'Notification' in window ? Notification.permission : 'n/a',
            userAgent: navigator.userAgent,
        };

        if (!publicKey.value) {
            state.value = 'unsupported';
            detail.value = 'The server sent no VAPID public key (VAPID_PUBLIC_KEY is missing or config is cached).';
            return;
        }

        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            // iPhones only expose push to apps that were added to the home screen.
            state.value = isIos && !isStandalone() ? 'needs-install' : 'unsupported';
            detail.value = 'serviceWorker, PushManager or Notification is not available in this context.';
            return;
        }

        if (Notification.permission === 'denied') {
            state.value = 'denied';
            return;
        }

        try {
            detail.value = 'Waiting for the service worker to become ready…';
            const registration = await navigator.serviceWorker.ready;
            detail.value = '';
            const subscription = await registration.pushManager.getSubscription();

            if (subscription && Notification.permission === 'granted') {
                // Keep the server's record owned by whoever is logged in on this device.
                await window.axios.post(route('push.subscribe'), subscription.toJSON());
                state.value = 'on';
            } else {
                state.value = 'off';
            }
        } catch (e) {
            state.value = 'unsupported';
            detail.value = `refresh failed: ${e?.name ?? ''} ${e?.message ?? e}`;
        }
    };

    const enable = async () => {
        busy.value = true;
        message.value = '';

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                await refresh();
                return;
            }

            const registration = await navigator.serviceWorker.ready;
            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: toKey(publicKey.value),
                });
            }

            await window.axios.post(route('push.subscribe'), subscription.toJSON());
            state.value = 'on';
        } catch (e) {
            console.error(e);
            detail.value = `enable failed: ${e?.name ?? ''} ${e?.message ?? e}`;
            message.value = 'Nem sikerült bekapcsolni az értesítéseket.';
            state.value = 'off';
        } finally {
            busy.value = false;
        }
    };

    const disable = async () => {
        busy.value = true;
        message.value = '';

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await window.axios.post(route('push.unsubscribe'), { endpoint: subscription.endpoint });
                await subscription.unsubscribe();
            }
            state.value = 'off';
        } catch (e) {
            message.value = 'Nem sikerült kikapcsolni.';
        } finally {
            busy.value = false;
        }
    };

    const sendTest = async () => {
        busy.value = true;
        message.value = '';

        try {
            const { data } = await window.axios.post(route('push.test'));
            message.value =
                data.sent > 0
                    ? 'Teszt elküldve, nézd meg az értesítéseidet!'
                    : 'Nem sikerült elküldeni. Próbáld kikapcsolni, majd újra bekapcsolni.';
        } catch (e) {
            message.value = 'A teszt küldése nem sikerült.';
        } finally {
            busy.value = false;
        }
    };

    return { state, busy, message, detail, diagnostics, refresh, enable, disable, sendTest };
}