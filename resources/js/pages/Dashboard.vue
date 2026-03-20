<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
import { computed } from 'vue';

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
                <div v-if="props.isCoopAdmin" class="rounded-xl border border-slate-200/70 bg-white/90 shadow-sm">
                    <div class="border-b border-slate-200/70 p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Cooperative Command Center</h2>
                        <p class="text-sm text-slate-500">
                            {{ props.coopInfo?.name || 'Assigned Cooperative' }}
                        </p>
                    </div>
                    <div class="p-6">
                        <div v-if="!props.coopInfo" class="rounded-md border border-dashed border-orange-200 bg-orange-50 p-3 text-sm text-orange-700">
                            No cooperative is assigned to this account yet. Please set a cooperative in User Management.
                        </div>
                        <div v-if="props.coopStats" class="grid gap-4 sm:grid-cols-3">
                            <div class="rounded-lg border border-slate-200/80 bg-slate-50/70 p-4">
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Total Members</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ props.coopStats.totalMembers }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-emerald-50/60 p-4">
                                <div class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Active Members</div>
                                <div class="mt-2 text-2xl font-semibold text-emerald-700">{{ props.coopStats.activeMembers }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200/80 bg-amber-50/60 p-4">
                                <div class="text-xs font-semibold uppercase tracking-widest text-amber-700">Inactive Members</div>
                                <div class="mt-2 text-2xl font-semibold text-amber-700">{{ props.coopStats.inactiveMembers }}</div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-widest text-slate-500">Recent Members</div>
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
