<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ChevronDown, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CountdownCard from '@/Components/CountdownCard.vue';
import EventSheet from '@/Components/EventSheet.vue';
import { categoryOf } from '@/lib/calendar';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    upcoming: Array,
    past: Array,
});

const sheetOpen = ref(false);
const editing = ref(null);
const showPast = ref(false);

const openNew = () => {
    editing.value = null;
    sheetOpen.value = true;
};
const openEdit = (event) => {
    editing.value = event;
    sheetOpen.value = true;
};

const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
const parseDate = (value) => {
    const [y, m, d] = value.split('-').map(Number);
    return new Date(y, m - 1, d);
};

const untilLabel = (iso) => {
    const days = Math.round((startOfDay(new Date(iso)) - startOfDay(new Date())) / 86400000);
    return days <= 0 ? 'Ma' : days === 1 ? 'Holnap' : `${days} nap`;
};

const sinceLabel = (value) => {
    const days = Math.round((startOfDay(new Date()) - startOfDay(parseDate(value))) / 86400000);
    if (days >= 365) return `${Math.floor(days / 365)} éve`;
    return days === 1 ? 'Tegnap' : `${days} napja`;
};

const badge = (date) => ({
    month: date.toLocaleDateString('hu-HU', { month: 'short' }).replace('.', ''),
    day: date.getDate(),
});
</script>

<template>
    <div class="space-y-5">
        <Head title="Naptár" />

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-extrabold tracking-tight">Naptár</h1>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 text-sm font-extrabold text-white shadow-pop transition active:scale-95"
                @click="openNew"
            >
                <Plus :size="18" /> Új
            </button>
        </div>

        <template v-if="upcoming.length">
            <CountdownCard :event="upcoming[0]" />

            <div v-if="upcoming.length > 1" class="grid grid-cols-2 gap-3">
                <button
                    v-for="event in upcoming.slice(1, 3)"
                    :key="event.id"
                    type="button"
                    class="card p-4 text-left transition active:scale-95"
                    @click="openEdit(event)"
                >
                    <div class="text-2xl">{{ event.emoji || categoryOf(event.category).emoji }}</div>
                    <div class="mt-1 truncate font-extrabold">{{ event.title }}</div>
                    <div class="text-sm font-bold text-primary">{{ untilLabel(event.next_at) }}</div>
                </button>
            </div>

            <section>
                <h2 class="mb-2 text-sm font-extrabold uppercase tracking-wider text-ink-soft">Közelgő események</h2>
                <ul class="flex flex-col gap-2">
                    <li v-for="event in upcoming" :key="event.id">
                        <button
                            type="button"
                            class="card flex w-full items-center gap-3 p-3 text-left transition active:scale-[.98]"
                            @click="openEdit(event)"
                        >
                            <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl bg-surface-2 leading-none text-primary">
                                <span class="text-[10px] font-extrabold uppercase">{{ badge(new Date(event.next_at)).month }}</span>
                                <span class="mt-0.5 text-xl font-extrabold">{{ badge(new Date(event.next_at)).day }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-extrabold">{{ event.emoji || categoryOf(event.category).emoji }} {{ event.title }}</div>
                                <div class="text-sm text-ink-soft">
                                    {{ categoryOf(event.category).label }}<template v-if="event.event_time"> · {{ event.event_time }}</template><template v-if="event.repeats_yearly"> · évente</template>
                                </div>
                            </div>
                            <div class="shrink-0 text-sm font-extrabold text-primary">{{ untilLabel(event.next_at) }}</div>
                        </button>
                    </li>
                </ul>
            </section>
        </template>

        <div v-else class="card p-8 text-center">
            <div class="animate-float text-5xl">📅</div>
            <h2 class="mt-3 text-lg font-extrabold">Még nincs közös esemény</h2>
            <p class="mt-1 text-sm text-ink-soft">Add hozzá az első fontos napotokat: évforduló, randi, mérföldkő…</p>
            <button type="button" class="btn-primary mt-4" @click="openNew">Esemény hozzáadása</button>
        </div>

        <section v-if="past.length">
            <button type="button" class="flex w-full items-center justify-between py-1" @click="showPast = !showPast">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-ink-soft">Elmúlt események ({{ past.length }})</h2>
                <ChevronDown :size="18" class="text-ink-soft transition" :class="{ 'rotate-180': showPast }" />
            </button>
            <ul v-if="showPast" class="mt-2 flex flex-col gap-2">
                <li v-for="event in past" :key="event.id">
                    <button
                        type="button"
                        class="card flex w-full items-center gap-3 p-3 text-left opacity-80 transition active:scale-[.98]"
                        @click="openEdit(event)"
                    >
                        <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl bg-surface-2 leading-none text-ink-soft">
                            <span class="text-[10px] font-extrabold uppercase">{{ badge(parseDate(event.event_date)).month }}</span>
                            <span class="mt-0.5 text-xl font-extrabold">{{ badge(parseDate(event.event_date)).day }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-extrabold">{{ event.emoji || categoryOf(event.category).emoji }} {{ event.title }}</div>
                            <div class="text-sm text-ink-soft">{{ categoryOf(event.category).label }}</div>
                        </div>
                        <div class="shrink-0 text-sm font-bold text-ink-soft">{{ sinceLabel(event.event_date) }}</div>
                    </button>
                </li>
            </ul>
        </section>

        <EventSheet :open="sheetOpen" :event="editing" @close="sheetOpen = false" />
    </div>
</template>