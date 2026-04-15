<script setup lang="ts">
import { Accessibility, Eye, EyeOff, Moon, Sun } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger, useSidebar } from '@/components/ui/sidebar';
import { useAppBackgroundPreference } from '@/composables/useAppBackgroundPreference';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';
import type { User } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { isLargeMode, toggleLargeMode } = useSidebar();
const { resolvedAppearance, updateAppearance } = useAppearance();
const page = usePage();
const user = computed(() => page.props.auth.user as User);

const humanizeSegment = (segment: string) =>
    segment
        .replace(/[-_]+/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());

const buildGenericBreadcrumbs = (path: string, rootTitle: string, rootHref: string): BreadcrumbItem[] => {
    const crumbs: BreadcrumbItem[] = [{ title: rootTitle, href: rootHref }];
    const segments = path.split('/').filter(Boolean);

    if (segments.length <= 1) {
        return crumbs;
    }

    const last = segments[segments.length - 1];
    const secondToLast = segments.length > 1 ? segments[segments.length - 2] : '';

    if (last === 'create') {
        crumbs.push({ title: 'Create', href: path });
        return crumbs;
    }

    if (last === 'edit') {
        crumbs.push({ title: 'Edit', href: path });
        return crumbs;
    }

    if (/^\d+$/.test(last) && secondToLast && secondToLast !== segments[0]) {
        crumbs.push({ title: humanizeSegment(secondToLast), href: path });
        return crumbs;
    }

    if (!/^\d+$/.test(last)) {
        crumbs.push({ title: humanizeSegment(last), href: path });
    }

    return crumbs;
};

const buildFinanceBreadcrumbs = (path: string): BreadcrumbItem[] => {
    const segments = path.split('/').filter(Boolean);
    const crumbs: BreadcrumbItem[] = [{ title: 'Finance', href: '/finance' }];

    if (segments.length < 2) {
        return crumbs;
    }

    const tab = segments[1];
    const financeTabs: Record<string, string> = {
        'funding-sources': 'Funding Sources',
        'financial-records': 'Financial Records',
        loans: 'Loans',
        savings: 'Savings',
        reports: 'Reports',
    };

    const tabTitle = financeTabs[tab];
    if (!tabTitle) {
        crumbs.push({ title: humanizeSegment(tab), href: path });
        return crumbs;
    }

    crumbs.push({
        title: tabTitle,
        href: tab === 'reports' ? '/finance/reports/statements' : `/finance/${tab}`,
    });

    if (tab === 'reports' && segments.length > 2) {
        const reportSections: Record<string, string> = {
            statements: 'Financial Statements',
            'loan-portfolio': 'Loan Portfolio',
            'savings-summary': 'Savings Summary',
            'funder-accountability': 'Funder Accountability',
            trends: 'Trend Analysis',
        };
        const section = segments[2];
        if (reportSections[section]) {
            crumbs.push({ title: reportSections[section], href: path });
        }
    }

    return crumbs;
};

const buildCooperativeManagementBreadcrumbs = (path: string): BreadcrumbItem[] => {
    const segments = path.split('/').filter(Boolean);

    if (segments[0] === 'members' && segments[1] === 'management') {
        const crumbs: BreadcrumbItem[] = [
            { title: 'Cooperative Management', href: '/members/management' },
            { title: 'Members', href: '/members/management' },
        ];

        if (segments[2] === 'select') {
            crumbs.push({ title: 'Choose Cooperative', href: path });
        }

        return crumbs;
    }

    const crumbs: BreadcrumbItem[] = [{ title: 'Cooperative Management', href: '/cooperatives' }];

    if (segments.length <= 1) {
        return crumbs;
    }

    const next = segments[1];

    if (next === 'types') {
        crumbs.push({ title: 'Cooperative Types', href: '/cooperatives/types' });
        return crumbs;
    }

    if (next === 'select') {
        crumbs.push({ title: 'Choose Cooperative', href: '/cooperatives/select' });
        return crumbs;
    }

    if (next === 'create') {
        crumbs.push({ title: 'Create Cooperative', href: '/cooperatives/create' });
        return crumbs;
    }

    if (next === 'my') {
        crumbs.push({ title: 'My Cooperative', href: '/cooperatives/my' });
        return crumbs;
    }

    if (/^\d+$/.test(next)) {
        crumbs.push({ title: 'Cooperative Profile', href: `/cooperatives/${next}` });

        if (segments[2] === 'edit') {
            crumbs.push({ title: 'Edit', href: path });
        }

        return crumbs;
    }

    crumbs.push({ title: humanizeSegment(next), href: path });
    return crumbs;
};

