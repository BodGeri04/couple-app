<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarHeart, Camera, ShoppingBasket, Gift, Heart } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useBurst } from '@/Composables/useBurst'; 
import InstallCard from '@/Components/InstallCard.vue';

defineOptions({ layout: AuthenticatedLayout });

const page = usePage();
const user = computed(() => page.props.auth.user);
const partner = computed(() => page.props.auth.partner);
const { burst } = useBurst();

const hour = new Date().getHours();
const greeting = hour < 10 ? 'Jó reggelt' : hour < 18 ? 'Szia' : 'Jó estét';

const tiles = [
    { label: 'Naptár', hint: 'Különleges napok', icon: CalendarHeart, href: route('calendar.index') },
    { label: 'Pillanat', hint: 'Képküldés', icon: Camera, href: route('snaps.index') },
    { label: 'Bevásárlás', hint: 'Közös lista', icon: ShoppingBasket, href: route('shopping.index') },
    { label: 'Kívánságok', hint: 'Ötletek ajándékhoz', icon: Gift, href: route('wishes.index') },
];
</script>

<template>
    <div class="space-y-5">
        <Head title="Kezdőlap" />

        <section class="card relative overflow-hidden p-6">
            <div class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-primary/15 blur-2xl"></div>
            <h1 class="text-2xl font-extrabold tracking-tight">{{ greeting }}, {{ user.name }}!</h1>
            <p class="mt-1 text-ink-soft">
                <template v-if="partner">Csak te és {{ partner.name }}. Ez a ti kis titkos világotok.</template>
                <template v-else>Ez a ti kis titkos világotok.</template>
            </p>
        </section>
            <InstallCard />
        <section class="card flex items-center gap-4 p-5">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-surface-2 text-primary">
                <CalendarHeart :size="24" />
            </div>
            <div>
                <h2 class="font-extrabold">Következő különleges nap</h2>
                <p class="text-sm text-ink-soft">A visszaszámláló hamarosan megjelenik itt.</p>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3">
            <Link v-for="tile in tiles" :key="tile.label" :href="tile.href" class="card p-4 transition active:scale-95">
                <div class="mb-3 grid h-11 w-11 place-items-center rounded-2xl bg-surface-2 text-primary">
                    <component :is="tile.icon" :size="22" />
                </div>
                <div class="font-extrabold">{{ tile.label }}</div>
                <div class="text-sm text-ink-soft">{{ tile.hint }}</div>
            </Link>
        </section>

        <section class="text-center">
            <button type="button" class="btn-primary" @click="burst($event, 'mixed', 14)">
                <Heart :size="20" fill="currentColor" />
                Gondolok rád
            </button>
            <p class="mt-2 text-xs text-ink-soft">Koppints, és szétpattannak a szívek!</p>
        </section>
    </div>
</template>