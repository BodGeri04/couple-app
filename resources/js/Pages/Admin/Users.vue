<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AdminTabs from './Partials/AdminTabs.vue';
import UserEditor from './Partials/UserEditor.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({ users: Array });
</script>

<template>
    <div class="space-y-5">
        <Head title="Felhasználók" />

        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Felhasználók</h1>
            <p class="text-sm text-ink-soft">Név, e-mail cím és jelszó szerkesztése. A szerepkör itt nem módosítható.</p>
        </div>

        <AdminTabs />

        <div class="grid gap-3 md:grid-cols-2">
            <UserEditor v-for="user in users" :key="user.id" :user="user" />
        </div>
    </div>
</template>
