<script setup>
import { Link } from '@inertiajs/vue3';
import { House, CalendarHeart, Camera, ShoppingBasket, Gift } from 'lucide-vue-next';

const items = [
    { label: 'Kezdőlap', icon: House, href: route('dashboard'), match: 'dashboard' },
    { label: 'Naptár', icon: CalendarHeart, href: route('calendar.index'), match: 'calendar.*' },
    { label: 'Pillanat', icon: Camera, href: route('snaps.index'), match: 'snaps.*', center: true },
    { label: 'Bevásárlás', icon: ShoppingBasket, href: route('shopping.index'), match: 'shopping.*' },
    { label: 'Kívánságok', icon: Gift, href: route('wishes.index'), match: 'wishes.*' },
];
</script>

<template>
    <nav
        class="bottom-nav fixed inset-x-0 bottom-0 z-30 border-t border-line/60 bg-surface/90 backdrop-blur-md"
        aria-label="Fő navigáció"
    >
        <ul class="mx-auto flex max-w-xl items-end justify-between px-2">
            <li v-for="item in items" :key="item.match" class="flex flex-1 justify-center">
                <Link
                    :href="item.href"
                    class="nav-item"
                    :class="{
                        'nav-item-active': route().current(item.match),
                        'nav-item-center': item.center,
                    }"
                    :aria-current="route().current(item.match) ? 'page' : undefined"
                >
                    <span v-if="item.center" class="nav-center-bubble">
                        <component :is="item.icon" :size="26" :stroke-width="2.2" />
                    </span>
                    <template v-else>
                        <span class="nav-icon"><component :is="item.icon" :size="22" /></span>
                        <span class="nav-label">{{ item.label }}</span>
                    </template>
                    <span v-if="item.center" class="sr-only">{{ item.label }}</span>
                </Link>
            </li>
        </ul>
    </nav>
</template>