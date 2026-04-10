<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <tr
        data-slot="table-row"
        :class="cn(
            'app-table-row border-b transition-colors duration-150 hover:bg-muted/80 dark:hover:bg-muted/45 data-[state=selected]:bg-muted',
            props.class
        )"
    >
        <slot />
    </tr>
</template>
