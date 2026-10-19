<script setup>
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import BottomSheet from '@/Components/BottomSheet.vue';
import { CATEGORIES } from '@/lib/calendar';
import { useBurst } from '@/Composables/useBurst';

const props = defineProps({
    open: Boolean,
    event: Object, // null = new event
});
const emit = defineEmits(['close']);

const { burst } = useBurst();
const EMOJIS = ['💍', '🎂', '🌹', '✈️', '🏠', '🥂', '🎁', '🌟', '💗', '🎉'];
const confirming = ref(false);

const form = useForm({
    title: '',
    description: '',
    event_date: '',
    event_time: '',
    category: 'anniversary',
    repeats_yearly: false,
    emoji: '',
});

const todayLocal = () => new Date().toLocaleDateString('sv-SE'); // YYYY-MM-DD in local time

watch(
    () => props.open,
    (open) => {
        if (!open) return;
        confirming.value = false;
        form.clearErrors();

        const e = props.event;
        form.title = e?.title ?? '';
        form.description = e?.description ?? '';
        form.event_date = e?.event_date ?? todayLocal();
        form.event_time = e?.event_time ?? '';
        form.category = e?.category ?? 'anniversary';
        form.repeats_yearly = e?.repeats_yearly ?? true;
        form.emoji = e?.emoji ?? '';
    },
);

const selectCategory = (value) => {
    form.category = value;
    if (!props.event) form.repeats_yearly = value === 'anniversary';
};

const submit = () => {
    const isNew = !props.event;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            if (isNew) burst({ clientX: window.innerWidth / 2, clientY: window.innerHeight / 2 }, 'mixed', 14);
            emit('close');
        },
    };

    if (isNew) form.post(route('calendar.store'), options);
    else form.patch(route('calendar.update', props.event.id), options);
};

const remove = () => {
    if (!confirming.value) {
        confirming.value = true;
        return;
    }
    router.delete(route('calendar.destroy', props.event.id), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
};
</script>

<template>
    <BottomSheet
        :model-value="open"
        :title="event ? 'Esemény szerkesztése' : 'Új esemény'"
        @update:model-value="emit('close')"
    >
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="ev-title" class="mb-1.5 block text-sm font-bold">Cím</label>
                <input id="ev-title" v-model="form.title" class="field" placeholder="pl. Első randi évfordulója" maxlength="80" autocomplete="off" />
                <p v-if="form.errors.title" class="mt-1.5 text-sm font-semibold text-primary">{{ form.errors.title }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="ev-date" class="mb-1.5 block text-sm font-bold">Dátum</label>
                    <input id="ev-date" v-model="form.event_date" type="date" class="field" />
                    <p v-if="form.errors.event_date" class="mt-1.5 text-sm font-semibold text-primary">{{ form.errors.event_date }}</p>
                </div>
                <div>
                    <label for="ev-time" class="mb-1.5 block text-sm font-bold">Időpont</label>
                    <input id="ev-time" v-model="form.event_time" type="time" class="field" />
                    <p v-if="form.errors.event_time" class="mt-1.5 text-sm font-semibold text-primary">{{ form.errors.event_time }}</p>
                </div>
            </div>

            <div>
                <span class="mb-1.5 block text-sm font-bold">Fajta</span>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="c in CATEGORIES"
                        :key="c.value"
                        type="button"
                        class="rounded-2xl border px-3 py-2.5 text-left text-sm font-extrabold transition active:scale-95"
                        :class="form.category === c.value ? 'border-primary bg-primary/10 text-primary' : 'border-line bg-surface text-ink-soft'"
                        @click="selectCategory(c.value)"
                    >
                        {{ c.emoji }} {{ c.label }}
                    </button>
                </div>
            </div>

            <label class="flex items-center justify-between rounded-2xl border border-line bg-surface px-4 py-3">
                <span class="text-sm font-bold">Minden évben ismétlődik</span>
                <input v-model="form.repeats_yearly" type="checkbox" class="h-6 w-6 rounded-md border-line text-primary focus:ring-primary/40" />
            </label>

            <div>
                <span class="mb-1.5 block text-sm font-bold">Jelölés</span>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="emoji in EMOJIS"
                        :key="emoji"
                        type="button"
                        class="grid h-11 w-11 place-items-center rounded-2xl border text-2xl transition active:scale-90"
                        :class="form.emoji === emoji ? 'border-primary bg-primary/10' : 'border-line bg-surface'"
                        @click="form.emoji = form.emoji === emoji ? '' : emoji"
                    >
                        {{ emoji }}
                    </button>
                </div>
            </div>

            <div>
                <label for="ev-desc" class="mb-1.5 block text-sm font-bold">Megjegyzés</label>
                <textarea id="ev-desc" v-model="form.description" rows="2" class="field resize-none" placeholder="Nem kötelező" maxlength="500"></textarea>
            </div>

            <button type="submit" class="btn-primary" :disabled="form.processing">
                {{ event ? 'Mentés' : 'Hozzáadás' }}
            </button>

            <button
                v-if="event"
                type="button"
                class="flex w-full items-center justify-center gap-2 rounded-2xl border px-5 py-3 font-extrabold transition active:scale-[.97]"
                :class="confirming ? 'border-primary bg-primary/10 text-primary' : 'border-line text-ink-soft'"
                @click="remove"
            >
                <Trash2 :size="18" />
                {{ confirming ? 'Biztosan törlöd?' : 'Törlés' }}
            </button>
        </form>
    </BottomSheet>
</template>