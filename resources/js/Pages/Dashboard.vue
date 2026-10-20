<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarHeart, Camera, ShoppingBasket, Gift, Heart } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CountdownCard from '@/Components/CountdownCard.vue';
import InstallCard from '@/Components/InstallCard.vue';
import { useBurst } from '@/Composables/useBurst';
import NotificationCard from '@/Components/NotificationCard.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    nextEvent: Object,
    shoppingOpen: { type: Number, default: 0 },
    wishesPending: { type: Number, default: 0 },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const partner = computed(() => page.props.auth.partner);
const unseen = computed(() => page.props.unseenSnaps ?? 0);
const site = computed(() => page.props.site);
const ui = computed(() => site.value?.ui ?? {});
const { burst } = useBurst();

const hour = new Date().getHours();
const greeting = hour < 10 ? 'Jó reggelt' : hour < 18 ? 'Szia' : 'Jó estét';

const tiles = computed(() =>
    [
        { feature: 'calendar', label: 'Naptár', hint: 'Különleges napok', icon: CalendarHeart, href: route('calendar.index') },
        {
            feature: 'snaps',
            label: 'Pillanat',
            hint: unseen.value ? `${unseen.value} új vár rád` : 'Képküldés',
            icon: Camera,
            href: route('snaps.index'),
        },
        {
            feature: 'shopping',
            label: 'Bevásárlás',
            hint: props.shoppingOpen ? `${props.shoppingOpen} tétel vár` : 'Közös lista',
            icon: ShoppingBasket,
            href: route('shopping.index'),
        },
        {
            feature: 'wishes',
            label: 'Kívánságok',
            hint: props.wishesPending ? `${props.wishesPending} vár válaszra` : 'Ötletek ajándékhoz',
            icon: Gift,
            href: route('wishes.index'),
        },
    ].filter((tile) => site.value?.features?.[tile.feature] !== false),
);
</script>

<template>
    <div class="space-y-5">
        <Head title="Kezdőlap" />

        <section class="card relative overflow-hidden p-6">
            <div class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-primary/15 blur-2xl"></div>
            <h1 class="text-2xl font-extrabold tracking-tight">{{ greeting }}, {{ user.name }}!</h1>
            <p class="mt-1 text-ink-soft">
                <template v-if="ui.welcome_text">{{ ui.welcome_text }}</template>
                <template v-else-if="partner">Csak te és {{ partner.name }}. Ez a ti kis titkos világotok.</template>
                <template v-else>Ez a ti kis titkos világotok.</template>
            </p>
        </section>

        <InstallCard v-if="ui.install_card !== false" />
        <NotificationCard v-if="ui.notification_card !== false" />

        <template v-if="ui.countdown !== false && site?.features?.calendar !== false">
            <Link v-if="nextEvent" :href="route('calendar.index')" class="block transition active:scale-[.98]">
                <CountdownCard :event="nextEvent" compact />
            </Link>
            <Link v-else :href="route('calendar.index')" class="card flex items-center gap-4 p-5 transition active:scale-[.98]">
                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-surface-2 text-primary">
                    <CalendarHeart :size="24" />
                </div>
                <div>
                    <h2 class="font-extrabold">Még nincs közelgő esemény</h2>
                    <p class="text-sm text-ink-soft">Koppints ide, és add hozzá az első különleges napotokat.</p>
                </div>
            </Link>
        </template>

        <section v-if="ui.tiles !== false && tiles.length" class="grid grid-cols-2 gap-3">
            <Link v-for="tile in tiles" :key="tile.label" :href="tile.href" class="card p-4 transition active:scale-95">
                <div class="mb-3 grid h-11 w-11 place-items-center rounded-2xl bg-surface-2 text-primary">
                    <component :is="tile.icon" :size="22" />
                </div>
                <div class="font-extrabold">{{ tile.label }}</div>
                <div class="text-sm text-ink-soft">{{ tile.hint }}</div>
            </Link>
        </section>

        <section v-if="ui.burst_button !== false" class="text-center">
            <button type="button" class="btn-primary" @click="burst($event, 'mixed', 14)">
                <Heart :size="20" fill="currentColor" />
                Gondolok rád
            </button>
            <p class="mt-2 text-xs text-ink-soft">Koppints, és szétpattannak a szívek!</p>
        </section>
    </div>
</template>