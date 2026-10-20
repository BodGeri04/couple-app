<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { iconFor } from '@/lib/icons';

const page = usePage();

// The menu is edited in the admin panel; the server already dropped hidden / switched-off items.
const items = computed(() =>
    (page.props.site?.menu ?? []).map((item) => ({
        ...item,
        icon: iconFor(item.icon),
        path: item.href.split(/[?#]/)[0],
        center: item.featured,
    })),
);

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
            <li v-for="item in items" :key="item.href" class="flex flex-1 justify-center">
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
                            v-if="item.key === 'snaps' && page.props.unseenSnaps > 0"
                            class="absolute -right-0.5 -top-0.5 grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-white px-1 text-[11px] font-extrabold leading-none text-primary shadow"
                        >
                            {{ page.props.unseenSnaps }}
                        </span>
                    </span>
                    <template v-else>
                        <span class="nav-icon relative">
                            <component :is="item.icon" :size="22" />
                            <span
                                v-if="item.key === 'snaps' && page.props.unseenSnaps > 0"
                                class="absolute right-1 top-0 grid h-4 min-w-[1rem] place-items-center rounded-full bg-primary px-1 text-[10px] font-extrabold leading-none text-white"
                            >
                                {{ page.props.unseenSnaps }}
                            </span>
                        </span>
                        <span class="nav-label">{{ item.label }}</span>
                    </template>
                    <span v-if="item.center" class="sr-only">{{ item.label }}</span>
                </Link>
            </li>
        </ul>
    </nav>
</template>