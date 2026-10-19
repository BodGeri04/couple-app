import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

export function usePullToRefresh({ threshold = 72, max = 120 } = {}) {
    const pull = ref(0);
    const dragging = ref(false);
    const refreshing = ref(false);
    let startY = null;

    const onStart = (e) => {
        // ignore touches inside modals / bottom sheets
        if (e.target instanceof Element && e.target.closest('[role="dialog"]')) {
            startY = null;
            return;
        }
        if (refreshing.value || window.scrollY > 0 || e.touches.length !== 1) {
            startY = null;
            return;
        }
        startY = e.touches[0].clientY;
    };

    const onMove = (e) => {
        if (startY === null) return;

        const dy = e.touches[0].clientY - startY;
        if (dy <= 0 || window.scrollY > 0) {
            pull.value = 0;
            dragging.value = false;
            return;
        }

        if (e.cancelable) e.preventDefault();
        dragging.value = true;
        pull.value = Math.min(max, dy * 0.45);
    };

    const onEnd = () => {
        if (startY === null) return;
        startY = null;
        dragging.value = false;

        if (pull.value < threshold) {
            pull.value = 0;
            return;
        }

        refreshing.value = true;
        pull.value = threshold;
        const startedAt = Date.now();

        router.reload({
            preserveScroll: true,
            onFinish: () => {
                // keep the heart visible for a moment so it feels deliberate
                setTimeout(() => {
                    refreshing.value = false;
                    pull.value = 0;
                }, Math.max(0, 700 - (Date.now() - startedAt)));
            },
        });
    };

    onMounted(() => {
        window.addEventListener('touchstart', onStart, { passive: true });
        window.addEventListener('touchmove', onMove, { passive: false });
        window.addEventListener('touchend', onEnd);
        window.addEventListener('touchcancel', onEnd);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('touchstart', onStart);
        window.removeEventListener('touchmove', onMove);
        window.removeEventListener('touchend', onEnd);
        window.removeEventListener('touchcancel', onEnd);
    });

    return { pull, dragging, refreshing };
}