const buildMemberPortalBreadcrumbs = (path: string): BreadcrumbItem[] => {
    const segments = path.split('/').filter(Boolean);
    const crumbs: BreadcrumbItem[] = [{ title: 'My Profile', href: '/member-portal' }];

    if (segments.length < 2) {
        return crumbs;
    }

    const sectionMap: Record<string, string> = {
        services: 'My Services',
        activities: 'My Activities',
        loans: 'My Loans',
    };

    const section = segments[1];
    if (sectionMap[section]) {
        crumbs.push({ title: sectionMap[section], href: `/member-portal/${section}` });
    } else {
        crumbs.push({ title: humanizeSegment(section), href: path });
    }

    if (segments.length > 2 && !/^\d+$/.test(segments[2])) {
        crumbs.push({ title: humanizeSegment(segments[2]), href: path });
    }

    return crumbs;
};

const fallbackBreadcrumbs = computed<BreadcrumbItem[]>(() => {
    const rawUrl = page.url || '/dashboard';
    const [pathOnly] = rawUrl.split('?');
    const path = pathOnly === '/' ? '/dashboard' : (pathOnly?.replace(/\/+$/, '') || '/dashboard');

    if (path.startsWith('/finance')) {
        return buildFinanceBreadcrumbs(path);
    }

    if (path.startsWith('/cooperatives') || path.startsWith('/members/management')) {
        return buildCooperativeManagementBreadcrumbs(path);
    }

    if (path.startsWith('/member-portal')) {
        return buildMemberPortalBreadcrumbs(path);
    }

    if (path.startsWith('/dashboard')) return [{ title: 'Dashboard', href: '/dashboard' }];
    if (path.startsWith('/users')) return buildGenericBreadcrumbs(path, 'User Management', '/users');
    if (path.startsWith('/roles-permissions')) return buildGenericBreadcrumbs(path, 'Roles & Permissions', '/roles-permissions');
    if (path.startsWith('/members')) return buildGenericBreadcrumbs(path, 'Members', '/members');
    if (path.startsWith('/pds/my')) return [{ title: 'My PDS', href: '/pds/my' }];
    if (path.startsWith('/pds')) return buildGenericBreadcrumbs(path, 'Personal Data Sheet', '/pds');
    if (path.startsWith('/officers')) return buildGenericBreadcrumbs(path, 'Officers & Committees', '/officers');
    if (path.startsWith('/activities')) return buildGenericBreadcrumbs(path, 'Activities & Projects', '/activities');
    if (path.startsWith('/trainings')) return buildGenericBreadcrumbs(path, 'Trainings', '/trainings');
    if (path.startsWith('/activity-logs')) return buildGenericBreadcrumbs(path, 'Activity Logs', '/activity-logs');

    if (path.startsWith('/financial-records')) return buildGenericBreadcrumbs(path, 'Financial Records', '/financial-records');
    if (path.startsWith('/external-supports')) return buildGenericBreadcrumbs(path, 'External Supports', '/external-supports');
    if (path.startsWith('/activity-funding-sources')) return buildGenericBreadcrumbs(path, 'Activity Funding Sources', '/activity-funding-sources');
    if (path.startsWith('/activity-participants')) return buildGenericBreadcrumbs(path, 'Activity Participants', '/activity-participants');
    if (path.startsWith('/training-participants')) return buildGenericBreadcrumbs(path, 'Training Participants', '/training-participants');
    if (path.startsWith('/committee-members')) return buildGenericBreadcrumbs(path, 'Committee Members', '/committee-members');
    if (path.startsWith('/skill-inventories')) return buildGenericBreadcrumbs(path, 'Skill Inventories', '/skill-inventories');
    if (path.startsWith('/session-history')) return buildGenericBreadcrumbs(path, 'Session History', '/session-history');
    if (path.startsWith('/account-status-history')) return buildGenericBreadcrumbs(path, 'Account Status History', '/account-status-history');
    if (path.startsWith('/audit-logs')) return buildGenericBreadcrumbs(path, 'Audit Logs', '/audit-logs');

    const segments = path.split('/').filter(Boolean);
    if (!segments.length) {
        return [{ title: 'Dashboard', href: '/dashboard' }];
    }

    return buildGenericBreadcrumbs(path, humanizeSegment(segments[0]), `/${segments[0]}`);
});

