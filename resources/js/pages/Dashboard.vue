<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { 
    Users, 
    TrendingUp, 
    FileText,
    Activity,
    Shield,
    UserCog,
    DollarSign
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

interface Stats {
    totalUsers: number;
    totalRoles: number;
    usersWithRoles: number;
    usersGrowth: number;
}

interface UserByRole {
    name: string;
    count: number;
}

interface RecentUser {
    id: number;
    name: string;
    email: string;
    roles: string[];
    created_at: string;
}

interface SystemTrends {
    labels: string[];
    registrations: number[];
}

interface SectorDistribution {
    labels: string[];
    values: number[];
    total: number;
}

interface CoopInfo {
    id: number;
    name: string;
    province: string | null;
    city_municipality: string | null;
    barangay: string | null;
}

interface CoopStats {
    totalMembers: number;
    activeMembers: number;
    inactiveMembers: number;
    totalActivities: number;
    totalTrainings: number;
}

interface CoopTrends {
    period: string;
    labels: string[];
    activities: number[];
    trainings: number[];
    members: number[];
    filters: {
        member_status: string | null;
        activity_status: string | null;
        activity_category: string | null;
        training_status: string | null;
        training_target_group: string | null;
    };
}

interface CoopMember {
    id: number;
    name: string;
    email: string | null;
    membership_status: string | null;
    date_joined: string | null;
}

interface MemberProfile {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    membership_status: string | null;
    membership_type: string | null;
    sector: string | null;
    date_joined: string | null;
    address: string | null;
    city_municipality: string | null;
    province: string | null;
}

interface MemberCoop {
    id: number;
    name: string;
    province: string | null;
    city_municipality: string | null;
    barangay: string | null;
    status: string | null;
}

interface MemberLoanSummary {
    id: number;
    principal: string;
    status: string;
    created_at: string | null;
}

interface SuperAdminStats {
    stats: {
        totalUsers: number;
        totalCooperatives: number;
        totalMembers: number;
        totalActivities: number;
        totalTrainings: number;
        totalRoles: number;
        totalPermissions: number;
    };
    usersByRole: Array<{ name: string; count: number }>;
    recentUsers: Array<{
        id: number;
        name: string;
        email: string;
        roles: string[];
        created_at: string;
        account_status: string;
    }>;
    recentActivities: Array<{
        id: number;
        name: string;
        status: string;
        cooperative: string | null;
        date_started: string;
        category: string;
    }>;
    coopsByProvince: Array<{ province: string; count: number }>;
    membersByStatus: Record<string, number>;
    userGrowthTrend: {
        labels: string[];
        values: number[];
    };
}

const props = defineProps<{
    stats?: Stats;
    usersByRole?: UserByRole[];
    recentUsers?: RecentUser[];
    systemTrends?: SystemTrends;
    sectorDistribution?: SectorDistribution;
    isSuperAdmin?: boolean;
    isCoopAdmin?: boolean;
    isMember?: boolean;
    coopInfo?: CoopInfo | null;
    coopStats?: CoopStats | null;
    coopMembers?: CoopMember[];
    coopTrends?: CoopTrends | null;
    memberProfile?: MemberProfile | null;
    memberCoop?: MemberCoop | null;
    memberLoansCount?: number;
    memberRecentLoans?: MemberLoanSummary[];
    memberServicesCount?: number;
    memberActivitiesCount?: number;
    superAdminStats?: SuperAdminStats;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

type SummaryCard = {
    title: string;
    value: string;
    helper?: string;
    icon: typeof Users;
    accent: {
        bg: string;
        text: string;
        ring: string;
    };
};

const formatNumber = (value: number | null | undefined) =>
    new Intl.NumberFormat('en-US').format(value ?? 0);

const summaryCards = computed<SummaryCard[]>(() => {
    if (props.isMember && props.memberProfile) {
        return [
            {
                title: 'My Loans',
                value: formatNumber(props.memberLoansCount || 0),
                helper: 'Loan records',
                icon: DollarSign,
                accent: { bg: 'bg-emerald-100/70', text: 'text-emerald-700', ring: 'ring-emerald-200/60' },
            },
            {
                title: 'My Activities',
                value: formatNumber(props.memberActivitiesCount || 0),
                helper: 'Participation entries',
                icon: Activity,
                accent: { bg: 'bg-blue-100/70', text: 'text-blue-700', ring: 'ring-blue-200/60' },
            },
            {
                title: 'My Services',
                value: formatNumber(props.memberServicesCount || 0),
                helper: 'Services availed',
                icon: FileText,
                accent: { bg: 'bg-violet-100/70', text: 'text-violet-700', ring: 'ring-violet-200/60' },
            },
            {
                title: 'My Status',
                value: props.memberProfile.membership_status || 'N/A',
                helper: 'Membership standing',
                icon: Shield,
                accent: { bg: 'bg-orange-100/70', text: 'text-orange-700', ring: 'ring-orange-200/60' },
            },
        ];
    }

    if (props.isSuperAdmin && props.superAdminStats) {
        return [
            {
                title: 'Total Users',
                value: formatNumber(props.superAdminStats.stats.totalUsers),
                helper: 'All registered accounts',
                icon: Users,
                accent: { bg: 'bg-blue-100/70', text: 'text-blue-700', ring: 'ring-blue-200/60' },
            },
            {
                title: 'Cooperatives',
                value: formatNumber(props.superAdminStats.stats.totalCooperatives),
                helper: 'Active cooperatives',
                icon: FileText,
                accent: { bg: 'bg-emerald-100/70', text: 'text-emerald-700', ring: 'ring-emerald-200/60' },
            },
            {
                title: 'Members',
                value: formatNumber(props.superAdminStats.stats.totalMembers),
                helper: 'Total membership',
                icon: UserCog,
                accent: { bg: 'bg-violet-100/70', text: 'text-violet-700', ring: 'ring-violet-200/60' },
            },
            {
                title: 'Activities',
                value: formatNumber(props.superAdminStats.stats.totalActivities),
                helper: 'System-wide projects',
                icon: Activity,
                accent: { bg: 'bg-orange-100/70', text: 'text-orange-700', ring: 'ring-orange-200/60' },
            },
        ];
    }

    if (props.isCoopAdmin && props.coopStats) {
        return [
            {
                title: 'Total Members',
                value: formatNumber(props.coopStats.totalMembers),
                helper: 'All registered members',
                icon: Users,
                accent: { bg: 'bg-blue-100/70', text: 'text-blue-700', ring: 'ring-blue-200/60' },
            },
            {
                title: 'Active Members',
                value: formatNumber(props.coopStats.activeMembers),
                helper: 'Currently active',
                icon: UserCog,
                accent: { bg: 'bg-emerald-100/70', text: 'text-emerald-700', ring: 'ring-emerald-200/60' },
            },
            {
                title: 'Activities',
                value: formatNumber(props.coopStats.totalActivities),
                helper: 'Planned and ongoing',
                icon: FileText,
                accent: { bg: 'bg-indigo-100/70', text: 'text-indigo-700', ring: 'ring-indigo-200/60' },
            },
            {
                title: 'Trainings',
                value: formatNumber(props.coopStats.totalTrainings),
                helper: 'Capacity building',
                icon: Activity,
                accent: { bg: 'bg-pink-100/70', text: 'text-pink-700', ring: 'ring-pink-200/60' },
            },
        ];
    }

    if (props.stats) {
        return [
            {
                title: 'Total Users',
                value: formatNumber(props.stats.totalUsers),
                helper: 'All registered accounts',
                icon: Users,
                accent: { bg: 'bg-blue-100/70', text: 'text-blue-700', ring: 'ring-blue-200/60' },
            },
            {
                title: 'Users with Roles',
                value: formatNumber(props.stats.usersWithRoles),
                helper: 'Assigned roles',
                icon: UserCog,
                accent: { bg: 'bg-emerald-100/70', text: 'text-emerald-700', ring: 'ring-emerald-200/60' },
            },
            {
                title: 'Total Roles',
                value: formatNumber(props.stats.totalRoles),
                helper: 'Active roles',
                icon: Shield,
                accent: { bg: 'bg-violet-100/70', text: 'text-violet-700', ring: 'ring-violet-200/60' },
            },
            {
                title: 'Weekly Growth',
                value: `${props.stats.usersGrowth}%`,
                helper: 'Weekly user growth',
                icon: TrendingUp,
                accent: { bg: 'bg-orange-100/70', text: 'text-orange-700', ring: 'ring-orange-200/60' },
            },
        ];
    }

    return [];
});

const recentUsersComputed = computed(() => props.recentUsers || []);
const usersByRoleComputed = computed(() => props.usersByRole || []);

const trendPeriods = [
    { value: 'day', label: 'Daily' },
    { value: 'week', label: 'Weekly' },
    { value: 'month', label: 'Monthly' },
    { value: 'year', label: 'Yearly' },
];

const activityStatusOptions = ['Planned', 'In Progress', 'Completed', 'Cancelled'];
const activityCategoryOptions = ['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'];
const trainingStatusOptions = ['Planned', 'Completed', 'Cancelled', 'Follow-Up Pending'];
const trainingTargetOptions = ['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'Fishfolk', 'New Members', 'Other'];
const memberStatusOptions = ['Active', 'Suspended', 'Resigned', 'Deceased'];

const selectedTrendPeriod = ref(props.coopTrends?.period ?? 'month');
const growthChartType = ref<'line' | 'bar'>('line');
const sectorChartType = ref<'bars' | 'donut'>('bars');

const trendFilters = ref({
    memberStatus: props.coopTrends?.filters.member_status ?? '',
    activityStatus: props.coopTrends?.filters.activity_status ?? '',
    activityCategory: props.coopTrends?.filters.activity_category ?? '',
    trainingStatus: props.coopTrends?.filters.training_status ?? '',
    trainingTargetGroup: props.coopTrends?.filters.training_target_group ?? '',
});

const visibleSeries = ref({
    members: true,
    activities: true,
    trainings: true,
});

const buildTrendQuery = (overrides: Partial<typeof trendFilters.value> = {}) => {
    const next = { ...trendFilters.value, ...overrides };
    const query: Record<string, string> = {
        period: selectedTrendPeriod.value,
    };

    if (next.memberStatus) query.member_status = next.memberStatus;
    if (next.activityStatus) query.activity_status = next.activityStatus;
    if (next.activityCategory) query.activity_category = next.activityCategory;
    if (next.trainingStatus) query.training_status = next.trainingStatus;
    if (next.trainingTargetGroup) query.training_target_group = next.trainingTargetGroup;

    return query;
};

const analyticsRangeOptions = [
    { value: '7d', label: 'Last 7 days' },
    { value: '30d', label: 'Last 30 days' },
    { value: '90d', label: 'Last 90 days' },
    { value: 'ytd', label: 'Year to date' },
];

const analyticsCategoryOptions = [
    { value: 'all', label: 'All metrics' },
    { value: 'members', label: 'Members' },
    { value: 'activities', label: 'Activities' },
    { value: 'trainings', label: 'Trainings' },
    { value: 'finance', label: 'Finance' },
];

const analyticsFilters = ref({
    range: '30d',
    category: 'all',
});

const buildAnalyticsQuery = () => {
    const query: Record<string, string> = {};

    if (analyticsFilters.value.range) {
        query.range = analyticsFilters.value.range;
    }

    if (analyticsFilters.value.category && analyticsFilters.value.category !== 'all') {
        query.category = analyticsFilters.value.category;
    }

    return query;
};

const applyTrendQuery = (query: Record<string, string>) => {
    const mergedQuery = {
        ...buildAnalyticsQuery(),
        ...query,
    };

    router.get(
        dashboard({
            query: mergedQuery,
        }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const updateAnalyticsFilters = (overrides: Partial<typeof analyticsFilters.value> = {}) => {
    analyticsFilters.value = { ...analyticsFilters.value, ...overrides };

    if (props.isCoopAdmin) {
        applyTrendQuery(buildTrendQuery());
        return;
    }

    router.get(
        dashboard({
            query: buildAnalyticsQuery(),
        }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const updateTrendPeriod = (period: string) => {
    if (period === selectedTrendPeriod.value) {
        return;
    }

    selectedTrendPeriod.value = period;
    applyTrendQuery(buildTrendQuery());
};

const updateTrendFilters = (overrides: Partial<typeof trendFilters.value> = {}) => {
    trendFilters.value = { ...trendFilters.value, ...overrides };
    applyTrendQuery(buildTrendQuery());
};

const chartWidth = 600;
const chartHeight = 180;
const chartPadding = 16;

const trendLabels = computed(() => props.coopTrends?.labels ?? []);
const trendSeries = computed(() => ({
    activities: props.coopTrends?.activities ?? [],
    trainings: props.coopTrends?.trainings ?? [],
    members: props.coopTrends?.members ?? [],
}));

const maxTrendValue = computed(() => {
    const values = [
        ...(visibleSeries.value.activities ? trendSeries.value.activities : []),
        ...(visibleSeries.value.trainings ? trendSeries.value.trainings : []),
        ...(visibleSeries.value.members ? trendSeries.value.members : []),
    ];

    return Math.max(1, ...values);
});

type BarPoint = {
    x: number;
    y: number;
    width: number;
    height: number;
    color: string;
    value: number;
    label: string;
    seriesLabel: string;
};

const barSeries = computed(() => {
    const series: Array<{ key: string; label: string; data: number[]; color: string }> = [];

    if (visibleSeries.value.members) {
        series.push({ key: 'members', label: 'Members', data: trendSeries.value.members, color: '#10b981' });
    }

    if (visibleSeries.value.activities) {
        series.push({ key: 'activities', label: 'Activities', data: trendSeries.value.activities, color: '#3b82f6' });
    }

    if (visibleSeries.value.trainings) {
        series.push({ key: 'trainings', label: 'Trainings', data: trendSeries.value.trainings, color: '#8b5cf6' });
    }

    return series;
});

const barPoints = computed<BarPoint[]>(() => {
    const labels = trendLabels.value;
    const series = barSeries.value;

    if (!labels.length || !series.length) {
        return [];
    }

    const width = chartWidth - chartPadding * 2;
    const height = chartHeight - chartPadding * 2;
    const groupWidth = width / labels.length;
    const groupGap = Math.min(10, groupWidth * 0.18);
    const barWidth = Math.max(6, (groupWidth - groupGap) / series.length);

    const points: BarPoint[] = [];

    labels.forEach((label, index) => {
        const groupStart = chartPadding + index * groupWidth + groupGap / 2;

        series.forEach((seriesItem, seriesIndex) => {
            const value = seriesItem.data[index] ?? 0;
            const barHeight = (value / maxTrendValue.value) * height;
            points.push({
                x: groupStart + seriesIndex * barWidth,
                y: chartHeight - chartPadding - barHeight,
                width: barWidth - 2,
                height: barHeight,
                color: seriesItem.color,
                value,
                label,
                seriesLabel: seriesItem.label,
            });
        });
    });

    return points;
});

const chartGridLines = computed(() => [
    chartPadding,
    chartPadding + (chartHeight - chartPadding * 2) / 2,
    chartHeight - chartPadding,
]);

const hasVisibleSeries = computed(() =>
    visibleSeries.value.members || visibleSeries.value.activities || visibleSeries.value.trainings
);

type TrendMarker = {
    x: number;
    y: number;
    value: number;
};

type SystemBarPoint = {
    x: number;
    y: number;
    width: number;
    height: number;
    value: number;
    label: string;
};

const systemChartWidth = 640;
const systemChartHeight = 220;
const systemChartPaddingX = 24;
const systemChartPaddingY = 20;

const systemTrendLabels = computed(() => {
    if (props.isSuperAdmin && props.superAdminStats?.userGrowthTrend) {
        return props.superAdminStats.userGrowthTrend.labels;
    }

    return props.systemTrends?.labels ?? [];
});

const systemTrendValues = computed(() => {
    if (props.isSuperAdmin && props.superAdminStats?.userGrowthTrend) {
        return props.superAdminStats.userGrowthTrend.values;
    }

    return props.systemTrends?.registrations ?? [];
});

const systemTrendMax = computed(() => Math.max(1, ...systemTrendValues.value));

const systemTrendGridLines = computed(() => [
    systemChartPaddingY,
    systemChartPaddingY + (systemChartHeight - systemChartPaddingY * 2) / 2,
    systemChartHeight - systemChartPaddingY,
]);

const systemTrendMarkers = computed<TrendMarker[]>(() => {
    if (!systemTrendValues.value.length) {
        return [];
    }

    const drawableWidth = systemChartWidth - systemChartPaddingX * 2;
    const drawableHeight = systemChartHeight - systemChartPaddingY * 2;
    const step = systemTrendValues.value.length > 1 ? drawableWidth / (systemTrendValues.value.length - 1) : 0;

    return systemTrendValues.value.map((value, index) => ({
        x: systemChartPaddingX + index * step,
        y: systemChartHeight - systemChartPaddingY - (value / systemTrendMax.value) * drawableHeight,
        value,
    }));
});

const systemTrendPoints = computed(() =>
    systemTrendMarkers.value.map((point) => `${point.x},${point.y}`).join(' ')
);

const systemTrendAreaPoints = computed(() => {
    if (!systemTrendMarkers.value.length) {
        return '';
    }

    const baselineY = systemChartHeight - systemChartPaddingY;
    const firstPoint = systemTrendMarkers.value[0];
    const lastPoint = systemTrendMarkers.value[systemTrendMarkers.value.length - 1];

    return `${firstPoint.x},${baselineY} ${systemTrendPoints.value} ${lastPoint.x},${baselineY}`;
});

const systemTrendBarPoints = computed<SystemBarPoint[]>(() => {
    if (!systemTrendValues.value.length) {
        return [];
    }

    const drawableWidth = systemChartWidth - systemChartPaddingX * 2;
    const drawableHeight = systemChartHeight - systemChartPaddingY * 2;
    const slotWidth = drawableWidth / systemTrendValues.value.length;
    const barWidth = Math.max(10, slotWidth * 0.55);

    return systemTrendValues.value.map((value, index) => {
        const height = (value / systemTrendMax.value) * drawableHeight;

        return {
            x: systemChartPaddingX + index * slotWidth + (slotWidth - barWidth) / 2,
            y: systemChartHeight - systemChartPaddingY - height,
            width: barWidth,
            height,
            value,
            label: systemTrendLabels.value[index] ?? `Point ${index + 1}`,
        };
    });
});

const sectorValues = computed(() => props.sectorDistribution?.values ?? []);
const sectorLabels = computed(() => props.sectorDistribution?.labels ?? []);
const sectorTotal = computed(() => props.sectorDistribution?.total ?? 0);
const sectorMax = computed(() => Math.max(1, ...sectorValues.value));

const sectorBars = computed(() =>
    sectorLabels.value.map((label, index) => {
        const value = sectorValues.value[index] ?? 0;

        return {
            label,
            value,
            percent: (value / sectorMax.value) * 100,
        };
    })
);

type SectorDonutSegment = {
    label: string;
    value: number;
    percentage: number;
    color: string;
    dash: number;
    offset: number;
};

const sectorPalette = ['#2563eb', '#7c3aed', '#059669', '#ea580c', '#dc2626', '#0891b2', '#65a30d', '#db2777'];

const sectorDonutSegments = computed<SectorDonutSegment[]>(() => {
    if (!sectorBars.value.length || !sectorTotal.value) {
        return [];
    }

    let cumulative = 0;

    return sectorBars.value.map((bar, index) => {
        const percentage = (bar.value / sectorTotal.value) * 100;
        const segment = {
            label: bar.label,
            value: bar.value,
            percentage,
            color: sectorPalette[index % sectorPalette.length],
            dash: percentage,
            offset: 25 - cumulative,
        };

        cumulative += percentage;
        return segment;
    });
});

const getRoleBadgeColor = (roleName: string) => {
    const colors: Record<string, string> = {
        'Provincial Admin': 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-200',
        'Coop Admin': 'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-200',
        'Officer': 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-200',
        'Committee Member': 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-200',
        'Member': 'bg-purple-100 text-purple-800 dark:bg-purple-500/20 dark:text-purple-200',
        'Viewer': 'bg-gray-100 text-gray-800 dark:bg-slate-500/20 dark:text-slate-200',
    };
    return colors[roleName] || 'bg-gray-100 text-gray-800 dark:bg-slate-500/20 dark:text-slate-200';
};

const getMembershipBadgeColor = (status: string | null) => {
    const colors: Record<string, string> = {
        'Active': 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-200',
        'Suspended': 'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-200',
        'Resigned': 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-200',
        'Deceased': 'bg-gray-100 text-gray-800 dark:bg-slate-500/20 dark:text-slate-200',
    };
    return colors[status || ''] || 'bg-gray-100 text-gray-800 dark:bg-slate-500/20 dark:text-slate-200';
};
</script>

<template>
    <Head title="Dashboard - Coop System" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="dashboard-theme relative flex h-full flex-1 flex-col gap-6 overflow-hidden p-6 text-foreground">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,rgba(14,64,120,0.08),transparent_60%),linear-gradient(180deg,rgba(15,23,42,0.03),rgba(15,23,42,0))]" />

            <!-- Header -->
            <Card :class="props.isMember ? 'gap-0 rounded-2xl border border-border bg-card p-6 shadow-sm' : 'gap-0 rounded-2xl border border-slate-200/70 bg-white/90 p-6 shadow-[0_10px_30px_rgba(15,23,42,0.1)] backdrop-blur'">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1">
                        <h1 :class="props.isMember ? 'text-2xl font-semibold tracking-tight text-foreground md:text-3xl' : 'text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl'">
                            {{ props.isSuperAdmin
                                ? 'Super Admin Analytics'
                                : props.isMember
                                    ? 'My Dashboard'
                                    : props.isCoopAdmin
                                        ? (props.coopInfo?.name || 'Cooperative Analytics')
                                        : 'System Analytics' }}
                        </h1>
                        <p :class="props.isMember ? 'text-sm text-muted-foreground' : 'text-sm text-slate-600'">
                            {{ props.isSuperAdmin
                                ? 'Unified insights across users, coops, and operations.'
                                : props.isMember
                                    ? 'Your account, your records, and your transactions at a glance.'
                                    : props.isCoopAdmin
                                        ? 'Performance, participation, and training insights.'
                                        : 'System-wide operational monitoring and trends.' }}
                        </p>
                    </div>
                    <div v-if="!props.isMember" class="flex flex-wrap items-center gap-3">
                        <div class="min-w-40">
                            <Select
                                :model-value="analyticsFilters.range"
                                @update:model-value="(value) => updateAnalyticsFilters({ range: String(value ?? '30d') })"
                            >
                                <SelectTrigger class="h-9 rounded-full border-slate-200/70 bg-white/80 text-xs font-semibold uppercase tracking-widest">
                                    <SelectValue placeholder="Date range" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in analyticsRangeOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="min-w-40">
                            <Select
                                :model-value="analyticsFilters.category"
                                @update:model-value="(value) => updateAnalyticsFilters({ category: String(value ?? 'all') })"
                            >
                                <SelectTrigger class="h-9 rounded-full border-slate-200/70 bg-white/80 text-xs font-semibold uppercase tracking-widest">
                                    <SelectValue placeholder="Category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in analyticsCategoryOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Card class="flex-row items-center gap-3 rounded-full border border-border bg-muted/70 px-3 py-2 shadow-sm">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                <Activity class="h-4 w-4" />
                            </div>
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Status</div>
                                <div class="text-sm font-semibold text-foreground">Operational</div>
                            </div>
                        </Card>
                    </div>
                </div>
            </Card>

            <!-- Summary Cards -->
            <div v-if="summaryCards.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card
                    v-for="card in summaryCards"
                    :key="card.title"
                    :class="props.isMember
                        ? 'gap-0 rounded-xl border border-border bg-card p-5 shadow-sm'
                        : 'group gap-0 rounded-2xl border border-border bg-card p-5 shadow-[0_8px_20px_rgba(15,23,42,0.08)] transition-all duration-300 hover:-translate-y-0.5'"
                >
                    <div class="flex items-center justify-between">
                        <div :class="['rounded-xl bg-muted/70 p-3 ring-1 ring-border/70 transition-colors', props.isMember ? '' : 'group-hover:bg-muted']">
                            <component :is="card.icon" class="h-5 w-5 text-foreground" />
                        </div>
                        <Badge v-if="card.helper && !props.isMember" class="rounded-full bg-muted px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                            {{ card.helper }}
                        </Badge>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            {{ card.title }}
                        </h3>
                        <p v-if="card.helper && props.isMember" class="mt-1 text-xs text-muted-foreground">
                            {{ card.helper }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-foreground">
                            {{ card.value }}
                        </p>
                    </div>
                </Card>
            </div>

            <!-- Main Content Area -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Super Admin Dashboard -->
                <div v-if="props.isSuperAdmin && props.superAdminStats" class="md:col-span-2 grid gap-4">
                    <!-- Users by Role -->
                    <Card class="gap-0 rounded-xl border border-slate-200/70 bg-white/90 p-6 py-0 shadow-sm">
                        <CardHeader class="px-6 pt-6 pb-4">
                            <CardTitle class="text-lg font-semibold text-slate-900">Users by Role</CardTitle>
                            <p class="text-sm text-slate-500 mt-1">Distribution of roles across all users</p>
                        </CardHeader>
                        <CardContent class="px-6 pb-6">
                            <div class="space-y-3">
                                <div v-for="role in props.superAdminStats.usersByRole" :key="role.name" class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-slate-600">{{ role.name }}</span>
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-32 rounded-full bg-slate-100">
                                            <div
                                                :style="{ width: (role.count / Math.max(...props.superAdminStats.usersByRole.map((r: any) => r.count))) * 100 + '%' }"
                                                class="h-full rounded-full bg-blue-500 transition-all duration-500"
                                            />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-900 min-w-12 text-right">{{ role.count }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Users -->
                    <Card class="gap-0 rounded-xl border border-slate-200/70 bg-white/90 p-6 py-0 shadow-sm">
                        <CardHeader class="px-6 pt-6 pb-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-lg font-semibold text-slate-900">Recent Users</CardTitle>
                                    <p class="text-sm text-slate-500 mt-1">Latest user registrations</p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="px-6 pb-6">
                            <div class="space-y-3">
                                <div v-for="user in props.superAdminStats.recentUsers" :key="user.id" class="flex items-center justify-between border-b border-slate-100 pb-3 last:border-0">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-slate-900">{{ user.name }}</p>
                                        <p class="text-xs text-slate-500">{{ user.email }}</p>
                                        <div v-if="user.roles.length > 0" class="mt-1 flex flex-wrap gap-1">
                                            <Badge
                                                v-for="role in user.roles"
                                                :key="role"
                                                :class="[getRoleBadgeColor(role), 'rounded-md px-2 py-0.5 text-xs font-medium']"
                                            >
                                                {{ role }}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <Badge :class="user.account_status === 'Active' ? 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-200' : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-200'">
                                            {{ user.account_status }}
                                        </Badge>
                                        <p class="text-xs text-slate-500 mt-2">{{ user.created_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Activities -->
                    <Card class="gap-0 rounded-xl border border-slate-200/70 bg-white/90 p-6 py-0 shadow-sm">
                        <CardHeader class="px-6 pt-6 pb-4">
                            <CardTitle class="text-lg font-semibold text-slate-900">Recent Activities</CardTitle>
                            <p class="text-sm text-slate-500 mt-1">Latest cooperative activities</p>
                        </CardHeader>
                        <CardContent class="px-6 pb-6">
                            <div class="space-y-3">
                                <div v-for="activity in props.superAdminStats.recentActivities" :key="activity.id" class="flex items-center justify-between border-b border-slate-100 pb-3 last:border-0">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-slate-900">{{ activity.name }}</p>
                                        <p class="text-xs text-slate-500">{{ activity.cooperative || 'N/A' }} • {{ activity.category }}</p>
                                    </div>
                                    <div class="text-right">
                                        <Badge 
                                            :class="activity.status === 'Completed' 
                                                ? 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-200'
                                                : activity.status === 'In Progress'
                                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-200'
                                                    : 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200'"
                                        >
                                            {{ activity.status }}
                                        </Badge>
                                        <p class="text-xs text-slate-500 mt-2">{{ activity.date_started }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Cooperatives by Province -->
                    <Card class="gap-0 rounded-xl border border-slate-200/70 bg-white/90 p-6 py-0 shadow-sm">
                        <CardHeader class="px-6 pt-6 pb-4">
                            <CardTitle class="text-lg font-semibold text-slate-900">Cooperatives by Province</CardTitle>
                            <p class="text-sm text-slate-500 mt-1">Geographic distribution</p>
                        </CardHeader>
                        <CardContent class="px-6 pb-6">
                            <div class="space-y-3">
                                <div v-for="prov in props.superAdminStats.coopsByProvince" :key="prov.province" class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-slate-600">{{ prov.province || 'Unassigned' }}</span>
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-32 rounded-full bg-slate-100">
                                            <div
                                                :style="{ width: (prov.count / Math.max(...props.superAdminStats.coopsByProvince.map((p: any) => p.count))) * 100 + '%' }"
                                                class="h-full rounded-full bg-teal-500 transition-all duration-500"
                                            />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-900 min-w-12 text-right">{{ prov.count }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Members by Status -->
                    <Card class="gap-0 rounded-xl border border-slate-200/70 bg-white/90 p-6 py-0 shadow-sm">
                        <CardHeader class="px-6 pt-6 pb-4">
                            <CardTitle class="text-lg font-semibold text-slate-900">Members by Status</CardTitle>
                            <p class="text-sm text-slate-500 mt-1">Membership status breakdown</p>
                        </CardHeader>
                        <CardContent class="px-6 pb-6">
                            <div class="space-y-3">
                                <div v-for="(count, status) in props.superAdminStats.membersByStatus" :key="status" class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-slate-600">{{ status }}</span>
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-32 rounded-full bg-slate-100">
                                            <div
                                                :style="{ width: (count / Math.max(...Object.values(props.superAdminStats.membersByStatus) as number[])) * 100 + '%' }"
                                                class="h-full rounded-full bg-violet-500 transition-all duration-500"
                                            />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-900 min-w-12 text-right">{{ count }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Member Dashboard -->
                <div v-if="props.isMember" class="md:col-span-2 grid gap-4">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <Card class="gap-0 rounded-lg border border-border bg-card p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-foreground">My Profile</h2>
                                    <p class="text-sm text-muted-foreground">Personal information on record.</p>
                                </div>
                                <Badge class="rounded-full bg-primary px-3 py-1 text-xs font-semibold uppercase tracking-widest text-primary-foreground">
                                    {{ props.memberProfile?.membership_status || 'N/A' }}
                                </Badge>
                            </div>

                            <div v-if="props.memberProfile" class="mt-5 text-sm text-foreground">
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-muted-foreground">Name</span>
                                    <span class="font-medium text-foreground">{{ props.memberProfile.name }}</span>
                                </div>
                                <div class="border-t border-border/70" />
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-muted-foreground">Email</span>
                                    <span class="font-medium text-foreground">{{ props.memberProfile.email || 'N/A' }}</span>
                                </div>
                                <div class="border-t border-border/70" />
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-muted-foreground">Phone</span>
                                    <span class="font-medium text-foreground">{{ props.memberProfile.phone || 'N/A' }}</span>
                                </div>
                                <div class="border-t border-border/70" />
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-muted-foreground">Date joined</span>
                                    <span class="font-medium text-foreground">{{ props.memberProfile.date_joined || 'N/A' }}</span>
                                </div>
                            </div>
                        </Card>

                        <Card class="gap-0 rounded-lg border border-border bg-card p-6 shadow-sm">
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">Membership</h2>
                                <p class="text-sm text-muted-foreground">Your membership classification.</p>
                            </div>
                            <div v-if="props.memberProfile" class="mt-5 text-sm text-foreground">
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-muted-foreground">Type</span>
                                    <span class="font-medium text-foreground">{{ props.memberProfile.membership_type || 'N/A' }}</span>
                                </div>
                                <div class="border-t border-border/70" />
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-muted-foreground">Sector</span>
                                    <span class="font-medium text-foreground">{{ props.memberProfile.sector || 'N/A' }}</span>
                                </div>
                                <div class="border-t border-border/70" />
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-muted-foreground">Status</span>
                                    <span class="font-medium text-foreground">{{ props.memberProfile.membership_status || 'N/A' }}</span>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <Card class="gap-0 rounded-lg border border-border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">My Activity & Services</h2>
                                <p class="text-sm text-muted-foreground">Your personal participation and services only.</p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <Card class="gap-0 rounded-md border border-border bg-muted/40">
                                <CardHeader class="px-4 pt-4 pb-2">
                                    <CardTitle class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">My Activities</CardTitle>
                                </CardHeader>
                                <CardContent class="px-4 pb-4">
                                    <div class="text-2xl font-semibold text-foreground">
                                        {{ formatNumber(props.memberActivitiesCount || 0) }}
                                    </div>
                                    <div class="mt-1 text-xs text-muted-foreground">Participation records</div>
                                    <div class="mt-3">
                                        <Button
                                            as-child
                                            variant="outline"
                                            size="sm"
                                            class="rounded border-border"
                                        >
                                            <a href="/member-portal/activities">View activities</a>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card class="gap-0 rounded-md border border-border bg-muted/40">
                                <CardHeader class="px-4 pt-4 pb-2">
                                    <CardTitle class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">My Services</CardTitle>
                                </CardHeader>
                                <CardContent class="px-4 pb-4">
                                    <div class="text-2xl font-semibold text-foreground">
                                        {{ formatNumber(props.memberServicesCount || 0) }}
                                    </div>
                                    <div class="mt-1 text-xs text-muted-foreground">Services availed</div>
                                    <div class="mt-3">
                                        <Button
                                            as-child
                                            variant="outline"
                                            size="sm"
                                            class="rounded border-border"
                                        >
                                            <a href="/member-portal/services">View services</a>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </Card>

                    <Card class="gap-0 rounded-lg border border-border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">My Loans</h2>
                                <p class="text-sm text-muted-foreground">Your loan applications and balances.</p>
                            </div>
                            <Button
                                as-child
                                variant="outline"
                                size="sm"
                                class="rounded border-border"
                            >
                                <a href="/member-portal/loans">View loans</a>
                            </Button>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <Card class="gap-0 rounded-md border border-border bg-muted/40">
                                <CardHeader class="px-4 pt-4 pb-2">
                                    <CardTitle class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Total Loans</CardTitle>
                                </CardHeader>
                                <CardContent class="px-4 pb-4">
                                    <div class="text-2xl font-semibold text-foreground">
                                        {{ formatNumber(props.memberLoansCount || 0) }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">All loan records</div>
                                </CardContent>
                            </Card>
                            <Card class="gap-0 rounded-md border border-border bg-muted/40">
                                <CardHeader class="px-4 pt-4 pb-2">
                                    <CardTitle class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Recent Loans</CardTitle>
                                </CardHeader>
                                <CardContent class="px-4 pb-4">
                                    <div v-if="props.memberRecentLoans?.length" class="space-y-2 text-sm text-foreground">
                                        <div v-for="loan in props.memberRecentLoans" :key="loan.id" class="flex items-center justify-between">
                                            <div>
                                                <div class="font-semibold text-foreground">{{ loan.principal }}</div>
                                                <div class="text-xs text-muted-foreground">{{ loan.created_at || 'N/A' }}</div>
                                            </div>
                                            <Badge class="rounded-full bg-primary px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-primary-foreground">
                                                {{ loan.status }}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-muted-foreground">No loans recorded yet.</div>
                                </CardContent>
                            </Card>
                        </div>
                    </Card>

                </div>
                <!-- Cooperative Admin Overview -->
                <div v-if="props.isCoopAdmin && !props.isMember" class="md:col-span-2 grid gap-4">
                    <Card class="gap-0 rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Activity & Training Trends</h2>
                                <p class="text-sm text-slate-500">Members, activities, and trainings over time.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-for="period in trendPeriods"
                                    :key="period.value"
                                    type="button"
                                    size="sm"
                                    :variant="selectedTrendPeriod === period.value ? 'default' : 'outline'"
                                    class="rounded-full text-xs font-semibold uppercase tracking-widest"
                                    @click="updateTrendPeriod(period.value)"
                                >
                                    {{ period.label }}
                                </Button>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 text-xs text-slate-600 md:grid-cols-2 xl:grid-cols-3">
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Members</span>
                                <Select
                                    :model-value="trendFilters.memberStatus"
                                    @update:model-value="(value) => updateTrendFilters({ memberStatus: String(value ?? '') })"
                                >
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="All statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All statuses</SelectItem>
                                        <SelectItem v-for="option in memberStatusOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Activities</span>
                                <Select
                                    :model-value="trendFilters.activityStatus"
                                    @update:model-value="(value) => updateTrendFilters({ activityStatus: String(value ?? '') })"
                                >
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="All statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All statuses</SelectItem>
                                        <SelectItem v-for="option in activityStatusOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Activity Category</span>
                                <Select
                                    :model-value="trendFilters.activityCategory"
                                    @update:model-value="(value) => updateTrendFilters({ activityCategory: String(value ?? '') })"
                                >
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="All categories" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All categories</SelectItem>
                                        <SelectItem v-for="option in activityCategoryOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Trainings</span>
                                <Select
                                    :model-value="trendFilters.trainingStatus"
                                    @update:model-value="(value) => updateTrendFilters({ trainingStatus: String(value ?? '') })"
                                >
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="All statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All statuses</SelectItem>
                                        <SelectItem v-for="option in trainingStatusOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Target Group</span>
                                <Select
                                    :model-value="trendFilters.trainingTargetGroup"
                                    @update:model-value="(value) => updateTrendFilters({ trainingTargetGroup: String(value ?? '') })"
                                >
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="All targets" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All targets</SelectItem>
                                        <SelectItem v-for="option in trainingTargetOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </label>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-500">
                            <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Series</span>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="visibleSeries.members = !visibleSeries.members"
                                :class="[
                                    'flex items-center gap-2 rounded-full px-3 py-1 transition',
                                    visibleSeries.members
                                        ? 'border-emerald-300 bg-emerald-100 text-emerald-800 dark:border-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-200'
                                        : 'border-border bg-card text-muted-foreground',
                                ]"
                            >
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500" />
                                Members
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="visibleSeries.activities = !visibleSeries.activities"
                                :class="[
                                    'flex items-center gap-2 rounded-full px-3 py-1 transition',
                                    visibleSeries.activities
                                        ? 'border-blue-300 bg-blue-100 text-blue-800 dark:border-blue-600 dark:bg-blue-500/20 dark:text-blue-200'
                                        : 'border-border bg-card text-muted-foreground',
                                ]"
                            >
                                <span class="h-2.5 w-2.5 rounded-full bg-blue-500" />
                                Activities
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="visibleSeries.trainings = !visibleSeries.trainings"
                                :class="[
                                    'flex items-center gap-2 rounded-full px-3 py-1 transition',
                                    visibleSeries.trainings
                                        ? 'border-violet-300 bg-violet-100 text-violet-800 dark:border-violet-600 dark:bg-violet-500/20 dark:text-violet-200'
                                        : 'border-border bg-card text-muted-foreground',
                                ]"
                            >
                                <span class="h-2.5 w-2.5 rounded-full bg-violet-500" />
                                Trainings
                            </Button>
                        </div>

                        <div v-if="props.coopTrends" class="mt-6 space-y-4 pb-2">
                            <Card class="mx-auto w-full gap-0 rounded-lg border border-slate-200/70 bg-slate-50/70 py-0 shadow-sm lg:w-1/2">
                                <CardContent class="p-4">
                                    <div v-if="!hasVisibleSeries" class="flex h-44 items-center justify-center text-sm text-slate-500">
                                        Select at least one series to display the chart.
                                    </div>
                                    <svg
                                        v-else
                                        class="h-44 w-full"
                                        :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                                        role="img"
                                        aria-label="Coop activity trends"
                                    >
                                        <g>
                                            <line
                                                v-for="(lineY, index) in chartGridLines"
                                                :key="`grid-${index}`"
                                                :x1="chartPadding"
                                                :x2="chartWidth - chartPadding"
                                                :y1="lineY"
                                                :y2="lineY"
                                                class="stroke-slate-200"
                                            />
                                        </g>
                                        <g>
                                            <rect
                                                v-for="(bar, index) in barPoints"
                                                :key="`bar-${index}`"
                                                :x="bar.x"
                                                :y="bar.y"
                                                :width="bar.width"
                                                :height="bar.height"
                                                :fill="bar.color"
                                                rx="4"
                                            >
                                                <title>{{ bar.label }} · {{ bar.seriesLabel }}: {{ bar.value }}</title>
                                            </rect>
                                        </g>
                                    </svg>
                                </CardContent>
                            </Card>

                            <div class="grid grid-cols-3 gap-2 text-xs text-slate-500 sm:grid-cols-6 lg:grid-cols-12">
                                <span
                                    v-for="(label, index) in trendLabels"
                                    :key="`label-${index}`"
                                    class="truncate"
                                >
                                    {{ label }}
                                </span>
                            </div>
                        </div>

                        <div v-else class="mt-6 text-sm text-slate-500">
                            No trend data available for this cooperative.
                        </div>
                    </Card>

                    <Card class="gap-0 rounded-xl border border-slate-200/70 bg-white/90 py-0 shadow-sm">
                        <div class="border-b border-slate-200/70 p-6">
                            <h2 class="text-lg font-semibold text-slate-900">Recent Members</h2>
                            <p class="text-sm text-slate-500">Latest member updates for your cooperative.</p>
                        </div>
                        <div class="p-6">
                            <div v-if="props.coopMembers?.length" class="space-y-3">
                                <Card
                                    v-for="member in props.coopMembers"
                                    :key="member.id"
                                    class="gap-0 rounded-lg border border-slate-200/80 bg-card py-0 shadow-sm"
                                >
                                    <CardContent class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <div class="font-semibold text-slate-900">{{ member.name }}</div>
                                            <div class="text-xs text-slate-500">{{ member.email || 'No email' }}</div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Badge :class="[getMembershipBadgeColor(member.membership_status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                                {{ member.membership_status || 'N/A' }}
                                            </Badge>
                                            <span class="text-xs text-slate-500">{{ member.date_joined || 'N/A' }}</span>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                            <div v-else class="text-sm text-slate-500">
                                No members found for this cooperative.
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Analytics Panels -->
                <div v-if="!props.isCoopAdmin && !props.isMember" class="grid gap-4 md:col-span-2">
                    <Card class="gap-0 rounded-xl border border-slate-200/70 bg-white/90 py-0 shadow-sm">
                        <div class="border-b border-slate-200/70 p-6">
                            <h2 class="text-lg font-semibold text-slate-900">Analytics Overview</h2>
                            <p class="text-sm text-slate-500">Growth and sector insights in one view.</p>
                        </div>

                        <div class="grid gap-4 p-6 lg:grid-cols-2">
                            <div class="rounded-lg border border-slate-200/70 bg-slate-50/70 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-semibold text-slate-900">Growth Analytics</h3>
                                        <p class="text-sm text-slate-500">Membership and activity trend</p>
                                    </div>

                                    <div class="inline-flex gap-1 rounded-lg bg-muted/55 p-1">
                                        <button
                                            type="button"
                                            class="h-8 rounded-md border px-3 text-xs font-semibold transition-all duration-200"
                                            :class="
                                                growthChartType === 'line'
                                                    ? 'border-primary/30 bg-primary text-primary-foreground shadow-[0_10px_20px_-16px_hsl(var(--primary))]'
                                                    : 'border-border/70 bg-background/80 text-muted-foreground hover:border-border hover:bg-background hover:text-foreground'
                                            "
                                            @click="growthChartType = 'line'"
                                        >
                                            Line
                                        </button>
                                        <button
                                            type="button"
                                            class="h-8 rounded-md border px-3 text-xs font-semibold transition-all duration-200"
                                            :class="
                                                growthChartType === 'bar'
                                                    ? 'border-primary/30 bg-primary text-primary-foreground shadow-[0_10px_20px_-16px_hsl(var(--primary))]'
                                                    : 'border-border/70 bg-background/80 text-muted-foreground hover:border-border hover:bg-background hover:text-foreground'
                                            "
                                            @click="growthChartType = 'bar'"
                                        >
                                            Bar
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div v-if="!systemTrendValues.length" class="flex h-56 items-center justify-center text-sm text-slate-500">
                                        No growth data available.
                                    </div>
                                    <div v-else class="space-y-3">
                                        <svg
                                            class="h-56 w-full"
                                            :viewBox="`0 0 ${systemChartWidth} ${systemChartHeight}`"
                                            role="img"
                                            aria-label="User registration growth trend"
                                        >
                                            <g>
                                                <line
                                                    v-for="(lineY, index) in systemTrendGridLines"
                                                    :key="`system-grid-${index}`"
                                                    :x1="systemChartPaddingX"
                                                    :x2="systemChartWidth - systemChartPaddingX"
                                                    :y1="lineY"
                                                    :y2="lineY"
                                                    class="stroke-slate-200"
                                                />
                                            </g>

                                            <template v-if="growthChartType === 'line'">
                                                <polygon
                                                    :points="systemTrendAreaPoints"
                                                    fill="rgba(59, 130, 246, 0.18)"
                                                />
                                                <polyline
                                                    :points="systemTrendPoints"
                                                    fill="none"
                                                    stroke="#3b82f6"
                                                    stroke-width="3"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                                <circle
                                                    v-for="(point, index) in systemTrendMarkers"
                                                    :key="`system-point-${index}`"
                                                    :cx="point.x"
                                                    :cy="point.y"
                                                    r="4"
                                                    fill="#3b82f6"
                                                >
                                                    <title>{{ systemTrendLabels[index] }}: {{ point.value }}</title>
                                                </circle>
                                            </template>

                                            <template v-else>
                                                <rect
                                                    v-for="(bar, index) in systemTrendBarPoints"
                                                    :key="`system-bar-${index}`"
                                                    :x="bar.x"
                                                    :y="bar.y"
                                                    :width="bar.width"
                                                    :height="bar.height"
                                                    rx="4"
                                                    fill="#3b82f6"
                                                >
                                                    <title>{{ bar.label }}: {{ bar.value }}</title>
                                                </rect>
                                            </template>
                                        </svg>

                                        <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-slate-500 sm:grid-cols-3 lg:grid-cols-6">
                                            <span
                                                v-for="(label, index) in systemTrendLabels"
                                                :key="`system-label-${index}`"
                                                class="truncate"
                                            >
                                                {{ label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg border border-slate-200/70 bg-slate-50/70 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-semibold text-slate-900">Sector Distribution</h3>
                                        <p class="text-sm text-slate-500">Active cooperatives by sector</p>
                                    </div>

                                    <div class="inline-flex gap-1 rounded-lg bg-muted/55 p-1">
                                        <button
                                            type="button"
                                            class="h-8 rounded-md border px-3 text-xs font-semibold transition-all duration-200"
                                            :class="
                                                sectorChartType === 'bars'
                                                    ? 'border-primary/30 bg-primary text-primary-foreground shadow-[0_10px_20px_-16px_hsl(var(--primary))]'
                                                    : 'border-border/70 bg-background/80 text-muted-foreground hover:border-border hover:bg-background hover:text-foreground'
                                            "
                                            @click="sectorChartType = 'bars'"
                                        >
                                            Bars
                                        </button>
                                        <button
                                            type="button"
                                            class="h-8 rounded-md border px-3 text-xs font-semibold transition-all duration-200"
                                            :class="
                                                sectorChartType === 'donut'
                                                    ? 'border-primary/30 bg-primary text-primary-foreground shadow-[0_10px_20px_-16px_hsl(var(--primary))]'
                                                    : 'border-border/70 bg-background/80 text-muted-foreground hover:border-border hover:bg-background hover:text-foreground'
                                            "
                                            @click="sectorChartType = 'donut'"
                                        >
                                            Donut
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div v-if="!sectorBars.length" class="flex h-56 items-center justify-center text-sm text-slate-500">
                                        No sector distribution data available.
                                    </div>

                                    <div v-else-if="sectorChartType === 'bars'" class="space-y-3">
                                        <div
                                            v-for="(bar, index) in sectorBars"
                                            :key="`sector-${bar.label}-${index}`"
                                            class="space-y-1"
                                        >
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-medium text-slate-700">{{ bar.label }}</span>
                                                <span class="text-slate-500">{{ bar.value }}</span>
                                            </div>
                                            <div class="h-2.5 overflow-hidden rounded-full bg-white/70 dark:bg-slate-800/70">
                                                <div
                                                    class="h-full rounded-full bg-linear-to-r from-indigo-500 via-blue-500 to-cyan-500"
                                                    :style="{ width: `${bar.percent}%` }"
                                                />
                                            </div>
                                        </div>

                                        <div class="pt-1 text-xs text-slate-500">
                                            Total cooperatives: {{ sectorTotal }}
                                        </div>
                                    </div>

                                    <div v-else class="grid gap-4 sm:grid-cols-[220px_1fr] sm:items-center">
                                        <div class="mx-auto flex items-center justify-center">
                                            <svg
                                                class="h-48 w-48"
                                                viewBox="0 0 120 120"
                                                role="img"
                                                aria-label="Sector distribution donut chart"
                                            >
                                                <circle cx="60" cy="60" r="42" fill="none" stroke="rgba(148,163,184,0.25)" stroke-width="16" />
                                                <circle
                                                    v-for="(segment, index) in sectorDonutSegments"
                                                    :key="`sector-donut-${segment.label}-${index}`"
                                                    cx="60"
                                                    cy="60"
                                                    r="42"
                                                    fill="none"
                                                    :stroke="segment.color"
                                                    stroke-width="16"
                                                    stroke-linecap="butt"
                                                    pathLength="100"
                                                    :stroke-dasharray="`${segment.dash} ${100 - segment.dash}`"
                                                    :stroke-dashoffset="segment.offset"
                                                    transform="rotate(-90 60 60)"
                                                >
                                                    <title>{{ segment.label }}: {{ segment.value }} ({{ segment.percentage.toFixed(1) }}%)</title>
                                                </circle>
                                            </svg>
                                        </div>

                                        <div class="space-y-2">
                                            <div
                                                v-for="(segment, index) in sectorDonutSegments"
                                                :key="`sector-legend-${segment.label}-${index}`"
                                                class="flex items-center justify-between gap-3 text-xs"
                                            >
                                                <span class="flex items-center gap-2 text-slate-700">
                                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: segment.color }" />
                                                    {{ segment.label }}
                                                </span>
                                                <span class="text-slate-500">{{ segment.value }} ({{ segment.percentage.toFixed(1) }}%)</span>
                                            </div>

                                            <div class="pt-1 text-xs text-slate-500">
                                                Total cooperatives: {{ sectorTotal }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Recent User Registrations -->
                <Card v-if="!props.isCoopAdmin && !props.isMember" class="gap-0 rounded-xl border border-slate-200/70 bg-white/90 py-0 shadow-sm">
                    <div class="border-b border-slate-200/70 p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Recent User Registrations</h2>
                        <p class="text-sm text-slate-500">Latest users added to the system</p>
                    </div>
                    <div class="p-6">
                        <div v-if="recentUsersComputed.length > 0" class="space-y-4">
                            <Card
                                v-for="user in recentUsersComputed"
                                :key="user.id"
                                class="gap-0 rounded-lg border border-slate-200/80 bg-card py-0 shadow-sm"
                            >
                                <CardContent class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                                    <div class="rounded-full bg-slate-900 p-2 dark:bg-slate-100">
                                        <Users class="h-5 w-5 text-white dark:text-slate-900" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-slate-900">{{ user.name }}</p>
                                        <p class="text-sm text-slate-500">{{ user.email }}</p>
                                        <div v-if="user.roles.length > 0" class="mt-1 flex flex-wrap gap-1">
                                            <Badge
                                                v-for="role in user.roles"
                                                :key="role"
                                                :class="[getRoleBadgeColor(role), 'rounded-md px-2 py-0.5 text-xs font-medium']"
                                            >
                                                {{ role }}
                                            </Badge>
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ user.created_at }}</span>
                                </CardContent>
                            </Card>
                        </div>
                        <div v-else class="text-center py-8 text-slate-500">
                            <Users class="h-12 w-12 mx-auto mb-2 opacity-40" />
                            <p>No recent registrations</p>
                        </div>
                    </div>
                </Card>

                <!-- Users by Role Breakdown -->
                <Card v-if="!props.isCoopAdmin && !props.isMember" class="gap-0 rounded-xl border border-slate-200/70 bg-white/90 py-0 shadow-sm">
                    <div class="border-b border-slate-200/70 p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Users by Role</h2>
                        <p class="text-sm text-slate-500">Distribution of users across roles</p>
                    </div>
                    <div class="p-6">
                        <div v-if="usersByRoleComputed.length > 0" class="space-y-4">
                            <Card
                                v-for="roleData in usersByRoleComputed"
                                :key="roleData.name"
                                class="gap-0 rounded-lg border border-slate-200/80 bg-card py-0 shadow-sm"
                            >
                                <CardContent class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="rounded-full bg-slate-900 p-2 dark:bg-slate-100">
                                            <Shield class="h-5 w-5 text-white dark:text-slate-900" />
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ roleData.name }}</p>
                                            <p class="text-sm text-slate-500">
                                                {{ ((roleData.count / (props.stats?.totalUsers || 1)) * 100).toFixed(1) }}% of total
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-semibold text-slate-900">{{ roleData.count }}</p>
                                        <p class="text-xs text-slate-500">users</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                        <div v-else class="text-center py-8 text-slate-500">
                            <Shield class="h-12 w-12 mx-auto mb-2 opacity-40" />
                            <p>No role assignments yet</p>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap');

.dashboard-theme {
    font-family: 'Manrope', 'Segoe UI', sans-serif;
}
</style>

<style scoped>
.dashboard-theme :deep(.bg-white\/90) {
    background-color: var(--card);
}

.dashboard-theme :deep(.border-slate-200\/70),
.dashboard-theme :deep(.border-slate-200\/80),
.dashboard-theme :deep(.border-slate-200) {
    border-color: var(--border);
}

.dashboard-theme :deep(.stroke-slate-200) {
    stroke: var(--border);
}

.dashboard-theme :deep(.bg-slate-50),
.dashboard-theme :deep(.bg-slate-50\/60),
.dashboard-theme :deep(.bg-slate-50\/70) {
    background-color: var(--muted);
}

.dashboard-theme :deep(.text-slate-900) {
    color: var(--foreground);
}

.dashboard-theme :deep(.text-slate-700),
.dashboard-theme :deep(.text-slate-600),
.dashboard-theme :deep(.text-slate-500) {
    color: var(--muted-foreground);
}
</style>
