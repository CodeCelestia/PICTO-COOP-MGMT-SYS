<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="flex h-14 shrink-0 items-center justify-between px-4 bg-white border-b border-slate-200 shadow-sm transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-5"
    >
        <div class="flex items-center gap-3">
            <SidebarTrigger class="-ml-1 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-md transition-colors" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <div class="h-4 w-px bg-slate-200"></div>
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-400 font-medium select-none">
            PICTO Coop Management System
        </div>
    </header>
</template>
