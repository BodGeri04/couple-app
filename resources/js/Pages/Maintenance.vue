<script setup>
import { Head, router } from '@inertiajs/vue3';
import { Wrench } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({ message: String });

const logout = () => router.post(route('logout'));
</script>

<template>
    <GuestLayout>
        <Head title="Karbantartás" />

        <div class="py-4 text-center">
            <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-surface-2 text-primary">
                <Wrench :size="30" />
            </div>
            <h2 class="mt-4 text-xl font-extrabold">Karbantartás alatt</h2>
            <p class="mt-2 text-ink-soft">{{ message }}</p>

            <div class="mt-6 flex flex-col gap-2">
                <button type="button" class="btn-primary" @click="router.visit(route('maintenance'), { replace: true })">Újrapróbálom</button>
                <button type="button" class="text-sm font-extrabold text-ink-soft" @click="logout">Kijelentkezés</button>
            </div>
        </div>
    </GuestLayout>
</template>
