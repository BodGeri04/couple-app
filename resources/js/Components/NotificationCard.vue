<script setup>
import { onMounted } from 'vue';
import { Bell, BellOff, BellRing } from 'lucide-vue-next';
import { usePush } from '@/Composables/usePush';

const { state, busy, message, detail, diagnostics, refresh, enable, disable, sendTest } = usePush();

onMounted(refresh);
</script>

<template>
    <!-- Not turned on yet -->
    <section v-if="state === 'off'" class="card flex items-start gap-4 p-5">
        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-surface-2 text-primary">
            <Bell :size="24" />
        </div>
        <div class="min-w-0 flex-1">
            <h2 class="font-extrabold">Értesítések</h2>
            <p class="mt-0.5 text-sm text-ink-soft">Kapj értesítést, amikor a párod pillanatot küld.</p>
            <button type="button" class="btn-primary mt-3 !w-auto !px-5 !py-2.5" :disabled="busy" @click="enable">
                Bekapcsolás
            </button>
            <p v-if="message" class="mt-2 text-sm font-semibold text-primary">{{ message }}</p>
        </div>
    </section>

    <!-- Blocked in the phone settings -->
    <section v-else-if="state === 'denied'" class="card flex items-start gap-4 p-5">
        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-surface-2 text-ink-soft">
            <BellOff :size="24" />
        </div>
        <div>
            <h2 class="font-extrabold">Az értesítések le vannak tiltva</h2>
            <p class="mt-0.5 text-sm text-ink-soft">A telefon beállításaiban engedélyezheted újra ennél az alkalmazásnál.</p>
        </div>
    </section>

    <!-- iPhone: only home-screen web apps can receive push -->
    <section v-else-if="state === 'needs-install'" class="card flex items-start gap-4 p-5">
        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-surface-2 text-primary">
            <Bell :size="24" />
        </div>
        <div>
            <h2 class="font-extrabold">Add hozzá a kezdőképernyőhöz</h2>
            <p class="mt-0.5 text-sm text-ink-soft">
                iPhone-on az értesítések csak a kezdőképernyőre telepített appban működnek: Megosztás → Főképernyőhöz adás.
            </p>
        </div>
    </section>

    <!-- Push is not available here -->
    <section v-else-if="state === 'unsupported'" class="card flex items-start gap-4 p-5">
        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-surface-2 text-ink-soft">
            <BellOff :size="24" />
        </div>
        <div>
            <h2 class="font-extrabold">Ezen a böngészőn nem elérhetők az értesítések</h2>
            <p class="mt-0.5 text-sm text-ink-soft">Próbáld meg a kezdőképernyőre telepített appból.</p>
        </div>
    </section>

    <!-- Checking -->
    <section v-else-if="state === 'loading'" class="card flex items-start gap-4 p-5">
        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-surface-2 text-ink-soft">
            <Bell :size="24" />
        </div>
        <div>
            <h2 class="font-extrabold">Értesítések ellenőrzése…</h2>
        </div>
    </section>

    <!-- Active -->
    <section v-else-if="state === 'on'" class="card flex items-center gap-4 p-4">
        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-primary/15 text-primary">
            <BellRing :size="22" />
        </div>
        <div class="min-w-0 flex-1">
            <h2 class="text-sm font-extrabold">Az értesítések be vannak kapcsolva</h2>
            <p v-if="message" class="text-xs font-semibold text-primary">{{ message }}</p>
            <div v-else class="mt-1 flex gap-4 text-sm font-extrabold">
                <button type="button" class="text-primary" :disabled="busy" @click="sendTest">Teszt</button>
                <button type="button" class="text-ink-soft" :disabled="busy" @click="disable">Kikapcsolás</button>
            </div>
        </div>
    </section>

    <!-- Temporary debug panel (remove once push works on iPhone) -->
    <section class="card p-4 text-xs text-ink-soft">
        <p class="font-extrabold">Debug – state: {{ state }}</p>
        <p v-if="detail" class="mt-1 font-semibold text-primary">{{ detail }}</p>
        <pre class="mt-2 whitespace-pre-wrap break-all">{{ JSON.stringify(diagnostics, null, 2) }}</pre>
    </section>
</template>