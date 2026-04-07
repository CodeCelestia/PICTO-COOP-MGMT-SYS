<script setup lang="ts">
import { computed } from 'vue';

export type LiftedTab = {
    id: string;
    label: string;
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
            {{ tab.label }}
        </button>
    </div>
</template>
