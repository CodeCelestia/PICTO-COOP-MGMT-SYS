<script setup lang="ts">
import { computed } from 'vue';
import type { LucideIcon } from 'lucide-vue-next';

export type LiftedTab = {
    id: string;
    label: string;
    icon?: LucideIcon;
};

const props = defineProps<{
    tabs: LiftedTab[];
    modelValue: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const activeId = computed(() => props.modelValue || props.tabs[0]?.id);

const setActive = (id: string) => {
    if (id === activeId.value) return;
    emit('update:modelValue', id);
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-1 rounded-xl border border-border/70 bg-muted/35 p-1">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            :class="[
                'inline-flex items-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/60',
                tab.id === activeId
                    ? 'bg-primary/15 text-primary shadow-[0_10px_20px_-14px_rgba(15,23,42,0.55)] ring-1 ring-primary/40 dark:bg-background dark:text-foreground dark:shadow-[0_10px_20px_-14px_rgba(0,0,0,0.8)] dark:ring-white/10'
                    : 'text-muted-foreground hover:bg-background/75 hover:text-foreground',
            ]"
            :aria-selected="tab.id === activeId"
            role="tab"
            @click="setActive(tab.id)"
        >
            <span class="inline-flex items-center gap-1.5">
                <component :is="tab.icon" v-if="tab.icon" class="h-4 w-4" />
                <span>{{ tab.label }}</span>
            </span>
        </button>
    </div>
</template>
