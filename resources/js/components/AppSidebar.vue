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
} from 'lucide-vue-next';
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
import { computed } from 'vue';

const page = usePage();
const isCoopAdmin = computed(() => Boolean(page.props.auth?.isCoopAdmin));
const roles = computed<string[]>(() => (page.props.auth?.roles as string[]) || []);
const accountType = computed(() => page.props.auth?.user?.account_type as string | undefined);
const isProvincialAdmin = computed(() => roles.value.includes('Provincial Admin') || accountType.value === 'Provincial Admin');
const isOfficer = computed(() => roles.value.includes('Officer') || accountType.value === 'Officer');
const isCommitteeMember = computed(() => roles.value.includes('Committee Member') || accountType.value === 'Committee Member');
const isViewer = computed(() => roles.value.includes('Viewer') || accountType.value === 'Viewer');
const isMember = computed(() => {
    const accountType = page.props.auth?.user?.account_type;
    return roles.value.includes('Member') || accountType === 'Member';
});
const isMemberOnly = computed(() => isMember.value && !isCoopAdmin.value && !isProvincialAdmin.value && roles.value.length === 1);

const canViewCoops = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value || isCommitteeMember.value || isViewer.value);
const canViewMembers = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value || isCommitteeMember.value || isViewer.value);
const canViewOfficers = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value || isCommitteeMember.value || isViewer.value);
const canViewActivities = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value || isCommitteeMember.value || isViewer.value);
const canViewActivityParticipants = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value || isCommitteeMember.value || isViewer.value);
const canViewActivityFundingSources = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value || isCommitteeMember.value || isViewer.value);
const canViewFinancialRecords = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value || isCommitteeMember.value || isViewer.value);
const canViewExternalSupports = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value || isCommitteeMember.value || isViewer.value);
const canViewTrainings = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value || isCommitteeMember.value || isViewer.value);
const canViewTrainingParticipants = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value || isCommitteeMember.value || isViewer.value);
const canViewSkillInventories = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value || isCommitteeMember.value || isViewer.value);
const canManageUsers = computed(() => isProvincialAdmin.value);
const canViewActivityLogs = computed(() => isProvincialAdmin.value);

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
            href: '/cooperatives',
            icon: Building2,
        },
        {
            title: 'Members',
            href: '/members',
            icon: Users,
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
            title: 'Funding Sources',
            href: '/activity-funding-sources',
            icon: DollarSign,
        },
        {
            title: 'Activity Participants',
            href: '/activity-participants',
            icon: Users,
        },
        {
            title: 'Trainings',
            href: '/trainings',
            icon: GraduationCap,
        },
        {
            title: 'Training Participants',
            href: '/training-participants',
            icon: Users,
        },
        {
            title: 'Skills Inventory',
            href: '/skill-inventories',
            icon: Sparkles,
        },
        {
            title: 'Financial Records',
            href: '/financial-records',
            icon: DollarSign,
        },
        {
            title: 'External Supports',
            href: '/external-supports',
            icon: DollarSign,
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

    if (canViewCoops.value) {
        items.push(baseItems[2]);
    }

    if (canViewMembers.value) {
        items.push(baseItems[3]);
    }

    if (canViewOfficers.value) {
        items.push(baseItems[4]);
    }

    if (canViewActivities.value) {
        items.push(baseItems[5]);
    }

    if (canViewActivityFundingSources.value) {
        items.push(baseItems[6]);
    }

    if (canViewActivityParticipants.value) {
        items.push(baseItems[7]);
    }

    if (canViewTrainings.value) {
        items.push(baseItems[8]);
    }

    if (canViewTrainingParticipants.value) {
        items.push(baseItems[9]);
    }

    if (canViewSkillInventories.value) {
        items.push(baseItems[10]);
    }

    if (canViewFinancialRecords.value) {
        items.push(baseItems[11]);
    }

    if (canViewExternalSupports.value) {
        items.push(baseItems[12]);
    }

    if (isCoopAdmin.value || isProvincialAdmin.value) {
        items.push(baseItems[13], baseItems[14], baseItems[15]);
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
