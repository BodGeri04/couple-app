<script setup>
import { computed, ref } from 'vue';
import { Download, Share, X } from 'lucide-vue-next';
import { usePwaInstall } from '@/Composables/usePwaInstall';

const { installed, canPrompt, isIos, promptInstall } = usePwaInstall();

const DISMISS_KEY = 'install-card-dismissed';
const dismissed = ref(false);
try {
    dismissed.value = localStorage.getItem(DISMISS_KEY) === '1';
} catch (e) {}

const visible = computed(() => !installed.value && !dismissed.value && (!!canPrompt.value || isIos));

const dismiss = () => {
    dismissed.value = true;
    try {
        localStorage.setItem(DISMISS_KEY, '1');
    } catch (e) {}
};
</script>

<template>
    <section v-if="visible" class="card flex items-start gap-4 p-5">
        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-surface-2 text-primary">
            <Download :size="24" />
        </div>

        <div class="min-w-0 flex-1">
            <h2 class="font-extrabold">Telepítsd az Aplikációt!</h2>

            <template v-if="canPrompt">
                <p class="mt-0.5 text-sm text-ink-soft">Teljes képernyőn, igazi alkalmazásként fut majd.</p>
                <button type="button" class="btn-primary mt-3 !w-auto !px-5 !py-2.5" @click="promptInstall">
                    Telepítés
                </button>
            </template>

            <p v-else class="mt-0.5 text-sm text-ink-soft">
                Safariban koppints a Megosztás
                <Share :size="14" class="-mt-0.5 inline" />
                gombra, majd a „Hozzáadás a kezdőképernyőhöz” menüpontra.
            </p>
        </div>

        <button type="button" class="icon-btn !h-8 !w-8 shrink-0" aria-label="Bezárás" @click="dismiss">
            <X :size="16" />
        </button>
    </section>
</template>