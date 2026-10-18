<script setup>
import { computed } from 'vue';
import { Sun, Moon, Monitor } from 'lucide-vue-next';
import { useTheme } from '@/Composables/useTheme';

const { mode, cycle } = useTheme();

const icon = computed(() => ({ light: Sun, dark: Moon, system: Monitor })[mode.value]);
const label = computed(
    () => ({ light: 'Világos téma', dark: 'Sötét téma', system: 'Rendszer téma' })[mode.value],
);
</script>

<template>
    <button type="button" class="icon-btn" :aria-label="label" :title="label" @click="cycle">
        <Transition name="icon-swap" mode="out-in">
            <component :is="icon" :key="mode" :size="20" />
        </Transition>
    </button>
</template>