<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, Plus, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useBurst } from '@/Composables/useBurst';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({ items: Array });
const { burst } = useBurst();

// Local copy so taps feel instant; the server's answer replaces it a moment later.
const clone = (list) => list.map((item) => ({ ...item }));
const local = ref(clone(props.items));
watch(
    () => props.items,
    (list) => (local.value = clone(list)),
);

const open = computed(() => local.value.filter((item) => !item.is_checked));
const done = computed(() =>
    local.value
        .filter((item) => item.is_checked)
        .sort((a, b) => (Date.parse(b.checked_at) || 0) - (Date.parse(a.checked_at) || 0)),
);

const inFlight = ref(0);

const send = (method, url) => {
    inFlight.value++;
    router.visit(url, {
        method,
        preserveScroll: true,
        preserveState: true,
        only: ['items'],
        onFinish: () => {
            inFlight.value--;
            local.value = clone(props.items);
        },
    });
};

const toggle = (item, event) => {
    item.is_checked = !item.is_checked;
    item.checked_at = item.is_checked ? new Date().toISOString() : null;
    if (item.is_checked) burst(event, 'star', 6);
    send('patch', route('shopping.toggle', item.id));
};

const remove = (item) => {
    local.value = local.value.filter((i) => i.id !== item.id);
    send('delete', route('shopping.destroy', item.id));
};

const clearDone = () => {
    local.value = local.value.filter((item) => !item.is_checked);
    send('delete', route('shopping.clear'));
};

// Adding
const form = useForm({ name: '', quantity: '' });
const nameInput = ref(null);

const add = () => {
    if (!form.name.trim()) return;
    form.post(route('shopping.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            nextTick(() => nameInput.value?.focus()); // keep the keyboard open for the next item
        },
    });
};

// Near real-time: refresh the list every few seconds while this screen is visible.
let timer;
const poll = () => {
    if (document.hidden || inFlight.value > 0 || form.processing) return;
    router.reload({ only: ['items'], preserveScroll: true, preserveState: true, showProgress: false });
};
const onVisible = () => {
    if (!document.hidden) poll();
};

onMounted(() => {
    timer = setInterval(poll, 4000);
    document.addEventListener('visibilitychange', onVisible);
});
onBeforeUnmount(() => {
    clearInterval(timer);
    document.removeEventListener('visibilitychange', onVisible);
});
</script>

<template>
    <div class="space-y-5">
        <Head title="Bevásárlás" />

        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Bevásárlás</h1>
            <p class="text-sm text-ink-soft">
                {{ open.length ? `${open.length} tétel vár` : local.length ? 'Minden kipipálva 🎉' : 'Közös lista kettőtöknek' }}
            </p>
        </div>

        <form class="flex items-center gap-2" @submit.prevent="add">
            <input
                ref="nameInput"
                v-model="form.name"
                class="field min-w-0 flex-1"
                placeholder="Mit vegyünk?"
                maxlength="80"
                autocomplete="off"
                enterkeyhint="done"
            />
            <input
                v-model="form.quantity"
                class="field !w-24 shrink-0"
                placeholder="Menny."
                maxlength="30"
                autocomplete="off"
                enterkeyhint="done"
            />
            <button
                type="submit"
                class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-primary to-peach text-white shadow-pop transition active:scale-90 disabled:opacity-50"
                :disabled="form.processing || !form.name.trim()"
                aria-label="Hozzáadás"
            >
                <Plus :size="24" />
            </button>
        </form>
        <p v-if="form.errors.name" class="-mt-3 text-sm font-semibold text-primary">{{ form.errors.name }}</p>

        <div v-if="!local.length" class="card p-8 text-center">
            <div class="animate-float text-5xl">🛒</div>
            <h2 class="mt-3 text-lg font-extrabold">A lista üres</h2>
            <p class="mt-1 text-sm text-ink-soft">Írd be fent, mit kell venni.</p>
        </div>

        <TransitionGroup v-if="open.length" tag="ul" name="list" class="relative flex flex-col gap-2">
            <li v-for="item in open" :key="item.id" class="card flex items-center gap-3 p-3">
                <button
                    type="button"
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-full border-2 border-primary/50 text-transparent transition active:scale-90"
                    :aria-label="`Kipipálom: ${item.name}`"
                    @click="toggle(item, $event)"
                >
                    <Check :size="20" />
                </button>
                <div class="min-w-0 flex-1">
                    <div class="truncate font-extrabold">{{ item.name }}</div>
                    <div v-if="item.added_by" class="text-xs text-ink-soft">{{ item.added_by }}</div>
                </div>
                <span v-if="item.quantity" class="shrink-0 rounded-full bg-surface-2 px-3 py-1 text-sm font-bold text-primary">
                    {{ item.quantity }}
                </span>
                <button type="button" class="icon-btn shrink-0" :aria-label="`Törlés: ${item.name}`" @click="remove(item)">
                    <Trash2 :size="18" />
                </button>
            </li>
        </TransitionGroup>

        <section v-if="done.length">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-ink-soft">Kipipálva ({{ done.length }})</h2>
                <button type="button" class="text-sm font-extrabold text-primary" @click="clearDone">Kipipáltak törlése</button>
            </div>

            <TransitionGroup tag="ul" name="list" class="relative flex flex-col gap-2">
                <li v-for="item in done" :key="item.id" class="card flex items-center gap-3 p-3 opacity-70">
                    <button
                        type="button"
                        class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-primary text-white transition active:scale-90"
                        :aria-label="`Visszateszem: ${item.name}`"
                        @click="toggle(item, $event)"
                    >
                        <Check :size="20" />
                    </button>
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-bold text-ink-soft line-through">{{ item.name }}</div>
                        <div v-if="item.checked_by" class="text-xs text-ink-soft">Kipipálta: {{ item.checked_by }}</div>
                    </div>
                    <span v-if="item.quantity" class="shrink-0 text-sm font-bold text-ink-soft">{{ item.quantity }}</span>
                    <button type="button" class="icon-btn shrink-0" :aria-label="`Törlés: ${item.name}`" @click="remove(item)">
                        <Trash2 :size="18" />
                    </button>
                </li>
            </TransitionGroup>
        </section>
    </div>
</template>