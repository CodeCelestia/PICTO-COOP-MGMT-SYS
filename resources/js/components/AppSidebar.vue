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
    roles?: string[];
    permissions?: string[];
    isCoopAdmin?: boolean;
} | undefined);
const roles = computed<string[]>(() => auth.value?.roles || []);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin) || roles.value.includes('Coop Admin'));
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const isSuperAdmin = computed(() => roles.value.includes('Super Admin'));
const isProvincialAdmin = computed(() => roles.value.includes('Provincial Admin'));
const isSuperOrProv = computed(() => isSuperAdmin.value || isProvincialAdmin.value);
const isMember = computed(() => roles.value.includes('Member'));
const isMemberOnly = computed(() => isMember.value && roles.value.length === 1);

const can = (permission: string) => permissions.value.includes(permission);

const canViewCoops = computed(() => can('read coop-master-profile') || can('view-all-cooperatives'));
const canViewMembers = computed(() => can('read members-profile'));
const canViewMembersManagement = computed(() => can('read members-management'));
const canViewOfficers = computed(() => can('read officers-&-committees'));
const canViewActivities = computed(() => can('read activities-&-projects'));
const canViewFinance = computed(() => can('read financial-&-support'));
const canViewTrainings = computed(() => can('read training-&-capacity'));
const canViewSkillInventories = computed(() => can('read training-&-capacity'));
const canManageUsers = computed(() => can('read user-accounts'));
const canManagePermissions = computed(() => can('manage-permissions'));
const canViewActivityLogs = computed(() => can('read audit-logs'));

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

    if (canViewMembers.value && !isCoopAdmin.value) {
        items.push(baseItems[3]);
    }

    if (canViewMembers.value || isMember.value) {
        items.push(baseItems[4]);
    }

    if (canViewOfficers.value && !isCoopAdmin.value) {
        items.push({
            title: 'Officers & Committees',
            href: isSuperOrProv.value ? '/officers/select' : '/officers',
            icon: Users,
        });
    }

    if (canViewActivities.value && !isCoopAdmin.value) {
        items.push({
            title: 'Activities & Projects',
            href: isSuperOrProv.value ? '/activities/select' : '/activities',
            icon: FileText,
        });
    }

    if (canViewFinance.value) {
        items.push(baseItems[7]);
    }

    if (canViewTrainings.value && !isCoopAdmin.value) {
        items.push({
            title: 'Trainings',
            href: isSuperOrProv.value ? '/trainings/select' : '/trainings',
            icon: GraduationCap,
        });
    }

    if (canViewSkillInventories.value) {
        items.push({
            title: 'Skills Inventory',
            href: isSuperOrProv.value ? '/skill-inventories/select' : '/skill-inventories',
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
