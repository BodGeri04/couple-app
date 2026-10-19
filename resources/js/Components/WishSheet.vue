<script setup>
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import BottomSheet from '@/Components/BottomSheet.vue';
import { useBurst } from '@/Composables/useBurst';

const props = defineProps({
    open: Boolean,
    wish: Object, // null = new wish
});
const emit = defineEmits(['close']);

const { burst } = useBurst();
const confirming = ref(false);
const form = useForm({ name: '', url: '' });

watch(
    () => props.open,
    (open) => {
        if (!open) return;
        confirming.value = false;
        form.clearErrors();
        form.name = props.wish?.name ?? '';
        form.url = props.wish?.url ?? '';
    },
);

const submit = () => {
    const isNew = !props.wish;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            if (isNew) burst({ clientX: window.innerWidth / 2, clientY: window.innerHeight / 2 }, 'heart', 12);
            emit('close');
        },
    };

    if (isNew) form.post(route('wishes.store'), options);
    else form.patch(route('wishes.update', props.wish.id), options);
};

const remove = () => {
    if (!confirming.value) {
        confirming.value = true;
        return;
    }
    router.delete(route('wishes.destroy', props.wish.id), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
};
</script>

<template>
    <BottomSheet
        :model-value="open"
        :title="wish ? 'Kívánság szerkesztése' : 'Új kívánság'"
        @update:model-value="emit('close')"
    >
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="wish-name" class="mb-1.5 block text-sm font-bold">Megnevezés</label>
                <input id="wish-name" v-model="form.name" class="field" placeholder="Mit szeretnél kapni?" maxlength="100" autocomplete="off" />
                <p v-if="form.errors.name" class="mt-1.5 text-sm font-semibold text-primary">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="wish-url" class="mb-1.5 block text-sm font-bold">Link</label>
                <input
                    id="wish-url"
                    v-model="form.url"
                    type="url"
                    inputmode="url"
                    class="field"
                    placeholder="https://…"
                    autocapitalize="none"
                    autocomplete="off"
                />
                <p v-if="form.errors.url" class="mt-1.5 text-sm font-semibold text-primary">{{ form.errors.url }}</p>
            </div>

            <button type="submit" class="btn-primary" :disabled="form.processing">
                {{ wish ? 'Mentés' : 'Hozzáadás' }}
            </button>

            <button
                v-if="wish"
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