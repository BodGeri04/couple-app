<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Heart } from 'lucide-vue-next';
import BottomNav from '@/Components/BottomNav.vue';
import BurstLayer from '@/Components/BurstLayer.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { usePullToRefresh } from '@/Composables/usePullToRefresh';

const page = usePage();
const initial = computed(() => (page.props.auth.user?.name ?? '?').charAt(0).toUpperCase());
const pageKey = computed(() => page.url.split('?')[0]);

const { pull, dragging, refreshing } = usePullToRefresh();

const mainStyle = computed(() => ({
    transform: pull.value ? `translateY(${pull.value}px)` : undefined,
    transition: dragging.value ? 'none' : 'transform .25s ease',
}));

const heartStyle = computed(() => ({
    opacity: Math.min(1, pull.value / 50),
    transform: `scale(${0.6 + Math.min(1, pull.value / 72) * 0.6})`,
}));
</script>

<template>
    <div class="min-h-screen bg-canvas text-ink">
        <!-- Header -->
        <header class="app-header fixed inset-x-0 top-0 z-30 border-b border-line/60 bg-canvas/85 backdrop-blur-md">
            <div class="mx-auto flex h-14 max-w-xl items-center justify-between px-4">
                <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-2">
                    <Heart :size="22" class="shrink-0 text-primary" fill="currentColor" />
                    <slot name="header">
                        <span class="truncate text-lg font-extrabold tracking-tight">dateAPP</span>
                    </slot>
                </Link>

                <div class="flex items-center gap-2">
                    <ThemeToggle />
                    <Link :href="route('profile.edit')" class="avatar" aria-label="Profil">
                        {{ initial }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Pull-to-refresh heart (revealed behind the content as it slides down) -->
        <div
            class="pointer-events-none fixed inset-x-0 z-0 flex justify-center pt-3"
            style="top: var(--header-h)"
            aria-hidden="true"
        >
            <Heart
                :size="30"
                class="text-primary"
                :class="{ 'animate-heartbeat': refreshing }"
                fill="currentColor"
                :style="heartStyle"
            />
        </div>

        <!-- Page content -->
        <main class="app-main relative z-10 mx-auto max-w-xl px-4" :style="mainStyle">
            <Transition name="page" mode="out-in">
                <div :key="pageKey">
                    <slot />
                </div>
            </Transition>
        </main>

        <BottomNav />
        <BurstLayer />
    </div>
</template>