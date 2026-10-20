<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

defineProps({ page: { type: Object, required: true } });
</script>

<template>
    <nav v-if="page.last_page > 1" class="flex items-center justify-between gap-3" aria-label="Lapozás">
        <Link
            v-if="page.prev_page_url"
            :href="page.prev_page_url"
            preserve-scroll
            class="inline-flex items-center gap-1 rounded-full border border-line bg-surface px-4 py-2 text-sm font-extrabold"
        >
            <ChevronLeft :size="16" /> Előző
        </Link>
        <span v-else class="w-24"></span>

        <span class="text-sm font-bold text-ink-soft">{{ page.current_page }} / {{ page.last_page }}</span>

        <Link
            v-if="page.next_page_url"
            :href="page.next_page_url"
            preserve-scroll
            class="inline-flex items-center gap-1 rounded-full border border-line bg-surface px-4 py-2 text-sm font-extrabold"
        >
            Következő <ChevronRight :size="16" />
        </Link>
        <span v-else class="w-24"></span>
    </nav>
</template>
