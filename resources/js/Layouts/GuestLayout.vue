<script setup>
import { Heart } from 'lucide-vue-next';
import ThemeToggle from '@/Components/ThemeToggle.vue';
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-canvas px-5">
        <!-- soft decorative blobs -->
        <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-primary/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-accent/25 blur-3xl"></div>

        <div class="absolute right-4" style="top: calc(env(safe-area-inset-top) + 1rem)">
            <ThemeToggle />
        </div>

        <div class="relative w-full max-w-sm">
            <div class="mb-8 flex flex-col items-center gap-3">
                <div class="grid h-20 w-20 animate-float place-items-center rounded-3xl bg-gradient-to-br from-primary to-peach text-white shadow-pop">
                    <Heart :size="38" fill="currentColor" />
                </div>
                <h1 class="text-2xl font-extrabold tracking-tight">dateAPP</h1>
                <p class="text-sm text-ink-soft">Csak mi ketten</p>
            </div>

            <div class="card p-6">
                <slot />
            </div>
        </div>
    </div>
</template>