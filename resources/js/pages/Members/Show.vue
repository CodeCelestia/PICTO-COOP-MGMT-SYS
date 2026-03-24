<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { ArrowLeft, Pencil, User, MapPin, Briefcase, Calendar, ClipboardList, Users, CheckCircle2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface Cooperative {
    id: number;
    name: string;
}

interface Member {
    id: number;
    coop_id: number;
    first_name: string;
    last_name: string;
    full_name?: string;
    birth_date: string | null;
    gender: string | null;
    address: string | null;
    region: string | null;
    province: string | null;
    city_municipality: string | null;
    barangay: string | null;
    phone: string | null;
    email: string | null;
    date_joined: string | null;
    membership_type: string | null;
    membership_status: string | null;
    share_capital: string | null;
    educational_attainment: string | null;
    primary_livelihood: string | null;
    sector: string | null;
    cooperative: Cooperative;
}

interface Props {
    member: Member;
    userAccount: {
        email: string;
        roles: string[];
    } | null;
    servicesAvailed: Array<{
        id: number;
        service_type: string;
        service_detail: string | null;
        date_availed: string;
        amount: number | null;
        status: string;
        reference_no: string |
            null;
        remarks: string | null;
        recorded_by: string | null;
    }>;
    activityParticipants: Array<{
        id: number;
        activity: string | null;
        role: string | null;
        date_joined: string | null;
        is_beneficiary: boolean;
    }>;
}

const props = defineProps<Props>();

const page = usePage();
const roles = computed<string[]>(() => (page.props.auth?.roles as string[]) || []);
const accountType = computed(() => page.props.auth?.user?.account_type as string | undefined);
const isProvincialAdmin = computed(() => roles.value.includes('Provincial Admin') || accountType.value === 'Provincial Admin');
const isCoopAdmin = computed(() => roles.value.includes('Coop Admin') || accountType.value === 'Coop Admin');
const canEditMember = computed(() => isProvincialAdmin.value || isCoopAdmin.value || roles.value.includes('Officer'));

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const badgeStyle = (status: string | null) => {
    const map: Record<string, string> = {
        Active: 'bg-emerald-100 text-emerald-700 border-emerald-200',
        Suspended: 'bg-amber-100 text-amber-700 border-amber-200',
        Resigned: 'bg-slate-100 text-slate-700 border-slate-200',
        Deceased: 'bg-gray-100 text-gray-700 border-gray-200',
    };
    return map[status || ''] ?? 'bg-slate-100 text-slate-700 border-slate-200';
};

const fullName = `${props.member.first_name} ${props.member.last_name}`;
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Member Details</h1>
                    <p class="text-sm text-slate-500">View full profile and activity details.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link href="/members">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Back to list
                        </Button>
                    </Link>
                    <Link v-if="canEditMember" :href="`/members/${props.member.id}/edit`">
                        <Button class="gap-2">
                            <Pencil class="h-4 w-4" />
                            Edit Member
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-4">
                    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-xl font-semibold">{{ fullName }}</h2>
                                <p class="text-sm text-slate-500">{{ props.member.cooperative.name }}</p>
                            </div>
                            <div class="text-right">
                                <span :class="['inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold', badgeStyle(props.member.membership_status)]">
                                    <CheckCircle2 class="h-3.5 w-3.5" />
                                    {{ props.member.membership_status || 'N/A' }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-3">
                                <div class="text-xs uppercase tracking-wider text-slate-400">Personal information</div>
                                <div class="space-y-1 text-sm text-slate-700">
                                    <div><strong>Date of birth:</strong> {{ formatDate(props.member.birth_date) }}</div>
                                    <div><strong>Gender:</strong> {{ props.member.gender || 'N/A' }}</div>
                                    <div><strong>Joined:</strong> {{ formatDate(props.member.date_joined) }}</div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="text-xs uppercase tracking-wider text-slate-400">Contact</div>
                                <div class="space-y-1 text-sm text-slate-700">
                                    <div><strong>Email:</strong> {{ props.member.email || 'N/A' }}</div>
                                    <div><strong>Phone:</strong> {{ props.member.phone || 'N/A' }}</div>
                                    <div><strong>Address:</strong>
                                        <span>
                                            {{ [props.member.address, props.member.city_municipality, props.member.province, props.member.barangay, props.member.region]
                                                .filter(Boolean)
                                                .join(', ') || 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Membership & Socio-Economic</h3>
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="rounded-lg bg-slate-50 p-4">
                                <h4 class="text-xs uppercase tracking-widest text-slate-500">Member status</h4>
                                <p class="mt-2 text-sm text-slate-700">{{ props.member.membership_type || 'N/A' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <h4 class="text-xs uppercase tracking-widest text-slate-500">Share capital</h4>
                                <p class="mt-2 text-sm text-slate-700">{{ props.member.share_capital ? '₱ ' + Number(props.member.share_capital).toLocaleString() : 'N/A' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <h4 class="text-xs uppercase tracking-widest text-slate-500">Sector</h4>
                                <p class="mt-2 text-sm text-slate-700">{{ props.member.sector || 'N/A' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <h4 class="text-xs uppercase tracking-widest text-slate-500">Education</h4>
                                <p class="mt-2 text-sm text-slate-700">{{ props.member.educational_attainment || 'N/A' }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">Account & Roles</h3>
                        </div>
                        <div class="mt-4 text-sm text-slate-700">
                            <div><strong>Accounts:</strong> {{ props.userAccount?.email || 'No linked account' }}</div>
                            <div><strong>Roles:</strong> {{ props.userAccount?.roles.length ? props.userAccount.roles.join(', ') : 'N/A' }}</div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Services Availed</h3>
                        <div class="mt-4">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Service</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Recorded By</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="servicesAvailed.length === 0">
                                        <TableCell :colspan="5" class="text-center py-4 text-slate-500">No service history yet.</TableCell>
                                    </TableRow>
                                    <TableRow v-for="item in servicesAvailed" :key="item.id">
                                        <TableCell>{{ formatDate(item.date_availed) }}</TableCell>
                                        <TableCell>{{ item.service_type }}</TableCell>
                                        <TableCell>{{ item.status }}</TableCell>
                                        <TableCell>{{ item.amount !== null ? '₱ ' + Number(item.amount).toLocaleString() : '–' }}</TableCell>
                                        <TableCell>{{ item.recorded_by || '-' }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Activity Participation</h3>
                        <div class="mt-4">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Activity</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Beneficiary</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="activityParticipants.length === 0">
                                        <TableCell :colspan="4" class="text-center py-4 text-slate-500">No activity engagements yet.</TableCell>
                                    </TableRow>
                                    <TableRow v-for="item in activityParticipants" :key="item.id">
                                        <TableCell>{{ formatDate(item.date_joined) }}</TableCell>
                                        <TableCell>{{ item.activity || 'N/A' }}</TableCell>
                                        <TableCell>{{ item.role || 'N/A' }}</TableCell>
                                        <TableCell>
                                            <Badge :class="item.is_beneficiary ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'" class="border">
                                                {{ item.is_beneficiary ? 'Yes' : 'No' }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </section>
                </div>

                <aside class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-900">Quick Summary</h3>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-lg bg-slate-50 p-3">
                                <div class="text-xs uppercase tracking-widest text-slate-400">Total Services</div>
                                <div class="mt-1 text-xl font-semibold text-slate-800">{{ servicesAvailed.length }}</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-3">
                                <div class="text-xs uppercase tracking-widest text-slate-400">Total Activities</div>
                                <div class="mt-1 text-xl font-semibold text-slate-800">{{ activityParticipants.length }}</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-3">
                                <div class="text-xs uppercase tracking-widest text-slate-400">Cooperative</div>
                                <div class="mt-1 text-xl font-semibold text-slate-800">{{ props.member.cooperative.name }}</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-3">
                                <div class="text-xs uppercase tracking-widest text-slate-400">Status</div>
                                <div class="mt-1 text-xl font-semibold text-slate-800">{{ props.member.membership_status || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-900">Key Details</h3>
                        <ul class="mt-4 space-y-2 text-sm text-slate-700">
                            <li class="flex items-center gap-2"><User class="h-4 w-4 text-slate-400"/> <span>{{ props.member.gender || 'No gender specified' }}</span></li>
                            <li class="flex items-center gap-2"><MapPin class="h-4 w-4 text-slate-400"/> <span>{{ [props.member.city_municipality, props.member.province].filter(Boolean).join(', ') || 'Location not set' }}</span></li>
                            <li class="flex items-center gap-2"><Briefcase class="h-4 w-4 text-slate-400"/> <span>{{ props.member.sector || 'Sector not set' }}</span></li>
                            <li class="flex items-center gap-2"><Calendar class="h-4 w-4 text-slate-400"/> <span>Joined {{ formatDate(props.member.date_joined) }}</span></li>
                            <li class="flex items-center gap-2"><ClipboardList class="h-4 w-4 text-slate-400"/> <span>Education - {{ props.member.educational_attainment || 'N/A' }}</span></li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
