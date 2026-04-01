<script setup lang="ts">
import { useStorage } from '@vueuse/core';
import { Head } from '@inertiajs/vue3';
import { Accessibility, Eye, EyeOff, Sparkles } from 'lucide-vue-next';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { SIDEBAR_LARGE_MODE_STORAGE_KEY } from '@/components/ui/sidebar/utils';
import { useAppBackgroundPreference } from '@/composables/useAppBackgroundPreference';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';
import type { BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: edit(),
    },
];

const isLargeMode = useStorage(SIDEBAR_LARGE_MODE_STORAGE_KEY, false);
const {
    prefersReducedMotion,
    isAppBackgroundVisible,
    toggleAppBackground,
} = useAppBackgroundPreference();

if (typeof document !== 'undefined') {
    document.documentElement.dataset.a11ySize = isLargeMode.value ? 'large' : 'default';
}

function toggleLargeMode() {
    isLargeMode.value = !isLargeMode.value;

    if (typeof document !== 'undefined') {
        document.documentElement.dataset.a11ySize = isLargeMode.value ? 'large' : 'default';
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

        <h1 class="sr-only">Appearance settings</h1>

        <SettingsLayout>
            <div class="space-y-8">
                <div class="rounded-2xl border border-border/70 bg-card/75 p-5 shadow-[0_14px_34px_-26px_rgba(10,30,45,0.45)] md:p-6">
                    <Heading
                        variant="small"
                        title="Appearance settings"
                        description="Customize visual theme and accessibility options"
                    />
                </div>

                <section class="space-y-4 rounded-2xl border border-border/70 bg-card/75 p-5 shadow-[0_14px_34px_-26px_rgba(10,30,45,0.45)] md:p-6">
                    <div class="flex items-center gap-2">
                        <Sparkles class="h-4 w-4 text-primary" />
                        <h3 class="text-sm font-semibold tracking-wide uppercase text-muted-foreground">Color theme</h3>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Pick how the interface appears across the application.
                    </p>
                    <AppearanceTabs />
                </section>

                <section class="space-y-4 rounded-2xl border border-border/70 bg-card/75 p-5 shadow-[0_14px_34px_-26px_rgba(10,30,45,0.45)] md:p-6">
                    <div class="flex items-center gap-2">
                        <Accessibility class="h-4 w-4 text-primary" />
                        <h3 class="text-sm font-semibold tracking-wide uppercase text-muted-foreground">Accessibility</h3>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Adjust readability and visual comfort using existing quick settings.
                    </p>

                    <div class="grid grid-cols-1 gap-2 rounded-xl bg-muted/55 p-2 sm:grid-cols-2">
                        <button
                            type="button"
                            class="flex min-h-16 w-full items-start gap-3 rounded-lg border px-4 py-3 text-left transition-all duration-200"
                            :class="
                                isLargeMode
                                    ? 'border-primary/30 bg-primary text-primary-foreground shadow-[0_10px_20px_-16px_hsl(var(--primary))] hover:bg-primary/90 hover:text-primary-foreground'
                                    : 'border-border/70 bg-background/80 text-muted-foreground hover:border-border hover:bg-background hover:text-foreground'
                            "
                            :aria-pressed="isLargeMode"
                            :title="isLargeMode ? 'Use default text and sidebar size' : 'Use larger text and sidebar size'"
                            @click="toggleLargeMode"
                        >
                            <Accessibility class="h-4 w-4" />
                            <span class="flex flex-col items-start leading-tight">
                                <span class="text-sm font-semibold">Large text and sidebar</span>
                                <span class="text-xs opacity-80">{{ isLargeMode ? 'Enabled' : 'Disabled' }}</span>
                            </span>
                        </button>

                        <button
                            type="button"
                            class="flex min-h-16 w-full items-start gap-3 rounded-lg border px-4 py-3 text-left transition-all duration-200 disabled:pointer-events-none disabled:opacity-50"
                            :class="
                                isAppBackgroundVisible
                                    ? 'border-primary/30 bg-primary text-primary-foreground shadow-[0_10px_20px_-16px_hsl(var(--primary))] hover:bg-primary/90 hover:text-primary-foreground'
                                    : 'border-border/70 bg-background/80 text-muted-foreground hover:border-border hover:bg-background hover:text-foreground'
                            "
                            :aria-pressed="isAppBackgroundVisible"
                            :disabled="prefersReducedMotion"
                            :title="
                                prefersReducedMotion
                                    ? 'Animated background follows reduced-motion preference and is disabled'
                                    : isAppBackgroundVisible
                                      ? 'Hide animated background'
                                      : 'Show animated background'
                            "
                            @click="toggleAppBackground"
                        >
                            <Eye v-if="isAppBackgroundVisible" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                            <span class="flex flex-col items-start leading-tight">
                                <span class="text-sm font-semibold">Animated background</span>
                                <span class="text-xs opacity-80">
                                    {{ prefersReducedMotion ? 'Disabled by reduced motion preference' : isAppBackgroundVisible ? 'Enabled' : 'Disabled' }}
                                </span>
                            </span>
                        </button>
                    </div>
                </section>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
