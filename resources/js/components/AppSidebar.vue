<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    House,
    LayoutGrid,
    Users,
    FileText,
    DollarSign,
    UserCog,
    History,
    Building2,
    GraduationCap,
    FileSpreadsheet,
    Shield,
    Monitor,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { useCoopLabel } from '@/composables/useCoopLabel';
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
    roles?: string[];
    permissions?: string[];
    isCoopAdmin?: boolean;
} | undefined);
const authUser = computed(() => auth.value?.user);
const roles = computed<string[]>(() => auth.value?.roles || []);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const isMember = computed(() => Boolean(authUser.value?.member_id));
const isSuperAdmin = computed(() => roles.value.some((role) => role.toLowerCase() === 'super admin'));

const can = (permission: string) => permissions.value.includes(permission);

const canViewAllCoops = computed(() => can('view-all-cooperatives'));
const canViewCoops = computed(() => can('read coop-master-profile') || can('view-all-cooperatives'));
const canViewMembers = computed(() => can('read members-profile'));
const canViewMembersManagement = computed(() => can('read members-management'));
const canViewActivitiesProjects = computed(() => Boolean(auth.value?.permissions?.includes('read activities-&-projects')));
const canViewTrainings = computed(() => Boolean(auth.value?.permissions?.includes('read training-&-capacity')));
const canViewLoans = computed(() => can('read finance-member-loans') || can('apply-own finance-member-loans'));
const canViewFinance = computed(() =>
    can('read financial-&-support')
    || can('read finance-funding-sources')
    || can('read finance-ledger-entries')
    || can('read finance-member-loans')
    || can('read finance-savings-accounts')
    || can('read finance-reports')
);
const canManageUsers = computed(() => can('read user-accounts'));
const canManagePermissions = computed(() => can('manage-permissions'));
const canViewActivityLogs = computed(() => can('read audit-logs'));
const isMemberOnly = computed(() => isMember.value && !canViewMembersManagement.value && !canManageUsers.value && !canManagePermissions.value && !canViewCoops.value);
const { cooperativeLabel } = useCoopLabel();

const mainNavItems = computed<NavItem[]>(() => {
    const homepageItem: NavItem = {
        title: 'Homepage',
        href: '/homepage',
        icon: House,
    };

    const dashboardItem: NavItem = {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    };

    const myProfileItem: NavItem = {
        title: 'My Profile',
        href: '/member-portal',
        icon: Users,
    };

    const cooperativesItem: NavItem = {
        title: cooperativeLabel.value,
        href: isCoopAdmin.value ? '/cooperatives/my' : '/cooperatives',
        icon: Building2,
    };

    const pdsItem: NavItem = {
        title: isMember.value ? 'My PDS' : 'Personal Data Sheet',
        href: isMember.value ? '/pds/my' : '/pds',
        icon: FileSpreadsheet,
    };

    const activitiesItem: NavItem = {
        title: 'Activities & Projects',
        href: '/activities',
        icon: FileText,
    };

    const financeItem: NavItem = {
        title: 'Finance',
        href: '/finance',
        icon: DollarSign,
    };

    const trainingsItem: NavItem = {
        title: 'Trainings',
        href: '/trainings',
        icon: GraduationCap,
    };

    if (isMemberOnly.value) {
        const memberItems: NavItem[] = [
            homepageItem,
            dashboardItem,
            myProfileItem,
            pdsItem,
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

        if (canViewLoans.value) {
            memberItems.push({
                title: 'My Loans',
                href: '/member-portal/loans',
                icon: DollarSign,
            });
        }

        return memberItems;
    }

    const items: NavItem[] = [homepageItem, dashboardItem];

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

    if (isSuperAdmin.value) {
        items.push({
            title: 'Display',
            href: '/display',
            icon: Monitor,
        });
    }

    if (canViewCoops.value) {
        items.push(cooperativesItem);
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
        items.push(pdsItem);
    }

    if (canViewActivitiesProjects.value) {
        items.push(activitiesItem);
    }

    if (canViewFinance.value) {
        items.push(financeItem);
    }

    if (canViewTrainings.value) {
        items.push(trainingsItem);
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
        <SidebarHeader class="px-2 pt-3 pb-5">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/homepage">
                            <AppLogo variant="sidebar" />
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
