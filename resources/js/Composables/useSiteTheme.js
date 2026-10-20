import { watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';

/** Applies the admin-chosen main colour (an "r g b" triplet) over the default theme tokens. */
export function useSiteTheme() {
    const page = usePage();

    watchEffect(() => {
        const root = document.documentElement;
        const primary = page.props.site?.primary;

        if (primary) {
            root.style.setProperty('--c-primary', primary);
        } else {
            root.style.removeProperty('--c-primary');
        }
    });
}
