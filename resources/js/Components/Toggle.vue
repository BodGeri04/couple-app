<script setup>
defineProps({
    modelValue: Boolean,
    label: { type: String, required: true },
    hint: String,
    disabled: Boolean,
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="flex cursor-pointer items-center gap-3 py-2" :class="{ 'opacity-60': disabled }">
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-extrabold">{{ label }}</span>
            <span v-if="hint" class="block text-xs text-ink-soft">{{ hint }}</span>
        </span>
        <input
            type="checkbox"
            role="switch"
            class="peer sr-only"
            :checked="modelValue"
            :disabled="disabled"
            @change="$emit('update:modelValue', $event.target.checked)"
        />
        <span
            class="relative h-7 w-12 shrink-0 rounded-full bg-line transition peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 after:absolute after:left-0.5 after:top-0.5 after:h-6 after:w-6 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-5"
            aria-hidden="true"
        ></span>
    </label>
</template>
