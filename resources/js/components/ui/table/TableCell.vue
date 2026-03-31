<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <td
        data-slot="table-cell"
        :class="cn('py-2 px-3 align-middle first:pl-5 last:pr-5 sm:px-4 sm:first:pl-6 sm:last:pr-6 [&:has([role=checkbox])]:pr-0 *:[[role=checkbox]]:translate-y-0.5', props.class)"
    >
        <slot />
    </td>
</template>
