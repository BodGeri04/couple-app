<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Eye, EyeOff, Plus, Star, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Toggle from '@/Components/Toggle.vue';
import { iconFor } from '@/lib/icons';
import AdminTabs from './Partials/AdminTabs.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    ui: Array,
    menu: Array,
    icons: Array,
    maxVisible: Number,
});

/* ---------- UI settings ---------- */
const def = (key) => props.ui.find((s) => s.key === key);
const uiForm = useForm({ settings: Object.fromEntries(props.ui.map((s) => [s.key, s.value])) });
const uiDirty = computed(() => props.ui.some((s) => uiForm.settings[s.key] !== s.value));
const saveUi = () => uiForm.patch(route('admin.settings.update'), { preserveScroll: true });

const toggles = computed(() => props.ui.filter((s) => s.type === 'bool'));

// <input type="color"> cannot be empty, so "default" is a separate switch.
const customColor = computed({
    get: () => !!uiForm.settings.primary_color,
    set: (on) => (uiForm.settings.primary_color = on ? '#ec588a' : ''),
});

/* ---------- Menu ---------- */
const menuForm = useForm({ items: props.menu.map((i) => ({ ...i })) });

const visibleCount = computed(() => menuForm.items.filter((i) => i.visible).length);
const menuDirty = computed(() => JSON.stringify(menuForm.items) !== JSON.stringify(props.menu));

const move = (index, delta) => {
    const target = index + delta;
    if (target < 0 || target >= menuForm.items.length) return;
    const items = [...menuForm.items];
    [items[index], items[target]] = [items[target], items[index]];
    menuForm.items = items;
};

const setFeatured = (item) => {
    const next = !item.featured;
    menuForm.items.forEach((i) => (i.featured = false));
    item.featured = next;
};

const addItem = () =>
    menuForm.items.push({ id: null, key: null, label: 'Új', icon: 'Star', href: '/', visible: false, featured: false });

const removeItem = (index) => menuForm.items.splice(index, 1);

const saveMenu = () => menuForm.put(route('admin.cms.menu'), { preserveScroll: true });

const err = (path) => menuForm.errors[path];
</script>

