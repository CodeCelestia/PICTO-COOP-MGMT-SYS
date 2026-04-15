<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'default' | 'sidebar';
    }>(),
    {
        variant: 'default',
    },
);

const isSidebar = computed(() => props.variant === 'sidebar');

const wrapperClasses = computed(() =>
    isSidebar.value
        ? 'flex size-11 shrink-0 items-center justify-center transition-all duration-300 ease-out group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-10'
        : 'flex size-9 shrink-0 items-center justify-center transition-all duration-300 ease-out group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-9',
);

const imageClasses = computed(() =>
    isSidebar.value
        ? 'h-12 w-12 object-contain drop-shadow-[0_4px_10px_rgba(14,126,160,0.38)] transition-all duration-300 ease-out group-data-[collapsible=icon]:h-9 group-data-[collapsible=icon]:w-9'
        : 'h-10 w-10 object-contain drop-shadow-[0_3px_8px_rgba(14,126,160,0.38)] transition-all duration-300 ease-out group-data-[collapsible=icon]:h-8 group-data-[collapsible=icon]:w-8',
);

const textContainerClasses = computed(() =>
    isSidebar.value
    ? 'ml-3 grid min-w-0 max-w-[13rem] flex-1 overflow-hidden text-left leading-tight transition-[max-width,opacity,margin] duration-300 ease-out group-data-[collapsible=icon]:ml-0 group-data-[collapsible=icon]:max-w-0 group-data-[collapsible=icon]:opacity-0 group-data-[collapsible=icon]:pointer-events-none'
    : 'ml-2.5 grid min-w-0 max-w-[12rem] flex-1 overflow-hidden text-left text-sm leading-tight transition-[max-width,opacity,margin] duration-300 ease-out group-data-[collapsible=icon]:ml-0 group-data-[collapsible=icon]:max-w-0 group-data-[collapsible=icon]:opacity-0 group-data-[collapsible=icon]:pointer-events-none',
);

const titleClasses = computed(() =>
    isSidebar.value
    ? 'truncate text-lg font-bold tracking-[0.04em] text-sidebar-foreground/95'
        : 'truncate font-bold',
);
</script>

<template>
    <div :class="wrapperClasses">
        <img
            src="/SDN_Logo.png"
            alt="SDN Logo"
            :class="imageClasses"
        />
    </div>
    <div :class="textContainerClasses">
        <span :class="titleClasses">COOP SDN-MIS</span>
    </div>
</template>
