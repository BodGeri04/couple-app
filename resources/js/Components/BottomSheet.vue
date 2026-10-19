<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: Boolean,
    title: String,
});
const emit = defineEmits(['update:modelValue']);

const close = () => emit('update:modelValue', false);
const onKey = (e) => {
    if (e.key === 'Escape') close();
};

watch(
    () => props.modelValue,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';
        if (open) window.addEventListener('keydown', onKey);
        else window.removeEventListener('keydown', onKey);
    },
);

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKey);
});
</script>

<template>
    <Teleport to="body">
        <Transition name="sheet" :duration="280">
            <div v-if="modelValue" class="fixed inset-0 z-50 flex items-end justify-center">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close"></div>

                <div
                    class="sheet-panel relative w-full max-w-xl overflow-y-auto rounded-t-[2rem] bg-canvas px-5 pt-3 shadow-2xl"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="title"
                    style="max-height: 90dvh; padding-bottom: max(1.5rem, env(safe-area-inset-bottom))"
                >
                    <div class="mx-auto mb-3 h-1.5 w-10 rounded-full bg-line"></div>

                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-extrabold">{{ title }}</h2>
                        <button type="button" class="icon-btn" aria-label="Bezárás" @click="close">
                            <X :size="20" />
                        </button>
                    </div>

                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>