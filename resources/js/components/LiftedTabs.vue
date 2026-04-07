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
    <div class="flex border-b border-border">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            :class="[
                'px-6 py-2.5 text-sm transition-colors',
                tab.id === activeId
                    ? 'bg-background border border-border border-b-background rounded-t-md font-medium text-foreground -mb-px'
                    : 'bg-transparent text-muted-foreground hover:text-foreground font-normal',
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
