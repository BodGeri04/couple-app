<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, X } from 'lucide-vue-next';

const page = usePage();
const dismissed = ref(null);
const flash = computed(() => page.props.flash);

// A new flash object arrives with every response that carries one.
watch(flash, () => (dismissed.value = null));

const visible = computed(() => flash.value && dismissed.value !== flash.value);
</script>

<template>
    <div
        v-if="visible"
        role="status"
        class="card flex items-start gap-3 p-4 text-sm font-semibold"
        :class="flash.type === 'success' ? 'text-ink' : 'text-primary'"
    >
        <CircleCheck v-if="flash.type === 'success'" :size="20" class="mt-0.5 shrink-0 text-primary" />
        <CircleAlert v-else :size="20" class="mt-0.5 shrink-0" />
        <span class="min-w-0 flex-1 break-words">{{ flash.message }}</span>
        <button type="button" class="shrink-0 text-ink-soft" aria-label="Bezár" @click="dismissed = flash">
            <X :size="18" />
        </button>
    </div>
</template>
