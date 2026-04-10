<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch, ref } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import AppThreeBackground from '@/components/background/AppThreeBackground.vue';
import { useAppBackgroundPreference } from '@/composables/useAppBackgroundPreference';
import { notifyError, notifySuccess } from '@/lib/alerts';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const lastFlashKey = ref('');
const { isAppBackgroundVisible } = useAppBackgroundPreference();

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
        <AppContent
            variant="sidebar"
            class="app-glass-theme relative"
            :class="isAppBackgroundVisible ? 'bg-transparent' : 'bg-background/95 dark:bg-background/90'"
        >
            <div
                v-if="isAppBackgroundVisible"
                aria-hidden="true"
                class="pointer-events-none fixed inset-0 z-0 overflow-hidden"
            >
                <AppThreeBackground />
                <div class="app-bg-content-overlay absolute inset-0" />
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_18%,rgba(148,199,223,0.16),transparent_52%)] dark:bg-[radial-gradient(circle_at_30%_18%,rgba(48,96,120,0.24),transparent_52%)]" />
            </div>

            <div class="relative z-10">
                <AppSidebarHeader :breadcrumbs="breadcrumbs" />
                <div :key="page.component">
                    <slot />
                </div>
            </div>
        </AppContent>
    </AppShell>
</template>
