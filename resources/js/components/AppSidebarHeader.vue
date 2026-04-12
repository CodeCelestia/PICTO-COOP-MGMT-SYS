<script setup lang="ts">
import { Accessibility, Eye, EyeOff, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger, useSidebar } from '@/components/ui/sidebar';
import { useAppBackgroundPreference } from '@/composables/useAppBackgroundPreference';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { isLargeMode, toggleLargeMode } = useSidebar();
const { resolvedAppearance, updateAppearance } = useAppearance();
const {
    isBackgroundAutoManaged,
    isBackgroundButtonLocked,
    prefersReducedMotion,
    isAppBackgroundVisible,
    toggleAppBackground,
} = useAppBackgroundPreference();

const isDarkMode = computed(() => resolvedAppearance.value === 'dark');

function toggleAppearance() {
    updateAppearance(isDarkMode.value ? 'light' : 'dark');
}
</script>

<template>
    <header
        class="app-header-surface app-shell-divider flex h-16 shrink-0 items-center gap-2 border-b px-6 backdrop-blur-xl transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :aria-pressed="isAppBackgroundVisible"
                                :aria-label="
                                        isBackgroundAutoManaged
                                                ? 'Animated background is set to Auto mode'
                                                : isBackgroundButtonLocked
                                                    ? 'Animated background is locked off'
                                                    : isAppBackgroundVisible
                                                        ? 'Hide animated background'
                                                        : 'Show animated background'
                                "
                :title="
                    prefersReducedMotion
                        ? 'Animated background follows reduced-motion preference and is disabled'
                                                : isBackgroundAutoManaged
                                                    ? 'Animated background follows color mode automatically (On in dark mode, Off in light mode)'
                                                    : isBackgroundButtonLocked
                                                        ? 'Animated background is locked Off. Change behavior in Appearance settings.'
                                                        : isAppBackgroundVisible
                                                            ? 'Hide animated background'
                                                            : 'Show animated background'
                "
                                :disabled="prefersReducedMotion || isBackgroundButtonLocked"
                class="h-8 gap-1.5 px-2 text-xs"
                :class="isAppBackgroundVisible ? 'bg-sidebar-accent text-sidebar-accent-foreground' : ''"
                @click="toggleAppBackground"
            >
                <Eye v-if="isAppBackgroundVisible" class="size-4" />
                <EyeOff v-else class="size-4" />
                                <span class="hidden sm:inline">{{ isBackgroundAutoManaged ? 'Auto' : isAppBackgroundVisible ? 'Bg On' : 'Bg Off' }}</span>
            </Button>

            <Button
                type="button"
                variant="ghost"
                size="sm"
                :aria-pressed="isLargeMode"
                :title="isLargeMode ? 'Use default text and sidebar size' : 'Use larger text and sidebar size'"
                class="h-8 gap-1.5 px-2 text-xs"
                :class="isLargeMode ? 'bg-sidebar-accent text-sidebar-accent-foreground' : ''"
                @click="toggleLargeMode"
            >
                <Accessibility class="size-4" />
                <span class="hidden sm:inline">{{ isLargeMode ? 'Large On' : 'Large Off' }}</span>
            </Button>

            <Button
                type="button"
                variant="ghost"
                size="sm"
                :aria-pressed="isDarkMode"
                :title="isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                class="h-8 gap-1.5 px-2 text-xs"
                :class="isDarkMode ? 'bg-sidebar-accent text-sidebar-accent-foreground' : ''"
                @click="toggleAppearance"
            >
                <span class="relative size-4">
                    <Sun
                        class="absolute inset-0 transition-all duration-300 ease-out"
                        :class="isDarkMode ? 'rotate-90 scale-0 opacity-0' : 'rotate-0 scale-100 opacity-100'"
                    />
                    <Moon
                        class="absolute inset-0 transition-all duration-300 ease-out"
                        :class="isDarkMode ? 'rotate-0 scale-100 opacity-100' : '-rotate-90 scale-0 opacity-0'"
                    />
                </span>
                <span class="hidden sm:inline">{{ isDarkMode ? 'Dark Mode' : 'Light Mode' }}</span>
            </Button>
        </div>
    </header>
</template>
