<script setup>
import { reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ChevronDown, Search } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SimplePager from '@/Components/SimplePager.vue';
import AdminTabs from './Partials/AdminTabs.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    logs: Object,
    filters: Object,
    categories: Object,
    users: Array,
});

const form = reactive({ ...props.filters });
const expanded = ref(null);

const apply = () => {
    const query = Object.fromEntries(Object.entries(form).filter(([, v]) => v !== '' && v !== null));
    router.get(route('admin.logs.index'), query, { preserveState: true, preserveScroll: true });
};

const reset = () => {
    Object.keys(form).forEach((key) => (form[key] = ''));
    apply();
};

const tone = {
    auth: 'bg-accent/25 text-ink',
    access: 'bg-primary/15 text-primary',
    activity: 'bg-surface-2 text-ink-soft',
    admin: 'bg-peach/40 text-ink',
};

const when = (iso) =>
    new Date(iso).toLocaleString('hu-HU', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
</script>

<template>
    <div class="space-y-5">
        <Head title="Napló" />

        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Napló</h1>
            <p class="text-sm text-ink-soft">Belépések, hozzáférések, tevékenységek és admin műveletek. Csak olvasható.</p>
        </div>

        <AdminTabs />

        <form class="card space-y-3 p-4" @submit.prevent="apply">
            <div class="relative">
                <Search :size="18" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-ink-soft" />
                <input v-model="form.q" class="field pl-11" placeholder="Keresés (leírás, IP, név…)" maxlength="100" />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <select v-model="form.category" class="field !py-2" aria-label="Kategória">
                    <option value="">Minden kategória</option>
                    <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                </select>
                <select v-model="form.user" class="field !py-2" aria-label="Felhasználó">
                    <option value="">Mindenki</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <input v-model="form.from" type="date" class="field !py-2" aria-label="Ettől" />
                <input v-model="form.to" type="date" class="field !py-2" aria-label="Eddig" />
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary !py-2.5">Szűrés</button>
                <button type="button" class="btn-primary !w-auto !bg-none !bg-surface-2 !px-5 !py-2.5 !text-ink-soft !shadow-none" @click="reset">
                    Törlés
                </button>
            </div>
        </form>

        <div v-if="!logs.data.length" class="card p-8 text-center">
            <div class="text-5xl">📜</div>
            <h2 class="mt-3 text-lg font-extrabold">Nincs találat</h2>
        </div>

        <ul v-else class="space-y-2">
            <li v-for="log in logs.data" :key="log.id" class="card p-3">
                <button
                    type="button"
                    class="flex w-full items-start gap-3 text-left"
                    :aria-expanded="expanded === log.id"
                    @click="expanded = expanded === log.id ? null : log.id"
                >
                    <span class="mt-0.5 shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-extrabold" :class="tone[log.category] ?? tone.activity">
                        {{ categories[log.category] ?? log.category }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block break-words text-sm font-bold">{{ log.description }}</span>
                        <span class="block text-xs text-ink-soft">
                            {{ log.actor ?? 'Vendég' }} · {{ when(log.created_at) }}<template v-if="log.ip"> · {{ log.ip }}</template>
                        </span>
                    </span>
                    <ChevronDown :size="18" class="mt-1 shrink-0 text-ink-soft transition" :class="{ 'rotate-180': expanded === log.id }" />
                </button>

                <dl v-if="expanded === log.id" class="mt-3 space-y-2 border-t border-line/60 pt-3 text-xs">
                    <div>
                        <dt class="font-extrabold text-ink-soft">Esemény</dt>
                        <dd class="break-all font-mono">{{ log.event }}</dd>
                    </div>
                    <div v-if="log.user_agent">
                        <dt class="font-extrabold text-ink-soft">Eszköz</dt>
                        <dd class="break-words">{{ log.user_agent }}</dd>
                    </div>
                    <div v-if="log.properties">
                        <dt class="font-extrabold text-ink-soft">Részletek</dt>
                        <dd><pre class="overflow-x-auto whitespace-pre-wrap break-words rounded-xl bg-surface-2 p-2 font-mono">{{ JSON.stringify(log.properties, null, 2) }}</pre></dd>
                    </div>
                </dl>
            </li>
        </ul>

        <SimplePager :page="logs" />
    </div>
</template>