const resolvedBreadcrumbs = computed<BreadcrumbItem[]>(() => {
    if (props.breadcrumbs && props.breadcrumbs.length > 0) {
        return props.breadcrumbs;
    }

    return fallbackBreadcrumbs.value;
});

const {
    isBackgroundAutoManaged,
    isBackgroundButtonLocked,
    prefersReducedMotion,
    isAppBackgroundVisible,
    toggleAppBackground,
} = useAppBackgroundPreference();

const isDarkMode = computed(() => resolvedAppearance.value === 'dark');

function toggleAppearance() {
    updateAppearance(isDarkMode.value ? 'light' : 'dark');
}
</script>

<template>
    <header
        class="app-header-surface app-shell-divider flex h-16 shrink-0 items-center gap-2 border-b px-6 backdrop-blur-xl transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="resolvedBreadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="resolvedBreadcrumbs" />
            </template>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :aria-pressed="isAppBackgroundVisible"
                                :aria-label="
                                        isBackgroundAutoManaged
                                                ? 'Animated background is set to Auto mode'
                                                : isBackgroundButtonLocked
                                                    ? 'Animated background is locked off'
                                                    : isAppBackgroundVisible
                                                        ? 'Hide animated background'
                                                        : 'Show animated background'
                                "
                :title="
                    prefersReducedMotion
                        ? 'Animated background follows reduced-motion preference and is disabled'
                                                : isBackgroundAutoManaged
                                                    ? 'Animated background follows color mode automatically (On in dark mode, Off in light mode)'
                                                    : isBackgroundButtonLocked
                                                        ? 'Animated background is locked Off. Change behavior in Appearance settings.'
                                                        : isAppBackgroundVisible
                                                            ? 'Hide animated background'
                                                            : 'Show animated background'
                "
                                :disabled="prefersReducedMotion || isBackgroundButtonLocked"
                class="h-8 gap-1.5 px-2 text-xs"
                :class="isAppBackgroundVisible ? 'bg-sidebar-accent text-sidebar-accent-foreground' : ''"
                @click="toggleAppBackground"
            >
                <Eye v-if="isAppBackgroundVisible" class="size-4" />
                <EyeOff v-else class="size-4" />
                                <span class="hidden sm:inline">{{ isBackgroundAutoManaged ? 'Auto' : isAppBackgroundVisible ? 'Bg On' : 'Bg Off' }}</span>
            </Button>

            <Button
                type="button"
                variant="ghost"
                size="sm"
                :aria-pressed="isLargeMode"
                :title="isLargeMode ? 'Use default text and sidebar size' : 'Use larger text and sidebar size'"
                class="h-8 gap-1.5 px-2 text-xs"
                :class="isLargeMode ? 'bg-sidebar-accent text-sidebar-accent-foreground' : ''"
                @click="toggleLargeMode"
            >
                <Accessibility class="size-4" />
                <span class="hidden sm:inline">{{ isLargeMode ? 'Large On' : 'Large Off' }}</span>
            </Button>

            <Button
                type="button"
                variant="ghost"
                size="sm"
                :aria-pressed="isDarkMode"
                :title="isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                class="h-8 gap-1.5 px-2 text-xs"
                :class="isDarkMode ? 'bg-sidebar-accent text-sidebar-accent-foreground' : ''"
                @click="toggleAppearance"
            >
                <span class="relative size-4">
                    <Sun
                        class="absolute inset-0 transition-all duration-300 ease-out"
                        :class="isDarkMode ? 'rotate-90 scale-0 opacity-0' : 'rotate-0 scale-100 opacity-100'"
                    />
                    <Moon
                        class="absolute inset-0 transition-all duration-300 ease-out"
                        :class="isDarkMode ? 'rotate-0 scale-100 opacity-100' : '-rotate-90 scale-0 opacity-0'"
                    />
                </span>
                <span class="hidden sm:inline">{{ isDarkMode ? 'Dark Mode' : 'Light Mode' }}</span>
            </Button>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        type="button"
                        class="h-9 max-w-56 justify-start gap-2 px-2.5 border border-sidebar-primary/25 bg-sidebar-primary/12 text-sidebar-foreground shadow-[0_8px_18px_-16px_hsl(var(--sidebar-primary)/0.95)] hover:bg-sidebar-primary/16 dark:border-sidebar-primary/30 dark:bg-sidebar-primary/18 dark:hover:bg-sidebar-primary/24 data-[state=open]:bg-sidebar-primary/22 data-[state=open]:text-sidebar-foreground"
                    >
                        <UserInfo :user="user" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
