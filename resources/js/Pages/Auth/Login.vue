<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, Lock, LogIn } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Belépés" />

        <h2 class="mb-1 text-xl font-extrabold">Üdv újra!</h2>
        <p class="mb-5 text-sm text-ink-soft">Jelentkezz be a közös kis világotokba.</p>

        <p v-if="status" class="mb-4 rounded-2xl bg-surface-2 px-4 py-3 text-sm font-semibold text-primary">
            {{ status }}
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="email" class="mb-1.5 block text-sm font-bold">E-mail cím</label>
                <div class="relative">
                    <Mail :size="18" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-ink-soft" />
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="field pl-11"
                        placeholder="pelda@email.hu"
                        autocomplete="username"
                        autocapitalize="none"
                        inputmode="email"
                        required
                        autofocus
                    />
                </div>
                <p v-if="form.errors.email" class="mt-1.5 text-sm font-semibold text-primary">
                    {{ form.errors.email }}
                </p>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-bold">Jelszó</label>
                <div class="relative">
                    <Lock :size="18" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-ink-soft" />
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="field pl-11"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    />
                </div>
                <p v-if="form.errors.password" class="mt-1.5 text-sm font-semibold text-primary">
                    {{ form.errors.password }}
                </p>
            </div>

            <label class="flex items-center gap-3 text-sm font-semibold">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="h-5 w-5 rounded-md border-line text-primary focus:ring-primary/40"
                />
                Maradjak bejelentkezve
            </label>

            <button type="submit" class="btn-primary" :disabled="form.processing">
                <LogIn :size="20" />
                {{ form.processing ? 'Belépés…' : 'Belépés' }}
            </button>
        </form>
    </GuestLayout>
</template>