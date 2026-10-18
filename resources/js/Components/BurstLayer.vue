<script setup>
import { Heart, Star } from 'lucide-vue-next';
import { particles } from '@/Composables/useBurst';
</script>

<template>
    <div class="pointer-events-none fixed inset-0 z-[100] overflow-hidden" aria-hidden="true">
        <span
            v-for="p in particles"
            :key="p.id"
            class="burst-particle"
            :style="{
                left: p.x + 'px',
                top: p.y + 'px',
                '--dx': p.dx + 'px',
                '--dy': p.dy + 'px',
                '--rot': p.rot + 'deg',
                color: p.color,
            }"
        >
            <component :is="p.kind === 'star' ? Star : Heart" :size="p.size" fill="currentColor" :stroke-width="1.5" />
        </span>
    </div>
</template>