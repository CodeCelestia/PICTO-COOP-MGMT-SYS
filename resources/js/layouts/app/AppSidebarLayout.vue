<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { watch, ref } from 'vue';
import { notifyError, notifySuccess } from '@/lib/alerts';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const lastFlashKey = ref('');

watch(
    () => page.props.flash as { success?: string; error?: string } | undefined,
    (flash) => {
        if (!flash) return;

        const message = flash.success || flash.error;
        if (!message) return;

        const key = `${flash.success ? 'success' : 'error'}:${message}`;
        if (key === lastFlashKey.value) return;

        lastFlashKey.value = key;

        if (flash.success) {
            notifySuccess(flash.success);
        } else if (flash.error) {
            notifyError(flash.error);
        }
    },
    { immediate: true }
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div :key="page.component">
                <slot />
            </div>
        </AppContent>
    </AppShell>
</template>
