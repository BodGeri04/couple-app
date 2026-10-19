<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { House, CalendarHeart, Camera, ShoppingBasket, Gift } from 'lucide-vue-next';

const page = usePage();

const items = [
    { label: 'Kezdőlap', icon: House, href: route('dashboard'), path: '/dashboard' },
    { label: 'Naptár', icon: CalendarHeart, href: route('calendar.index'), path: '/calendar' },
    { label: 'Pillanat', icon: Camera, href: route('snaps.index'), path: '/snaps', center: true },
    { label: 'Bevásárlás', icon: ShoppingBasket, href: route('shopping.index'), path: '/shopping' },
    { label: 'Kívánságok', icon: Gift, href: route('wishes.index'), path: '/wishes' },
];

// usePage().url is reactive, so the highlight follows every navigation.
const isActive = (item) => {
    const current = page.url.split('?')[0];
    return current === item.path || current.startsWith(`${item.path}/`);
};
</script>

<template>
    <nav
        class="bottom-nav fixed inset-x-0 bottom-0 z-30 border-t border-line/60 bg-surface/90 backdrop-blur-md"
        aria-label="Fő navigáció"
    >
        <ul class="mx-auto flex max-w-xl items-end justify-between px-2">
            <li v-for="item in items" :key="item.path" class="flex flex-1 justify-center">
                <Link
                    :href="item.href"
                    class="nav-item"
                    :class="{
                        'nav-item-active': isActive(item),
                        'nav-item-center': item.center,
                    }"
                    :aria-current="isActive(item) ? 'page' : undefined"
                >
                    <span v-if="item.center" class="nav-center-bubble relative">
                        <component :is="item.icon" :size="26" :stroke-width="2.2" />
                        <span
                            v-if="page.props.unseenSnaps > 0"
                            class="absolute -right-0.5 -top-0.5 grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-white px-1 text-[11px] font-extrabold leading-none text-primary shadow"
                        >
                            {{ page.props.unseenSnaps }}
                        </span>
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