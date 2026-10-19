<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WishSheet from '@/Components/WishSheet.vue';
import { useBurst } from '@/Composables/useBurst';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    mine: Array,
    theirs: Array,
});

const page = usePage();
const { burst } = useBurst();

const partnerName = computed(() => page.props.auth.partner?.name ?? 'Párod');
const unanswered = computed(() => props.theirs.filter((wish) => !wish.reaction).length);

const tab = ref('mine');
const tabs = computed(() => [
    { key: 'mine', label: 'Kívánságaim', badge: 0 },
    { key: 'theirs', label: `${partnerName.value} kívánságai`, badge: unanswered.value },
]);

const sheetOpen = ref(false);
const editing = ref(null);

const openNew = () => {
    editing.value = null;
    sheetOpen.value = true;
};
const openEdit = (wish) => {
    editing.value = wish;
    sheetOpen.value = true;
};

const host = (url) => {
    try {
        return new URL(url).hostname.replace(/^www\./, '');
    } catch (e) {
        return url;
    }
};

const REACTIONS = [
    { key: 'consider', label: 'Lehet róla szó', emoji: '💗' },
    { key: 'decline', label: 'Felejtős', emoji: '🙈' },
];
const reactionOf = (key) => REACTIONS.find((r) => r.key === key);

const react = (wish, reaction, event) => {
    const next = wish.reaction === reaction ? null : reaction; // tapping again clears it
    if (next === 'consider') burst(event, 'heart', 8);

    router.patch(route('wishes.react', wish.id), { reaction: next }, { preserveScroll: true });
};
</script>

<template>
    <div class="space-y-5">
        <Head title="Kívánságok" />

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-extrabold tracking-tight">Kívánságok</h1>
            <button
                v-if="tab === 'mine'"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 text-sm font-extrabold text-white shadow-pop transition active:scale-95"
                @click="openNew"
            >
                <Plus :size="18" /> Új
            </button>
        </div>

        <div class="grid grid-cols-2 gap-1 rounded-2xl bg-surface-2 p-1">
            <button
                v-for="t in tabs"
                :key="t.key"
                type="button"
                class="flex min-w-0 items-center justify-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-extrabold transition"
                :class="tab === t.key ? 'bg-surface text-primary shadow-soft' : 'text-ink-soft'"
                @click="tab = t.key"
            >
                <span class="truncate">{{ t.label }}</span>
                <span v-if="t.badge" class="shrink-0 rounded-full bg-primary px-1.5 py-0.5 text-[11px] leading-none text-white">
                    {{ t.badge }}
                </span>
            </button>
        </div>

        <Transition name="page" mode="out-in">
            <!-- My wishes -->
            <div v-if="tab === 'mine'" key="mine">
                <div v-if="!mine.length" class="card p-8 text-center">
                    <div class="animate-float text-5xl">🎁</div>
                    <h2 class="mt-3 text-lg font-extrabold">Még nincs kívánságod</h2>
                    <p class="mt-1 text-sm text-ink-soft">Add hozzá az elsőt, hogy {{ partnerName }} tudja, minek örülnél.</p>
                    <button type="button" class="btn-primary mt-4" @click="openNew">Kívánság hozzáadása</button>
                </div>

                <ul v-else class="flex flex-col gap-3">
                    <li v-for="wish in mine" :key="wish.id" class="card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="min-w-0 break-words font-extrabold">{{ wish.name }}</h2>
                            <button type="button" class="icon-btn shrink-0" :aria-label="`Szerkesztés: ${wish.name}`" @click="openEdit(wish)">
                                <Pencil :size="18" />
                            </button>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <a
                                :href="wish.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-surface-2 px-3 py-1.5 text-sm font-bold text-primary"
                            >
                                <ExternalLink :size="14" class="shrink-0" />
                                <span class="truncate">{{ host(wish.url) }}</span>
                            </a>

                            <span
                                v-if="wish.reaction"
                                class="rounded-full px-3 py-1.5 text-sm font-extrabold"
                                :class="wish.reaction === 'consider' ? 'bg-primary/15 text-primary' : 'bg-surface-2 text-ink-soft'"
                            >
                                {{ reactionOf(wish.reaction).label }} {{ reactionOf(wish.reaction).emoji }}
                            </span>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Partner's wishes -->
            <div v-else key="theirs">
                <div v-if="!theirs.length" class="card p-8 text-center">
                    <div class="animate-float text-5xl">🎀</div>
                    <h2 class="mt-3 text-lg font-extrabold">Még nincs itt semmi</h2>
                    <p class="mt-1 text-sm text-ink-soft">{{ partnerName }} még nem adott hozzá kívánságot.</p>
                </div>

                <ul v-else class="flex flex-col gap-3">
                    <li v-for="wish in theirs" :key="wish.id" class="card p-4">
                        <h2 class="break-words font-extrabold">{{ wish.name }}</h2>

                        <a
                            :href="wish.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-2 inline-flex max-w-full items-center gap-1.5 rounded-full bg-surface-2 px-3 py-1.5 text-sm font-bold text-primary"
                        >
                            <ExternalLink :size="14" class="shrink-0" />
                            <span class="truncate">{{ host(wish.url) }}</span>
                        </a>

                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <button
                                v-for="r in REACTIONS"
                                :key="r.key"
                                type="button"
                                class="rounded-2xl border px-3 py-2.5 text-sm font-extrabold transition active:scale-95"
                                :class="
                                    wish.reaction === r.key
                                        ? r.key === 'consider'
                                            ? 'border-primary bg-primary text-white shadow-pop'
                                            : 'border-ink-soft bg-ink-soft text-white'
                                        : 'border-line bg-surface text-ink-soft'
                                "
                                @click="react(wish, r.key, $event)"
                            >
                                {{ r.emoji }} {{ r.label }}
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </Transition>

        <WishSheet :open="sheetOpen" :wish="editing" @close="sheetOpen = false" />
    </div>
</template>