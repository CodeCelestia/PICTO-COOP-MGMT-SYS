<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useSidebar } from '@/components/ui/sidebar/utils';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
const { isMobile, setOpenMobile } = useSidebar();

const handleNavigate = () => {
    if (isMobile.value) {
        setOpenMobile(false);
    }
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="`${item.title}:${item.href}`">
                <Link
                    :href="item.href"
                    @click="handleNavigate"
                    class="flex w-full items-center gap-2 rounded-md p-2 text-left text-sm transition hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                    :class="{
                        'bg-sidebar-accent text-sidebar-accent-foreground font-medium': isCurrentUrl(item.href),
                    }"
                >
                    <component :is="item.icon" />
                    <span>{{ item.title }}</span>
                </Link>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
