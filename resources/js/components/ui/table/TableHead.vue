<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <th
        data-slot="table-head"
        :class="cn(
            'h-10 px-3 text-left align-middle font-medium text-muted-foreground first:pl-5 last:pr-5 sm:px-4 sm:first:pl-6 sm:last:pr-6 [&:has([role=checkbox])]:pr-0 *:[[role=checkbox]]:translate-y-0.5',
            props.class
        )"
    >
        <slot />
    </th>
</template>
