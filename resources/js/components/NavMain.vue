<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-0 py-2">
        <SidebarGroupLabel class="px-4 mb-1 text-xs font-semibold uppercase tracking-widest text-slate-500">
            Navigation
        </SidebarGroupLabel>
        <SidebarMenu class="gap-0.5">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <Link
                    :href="item.href"
                    :data-active="isCurrentUrl(item.href)"
                    class="flex items-center gap-3 mx-2 px-3 h-9 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 data-[active=true]:bg-indigo-600 data-[active=true]:text-white data-[active=true]:shadow-lg transition-all"
                >
                    <component :is="item.icon" class="w-4 h-4 shrink-0" />
                    <span class="text-sm font-medium">{{ item.title }}</span>
                </Link>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
