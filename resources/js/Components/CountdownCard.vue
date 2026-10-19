<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    event: { type: Object, required: true },
    compact: Boolean,
});

const now = ref(Date.now());
let timer;
onMounted(() => {
    timer = setInterval(() => (now.value = Date.now()), 1000);
});
onBeforeUnmount(() => clearInterval(timer));

const target = computed(() => new Date(props.event.next_at).getTime());
const remaining = computed(() => target.value - now.value);
const started = computed(() => remaining.value <= 0);

const parts = computed(() => {
    const total = Math.max(0, Math.floor(remaining.value / 1000));
    return [
        { value: Math.floor(total / 86400), label: 'nap' },
        { value: Math.floor((total % 86400) / 3600), label: 'óra' },
        { value: Math.floor((total % 3600) / 60), label: 'perc' },
        { value: total % 60, label: 'mp' },
    ];
});

const dateLabel = computed(() =>
    new Date(props.event.next_at).toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long',
    }),
);

const anniversary = computed(() =>
    props.event.repeats_yearly && props.event.years > 0 ? `${props.event.years}. évforduló` : null,
);
</script>

<template>
    <section
        class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary to-peach text-white shadow-pop"
        :class="compact ? 'p-5' : 'p-6'"
    >
        <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/15"></div>
        <div class="pointer-events-none absolute -bottom-12 -left-8 h-32 w-32 rounded-full bg-white/10"></div>

        <p class="relative text-xs font-extrabold uppercase tracking-wider text-white/85">Következő különleges nap</p>

        <div class="relative mt-2 flex items-center gap-3">
            <span :class="compact ? 'text-3xl' : 'text-4xl'">{{ event.emoji || '💗' }}</span>
            <div class="min-w-0">
                <h2 class="truncate font-extrabold" :class="compact ? 'text-xl' : 'text-2xl'">{{ event.title }}</h2>
                <p class="text-sm text-white/90">
                    <template v-if="anniversary">{{ anniversary }} · </template>{{ dateLabel }}
                </p>
            </div>
        </div>

        <div v-if="!started" class="relative mt-4 grid grid-cols-4 gap-2 text-center">
            <div v-for="part in parts" :key="part.label" class="rounded-2xl bg-white/20 py-2.5">
                <div class="font-extrabold tabular-nums" :class="compact ? 'text-xl' : 'text-2xl'">
                    {{ part.value }}
                </div>
                <div class="text-[11px] font-bold uppercase tracking-wide text-white/85">{{ part.label }}</div>
            </div>
        </div>
        <div v-else class="relative mt-4 rounded-2xl bg-white/20 py-4 text-center text-xl font-extrabold">
            Ma van! 🎉
        </div>
    </section>
</template>