<template>
    <div class="space-y-5">
        <Head title="Megjelenés" />

        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Megjelenés</h1>
            <p class="text-sm text-ink-soft">Menü, színek és a kezdőlap elemei – azonnal érvényesek.</p>
        </div>

        <AdminTabs />

        <!-- Menu editor -->
        <form class="card space-y-3 p-5" @submit.prevent="saveMenu">
            <div>
                <h2 class="text-lg font-extrabold">Alsó menü</h2>
                <p class="text-xs text-ink-soft">
                    Legfeljebb {{ maxVisible }} látható elem ({{ visibleCount }} most). A kiemelt elem a középső nagy gomb.
                </p>
            </div>

            <ul class="space-y-3">
                <li v-for="(item, index) in menuForm.items" :key="item.id ?? `new-${index}`" class="rounded-2xl border border-line/70 bg-surface-2/50 p-3">
                    <div class="flex items-center gap-2">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-surface text-primary">
                            <component :is="iconFor(item.icon)" :size="20" />
                        </span>
                        <input
                            v-model="item.label"
                            class="field !py-2"
                            maxlength="30"
                            :aria-label="`Név (${index + 1}. elem)`"
                            required
                        />
                    </div>

                    <div class="mt-2 flex items-center gap-2">
                        <select v-model="item.icon" class="field !py-2" :aria-label="`Ikon (${index + 1}. elem)`">
                            <option v-for="name in icons" :key="name" :value="name">{{ name }}</option>
                        </select>
                        <input
                            v-if="!item.key"
                            v-model="item.href"
                            class="field !py-2"
                            placeholder="/oldal"
                            maxlength="200"
                            :aria-label="`Hivatkozás (${index + 1}. elem)`"
                        />
                        <span v-else class="w-full truncate rounded-2xl px-3 text-sm text-ink-soft">{{ item.href }}</span>
                    </div>

                    <div class="mt-2 flex items-center gap-1">
                        <button type="button" class="icon-btn" :aria-label="item.visible ? 'Elrejtés' : 'Megjelenítés'" @click="item.visible = !item.visible">
                            <Eye v-if="item.visible" :size="18" />
                            <EyeOff v-else :size="18" />
                        </button>
                        <button
                            type="button"
                            class="icon-btn"
                            :class="{ '!bg-primary !text-white': item.featured }"
                            :aria-pressed="item.featured"
                            aria-label="Kiemelés"
                            @click="setFeatured(item)"
                        >
                            <Star :size="18" />
                        </button>
                        <span class="flex-1"></span>
                        <button type="button" class="icon-btn" aria-label="Feljebb" :disabled="index === 0" @click="move(index, -1)">
                            <ArrowUp :size="18" />
                        </button>
                        <button
                            type="button"
                            class="icon-btn"
                            aria-label="Lejjebb"
                            :disabled="index === menuForm.items.length - 1"
                            @click="move(index, 1)"
                        >
                            <ArrowDown :size="18" />
                        </button>
                        <button v-if="!item.key" type="button" class="icon-btn" aria-label="Törlés" @click="removeItem(index)">
                            <Trash2 :size="18" />
                        </button>
                    </div>

                    <p v-for="field in ['label', 'icon', 'href']" :key="field" class="mt-1.5 text-sm font-semibold text-primary" v-show="err(`items.${index}.${field}`)">
                        {{ err(`items.${index}.${field}`) }}
                    </p>
                </li>
            </ul>

            <p v-if="menuForm.errors.items" class="text-sm font-semibold text-primary">{{ menuForm.errors.items }}</p>

            <button type="button" class="inline-flex items-center gap-1.5 text-sm font-extrabold text-primary" @click="addItem">
                <Plus :size="18" /> Új hivatkozás
            </button>

            <button type="submit" class="btn-primary" :disabled="menuForm.processing || !menuDirty">
                {{ menuForm.processing ? 'Mentés…' : 'Menü mentése' }}
            </button>
        </form>

        <!-- UI settings -->
        <form class="card space-y-3 p-5" @submit.prevent="saveUi">
            <h2 class="text-lg font-extrabold">Felület</h2>

            <div>
                <label for="app_name" class="mb-1.5 block text-sm font-bold">{{ def('app_name').label }}</label>
                <input id="app_name" v-model="uiForm.settings.app_name" class="field" :maxlength="def('app_name').max" required />
                <p v-if="uiForm.errors['settings.app_name']" class="mt-1.5 text-sm font-semibold text-primary">{{ uiForm.errors['settings.app_name'] }}</p>
            </div>

            <div>
                <label for="welcome_text" class="mb-1.5 block text-sm font-bold">{{ def('welcome_text').label }}</label>
                <input id="welcome_text" v-model="uiForm.settings.welcome_text" class="field" :maxlength="def('welcome_text').max" />
                <p class="mt-1 text-xs text-ink-soft">{{ def('welcome_text').hint }}</p>
                <p v-if="uiForm.errors['settings.welcome_text']" class="mt-1.5 text-sm font-semibold text-primary">{{ uiForm.errors['settings.welcome_text'] }}</p>
            </div>

            <div>
                <Toggle v-model="customColor" :label="def('primary_color').label" :hint="customColor ? undefined : def('primary_color').hint" />
                <div v-if="customColor" class="flex items-center gap-3">
                    <input
                        v-model="uiForm.settings.primary_color"
                        type="color"
                        class="h-12 w-16 cursor-pointer rounded-2xl border border-line bg-surface p-1"
                        aria-label="Főszín"
                    />
                    <code class="text-sm font-bold text-ink-soft">{{ uiForm.settings.primary_color }}</code>
                </div>
                <p v-if="uiForm.errors['settings.primary_color']" class="mt-1.5 text-sm font-semibold text-primary">{{ uiForm.errors['settings.primary_color'] }}</p>
            </div>

            <h3 class="pt-2 text-sm font-extrabold uppercase tracking-wider text-ink-soft">Kezdőlap elemei</h3>
            <div class="divide-y divide-line/60">
                <Toggle v-for="s in toggles" :key="s.key" v-model="uiForm.settings[s.key]" :label="s.label" />
            </div>

            <button type="submit" class="btn-primary" :disabled="uiForm.processing || !uiDirty">
                {{ uiForm.processing ? 'Mentés…' : 'Felület mentése' }}
            </button>
        </form>
    </div>
</template>
