<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Camera } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SnapComposer from '@/Components/SnapComposer.vue';
import SnapViewer from '@/Components/SnapViewer.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    snaps: Array,
    openId: Number,
});

const page = usePage();
const partner = computed(() => page.props.auth.partner);

const clone = (list) => list.map((snap) => ({ ...snap }));
const items = ref(clone(props.snaps));
watch(
    () => props.snaps,
    (list) => (items.value = clone(list)),
);

const latestIncoming = computed(() => items.value.find((snap) => !snap.mine) ?? null);

const selected = ref(null);
const composerOpen = ref(false);
const replyTo = ref(null);

const refreshCounters = () =>
    router.reload({
        only: ['snaps', 'unseenSnaps'],
        preserveScroll: true,
        preserveState: true,
        showProgress: false,
    });

const openSnap = (snap) => {
    selected.value = snap;

    if (!snap.mine && !snap.viewed_at) {
        snap.viewed_at = new Date().toISOString();
        window.axios.post(route('snaps.seen', snap.id)).then(refreshCounters).catch(() => {});
    }
};

const startNew = () => {
    replyTo.value = null;
    composerOpen.value = true;
};

const startReply = (snap) => {
    selected.value = null;
    replyTo.value = snap;
    composerOpen.value = true;
};

const remove = (snap) => {
    router.delete(route('snaps.destroy', snap.id), {
        preserveScroll: true,
        onSuccess: () => (selected.value = null),
    });
};

const ago = (iso) => {
    const minutes = Math.round((Date.now() - new Date(iso).getTime()) / 60000);
    if (minutes < 1) return 'most';
    if (minutes < 60) return `${minutes} perce`;
    const hours = Math.round(minutes / 60);
    if (hours < 24) return `${hours} órája`;
    return `${Math.round(hours / 24)} napja`;
};

const onVisible = () => {
    if (!document.hidden) refreshCounters();
};

onMounted(() => {
    document.addEventListener('visibilitychange', onVisible);

    // Opened from a push notification: show that snap right away.
    if (props.openId) {
        const snap = items.value.find((s) => s.id === props.openId);
        if (snap) openSnap(snap);
    }
});
onBeforeUnmount(() => document.removeEventListener('visibilitychange', onVisible));
</script>

<template>
    <div class="space-y-5">
        <Head title="Pillanat" />

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-extrabold tracking-tight">Pillanat</h1>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 text-sm font-extrabold text-white shadow-pop transition active:scale-95"
                @click="startNew"
            >
                <Camera :size="18" /> Küldés
            </button>
        </div>

        <!-- Latest from the partner -->
        <button
            v-if="latestIncoming"
            type="button"
            class="card relative block w-full overflow-hidden text-left transition active:scale-[.98]"
            @click="openSnap(latestIncoming)"
        >
            <img :src="latestIncoming.image_url" alt="" class="aspect-[4/5] max-h-[60vh] w-full object-cover" />
            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-4 text-white">
                <div class="font-extrabold">{{ latestIncoming.sender }} legutóbbi pillanata</div>
                <div class="text-sm text-white/85">
                    {{ latestIncoming.mood }} {{ latestIncoming.caption }}
                    <span class="text-white/70"> · {{ ago(latestIncoming.created_at) }}</span>
                </div>
            </div>
            <span
                v-if="!latestIncoming.viewed_at"
                class="absolute right-3 top-3 rounded-full bg-primary px-3 py-1 text-xs font-extrabold text-white shadow-pop"
            >
                Új
            </span>
        </button>

        <div v-else class="card p-8 text-center">
            <div class="animate-float text-5xl">📸</div>
            <h2 class="mt-3 text-lg font-extrabold">Még nincs pillanat</h2>
            <p class="mt-1 text-sm text-ink-soft">
                Küldd el az elsőt{{ partner ? ` ${partner.name} részére` : ' a párodnak' }}!
            </p>
            <button type="button" class="btn-primary mt-4" @click="startNew">Pillanat küldése</button>
        </div>

        <!-- Memories grid -->
        <section v-if="items.length">
            <h2 class="mb-2 text-sm font-extrabold uppercase tracking-wider text-ink-soft">Emlékek</h2>
            <div class="grid grid-cols-3 gap-2">
                <button
                    v-for="snap in items"
                    :key="snap.id"
                    type="button"
                    class="relative aspect-square overflow-hidden rounded-2xl bg-surface-2 transition active:scale-95"
                    :class="!snap.mine && !snap.viewed_at ? 'ring-2 ring-primary' : ''"
                    @click="openSnap(snap)"
                >
                    <img :src="snap.thumb_url" alt="" loading="lazy" class="h-full w-full object-cover" />
                    <span
                        class="absolute bottom-1.5 left-1.5 rounded-full bg-black/55 px-2 py-0.5 text-[11px] font-bold text-white"
                    >
                        {{ snap.mine ? 'Te' : snap.sender }}
                    </span>
                    <span v-if="snap.mood" class="absolute right-1.5 top-1 text-lg">{{ snap.mood }}</span>
                </button>
            </div>
        </section>

        <SnapViewer :snap="selected" @close="selected = null" @reply="startReply" @remove="remove" />
        <SnapComposer :open="composerOpen" :reply-to="replyTo" @close="composerOpen = false" />
    </div>
</template>