<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CooperativeForm from '@/components/Cooperatives/CooperativeForm.vue';
import { router } from '@inertiajs/vue3';

const cancel = () => {
    router.get('/cooperatives');
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Register New Cooperative</h1>
                <p class="mt-1 text-sm text-gray-500">Create a new cooperative master profile</p>
            </div>

            <CooperativeForm
                action="/cooperatives"
                method="post"
                :onCancel="cancel"
            />
        </div>
    </AppLayout>
</template>
