<script setup lang="ts">
import { computed } from 'vue';
import { SidebarInset } from '@/components/ui/sidebar';

type Props = {
    variant?: 'header' | 'sidebar';
    class?: string;
};

const props = defineProps<Props>();
const className = computed(() => props.class);
</script>

<template>
    <SidebarInset v-if="props.variant === 'sidebar'" :class="className" class="bg-[hsl(220_14%_97%)]">
        <slot />
    </SidebarInset>
    <main
        v-else
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl bg-[hsl(220_14%_97%)]"
        :class="className"
    >
        <slot />
    </main>
</template>
