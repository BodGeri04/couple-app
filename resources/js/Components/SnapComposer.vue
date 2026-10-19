<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Camera, Image as ImageIcon, RefreshCw } from 'lucide-vue-next';
import BottomSheet from '@/Components/BottomSheet.vue';
import { resizeImage } from '@/lib/image';
import { useBurst } from '@/Composables/useBurst';

const props = defineProps({
    open: Boolean,
    replyTo: Object, // the snap we're answering, or null
});
const emit = defineEmits(['close']);

const { burst } = useBurst();
const MOODS = ['😊', '🥰', '😴', '😎', '🤒', '🥳', '😭', '🤗'];

const form = useForm({ photo: null, thumb: null, caption: '', mood: '', reply_to_id: null });
const previewUrl = ref(null);
const processing = ref(false);
const error = ref('');
const cameraInput = ref(null);
const galleryInput = ref(null);

const reset = () => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = null;
    error.value = '';
    form.reset();
    form.clearErrors();
};

watch(
    () => props.open,
    (open) => {
        reset();
        if (open) form.reply_to_id = props.replyTo?.id ?? null;
    },
);

const pick = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = ''; // lets you pick the same file again
    if (!file) return;

    processing.value = true;
    error.value = '';

    try {
        const photo = await resizeImage(file, 1600, 0.82);
        const thumb = await resizeImage(file, 480, 0.75);

        form.photo = photo;
        form.thumb = thumb;

        if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = URL.createObjectURL(photo);
    } catch (e) {
        error.value = e.message || 'A kép nem olvasható.';
    } finally {
        processing.value = false;
    }
};

const submit = () => {
    if (!form.photo) return;

    form.post(route('snaps.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            burst({ clientX: window.innerWidth / 2, clientY: window.innerHeight / 2 }, 'heart', 14);
            emit('close');
        },
    });
};
</script>

<template>
    <BottomSheet
        :model-value="open"
        :title="replyTo ? `Válasz ${replyTo.sender} pillanatára` : 'Új pillanat'"
        @update:model-value="emit('close')"
    >
        <input ref="cameraInput" type="file" accept="image/*" capture="environment" class="hidden" @change="pick" />
        <input ref="galleryInput" type="file" accept="image/*" class="hidden" @change="pick" />

        <form class="space-y-4" @submit.prevent="submit">
            <!-- Step 1: choose a photo -->
            <div v-if="!previewUrl" class="grid grid-cols-2 gap-3">
                <button
                    type="button"
                    class="card flex flex-col items-center gap-2 p-6 transition active:scale-95"
                    :disabled="processing"
                    @click="cameraInput.click()"
                >
                    <Camera :size="32" class="text-primary" />
                    <span class="font-extrabold">Fotó készítése</span>
                </button>
                <button
                    type="button"
                    class="card flex flex-col items-center gap-2 p-6 transition active:scale-95"
                    :disabled="processing"
                    @click="galleryInput.click()"
                >
                    <ImageIcon :size="32" class="text-primary" />
                    <span class="font-extrabold">Galéria</span>
                </button>
            </div>

            <p v-if="processing" class="text-center text-sm font-semibold text-ink-soft">Kép feldolgozása…</p>
            <p v-if="error" class="text-center text-sm font-semibold text-primary">{{ error }}</p>
            <p v-if="form.errors.photo" class="text-center text-sm font-semibold text-primary">{{ form.errors.photo }}</p>

            <!-- Step 2: preview, caption, mood, send -->
            <template v-if="previewUrl">
                <div class="relative">
                    <img :src="previewUrl" alt="" class="max-h-[42vh] w-full rounded-3xl object-cover" />
                    <button
                        type="button"
                        class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-black/55 px-3 py-1.5 text-sm font-bold text-white backdrop-blur"
                        @click="galleryInput.click()"
                    >
                        <RefreshCw :size="14" /> Másik kép
                    </button>
                </div>

                <input
                    v-model="form.caption"
                    class="field"
                    placeholder="Írj hozzá valamit… (nem kötelező)"
                    maxlength="140"
                    autocomplete="off"
                />

                <div>
                    <span class="mb-1.5 block text-sm font-bold">Hangulat</span>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="mood in MOODS"
                            :key="mood"
                            type="button"
                            class="grid h-11 w-11 place-items-center rounded-2xl border text-2xl transition active:scale-90"
                            :class="form.mood === mood ? 'border-primary bg-primary/10' : 'border-line bg-surface'"
                            @click="form.mood = form.mood === mood ? '' : mood"
                        >
                            {{ mood }}
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? 'Küldés…' : 'Küldés 💗' }}
                </button>
            </template>
        </form>
    </BottomSheet>
</template>