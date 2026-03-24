<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { 
    Users, 
    TrendingUp, 
    DollarSign, 
    FileText,
    Activity,
    ArrowUpRight,
    ArrowDownRight,
    Shield,
    UserCog
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { computed, ref } from 'vue';

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

const props = defineProps<{
    stats: Stats;
    usersByRole: UserByRole[];
    recentUsers: RecentUser[];
    isCoopAdmin: boolean;
    isMember: boolean;
    coopInfo: CoopInfo | null;
    coopStats: CoopStats | null;
    coopMembers: CoopMember[];
    coopTrends: CoopTrends | null;
    memberProfile: MemberProfile | null;
    memberCoop: MemberCoop | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const dashboardStats = computed(() => [
    {
        title: 'Total Users',
        value: props.stats.totalUsers.toString(),
        change: `+${props.stats.usersGrowth}%`,
        trend: 'up',
        icon: Users,
        color: 'text-blue-600',
        bgColor: 'bg-blue-50',
    },
    {
        title: 'Users with Roles',
        value: props.stats.usersWithRoles.toString(),
        change: `${((props.stats.usersWithRoles / props.stats.totalUsers) * 100).toFixed(1)}%`,
        trend: 'up',
        icon: UserCog,
        color: 'text-green-600',
        bgColor: 'bg-green-50',
    },
    {
        title: 'Total Roles',
        value: props.stats.totalRoles.toString(),
        change: 'Active',
        trend: 'up',
        icon: Shield,
        color: 'text-emerald-600',
        bgColor: 'bg-emerald-50',
    },
    {
        title: 'Weekly Growth',
        value: `${props.stats.usersGrowth}%`,
        change: props.stats.usersGrowth > 0 ? `+${props.stats.usersGrowth}%` : `${props.stats.usersGrowth}%`,
        trend: props.stats.usersGrowth >= 0 ? 'up' : 'down',
        icon: TrendingUp,
        color: 'text-purple-600',
        bgColor: 'bg-purple-50',
    },
]);

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

const applyTrendQuery = (query: Record<string, string>) => {
    router.get(
        dashboard({
            query,
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

const getRoleBadgeColor = (roleName: string) => {
    const colors: Record<string, string> = {
        'Provincial Admin': 'bg-red-100 text-red-800',
        'Coop Admin': 'bg-orange-100 text-orange-800',
        'Officer': 'bg-blue-100 text-blue-800',
        'Committee Member': 'bg-green-100 text-green-800',
        'Member': 'bg-purple-100 text-purple-800',
        'Viewer': 'bg-gray-100 text-gray-800',
    };
    return colors[roleName] || 'bg-gray-100 text-gray-800';
};

const getMembershipBadgeColor = (status: string | null) => {
    const colors: Record<string, string> = {
        'Active': 'bg-green-100 text-green-800',
        'Suspended': 'bg-orange-100 text-orange-800',
        'Resigned': 'bg-red-100 text-red-800',
        'Deceased': 'bg-gray-100 text-gray-800',
    };
    return colors[status || ''] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Dashboard - Coop System" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="relative flex h-full flex-1 flex-col gap-6 overflow-hidden p-6">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,rgba(14,64,120,0.08),transparent_60%),linear-gradient(180deg,rgba(15,23,42,0.03),rgba(15,23,42,0))]" />

            <!-- Header -->
            <div class="rounded-xl border border-slate-200/70 bg-white/90 p-5 shadow-[0_6px_24px_rgba(15,23,42,0.08)] backdrop-blur">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-xl font-semibold tracking-tight text-slate-900 md:text-2xl">
                            {{ props.isMember
                                ? 'Member Dashboard'
                                : props.isCoopAdmin
                                    ? (props.coopInfo?.name || 'Assigned Cooperative')
                                    : 'System Dashboard' }}
                        </h1>
                        <p class="text-sm text-slate-600">
                            {{ props.isMember
                                ? 'Your membership profile and cooperative information.'
                                : props.isCoopAdmin
                                    ? 'Your cooperative overview and latest member updates.'
                                    : 'Real-time cooperative oversight and operational monitoring.' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3 rounded-lg border border-slate-200/80 bg-slate-50 px-3 py-2">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900">
                            <Activity class="h-4 w-4 text-white" />
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Status</div>
                            <div class="text-sm font-semibold text-slate-900">Operational</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coop Admin Stat Boxes -->
            <div v-if="props.isCoopAdmin" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-if="props.coopStats"
                    class="group rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_10px_30px_rgba(15,23,42,0.12)]"
                >
                    <div class="flex items-center justify-between">
                        <div class="rounded-lg bg-slate-50 p-3 transition-colors group-hover:bg-slate-900/10">
                            <Users class="h-6 w-6 text-slate-700" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-500">Total Members</h3>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ props.coopStats.totalMembers }}</p>
                    </div>
                </div>
                <div
                    v-if="props.coopStats"
                    class="group rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_10px_30px_rgba(15,23,42,0.12)]"
                >
                    <div class="flex items-center justify-between">
                        <div class="rounded-lg bg-emerald-50 p-3 transition-colors group-hover:bg-slate-900/10">
                            <UserCog class="h-6 w-6 text-emerald-600" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Active Members</h3>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-emerald-700">{{ props.coopStats.activeMembers }}</p>
                    </div>
                </div>
                <div
                    v-if="props.coopStats"
                    class="group rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_10px_30px_rgba(15,23,42,0.12)]"
                >
                    <div class="flex items-center justify-between">
                        <div class="rounded-lg bg-blue-50 p-3 transition-colors group-hover:bg-slate-900/10">
                            <FileText class="h-6 w-6 text-blue-600" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-blue-700">Activities</h3>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-blue-700">{{ props.coopStats.totalActivities }}</p>
                    </div>
                </div>
                <div
                    v-if="props.coopStats"
                    class="group rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_10px_30px_rgba(15,23,42,0.12)]"
                >
                    <div class="flex items-center justify-between">
                        <div class="rounded-lg bg-violet-50 p-3 transition-colors group-hover:bg-slate-900/10">
                            <Activity class="h-6 w-6 text-violet-600" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-violet-700">Trainings</h3>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-violet-700">{{ props.coopStats.totalTrainings }}</p>
                    </div>
                </div>
                <div v-if="!props.coopStats" class="text-sm text-slate-500">No stats available.</div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4" v-if="!props.isCoopAdmin && !props.isMember">
                <div
                    v-for="stat in dashboardStats"
                    :key="stat.title"
                    class="group rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_10px_30px_rgba(15,23,42,0.12)]"
                >
                    <div class="flex items-center justify-between">
                        <div :class="[stat.bgColor, 'rounded-lg p-3 transition-colors group-hover:bg-slate-900/10']">
                            <component 
                                :is="stat.icon" 
                                :class="[stat.color, 'h-6 w-6']"
                            />
                        </div>
                        <div 
                            v-if="stat.change !== 'Active'"
                            :class="[
                                'flex items-center gap-1 text-sm font-medium',
                                stat.trend === 'up' ? 'text-green-600' : 'text-red-600'
                            ]"
                        >
                            <component 
                                :is="stat.trend === 'up' ? ArrowUpRight : ArrowDownRight"
                                class="h-4 w-4"
                            />
                            {{ stat.change }}
                        </div>
                        <div v-else class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                            {{ stat.change }}
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-500">
                            {{ stat.title }}
                        </h3>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                            {{ stat.value }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Member Dashboard -->
                <div v-if="props.isMember" class="md:col-span-2 grid gap-4">
                    <div class="rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">My Membership Profile</h2>
                                <p class="text-sm text-slate-500">Summary of your membership record.</p>
                            </div>
                            <div class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-white">
                                {{ props.memberProfile?.membership_status || 'N/A' }}
                            </div>
                        </div>

                        <div v-if="props.memberProfile" class="mt-6 grid gap-4 md:grid-cols-2">
                            <div class="rounded-lg border border-slate-200/70 bg-slate-50/60 p-4">
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Member</div>
                                <div class="mt-2 space-y-1 text-sm text-slate-700">
                                    <div><strong>Name:</strong> {{ props.memberProfile.name }}</div>
                                    <div><strong>Email:</strong> {{ props.memberProfile.email || 'N/A' }}</div>
                                    <div><strong>Phone:</strong> {{ props.memberProfile.phone || 'N/A' }}</div>
                                    <div><strong>Date Joined:</strong> {{ props.memberProfile.date_joined || 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="rounded-lg border border-slate-200/70 bg-slate-50/60 p-4">
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Membership</div>
                                <div class="mt-2 space-y-1 text-sm text-slate-700">
                                    <div><strong>Type:</strong> {{ props.memberProfile.membership_type || 'N/A' }}</div>
                                    <div><strong>Sector:</strong> {{ props.memberProfile.sector || 'N/A' }}</div>
                                    <div><strong>Status:</strong> {{ props.memberProfile.membership_status || 'N/A' }}</div>
                                    <div>
                                        <strong>Location:</strong>
                                        {{ props.memberProfile.city_municipality || 'N/A' }}
                                        {{ props.memberProfile.province ? `, ${props.memberProfile.province}` : '' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">My Cooperative</h2>
                                <p class="text-sm text-slate-500">Cooperative assignment details.</p>
                            </div>
                        </div>

                        <div v-if="props.memberCoop" class="mt-6 grid gap-4 md:grid-cols-2">
                            <div class="rounded-lg border border-slate-200/70 bg-slate-50/60 p-4">
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Cooperative</div>
                                <div class="mt-2 space-y-1 text-sm text-slate-700">
                                    <div><strong>Name:</strong> {{ props.memberCoop.name }}</div>
                                    <div>
                                        <strong>Location:</strong>
                                        {{ props.memberCoop.city_municipality || 'N/A' }}
                                        {{ props.memberCoop.province ? `, ${props.memberCoop.province}` : '' }}
                                    </div>
                                    <div><strong>Status:</strong> {{ props.memberCoop.status || 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="rounded-lg border border-slate-200/70 bg-slate-50/60 p-4">
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Actions</div>
                                <div class="mt-2 text-sm text-slate-700">
                                    Use the My Profile page to update your personal information.
                                </div>
                            </div>
                        </div>

                        <div v-else class="mt-6 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            No cooperative is assigned to your account yet. Please contact your cooperative admin.
                        </div>
                    </div>
                </div>
                <!-- Cooperative Admin Overview -->
                <div v-if="props.isCoopAdmin" class="md:col-span-2 grid gap-4">
                    <div class="rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Activity & Training Trends</h2>
                                <p class="text-sm text-slate-500">Members, activities, and trainings over time.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="period in trendPeriods"
                                    :key="period.value"
                                    type="button"
                                    @click="updateTrendPeriod(period.value)"
                                    :class="[
                                        'rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-widest transition',
                                        selectedTrendPeriod === period.value
                                            ? 'bg-slate-900 text-white'
                                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                                    ]"
                                >
                                    {{ period.label }}
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 text-xs text-slate-600 md:grid-cols-2 xl:grid-cols-3">
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Members</span>
                                <select
                                    :value="trendFilters.memberStatus"
                                    @change="updateTrendFilters({ memberStatus: ($event.target as HTMLSelectElement).value })"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"
                                >
                                    <option value="">All statuses</option>
                                    <option v-for="option in memberStatusOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Activities</span>
                                <select
                                    :value="trendFilters.activityStatus"
                                    @change="updateTrendFilters({ activityStatus: ($event.target as HTMLSelectElement).value })"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"
                                >
                                    <option value="">All statuses</option>
                                    <option v-for="option in activityStatusOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Activity Category</span>
                                <select
                                    :value="trendFilters.activityCategory"
                                    @change="updateTrendFilters({ activityCategory: ($event.target as HTMLSelectElement).value })"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"
                                >
                                    <option value="">All categories</option>
                                    <option v-for="option in activityCategoryOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Trainings</span>
                                <select
                                    :value="trendFilters.trainingStatus"
                                    @change="updateTrendFilters({ trainingStatus: ($event.target as HTMLSelectElement).value })"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"
                                >
                                    <option value="">All statuses</option>
                                    <option v-for="option in trainingStatusOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Target Group</span>
                                <select
                                    :value="trendFilters.trainingTargetGroup"
                                    @change="updateTrendFilters({ trainingTargetGroup: ($event.target as HTMLSelectElement).value })"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"
                                >
                                    <option value="">All targets</option>
                                    <option v-for="option in trainingTargetOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </label>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-500">
                            <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Series</span>
                            <button
                                type="button"
                                @click="visibleSeries.members = !visibleSeries.members"
                                :class="[
                                    'flex items-center gap-2 rounded-full border px-3 py-1 transition',
                                    visibleSeries.members
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                        : 'border-slate-200 bg-white text-slate-500',
                                ]"
                            >
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500" />
                                Members
                            </button>
                            <button
                                type="button"
                                @click="visibleSeries.activities = !visibleSeries.activities"
                                :class="[
                                    'flex items-center gap-2 rounded-full border px-3 py-1 transition',
                                    visibleSeries.activities
                                        ? 'border-blue-200 bg-blue-50 text-blue-700'
                                        : 'border-slate-200 bg-white text-slate-500',
                                ]"
                            >
                                <span class="h-2.5 w-2.5 rounded-full bg-blue-500" />
                                Activities
                            </button>
                            <button
                                type="button"
                                @click="visibleSeries.trainings = !visibleSeries.trainings"
                                :class="[
                                    'flex items-center gap-2 rounded-full border px-3 py-1 transition',
                                    visibleSeries.trainings
                                        ? 'border-violet-200 bg-violet-50 text-violet-700'
                                        : 'border-slate-200 bg-white text-slate-500',
                                ]"
                            >
                                <span class="h-2.5 w-2.5 rounded-full bg-violet-500" />
                                Trainings
                            </button>
                        </div>

                        <div v-if="props.coopTrends" class="mt-6 space-y-4">
                            <div class="mx-auto w-full rounded-lg border border-slate-200/70 bg-slate-50/70 p-4 lg:w-1/2">
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
                            </div>

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
                    </div>

                    <div class="rounded-xl border border-slate-200/70 bg-white/90 shadow-sm">
                        <div class="border-b border-slate-200/70 p-6">
                            <h2 class="text-lg font-semibold text-slate-900">Recent Members</h2>
                            <p class="text-sm text-slate-500">Latest member updates for your cooperative.</p>
                        </div>
                        <div class="p-6">
                            <div v-if="props.coopMembers.length" class="space-y-3">
                                <div
                                    v-for="member in props.coopMembers"
                                    :key="member.id"
                                    class="flex flex-col gap-2 rounded-lg border border-slate-200/80 bg-white p-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ member.name }}</div>
                                        <div class="text-xs text-slate-500">{{ member.email || 'No email' }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span :class="[getMembershipBadgeColor(member.membership_status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                            {{ member.membership_status || 'N/A' }}
                                        </span>
                                        <span class="text-xs text-slate-500">{{ member.date_joined || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-sm text-slate-500">
                                No members found for this cooperative.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Panels -->
                <div v-if="!props.isCoopAdmin && !props.isMember" class="grid gap-4 md:col-span-2">
                    <div class="rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Growth Analytics</h2>
                                <p class="text-sm text-slate-500">Membership and activity trend</p>
                            </div>
                            <div class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-white">
                                Weekly
                            </div>
                        </div>
                        <div class="mt-6 h-56 rounded-lg border border-dashed border-slate-200 bg-slate-50/70" />
                    </div>
                    <div class="rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Sector Distribution</h2>
                                <p class="text-sm text-slate-500">Active cooperatives by sector</p>
                            </div>
                            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-slate-600">
                                Updated
                            </div>
                        </div>
                        <div class="mt-6 h-56 rounded-lg border border-dashed border-slate-200 bg-slate-50/70" />
                    </div>
                </div>

                <!-- Recent User Registrations -->
                <div v-if="!props.isCoopAdmin && !props.isMember" class="rounded-xl border border-slate-200/70 bg-white/90 shadow-sm">
                    <div class="border-b border-slate-200/70 p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Recent User Registrations</h2>
                        <p class="text-sm text-slate-500">Latest users added to the system</p>
                    </div>
                    <div class="p-6">
                        <div v-if="recentUsers.length > 0" class="space-y-4">
                            <div
                                v-for="user in recentUsers"
                                :key="user.id"
                                class="flex flex-col gap-4 rounded-lg border border-slate-200/80 bg-white p-4 sm:flex-row sm:items-center"
                            >
                                <div class="rounded-full bg-slate-900 p-2">
                                    <Users class="h-5 w-5 text-white" />
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-slate-900">{{ user.name }}</p>
                                    <p class="text-sm text-slate-500">{{ user.email }}</p>
                                    <div v-if="user.roles.length > 0" class="mt-1 flex flex-wrap gap-1">
                                        <span
                                            v-for="role in user.roles"
                                            :key="role"
                                            :class="[getRoleBadgeColor(role), 'rounded-md px-2 py-0.5 text-xs font-medium']"
                                        >
                                            {{ role }}
                                        </span>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ user.created_at }}</span>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-slate-500">
                            <Users class="h-12 w-12 mx-auto mb-2 opacity-40" />
                            <p>No recent registrations</p>
                        </div>
                    </div>
                </div>

                <!-- Users by Role Breakdown -->
                <div v-if="!props.isCoopAdmin && !props.isMember" class="rounded-xl border border-slate-200/70 bg-white/90 shadow-sm">
                    <div class="border-b border-slate-200/70 p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Users by Role</h2>
                        <p class="text-sm text-slate-500">Distribution of users across roles</p>
                    </div>
                    <div class="p-6">
                        <div v-if="usersByRole.length > 0" class="space-y-4">
                            <div
                                v-for="roleData in usersByRole"
                                :key="roleData.name"
                                class="flex flex-col gap-4 rounded-lg border border-slate-200/80 bg-white p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="rounded-full bg-slate-900 p-2">
                                        <Shield class="h-5 w-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ roleData.name }}</p>
                                        <p class="text-sm text-slate-500">
                                            {{ ((roleData.count / stats.totalUsers) * 100).toFixed(1) }}% of total
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-semibold text-slate-900">{{ roleData.count }}</p>
                                    <p class="text-xs text-slate-500">users</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-slate-500">
                            <Shield class="h-12 w-12 mx-auto mb-2 opacity-40" />
                            <p>No role assignments yet</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
