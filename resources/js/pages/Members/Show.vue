<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, CheckCircle2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';

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
        <div class="sticky top-16 z-30 bg-background/95 px-4 pb-4 pt-4 backdrop-blur-sm md:px-6 md:pt-6">
            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm md:p-6">
                <div class="mb-4 flex flex-col gap-3 border-b border-border pb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground">Member Details</h1>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <Link href="/members">
                            <Button variant="outline" size="sm" class="gap-2">
                                <ArrowLeft class="h-4 w-4" />
                                Back to list
                            </Button>
                        </Link>
                        <Link v-if="canEditMember" :href="`/members/${props.member.id}/services-availed`">
                            <Button variant="outline" size="sm">Services</Button>
                        </Link>
                        <Link v-if="canEditMember" :href="`/members/${props.member.id}/activity-participants`">
                            <Button variant="outline" size="sm">Activities</Button>
                        </Link>
                        <Link v-if="canEditMember" :href="`/members/${props.member.id}/edit`">
                            <Button size="sm" class="gap-2">
                                <Pencil class="h-4 w-4" />
                                Edit Member
                            </Button>
                        </Link>
                    </div>
                </div>

                <div class="mb-5 flex flex-col gap-2 border-b border-border pb-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold text-foreground">{{ fullName }}</h2>
                        <span :class="['inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold', badgeStyle(props.member.membership_status)]">
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            {{ props.member.membership_status || 'N/A' }}
                        </span>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ props.member.cooperative.name }}</p>
                </div>

                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-3">
                                <div class="text-xs uppercase tracking-wider text-muted-foreground">Personal information</div>
                                <div class="space-y-1 text-sm text-foreground/90">
                                    <div><strong>Gender:</strong> {{ props.member.gender || 'N/A' }}</div>
                                    <div><strong>Profession:</strong> {{ props.member.primary_livelihood || 'N/A' }}</div>
                                    <div><strong>Education:</strong> {{ props.member.educational_attainment || 'N/A' }}</div>
                                    <div><strong>Joined:</strong> {{ formatDate(props.member.date_joined) }}</div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="text-xs uppercase tracking-wider text-muted-foreground">Contact</div>
                                <div class="space-y-1 text-sm text-foreground/90">
                                    <div><strong>Email: </strong> {{ props.member.email || 'N/A' }}</div>
                                    <div><strong>Address: </strong>
                                        <span>
                                            {{ [props.member.address, props.member.city_municipality, props.member.province, props.member.barangay, props.member.region]
                                                .filter(Boolean)
                                                .join(', ') || 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="px-4 pb-6 md:px-6">
            <div class="space-y-4">
                    <section class="rounded-xl border border-border bg-card p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-foreground">Membership & Socio-Economic</h3>
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="rounded-lg border border-border bg-muted/40 p-4">
                                <h4 class="text-xs uppercase tracking-widest text-muted-foreground">Member status</h4>
                                <p class="mt-2 text-sm text-foreground/90">{{ props.member.membership_type || 'N/A' }}</p>
                            </div>
                            <div class="rounded-lg border border-border bg-muted/40 p-4">
                                <h4 class="text-xs uppercase tracking-widest text-muted-foreground">Share capital</h4>
                                <p class="mt-2 text-sm text-foreground/90">{{ props.member.share_capital ? '₱ ' + Number(props.member.share_capital).toLocaleString() : 'N/A' }}</p>
                            </div>
                            <div class="rounded-lg border border-border bg-muted/40 p-4">
                                <h4 class="text-xs uppercase tracking-widest text-muted-foreground">Sector</h4>
                                <p class="mt-2 text-sm text-foreground/90">{{ props.member.sector || 'N/A' }}</p>
                            </div>
                            <div class="rounded-lg border border-border bg-muted/40 p-4">
                                <h4 class="text-xs uppercase tracking-widest text-muted-foreground">Education</h4>
                                <p class="mt-2 text-sm text-foreground/90">{{ props.member.educational_attainment || 'N/A' }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-foreground">Account & Roles</h3>
                        </div>
                        <div class="mt-4 text-sm text-foreground/90">
                            <div><strong>Accounts:</strong> {{ props.userAccount?.email || 'No linked account' }}</div>
                            <div><strong>Roles:</strong> {{ props.userAccount?.roles.length ? props.userAccount.roles.join(', ') : 'N/A' }}</div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-border bg-card p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-foreground">Services Availed</h3>
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
                                        <TableCell :colspan="5" class="py-4 text-center text-muted-foreground">No service history yet.</TableCell>
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

                    <section class="rounded-xl border border-border bg-card p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-foreground">Activity Participation</h3>
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
                                        <TableCell :colspan="4" class="py-4 text-center text-muted-foreground">No activity engagements yet.</TableCell>
                                    </TableRow>
                                    <TableRow v-for="item in activityParticipants" :key="item.id">
                                        <TableCell>{{ formatDate(item.date_joined) }}</TableCell>
                                        <TableCell>{{ item.activity || 'N/A' }}</TableCell>
                                        <TableCell>{{ item.role || 'N/A' }}</TableCell>
                                        <TableCell>
                                            <Badge :class="item.is_beneficiary ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-200' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'" class="border">
                                                {{ item.is_beneficiary ? 'Yes' : 'No' }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </section>
            </div>
        </div>
    </AppLayout>
</template>
