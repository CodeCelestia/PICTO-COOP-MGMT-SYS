<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import {
    Building2,
    Calendar,
    FileText,
    LayoutGrid,
    ScrollText,
    Shield,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const page = usePage();

const isSuperAdmin = computed(() => {
    const auth = page.props.auth as Auth;

    return auth.roles.includes('super_admin');
});

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (isSuperAdmin.value) {
        items.push(
            {
                title: 'System Overview',
                href: '/super-admin/dashboard',
                icon: Shield,
            },
            {
                title: 'PDS Management',
                href: '/super-admin/pds',
                icon: FileText,
            },
            {
                title: 'Offices',
                href: '/super-admin/offices',
                icon: Building2,
            },
            {
                title: 'Members',
                href: '/super-admin/members',
                icon: UserCheck,
            },
            {
                title: 'Committees',
                href: '/super-admin/committees',
                icon: Shield,
            },
            {
                title: 'Activities',
                href: '/super-admin/activities',
                icon: Calendar,
            },
            {
                title: 'User Management',
                href: '/super-admin/users',
                icon: Users,
            },
            {
                title: 'System Logs',
                href: '/super-admin/logs',
                icon: ScrollText,
            },
        );
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="bg-slate-950 border-r border-white/5">
        <SidebarHeader class="border-b border-white/8 bg-slate-950 pb-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="hover:bg-white/5 rounded-xl px-3">
                        <Link :href="dashboard()" class="group">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="py-3">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter class="border-t border-white/8 bg-slate-950 pt-3">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
