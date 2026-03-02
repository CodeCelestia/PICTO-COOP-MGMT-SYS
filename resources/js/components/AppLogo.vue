<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-indigo-600 shadow-lg shadow-indigo-700/40"
    >
        <AppLogoIcon class="size-5 fill-current text-white" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-bold text-white tracking-tight">PICTO COOP</span>
        <span class="truncate text-[10px] text-slate-400 font-medium">Management System</span>
    </div>
</template>
