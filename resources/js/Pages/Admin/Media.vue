<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Eye, EyeOff, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SimplePager from '@/Components/SimplePager.vue';
import AdminTabs from './Partials/AdminTabs.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    snaps: Object,
    filters: Object,
    senders: Array,
    totals: Object,
});

const status = ref(props.filters.status);
const user = ref(props.filters.user ?? '');

const applyFilters = () =>
    router.get(
        route('admin.media.index'),
        { status: status.value === 'all' ? undefined : status.value, user: user.value || undefined },
        { preserveScroll: true, preserveState: true },
    );

const moderate = (snap, hidden) =>
    router.patch(route('admin.media.moderate', snap.id), { hidden }, { preserveScroll: true });

const remove = (snap) => {
    if (!confirm('Biztosan véglegesen törlöd ezt a képet? Ez nem vonható vissza.')) return;
    router.delete(route('admin.media.destroy', snap.id), { preserveScroll: true });
};

const mb = (bytes) => (bytes ? `${(bytes / 1048576).toFixed(1)} MB` : '–');

const when = (iso) =>
    new Date(iso).toLocaleString('hu-HU', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <div class="space-y-5">
        <Head title="Képek" />

        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Képek</h1>
            <p class="text-sm text-ink-soft">
                {{ totals.all }} kép · {{ totals.hidden }} elrejtve · {{ mb(totals.bytes) }}
            </p>
            <p class="mt-1 text-xs text-ink-soft">A teljes méretű kép megnyitása naplózva van.</p>
        </div>

        <AdminTabs />

        <div class="flex gap-2">
            <select v-model="status" class="field !py-2" aria-label="Állapot" @change="applyFilters">
                <option value="all">Mind</option>
                <option value="visible">Látható</option>
                <option value="hidden">Elrejtett</option>
            </select>
            <select v-model="user" class="field !py-2" aria-label="Küldő" @change="applyFilters">
                <option value="">Minden küldő</option>
                <option v-for="s in senders" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
        </div>

        <div v-if="!snaps.data.length" class="card p-8 text-center">
            <div class="text-5xl">🖼️</div>
            <h2 class="mt-3 text-lg font-extrabold">Nincs megjeleníthető kép</h2>
        </div>

        <ul v-else class="grid grid-cols-2 gap-3 md:grid-cols-3">
            <li v-for="snap in snaps.data" :key="snap.id" class="card overflow-hidden">
                <a :href="snap.image_url" target="_blank" rel="noopener" class="relative block aspect-square bg-surface-2">
                    <img :src="snap.thumb_url" :alt="`Kép #${snap.id}`" loading="lazy" class="h-full w-full object-cover" :class="{ 'opacity-40': snap.hidden_at }" />
                    <span
                        v-if="snap.hidden_at"
                        class="absolute left-2 top-2 rounded-full bg-ink px-2 py-0.5 text-[11px] font-extrabold text-canvas"
                    >
                        Elrejtve
                    </span>
                    <span v-if="snap.mood" class="absolute bottom-2 right-2 text-xl">{{ snap.mood }}</span>
                </a>
                <div class="space-y-1 p-3">
                    <div class="truncate text-sm font-extrabold">{{ snap.sender }} → {{ snap.recipient }}</div>
                    <div v-if="snap.caption" class="truncate text-xs text-ink-soft">„{{ snap.caption }}”</div>
                    <div class="text-xs text-ink-soft">
                        {{ when(snap.created_at) }} · {{ mb(snap.size) }} · {{ snap.viewed_at ? 'megnézte' : 'még nem látta' }}
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <button
                            v-if="!snap.hidden_at"
                            type="button"
                            class="icon-btn"
                            aria-label="Elrejtés a felhasználók elől"
                            @click="moderate(snap, true)"
                        >
                            <EyeOff :size="18" />
                        </button>
                        <button v-else type="button" class="icon-btn" aria-label="Visszaállítás" @click="moderate(snap, false)">
                            <Eye :size="18" />
                        </button>
                        <button type="button" class="icon-btn" aria-label="Végleges törlés" @click="remove(snap)">
                            <Trash2 :size="18" />
                        </button>
                    </div>
                </div>
            </li>
        </ul>

        <SimplePager :page="snaps" />
    </div>
</template>
