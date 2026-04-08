<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Users,
    FileText,
    DollarSign,
    BarChart3,
    UserCog,
    History,
    Building2,
    GraduationCap,
    Sparkles,
    FileSpreadsheet,
    Shield,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import type { NavItem } from '@/types';

const page = usePage();
const auth = computed(() => page.props.auth as {
    user?: { member_id?: number | null };
    permissions?: string[];
    isCoopAdmin?: boolean;
} | undefined);
const authUser = computed(() => auth.value?.user);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const isMember = computed(() => Boolean(authUser.value?.member_id));

const can = (permission: string) => permissions.value.includes(permission);

const canViewAllCoops = computed(() => can('view-all-cooperatives'));
const canViewCoops = computed(() => can('read coop-master-profile') || can('view-all-cooperatives'));
const canViewMembers = computed(() => can('read members-profile'));
const canViewMembersManagement = computed(() => can('read members-management'));
const canViewFinance = computed(() => can('read financial-&-support'));
const canViewSkillInventories = computed(() => can('read training-&-capacity'));
const canManageUsers = computed(() => can('read user-accounts'));
const canManagePermissions = computed(() => can('manage-permissions'));
const canViewActivityLogs = computed(() => can('read audit-logs'));
const isMemberOnly = computed(() => isMember.value && !canViewMembersManagement.value && !canManageUsers.value && !canManagePermissions.value && !canViewCoops.value);

const mainNavItems = computed<NavItem[]>(() => {
    const baseItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'My Profile',
            href: '/member-portal',
            icon: Users,
        },
        {
            title: 'Cooperatives',
            href: isCoopAdmin.value ? '/cooperatives/my' : '/cooperatives',
            icon: Building2,
        },
        {
            title: 'Members',
            href: '/members',
            icon: Users,
        },
        {
            title: isMember.value ? 'My PDS' : 'Personal Data Sheet',
            href: isMember.value ? '/pds/my' : '/pds',
            icon: FileSpreadsheet,
        },
        {
            title: 'Officers & Committees',
            href: '/officers',
            icon: Users,
        },
        {
            title: 'Activities & Projects',
            href: '/activities',
            icon: FileText,
        },
        {
            title: 'Finance',
            href: '/finance',
            icon: DollarSign,
        },
        {
            title: 'Trainings',
            href: '/trainings',
            icon: GraduationCap,
        },
        {
            title: 'Skills Inventory',
            href: '/skill-inventories',
            icon: Sparkles,
        },
        {
            title: 'Loans',
            href: '#',
            icon: FileText,
        },
        {
            title: 'Savings',
            href: '#',
            icon: DollarSign,
        },
        {
            title: 'Reports',
            href: '#',
            icon: BarChart3,
        },
    ];

    if (isMemberOnly.value) {
        return [
            baseItems[0],
            baseItems[1],
            baseItems[4],
            {
                title: 'My Services',
                href: '/member-portal/services',
                icon: FileText,
            },
            {
                title: 'My Activities',
                href: '/member-portal/activities',
                icon: FileText,
            },
        ];
    }

    const items: NavItem[] = [baseItems[0]];

    if (canManageUsers.value) {
        items.push({
            title: 'User Management',
            href: '/users',
            icon: UserCog,
        });
    }

    if (canManagePermissions.value) {
        items.push({
            title: 'Roles & Permissions',
            href: '/roles-permissions',
            icon: Shield,
        });
    }

    if (canViewCoops.value) {
        items.push(baseItems[2]);
    }

    if (canViewMembersManagement.value && isCoopAdmin.value) {
        items.push({
            title: 'Members Management',
            href: '/members/management',
            icon: Users,
        });
    }

    if (canViewMembersManagement.value && !isCoopAdmin.value && canViewCoops.value) {
        items.push({
            title: 'Members Management',
            href: '/members/management',
            icon: Users,
        });
    }

    if (canViewMembers.value || isMember.value) {
        items.push(baseItems[4]);
    }

    if (canViewFinance.value) {
        items.push(baseItems[7]);
    }


    if (canViewSkillInventories.value) {
        items.push({
            title: 'Skills Inventory',
            href: canViewAllCoops.value ? '/skill-inventories/select' : '/skill-inventories',
            icon: Sparkles,
        });
    }

    if (can('read reports-&-dashboard')) {
        items.push(baseItems[10], baseItems[11], baseItems[12]);
    }

    if (canViewActivityLogs.value) {
        items.push({
            title: 'Activity Logs',
            href: '/activity-logs',
            icon: History,
        });
    }

    return items;
});

const footerNavItems = computed<NavItem[]>(() => {
    return [];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
