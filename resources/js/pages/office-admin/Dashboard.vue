<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Users, FileText, Edit, Mail, Phone, MapPin, Calendar, CheckCircle2, XCircle, Shield, Briefcase } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Office {
    id: number;
    name: string;
    code: string;
    cooperative_type?: string;
    status: string;
    contact_email?: string;
    contact_phone?: string;
    city_municipality_name?: string;
    province_name?: string;
    region_name?: string;
    registration_number?: string;
    date_registered?: string;
    chairperson?: string;
    general_manager?: string;
}

interface Stats {
    total_members: number;
    total_offices: number;
    status: string;
}

interface Member {
    id: number;
    name: string;
    email: string;
    roles: string[];
    created_at: string;
}

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        system_roles: string[];
        office_position: {
            id: number;
            name: string;
            display_name: string;
        } | null;
    };
    office: Office | null;
    stats: Stats | null;
    recentMembers: Member[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/office-admin/dashboard' },
];

const statusColor = (status: string) => {
    const colors: Record<string, string> = {
        'active': 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'inactive': 'bg-slate-50 text-slate-700 ring-slate-200',
        'pending': 'bg-amber-50 text-amber-700 ring-amber-200',
    };
    return colors[status] || 'bg-slate-50 text-slate-700 ring-slate-200';
};
</script>

<template>
    <Head title="Office Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            
            <!-- Welcome Header -->
            <div v-if="office" class="rounded-2xl bg-linear-to-r from-indigo-600 to-purple-600 px-8 py-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-white/20">
                            <Building2 class="h-8 w-8" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">{{ office.name }}</h1>
                            <p class="text-sm text-indigo-200">{{ office.code }} • Office Admin Dashboard</p>
                        </div>
                    </div>
                    <Link :href="`/office-admin/profile`">
                        <Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2">
                            <Edit class="h-4 w-4" /> Manage Profile
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Role Explanation Banner -->
            <div v-if="user.system_roles.length > 0 && user.office_position" class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3">
                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-blue-500 p-1 text-white mt-0.5">
                        <Shield class="h-4 w-4" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-blue-900">Your Roles Explained</p>
                        <p class="text-xs text-blue-700 mt-1">
                            You have <strong>System Access</strong>: <span class="capitalize font-semibold">{{ user.system_roles[0].replace(/_/g, ' ') }}</span> 
                            (controls what dashboards/features you can access) 
                            AND 
                            <strong>Office Position</strong>: <span class="font-semibold">{{ user.office_position.display_name }}</span>
                            (your job title within {{ office?.name || 'this office' }}).
                        </p>
                    </div>
                </div>
            </div>

            <!-- No Office Warning -->
            <div v-else class="rounded-xl border border-amber-200 bg-amber-50 p-6">
                <div class="flex items-start gap-4">
                    <div class="rounded-lg bg-amber-500 p-2 text-white">
                        <Building2 class="h-6 w-6" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-amber-900">No Office Assigned</h3>
                        <p class="text-sm text-amber-700 mt-1">
                            Your account is not assigned to any office. Please contact your system administrator.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div v-if="stats" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-blue-50 p-3">
                            <Users class="h-6 w-6 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Total Members</p>
                            <p class="text-2xl font-bold text-slate-800">{{ stats.total_members }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-purple-50 p-3">
                            <Building2 class="h-6 w-6 text-purple-600" />
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Office Status</p>
                            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset mt-1', statusColor(stats.status)]">
                                <CheckCircle2 v-if="stats.status === 'active'" class="w-3 h-3" />
                                <XCircle v-else class="w-3 h-3" />
                                {{ stats.status.toUpperCase() }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-emerald-50 p-3">
                            <Shield class="h-6 w-6 text-emerald-600" />
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">System Access</p>
                            <p class="text-sm font-bold text-slate-800 capitalize">
                                {{ user.system_roles[0]?.replace(/_/g, ' ') || 'None' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-indigo-50 p-3">
                            <Briefcase class="h-6 w-6 text-indigo-600" />
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Office Position</p>
                            <p class="text-sm font-bold text-slate-800">
                                {{ user.office_position?.display_name || 'Not Assigned' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Office Information -->
            <div v-if="office" class="grid gap-6 md:grid-cols-2">
                <!-- Office Details -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h2 class="text-lg font-bold text-slate-800">Office Details</h2>
                        <p class="text-sm text-slate-500">Basic information about your cooperative</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-start gap-3">
                            <Building2 class="h-5 w-5 text-slate-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-medium text-slate-500">Cooperative Type</p>
                                <p class="text-sm font-semibold text-slate-800">{{ office.cooperative_type || '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <FileText class="h-5 w-5 text-slate-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-medium text-slate-500">Registration Number</p>
                                <p class="text-sm font-semibold text-slate-800">{{ office.registration_number || '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Calendar class="h-5 w-5 text-slate-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-medium text-slate-500">Date Registered</p>
                                <p class="text-sm font-semibold text-slate-800">{{ office.date_registered || '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <MapPin class="h-5 w-5 text-slate-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-medium text-slate-500">Location</p>
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ [office.city_municipality_name, office.province_name, office.region_name].filter(Boolean).join(', ') || '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact & Leadership -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h2 class="text-lg font-bold text-slate-800">Contact & Leadership</h2>
                        <p class="text-sm text-slate-500">Contact information and key personnel</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-start gap-3">
                            <Mail class="h-5 w-5 text-slate-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-medium text-slate-500">Contact Email</p>
                                <p class="text-sm font-semibold text-slate-800">{{ office.contact_email || '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Phone class="h-5 w-5 text-slate-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-medium text-slate-500">Contact Phone</p>
                                <p class="text-sm font-semibold text-slate-800">{{ office.contact_phone || '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Users class="h-5 w-5 text-slate-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-medium text-slate-500">Chairperson</p>
                                <p class="text-sm font-semibold text-slate-800">{{ office.chairperson || '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Users class="h-5 w-5 text-slate-400 mt-0.5" />
                            <div>
                                <p class="text-xs font-medium text-slate-500">General Manager</p>
                                <p class="text-sm font-semibold text-slate-800">{{ office.general_manager || '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Members -->
            <div v-if="recentMembers && recentMembers.length > 0" class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-bold text-slate-800">Recent Members</h2>
                    <p class="text-sm text-slate-500">Latest members added to your office</p>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="member in recentMembers" :key="member.id" class="flex items-center justify-between px-6 py-4 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-sm font-bold">
                                {{ member.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ member.name }}</p>
                                <p class="text-xs text-slate-500">{{ member.email }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500">{{ member.created_at }}</p>
                            <p class="text-xs font-medium text-slate-600 capitalize">{{ member.roles[0]?.replace(/_/g, ' ') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
