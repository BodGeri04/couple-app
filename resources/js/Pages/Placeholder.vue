<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    title: String,
    emoji: String,
    text: String,
});
</script>

<template>
    <div class="flex min-h-[55vh] flex-col items-center justify-center text-center">
        <Head :title="title" />
        <div class="animate-float text-6xl">{{ emoji }}</div>
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight">{{ title }}</h1>
        <p class="mt-2 max-w-xs text-ink-soft">{{ text }}</p>
    </div>
</template>