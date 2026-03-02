<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Users, Building2, Shield, Key, ScrollText, UserCheck, Calendar, Users as MembersIcon } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type DashboardStats = {
    totalUsers: number;
    totalMembers: number;
    totalOffices: number;
    totalCommittees: number;
    totalActivities: number;
    totalCooperatives: number;
    totalRoles: number;
    totalPermissions: number;
    totalActivityLogs: number;
    totalCoopAdmins: number;
};

type RoleSummary = {
    name: string;
    users_count: number;
};

type RecentLog = {
    id: number;
    description: string;
    event?: string | null;
    log_name?: string | null;
    created_at?: string | null;
    causer_name?: string | null;
};

const props = defineProps<{
    stats: DashboardStats;
    roleSummary: RoleSummary[];
    recentLogs: RecentLog[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Super Admin Dashboard',
        href: '/super-admin/dashboard',
    },
];

const statCards = [
    {
        title: 'Total Users',
        value: props.stats.totalUsers,
        icon: Users,
        gradient: 'from-blue-500 to-blue-600',
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
    },
    {
        title: 'Total Members',
        value: props.stats.totalMembers,
        icon: MembersIcon,
        gradient: 'from-emerald-500 to-emerald-600',
        iconBg: 'bg-emerald-100',
        iconColor: 'text-emerald-600',
    },
    {
        title: 'Offices',
        value: props.stats.totalOffices,
        icon: Building2,
        gradient: 'from-blue-500 to-indigo-600',
        iconBg: 'bg-indigo-100',
        iconColor: 'text-indigo-600',
    },
    {
        title: 'Committees',
        value: props.stats.totalCommittees,
        icon: Shield,
        gradient: 'from-orange-500 to-orange-600',
        iconBg: 'bg-orange-100',
        iconColor: 'text-orange-600',
    },
    {
        title: 'Activities',
        value: props.stats.totalActivities,
        icon: Calendar,
        gradient: 'from-purple-500 to-purple-600',
        iconBg: 'bg-purple-100',
        iconColor: 'text-purple-600',
    },
    {
        title: 'Activity Logs',
        value: props.stats.totalActivityLogs,
        icon: ScrollText,
        gradient: 'from-cyan-500 to-cyan-600',
        iconBg: 'bg-cyan-100',
        iconColor: 'text-cyan-600',
    },
];
</script>

<template>
    <Head title="Super Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">

            <!-- Welcome Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-slate-900 px-8 py-7 text-white shadow-xl">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(99,102,241,0.25),transparent_60%)]"></div>
                <div class="absolute right-0 bottom-0 opacity-5">
                    <svg width="320" height="160" viewBox="0 0 320 160" fill="none"><circle cx="270" cy="80" r="120" stroke="white" stroke-width="1"/><circle cx="270" cy="80" r="80" stroke="white" stroke-width="1"/><circle cx="270" cy="80" r="40" stroke="white" stroke-width="1"/></svg>
                </div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="h-5 w-1 rounded-full bg-indigo-500 inline-block"></span>
                            <span class="text-indigo-400 text-xs font-semibold uppercase tracking-widest">Super Admin</span>
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight mb-1">Welcome back to PICTO COOP</h1>
                        <p class="text-slate-400 text-sm">Manage your cooperative system — users, members, offices, and more.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-xs text-slate-500 font-medium">System Status</p>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-xs text-emerald-400 font-semibold">All systems operational</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="card in statCards"
                    :key="card.title"
                    class="group relative overflow-hidden rounded-xl bg-white border border-slate-200 shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5"
                >
                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">
                                    {{ card.title }}
                                </p>
                                <p class="text-3xl font-bold text-slate-900">
                                    {{ card.value }}
                                </p>
                            </div>
                            <div class="rounded-xl p-2.5 transition-transform group-hover:scale-110" :class="card.iconBg">
                                <component :is="card.icon" class="w-5 h-5" :class="card.iconColor" />
                            </div>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r" :class="card.gradient"></div>
                </article>
            </div>

            <!-- Role Distribution & Recent Logs -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Role Distribution -->
                <section class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-indigo-50">
                            <Shield class="w-4 h-4 text-indigo-600" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Role Distribution</h2>
                            <p class="text-xs text-slate-500">User roles across the system</p>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="space-y-2" v-if="roleSummary.length">
                            <div
                                v-for="(role, index) in roleSummary"
                                :key="role.name"
                                class="flex items-center justify-between px-4 py-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-md bg-indigo-600 flex items-center justify-center text-white text-xs font-bold shadow">
                                        {{ index + 1 }}
                                    </div>
                                    <span class="text-sm font-medium text-slate-700 capitalize">
                                        {{ role.name.replaceAll('_', ' ') }}
                                    </span>
                                </div>
                                <span class="text-sm font-semibold text-slate-500">{{ role.users_count }} {{ role.users_count === 1 ? 'user' : 'users' }}</span>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-10 text-slate-400">
                            <Shield class="w-10 h-10 mb-2 opacity-30" />
                            <p class="text-xs font-medium">No roles available yet</p>
                        </div>
                    </div>
                </section>

                <!-- Recent System Logs -->
                <section class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-lg bg-emerald-50">
                                <ScrollText class="w-4 h-4 text-emerald-600" />
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900">Recent Activity</h2>
                                <p class="text-xs text-slate-500">Latest system events</p>
                            </div>
                        </div>
                        <a href="/super-admin/logs" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                            View all →
                        </a>
                    </div>
                    <div class="p-5">
                        <div class="space-y-2" v-if="recentLogs.length">
                            <article
                                v-for="log in recentLogs"
                                :key="log.id"
                                class="flex gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors group"
                            >
                                <div class="mt-1.5 h-2 w-2 rounded-full bg-indigo-400 shrink-0 group-hover:bg-indigo-600 transition-colors"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-slate-800 leading-relaxed truncate">{{ log.description }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] px-1.5 py-0.5 bg-indigo-50 text-indigo-600 rounded font-medium">
                                            {{ log.causer_name ?? 'System' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400">{{ log.created_at ?? '' }}</span>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-10 text-slate-400">
                            <ScrollText class="w-10 h-10 mb-2 opacity-30" />
                            <p class="text-xs font-medium">No activity logs yet</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
