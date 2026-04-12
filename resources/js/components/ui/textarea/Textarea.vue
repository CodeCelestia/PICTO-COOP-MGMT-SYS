<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { useVModel } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = defineProps<{
    defaultValue?: string;
    modelValue?: string;
    class?: HTMLAttributes['class'];
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});
</script>

<template>
    <textarea
        v-model="modelValue"
        data-slot="textarea"
        :class="cn(
            'flex min-h-16 w-full rounded-md border border-gray-400 bg-white px-3 py-2 text-base text-gray-800 shadow-sm transition-all duration-150 ease-in-out outline-none placeholder:text-gray-400 focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:border-zinc-500 dark:bg-zinc-800 dark:text-gray-100 dark:placeholder:text-zinc-500 dark:focus-visible:ring-primary/30 dark:aria-invalid:ring-destructive/40',
            props.class
        )"
    />
</template>
