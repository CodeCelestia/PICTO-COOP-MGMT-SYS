<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { User, FileText, Edit, Mail, Phone, MapPin, Calendar, Building2, AlertCircle, CheckCircle2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface PDS {
    id: number;
    first_name: string;
    middle_name?: string;
    last_name: string;
    email: string;
    phone_number?: string;
    date_of_birth?: string;
    gender?: string;
    civil_status?: string;
    city_municipality_name?: string;
    province_name?: string;
    is_complete: boolean;
    completion_percentage: number;
}

interface Office {
    id: number;
    name: string;
    code: string;
    location?: string;
}

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        roles: string[];
    };
    pds: PDS | null;
    office: Office | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/member/dashboard' },
];

const fullName = props.pds 
    ? `${props.pds.first_name} ${props.pds.middle_name || ''} ${props.pds.last_name}`.replace(/\s+/g, ' ').trim()
    : props.user.name;

const getStatusColor = (percentage: number) => {
    if (percentage >= 80) return { bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'ring-emerald-200', icon: 'text-emerald-600' };
    if (percentage >= 50) return { bg: 'bg-amber-50', text: 'text-amber-700', ring: 'ring-amber-200', icon: 'text-amber-600' };
    return { bg: 'bg-red-50', text: 'text-red-700', ring: 'ring-red-200', icon: 'text-red-600' };
};

const getPDSStatus = () => {
    if (!props.pds) return { label: 'Not Set', color: getStatusColor(0) };
    if (props.pds.is_complete) return { label: 'Complete', color: getStatusColor(100) };
    return { label: `${props.pds.completion_percentage}% Complete`, color: getStatusColor(props.pds.completion_percentage) };
};

const pdsStatus = getPDSStatus();
</script>

<template>
    <Head title="My Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            
            <!-- Welcome Header -->
            <div class="rounded-2xl bg-linear-to-r from-blue-600 to-indigo-600 px-8 py-6 text-white shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-white/20">
                        <User class="h-8 w-8" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Welcome, {{ fullName }}!</h1>
                        <p class="text-sm text-blue-200">Member Dashboard</p>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div :class="['rounded-lg p-3', pdsStatus.color.bg]">
                            <component :is="pds?.is_complete ? CheckCircle2 : AlertCircle" :class="['h-6 w-6', pdsStatus.color.icon]" />
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">PDS Status</p>
                            <p class="text-lg font-bold text-slate-800">{{ pdsStatus.label }}</p>
                            <p v-if="pds && !pds.is_complete" class="text-xs text-slate-500 mt-0.5">Update your PDS</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-indigo-50 p-3">
                            <Building2 class="h-6 w-6 text-indigo-600" />
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Office</p>
                            <p class="text-sm font-semibold text-slate-800">{{ office?.name || 'Not Assigned' }}</p>
                            <p v-if="office" class="text-xs text-slate-500 mt-0.5">{{ office.code }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-blue-50 p-3">
                            <Mail class="h-6 w-6 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Email</p>
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ user.email }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-purple-50 p-3">
                            <User class="h-6 w-6 text-purple-600" />
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">System Access Role</p>
                            <p class="text-sm font-bold text-slate-800 capitalize">
                                {{ user.roles[0]?.replace(/_/g, ' ') || 'Member' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PDS Information Card -->
            <div v-if="pds" class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">My Personal Data Sheet</h2>
                            <p class="text-sm text-slate-500">View and manage your information</p>
                        </div>
                        <div class="flex gap-2">
                            <Link :href="`/member/my-pds`">
                                <Button variant="outline" size="sm" class="gap-2">
                                    <FileText class="h-4 w-4" /> View Full PDS
                                </Button>
                            </Link>
                            <Link :href="`/member/my-pds/edit`">
                                <Button size="sm" class="gap-2 bg-indigo-600 hover:bg-indigo-700 text-white">
                                    <Edit class="h-4 w-4" /> Edit PDS
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <Mail class="h-5 w-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Email Address</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ pds.email }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <Phone class="h-5 w-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Phone Number</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ pds.phone_number || '—' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <Calendar class="h-5 w-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Date of Birth</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ pds.date_of_birth || '—' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <User class="h-5 w-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Gender</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ pds.gender || '—' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <User class="h-5 w-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Civil Status</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ pds.civil_status || '—' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <MapPin class="h-5 w-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Address</p>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ [pds.city_municipality_name, pds.province_name].filter(Boolean).join(', ') || '—' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No PDS Warning -->
            <div v-else class="rounded-xl border border-amber-200 bg-amber-50 p-6">
                <div class="flex items-start gap-4">
                    <div class="rounded-lg bg-amber-500 p-2 text-white">
                        <FileText class="h-6 w-6" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-amber-900">No PDS Record Found</h3>
                        <p class="text-sm text-amber-700 mt-1">
                            Your account is not linked to a Personal Data Sheet. Please contact your administrator to have one created for you.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
