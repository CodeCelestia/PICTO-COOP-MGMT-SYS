<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <div class="grid grid-cols-1 gap-2 rounded-xl bg-muted/55 p-2 sm:grid-cols-3">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            :class="[
                'flex items-center justify-center gap-2 rounded-lg border px-3.5 py-2.5 text-sm font-medium transition-all duration-200',
                appearance === value
                    ? 'border-primary/30 bg-primary text-primary-foreground shadow-[0_10px_20px_-16px_hsl(var(--primary))]'
                    : 'border-border/70 bg-background/80 text-muted-foreground hover:border-border hover:text-foreground',
            ]"
            :aria-pressed="appearance === value"
        >
            <component :is="Icon" class="h-4 w-4" />
            <span>{{ label }}</span>
        </button>
    </div>
</template>
