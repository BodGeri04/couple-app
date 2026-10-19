<script setup>
import { computed, ref, watch } from 'vue';
import { CornerUpLeft, Trash2, X } from 'lucide-vue-next';

const props = defineProps({ snap: Object });
const emit = defineEmits(['close', 'reply', 'remove']);

const confirming = ref(false);
watch(
    () => props.snap?.id,
    () => (confirming.value = false),
);

const when = computed(() =>
    props.snap
        ? new Date(props.snap.created_at).toLocaleString('hu-HU', {
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '',
);

const seenAt = computed(() =>
    props.snap?.viewed_at
        ? new Date(props.snap.viewed_at).toLocaleTimeString('hu-HU', { hour: '2-digit', minute: '2-digit' })
        : null,
);

const remove = () => {
    if (!confirming.value) {
        confirming.value = true;
        return;
    }
    emit('remove', props.snap);
};
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="snap"
                class="fixed inset-0 z-50 flex flex-col bg-black text-white"
                role="dialog"
                aria-modal="true"
                aria-label="Pillanat"
                style="padding-top: env(safe-area-inset-top)"
            >
                <div class="flex items-center justify-between px-4 py-3">
                    <div>
                        <div class="font-extrabold">{{ snap.mine ? 'Te' : snap.sender }}</div>
                        <div class="text-xs text-white/70">{{ when }}</div>
                    </div>
                    <button
                        type="button"
                        class="grid h-10 w-10 place-items-center rounded-full bg-white/15 transition active:scale-90"
                        aria-label="Bezárás"
                        @click="emit('close')"
                    >
                        <X :size="22" />
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 items-center justify-center px-2">
                    <img :src="snap.image_url" alt="" class="max-h-full max-w-full rounded-2xl object-contain" />
                </div>

                <div class="space-y-3 px-5 pt-3" style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom))">
                    <p v-if="snap.caption || snap.mood" class="text-lg font-bold">{{ snap.mood }} {{ snap.caption }}</p>

                    <p v-if="snap.mine" class="text-sm text-white/70">
                        {{ seenAt ? `Látta ✓ ${seenAt}` : 'Még nem látta' }}
                    </p>

                    <div class="flex gap-3">
                        <button
                            v-if="!snap.mine"
                            type="button"
                            class="btn-primary"
                            @click="emit('reply', snap)"
                        >
                            <CornerUpLeft :size="20" /> Válasz
                        </button>
                        <button
                            v-else
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-2xl border px-5 py-3 font-extrabold transition active:scale-[.97]"
                            :class="confirming ? 'border-primary bg-primary/20 text-white' : 'border-white/25 text-white/80'"
                            @click="remove"
                        >
                            <Trash2 :size="18" />
                            {{ confirming ? 'Biztosan törlöd?' : 'Törlés' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>