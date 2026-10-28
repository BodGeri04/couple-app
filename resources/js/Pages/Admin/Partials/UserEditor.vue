<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ChevronDown, LogOut, Pencil, ShieldCheck } from 'lucide-vue-next';
import BottomSheet from '@/Components/BottomSheet.vue';

const props = defineProps({ user: Object });

const page = usePage();
const isSelf = computed(() => page.props.auth.user?.id === props.user.id);

const open = ref(false);
const confirmOpen = ref(false);

const logoutForm = useForm({});

const forceLogout = () =>
    logoutForm.post(route('admin.users.force-logout', props.user.id), {
        preserveScroll: true,
        onSuccess: () => (confirmOpen.value = false),
    });

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
});

// The server validates into a per-user error bag so the two forms never show each other's errors.
const errorBag = computed(() => `user-${props.user.id}`);

const submit = () =>
    form.patch(route('admin.users.update', props.user.id), {
        errorBag: errorBag.value,
        preserveScroll: true,
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });

const fmt = (iso) =>
    iso ? new Date(iso).toLocaleString('hu-HU', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '–';
</script>

<template>
    <article class="card p-4">
        <button type="button" class="flex w-full items-center gap-3 text-left" :aria-expanded="open" @click="open = !open">
            <span class="avatar !h-12 !w-12 shrink-0">{{ user.name.charAt(0).toUpperCase() }}</span>
            <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2">
                    <span class="truncate font-extrabold">{{ user.name }}</span>
                    <span
                        class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-extrabold"
                        :class="user.role === 'admin' ? 'bg-primary/15 text-primary' : 'bg-surface-2 text-ink-soft'"
                    >
                        <ShieldCheck v-if="user.role === 'admin'" :size="12" />
                        {{ user.role === 'admin' ? 'Admin' : 'Partner' }}
                    </span>
                </span>
                <span class="block truncate text-sm text-ink-soft">{{ user.email }}</span>
            </span>
            <ChevronDown :size="20" class="shrink-0 text-ink-soft transition" :class="{ 'rotate-180': open }" />
        </button>

        <dl class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
            <div class="rounded-2xl bg-surface-2 px-2 py-2">
                <dt class="text-ink-soft">Képek</dt>
                <dd class="text-base font-extrabold">{{ user.snaps_sent }}</dd>
            </div>
            <div class="rounded-2xl bg-surface-2 px-2 py-2">
                <dt class="text-ink-soft">Eszköz</dt>
                <dd class="text-base font-extrabold">{{ user.devices }}</dd>
            </div>
            <div class="rounded-2xl bg-surface-2 px-2 py-2">
                <dt class="text-ink-soft">Utoljára</dt>
                <dd class="text-[11px] font-extrabold leading-tight">{{ fmt(user.last_seen) }}</dd>
            </div>
        </dl>

        <div v-if="!isSelf" class="mt-3 flex items-center justify-between gap-3">
            <p class="text-xs text-ink-soft">Minden eszközről kijelentkezteti a felhasználót.</p>
            <button
                type="button"
                class="inline-flex shrink-0 items-center gap-2 rounded-2xl bg-primary/10 px-4 py-2.5 text-sm font-extrabold text-primary transition active:scale-[.97]"
                @click="confirmOpen = true"
            >
                <LogOut :size="16" />
                Kijelentkeztetés
            </button>
        </div>

        <form v-if="open" class="mt-4 space-y-3 border-t border-line/60 pt-4" @submit.prevent="submit">
            <div>
                <label :for="`name-${user.id}`" class="mb-1.5 block text-sm font-bold">Név</label>
                <input :id="`name-${user.id}`" v-model="form.name" class="field" maxlength="255" required autocomplete="off" />
                <p v-if="form.errors.name" class="mt-1.5 text-sm font-semibold text-primary">{{ form.errors.name }}</p>
            </div>

            <div>
                <label :for="`email-${user.id}`" class="mb-1.5 block text-sm font-bold">E-mail cím</label>
                <input
                    :id="`email-${user.id}`"
                    v-model="form.email"
                    type="email"
                    class="field"
                    maxlength="255"
                    required
                    autocomplete="off"
                    autocapitalize="none"
                />
                <p v-if="form.errors.email" class="mt-1.5 text-sm font-semibold text-primary">{{ form.errors.email }}</p>
            </div>

            <div>
                <label :for="`pw-${user.id}`" class="mb-1.5 block text-sm font-bold">Új jelszó</label>
                <input
                    :id="`pw-${user.id}`"
                    v-model="form.password"
                    type="password"
                    class="field"
                    placeholder="Hagyd üresen, ha nem változik"
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password" class="mt-1.5 text-sm font-semibold text-primary">{{ form.errors.password }}</p>
            </div>

            <div v-if="form.password">
                <label :for="`pw2-${user.id}`" class="mb-1.5 block text-sm font-bold">Új jelszó megerősítése</label>
                <input
                    :id="`pw2-${user.id}`"
                    v-model="form.password_confirmation"
                    type="password"
                    class="field"
                    autocomplete="new-password"
                />
                <p class="mt-1.5 text-xs text-ink-soft">Jelszóváltás után a felhasználó minden más eszközön kijelentkezik.</p>
            </div>

            <button type="submit" class="btn-primary" :disabled="form.processing">
                <Pencil :size="18" />
                {{ form.processing ? 'Mentés…' : 'Mentés' }}
            </button>
        </form>

        <BottomSheet v-model="confirmOpen" title="Kijelentkeztetés">
            <p class="text-sm text-ink-soft">
                <span class="font-extrabold text-ink">{{ user.name }}</span> azonnal kijelentkezik minden eszközről, az „Emlékezz rám”
                bejelentkezései pedig érvénytelenné válnak. Biztosan folytatod?
            </p>

            <div class="mt-6 grid grid-cols-2 gap-3">
                <button
                    type="button"
                    class="rounded-2xl bg-surface-2 px-5 py-3.5 font-extrabold text-ink transition active:scale-[.97] disabled:opacity-60"
                    :disabled="logoutForm.processing"
                    @click="confirmOpen = false"
                >
                    Mégse
                </button>
                <button type="button" class="btn-primary" :disabled="logoutForm.processing" @click="forceLogout">
                    <LogOut :size="18" />
                    {{ logoutForm.processing ? 'Folyamatban…' : 'Kijelentkeztetés' }}
                </button>
            </div>
        </BottomSheet>
    </article>
</template>
