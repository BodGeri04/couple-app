<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BellRing, Image, Send, Smartphone, TriangleAlert, Users } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Toggle from '@/Components/Toggle.vue';
import AdminTabs from './Partials/AdminTabs.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    stats: Object,
    settings: Object,
    pushConfigured: Boolean,
    users: Array,
    recent: Array,
});

const initial = (section) => Object.fromEntries(props.settings[section].map((s) => [s.key, s.value]));

const settingsForm = useForm({ settings: { ...initial('system'), ...initial('features') } });

const dirty = computed(() =>
    [...props.settings.system, ...props.settings.features].some((s) => settingsForm.settings[s.key] !== s.value),
);

const saveSettings = () => settingsForm.patch(route('admin.settings.update'), { preserveScroll: true });

const maintenance = computed(() => props.settings.system.find((s) => s.key === 'maintenance_message'));

const notifyForm = useForm({ user_id: '', title: 'Teszt értesítés 💗', body: 'Működik!', url: '/dashboard' });

const sendTest = () => notifyForm.post(route('admin.notifications.test'), { preserveScroll: true });

const when = (iso) =>
    new Date(iso).toLocaleString('hu-HU', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });

const statTiles = computed(() => [
    { label: 'Felhasználó', value: props.stats.users, icon: Users },
    { label: 'Kép (elrejtve)', value: `${props.stats.snaps} (${props.stats.hidden_snaps})`, icon: Image },
    { label: 'Értesítési eszköz', value: props.stats.devices, icon: Smartphone },
    { label: 'Esemény / 24 óra', value: props.stats.events_24h, icon: BellRing },
]);
</script>

<template>
    <div class="space-y-5">
        <Head title="Admin" />

        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Admin</h1>
            <p class="text-sm text-ink-soft">Rendszerbeállítások és eszközök</p>
        </div>

        <AdminTabs />

        <section class="grid grid-cols-2 gap-3">
            <div v-for="tile in statTiles" :key="tile.label" class="card p-4">
                <div class="mb-2 grid h-10 w-10 place-items-center rounded-2xl bg-surface-2 text-primary">
                    <component :is="tile.icon" :size="20" />
                </div>
                <div class="text-xl font-extrabold">{{ tile.value }}</div>
                <div class="text-xs text-ink-soft">{{ tile.label }}</div>
            </div>
        </section>

        <Link
            v-if="stats.failed_logins_24h > 0"
            :href="route('admin.logs.index', { category: 'auth' })"
            class="card flex items-center gap-3 p-4 text-sm font-bold text-primary"
        >
            <TriangleAlert :size="20" class="shrink-0" />
            {{ stats.failed_logins_24h }} sikertelen bejelentkezés az elmúlt 24 órában
        </Link>

        <!-- System settings & toggles -->
        <form class="card space-y-1 p-5" @submit.prevent="saveSettings">
            <h2 class="text-lg font-extrabold">Rendszerbeállítások</h2>

            <div class="divide-y divide-line/60">
                <Toggle
                    v-for="s in settings.system.filter((s) => s.type === 'bool')"
                    :key="s.key"
                    v-model="settingsForm.settings[s.key]"
                    :label="s.label"
                    :hint="s.hint"
                />
            </div>

            <div v-if="maintenance && settingsForm.settings.maintenance" class="pt-2">
                <label for="maintenance_message" class="mb-1.5 block text-sm font-bold">{{ maintenance.label }}</label>
                <input
                    id="maintenance_message"
                    v-model="settingsForm.settings.maintenance_message"
                    class="field"
                    :maxlength="maintenance.max"
                />
                <p v-if="settingsForm.errors['settings.maintenance_message']" class="mt-1.5 text-sm font-semibold text-primary">
                    {{ settingsForm.errors['settings.maintenance_message'] }}
                </p>
            </div>

            <h3 class="pt-4 text-sm font-extrabold uppercase tracking-wider text-ink-soft">Funkciók</h3>
            <div class="divide-y divide-line/60">
                <Toggle
                    v-for="s in settings.features"
                    :key="s.key"
                    v-model="settingsForm.settings[s.key]"
                    :label="s.label"
                    :hint="s.hint"
                />
            </div>

            <p v-if="settingsForm.errors.settings" class="text-sm font-semibold text-primary">{{ settingsForm.errors.settings }}</p>

            <div class="pt-3">
                <button type="submit" class="btn-primary" :disabled="settingsForm.processing || !dirty">
                    {{ settingsForm.processing ? 'Mentés…' : 'Beállítások mentése' }}
                </button>
            </div>
        </form>

        <!-- Notification tester -->
        <form class="card space-y-3 p-5" @submit.prevent="sendTest">
            <h2 class="text-lg font-extrabold">Értesítés tesztelése</h2>

            <p v-if="!pushConfigured" class="rounded-2xl bg-surface-2 px-4 py-3 text-sm font-semibold text-primary">
                A VAPID kulcsok nincsenek beállítva, ezért a küldés nem fog működni.
            </p>

            <div>
                <label for="n_user" class="mb-1.5 block text-sm font-bold">Címzett</label>
                <select id="n_user" v-model="notifyForm.user_id" class="field">
                    <option value="">Mindenki</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">
                        {{ user.name }} ({{ user.devices }} eszköz)
                    </option>
                </select>
                <p v-if="notifyForm.errors.user_id" class="mt-1.5 text-sm font-semibold text-primary">{{ notifyForm.errors.user_id }}</p>
            </div>

            <div>
                <label for="n_title" class="mb-1.5 block text-sm font-bold">Cím</label>
                <input id="n_title" v-model="notifyForm.title" class="field" maxlength="80" required />
                <p v-if="notifyForm.errors.title" class="mt-1.5 text-sm font-semibold text-primary">{{ notifyForm.errors.title }}</p>
            </div>

            <div>
                <label for="n_body" class="mb-1.5 block text-sm font-bold">Szöveg</label>
                <input id="n_body" v-model="notifyForm.body" class="field" maxlength="200" />
                <p v-if="notifyForm.errors.body" class="mt-1.5 text-sm font-semibold text-primary">{{ notifyForm.errors.body }}</p>
            </div>

            <div>
                <label for="n_url" class="mb-1.5 block text-sm font-bold">Megnyitott oldal</label>
                <input id="n_url" v-model="notifyForm.url" class="field" maxlength="200" placeholder="/dashboard" />
                <p v-if="notifyForm.errors.url" class="mt-1.5 text-sm font-semibold text-primary">{{ notifyForm.errors.url }}</p>
            </div>

            <button type="submit" class="btn-primary" :disabled="notifyForm.processing || !notifyForm.title.trim()">
                <Send :size="18" />
                {{ notifyForm.processing ? 'Küldés…' : 'Teszt küldése' }}
            </button>
        </form>

        <!-- Recent activity -->
        <section class="card p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-extrabold">Legutóbbi események</h2>
                <Link :href="route('admin.logs.index')" class="text-sm font-extrabold text-primary">Összes</Link>
            </div>
            <ul v-if="recent.length" class="divide-y divide-line/60">
                <li v-for="log in recent" :key="log.id" class="py-2">
                    <div class="text-sm font-bold">{{ log.description }}</div>
                    <div class="text-xs text-ink-soft">{{ log.actor ?? 'Vendég' }} · {{ when(log.created_at) }}</div>
                </li>
            </ul>
            <p v-else class="text-sm text-ink-soft">Még nincs rögzített esemény.</p>
        </section>
    </div>
</template>
