<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Image, LayoutDashboard, Palette, ScrollText, Users } from 'lucide-vue-next';
import FlashMessage from '@/Components/FlashMessage.vue';

const page = usePage();

const tabs = [
    { label: 'Áttekintés', icon: LayoutDashboard, href: route('admin.dashboard'), path: '/admin', exact: true },
    { label: 'Felhasználók', icon: Users, href: route('admin.users.index'), path: '/admin/users' },
    { label: 'Megjelenés', icon: Palette, href: route('admin.cms.index'), path: '/admin/cms' },
    { label: 'Képek', icon: Image, href: route('admin.media.index'), path: '/admin/media' },
    { label: 'Napló', icon: ScrollText, href: route('admin.logs.index'), path: '/admin/logs' },
];

const isActive = (tab) => {
    const current = page.url.split('?')[0];
    return tab.exact ? current === tab.path : current === tab.path || current.startsWith(`${tab.path}/`);
};
</script>

<template>
    <div class="space-y-4">
        <nav aria-label="Admin" class="-mx-4 overflow-x-auto px-4 pb-1">
            <ul class="flex w-max gap-2">
                <li v-for="tab in tabs" :key="tab.path">
                    <Link
                        :href="tab.href"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-2 text-sm font-extrabold transition active:scale-95"
                        :class="
                            isActive(tab)
                                ? 'border-primary bg-primary text-white shadow-pop'
                                : 'border-line bg-surface text-ink-soft'
                        "
                        :aria-current="isActive(tab) ? 'page' : undefined"
                    >
                        <component :is="tab.icon" :size="16" />
                        {{ tab.label }}
                    </Link>
                </li>
            </ul>
        </nav>

        <FlashMessage />
    </div>
</template>
