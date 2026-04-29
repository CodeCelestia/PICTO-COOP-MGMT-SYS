<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Building2, ClipboardList, FileText, GraduationCap, HandCoins, Landmark, Pencil, PiggyBank, ShieldCheck, Users, UsersRound, Wallet } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import LiftedTabs from '@/components/LiftedTabs.vue';
import type { LiftedTab } from '@/components/LiftedTabs.vue';
import ActivityListPanel from '@/components/panels/ActivityListPanel.vue';
import FinanceFundingPanel from '@/components/panels/FinanceFundingPanel.vue';
import FinanceLoanPanel from '@/components/panels/FinanceLoanPanel.vue';
import FinanceRecordsPanel from '@/components/panels/FinanceRecordsPanel.vue';
import FinanceSavingsPanel from '@/components/panels/FinanceSavingsPanel.vue';
import CooperativeCommitteeListPanel from '@/components/panels/CooperativeCommitteeListPanel.vue';
import CooperativeMemberListPanel from '@/components/panels/CooperativeMemberListPanel.vue';
import CooperativeOfficerListPanel from '@/components/panels/CooperativeOfficerListPanel.vue';
import TrainingListPanel from '@/components/panels/TrainingListPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCoopLabel } from '@/composables/useCoopLabel';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type {
    CommitteeMember,
    CooperativeSummary,
    Member,
    Officer,
} from '@/types/models';

interface Cooperative {
    id: number;
    name: string;
    registration_number: string;
    classification: 'micro' | 'small' | 'medium' | 'large' | 'Billion' | null;
    types?: Array<{ id: number; name: string }>;
    date_established: string;
    address: string;
    province: string;
    region: string | null;
    city_municipality: string | null;
    barangay: string | null;
    email: string | null;
    phone: string | null;
    status: string;
    accreditations?: Accreditation[];
}

interface Accreditation {
    id: number;
    cooperative_id: number;
    level: string;
    date_granted: string;
    valid_until: string | null;
    issuing_body: string | null;
    remarks: string | null;
}

interface KeyOfficer {
    id: number;
    position: string;
    status: string;
    term_start: string | null;
    term_end: string | null;
    member?: {
        id: number;
        full_name: string;
    } | null;
}


const props = defineProps<{
    cooperative: Cooperative;
    members: {
        data: Member[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    memberFilters: {
        search?: string;
        membership_status?: string;
        membership_type?: string;
        per_page?: string;
    };
    membershipTypes: string[];
    officers: {
        data: Officer[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    officerFilters: {
        search?: string;
        coop_id?: string;
        status?: string;
        per_page?: string;
    };
    committeeMembers: {
        data: CommitteeMember[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    committeeFilters: {
        search?: string;
        coop_id?: string;
        status?: string;
        per_page?: string;
    };
    activities: {
        data: Array<{
            id: number;
            coop_id: number;
            title: string;
            description: string | null;
            category: string;
            date_started: string | null;
            date_ended: string | null;
            status: string;
            venue: string | null;
            responsible_officer_id: number | null;
            funding_source: string | null;
            cooperative: {
                id: number;
                name: string;
            };
            responsible_officer?: {
                id: number;
                member: {
                    full_name: string;
                };
            } | null;
        }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    activityFilters: {
        search?: string;
        coop_id?: string;
        status?: string;
        category?: string;
        per_page?: string;
    };
    trainings: {
        data: Array<{
            id: number;
            coop_id: number;
            title: string;
            date_conducted: string | null;
            facilitator: string | null;
            target_group: string;
            status: string;
            cooperative: {
                id: number;
                name: string;
            };
        }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    loans: Array<{
        id: number;
        principal: string;
        status: string;
        created_at: string | null;
        purpose: string | null;
        member?: { first_name?: string; last_name?: string };
        loanType?: { name?: string };
    }>;
    savings: Array<{
        id: number;
        account_number: string;
        account_status: string;
        current_balance: string;
        interest_rate: string;
        member?: { first_name?: string; last_name?: string };
    }>;
    financialRecords: Array<{
        id: number;
        period: string;
        type: string;
        amount: string | null;
        source: string | null;
        purpose: string | null;
        date_recorded: string | null;
    }>;
    fundingSources: Array<{
        id: number;
        funder_name: string;
        funder_type: string;
        category: 'activity' | 'project' | 'member_concern';
        status: string;
        amount_allocated: string | null;
        amount_released: string | null;
        activity_id: number | null;
        activity?: { title?: string } | null;
    }>;
    chairperson: KeyOfficer | null;
    generalManager: KeyOfficer | null;
    trainingFilters: {
        search?: string;
        status?: string;
        target_group?: string;
        coop_id?: string;
        per_page?: string;
    };
    cooperatives: CooperativeSummary[];
    loanTypes: Array<{
        id: number;
        cooperative_id: number;
        name: string;
        classification: 'micro' | 'small' | 'medium' | 'large' | 'Billion' | null;
        description: string | null;
        is_active: boolean;
    }>;
    loanTypePermissions: {
        can_create: boolean;
        can_edit: boolean;
        can_delete: boolean;
    };
}>();

const tabs: LiftedTab[] = [
    { id: 'profile', label: 'Cooperative Profile', icon: Building2 },
    { id: 'members', label: 'Members', icon: Users },
    { id: 'officers', label: 'Officers', icon: ShieldCheck },
    { id: 'committees', label: 'Committees', icon: UsersRound },
    { id: 'loan-types', label: 'Loan Types', icon: UsersRound },
    { id: 'activities-projects', label: 'Activities & Projects', icon: ClipboardList },
    { id: 'training-capacity', label: 'Training & Capacity', icon: GraduationCap },
    { id: 'finance', label: 'Finance', icon: Landmark },
];

const financeTabs: LiftedTab[] = [
    { id: 'loans', label: 'Loans', icon: Wallet },
    { id: 'savings', label: 'Savings', icon: PiggyBank },
    { id: 'financial-records', label: 'Financial Records', icon: FileText },
    { id: 'funding-sources', label: 'Funding Sources', icon: HandCoins },
];

const page = usePage();
const currentUrl = page.url || '';
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const { cooperativeManagementLabel } = useCoopLabel();
const canEditCoop = computed(() => permissions.value.includes('update coop-master-profile'));
const canCreateOfficer = computed(() => permissions.value.includes('create officers-&-committees'));
const canCreateLoanType = computed(() => props.loanTypePermissions.can_create);
const canEditLoanType = computed(() => props.loanTypePermissions.can_edit);
const canDeleteLoanType = computed(() => props.loanTypePermissions.can_delete);
const activeTab = ref('profile');
const activeFinanceTab = ref<'loans' | 'savings' | 'financial-records' | 'funding-sources'>('loans');

const addLoanTypeForm = useForm({
    cooperative_id: props.cooperative.id,
    name: '',
    classification: '',
    description: '',
    is_active: true,
});

const editingLoanTypeId = ref<number | null>(null);
const editLoanTypeForm = useForm({
    cooperative_id: props.cooperative.id,
    name: '',
    classification: '',
    description: '',
    is_active: true,
});

const cooperativeBasePath = computed(() => {
    const [path] = page.url.split('?');
    return path || `/cooperatives/${props.cooperative.id}`;
});

const cooperativeTypeNames = computed(() => props.cooperative.types?.map((type) => type.name) || []);

const chairperson = computed(() => props.chairperson);
const generalManager = computed(() => props.generalManager);

const leaderTerm = (officer: KeyOfficer | null) => {
    if (!officer) {
        return 'N/A';
    }

    if (officer.term_start && officer.term_end) {
        return `${formatDate(officer.term_start)} - ${formatDate(officer.term_end)}`;
    }

    return officer.term_start ? `${formatDate(officer.term_start)} - Present` : 'N/A';
};

const classificationLabel = computed(() => {
    switch (props.cooperative.classification) {
        case 'micro':
            return 'Micro';
        case 'small':
            return 'Small';
        case 'medium':
            return 'Medium';
        case 'large':
            return 'Large';
        default:
            return null;
    }
});

const classificationBadgeClass = computed(() => {
    switch (props.cooperative.classification) {
        case 'micro':
            return 'border border-sky-300 bg-sky-100 text-sky-800 dark:border-sky-400/40 dark:bg-sky-500/20 dark:text-sky-200';
        case 'small':
            return 'border border-emerald-300 bg-emerald-100 text-emerald-800 dark:border-emerald-400/40 dark:bg-emerald-500/20 dark:text-emerald-200';
        case 'medium':
            return 'border border-amber-300 bg-amber-100 text-amber-800 dark:border-amber-400/40 dark:bg-amber-500/20 dark:text-amber-200';
        case 'large':
            return 'border border-rose-300 bg-rose-100 text-rose-800 dark:border-rose-400/40 dark:bg-rose-500/20 dark:text-rose-200';
        default:
            return 'border border-border bg-muted text-foreground';
    }
});

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: cooperativeManagementLabel.value,
        href: '/cooperatives',
    },
    {
        title: props.cooperative.name,
        href: `/cooperatives/${props.cooperative.id}`,
    },
]);

const resolveTab = (url: string) => {
    const queryString = url.includes('?') ? url.split('?')[1] : '';
    const params = new URLSearchParams(queryString);
    const tab = params.get('tab');
    return tabs.some((item) => item.id === tab) ? tab! : 'profile';
};

const resolveFinanceTab = (url: string) => {
    const queryString = url.includes('?') ? url.split('?')[1] : '';
    const params = new URLSearchParams(queryString);
    const subtab = params.get('subtab');
    return financeTabs.some((item) => item.id === subtab) ? (subtab as typeof activeFinanceTab.value) : 'loans';
};

watch(
    () => page.url,
    (url) => {
        activeTab.value = resolveTab(url);
        activeFinanceTab.value = resolveFinanceTab(url);
    },
    { immediate: true },
);

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatFullAddress = (coop: Cooperative) => {
    const parts = [
        coop.address,
        coop.barangay ? `Brgy. ${coop.barangay}` : null,
        coop.city_municipality,
        coop.province,
        coop.region,
    ].filter(Boolean);

    return parts.join(', ') || 'N/A';
};

const accreditations = computed(() => props.cooperative.accreditations || []);

const fundingSourcesForPanel = computed(() =>
    props.fundingSources.map((source) => ({
        ...source,
        activity: source.activity || undefined,
    })),
);

const startEditLoanType = (loanType: { id: number; name: string; classification: 'micro' | 'small' | 'medium' | 'large' | 'Billion' | null; description: string | null; is_active: boolean }) => {
    editingLoanTypeId.value = loanType.id;
    editLoanTypeForm.cooperative_id = props.cooperative.id;
    editLoanTypeForm.name = loanType.name;
    editLoanTypeForm.classification = loanType.classification || '';
    editLoanTypeForm.description = loanType.description || '';
    editLoanTypeForm.is_active = loanType.is_active;
};

const cancelEditLoanType = () => {
    editingLoanTypeId.value = null;
    editLoanTypeForm.reset();
    editLoanTypeForm.clearErrors();
};

const submitAddLoanType = () => {
    addLoanTypeForm.post('/finance/loan-types', {
        preserveScroll: true,
        onSuccess: () => {
            addLoanTypeForm.reset('name', 'classification', 'description');
            addLoanTypeForm.is_active = true;
        },
    });
};

const loanTypeClassificationLabel = (classification: 'micro' | 'small' | 'medium' | 'large' | 'Billion' | null): string | null => {
    switch (classification) {
        case 'micro':
            return 'Micro';
        case 'small':
            return 'Small';
        case 'medium':
            return 'Medium';
        case 'large':
            return 'Large';
        case 'Billion':
            return 'Billion';
        default:
            return null;
    }
};

const loanTypeClassificationBadgeClass = (classification: 'micro' | 'small' | 'medium' | 'large' | 'Billion' | null): string => {
    switch (classification) {
        case 'micro':
            return 'border border-sky-300 bg-sky-100 text-sky-800';
        case 'small':
            return 'border border-emerald-300 bg-emerald-100 text-emerald-800';
        case 'medium':
            return 'border border-amber-300 bg-amber-100 text-amber-800';
        case 'large':
            return 'border border-rose-300 bg-rose-100 text-rose-800';
        case 'Billion':
            return 'border border-violet-300 bg-violet-100 text-violet-800';
        default:
            return 'border border-slate-200 bg-slate-100 text-slate-700';
    }
};

const submitEditLoanType = (loanTypeId: number) => {
    editLoanTypeForm.put(`/finance/loan-types/${loanTypeId}`, {
        preserveScroll: true,
        onSuccess: () => {
            cancelEditLoanType();
        },
    });
};

const deleteLoanType = (loanTypeId: number) => {
    if (!window.confirm('Are you sure you want to delete this loan type?')) {
        return;
    }

    router.delete(`/finance/loan-types/${loanTypeId}`, {
        preserveScroll: true,
        data: { cooperative_id: props.cooperative.id },
    });
};

const statusBadgeClass = computed(() => {
    switch (props.cooperative.status) {
        case 'Active':
            return 'border border-emerald-300 bg-emerald-100 text-emerald-800 dark:border-emerald-400/40 dark:bg-emerald-500/20 dark:text-emerald-200';
        case 'Inactive':
            return 'border border-slate-300 bg-slate-100 text-slate-800 dark:border-slate-400/40 dark:bg-slate-500/20 dark:text-slate-200';
        case 'Dissolved':
            return 'border border-red-300 bg-red-100 text-red-800 dark:border-red-400/40 dark:bg-red-500/20 dark:text-red-200';
        case 'Suspended':
            return 'border border-amber-300 bg-amber-100 text-amber-800 dark:border-amber-400/40 dark:bg-amber-500/20 dark:text-amber-200';
        default:
            return 'border border-border bg-muted text-foreground';
    }
});

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 md:p-6">
            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardHeader class="space-y-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-muted text-foreground">
                                <Building2 class="h-6 w-6" />
                            </div>
                            <div class="space-y-1">
                                <CardTitle class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">
                                    {{ cooperativeManagementLabel }}  |  {{ cooperative.name }}
                                </CardTitle>
                                <p class="text-base text-muted-foreground">Review and manage profile, members, officers, and committees</p>
                            </div>
                        </div>
                        <Badge class="w-fit text-sm font-semibold" :class="statusBadgeClass">
                            {{ cooperative.status }}
                        </Badge>
                    </div>

                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="coop-detail-tabs rounded-xl border border-border/70 bg-card/95 p-1.5 shadow-sm shadow-black/5 dark:shadow-black/20">
                        <LiftedTabs v-model="activeTab" :tabs="tabs" />
                    </div>

                    <div v-show="activeTab === 'profile'" class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-foreground">Cooperative Profile</h2>
                                <p class="text-base text-muted-foreground">Key registration and accreditation details for quick review.</p>
                            </div>
                            <Link v-if="canEditCoop" :href="currentUrl ? `/cooperatives/${cooperative.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/cooperatives/${cooperative.id}/edit`">
                                <Button variant="outline" class="gap-2">
                                    <Pencil class="h-4 w-4" />
                                    Edit Cooperative
                                </Button>
                            </Link>
                        </div>
                        <div class="grid gap-4 xl:grid-cols-2">
                            <section class="rounded-xl border border-border bg-background p-5 shadow-sm">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Registration</h3>
                                <dl class="mt-4 space-y-3 text-base text-foreground">
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Name</dt>
                                        <dd class="font-semibold">{{ cooperative.name }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Registration #</dt>
                                        <dd class="font-semibold">{{ cooperative.registration_number || 'N/A' }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Type</dt>
                                        <dd class="flex flex-wrap gap-2">
                                            <Badge v-for="typeName in cooperativeTypeNames" :key="`${typeName}-profile`" variant="outline" class="font-medium">{{ typeName }}</Badge>
                                            <span v-if="!cooperativeTypeNames.length" class="text-muted-foreground">N/A</span>
                                        </dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Classification</dt>
                                        <dd>
                                            <Badge class="font-medium" :class="classificationBadgeClass">
                                                {{ classificationLabel || 'Not set' }}
                                            </Badge>
                                        </dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Date Established</dt>
                                        <dd class="font-semibold">{{ formatDate(cooperative.date_established) }}</dd>
                                    </div>
                                </dl>
                            </section>

                            <section class="rounded-xl border border-border bg-background p-5 shadow-sm">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Contact</h3>
                                <dl class="mt-4 space-y-3 text-base text-foreground">
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Email</dt>
                                        <dd class="font-semibold">{{ cooperative.email || 'N/A' }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Phone</dt>
                                        <dd class="font-semibold">{{ cooperative.phone || 'N/A' }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Address</dt>
                                        <dd class="font-semibold">{{ formatFullAddress(cooperative) }}</dd>
                                    </div>
                                </dl>
                            </section>

                            <section class="rounded-xl border border-border bg-background p-5 shadow-sm">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Key Leadership</h3>
                                    <div v-if="canCreateOfficer" class="flex flex-wrap gap-2">
                                        <Link :href="`/officers/create?coop_id=${cooperative.id}&position=Chairperson&return_to=${encodeURIComponent(currentUrl)}`">
                                            <Button size="sm" variant="outline" class="gap-2">
                                                Assign Chairperson
                                            </Button>
                                        </Link>
                                        <Link :href="`/officers/create?coop_id=${cooperative.id}&position=General%20Manager&return_to=${encodeURIComponent(currentUrl)}`">
                                            <Button size="sm" variant="outline" class="gap-2">
                                                Assign General Manager
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                                <dl class="mt-4 space-y-3 text-base text-foreground">
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Chairperson</dt>
                                        <dd class="font-semibold">{{ chairperson?.member?.full_name || 'Unassigned' }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Chairperson Term</dt>
                                        <dd class="font-semibold">{{ leaderTerm(chairperson || null) }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">General Manager</dt>
                                        <dd class="font-semibold">{{ generalManager?.member?.full_name || 'Unassigned' }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">General Manager Term</dt>
                                        <dd class="font-semibold">{{ leaderTerm(generalManager || null) }}</dd>
                                    </div>
                                </dl>
                            </section>

                            <section class="rounded-xl border border-border bg-background p-5 shadow-sm xl:col-span-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Accreditations</h3>

                                <div class="mt-4 overflow-hidden rounded-lg border border-border/70">
                                    <table class="w-full text-sm">
                                        <thead class="bg-muted/40">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Level</th>
                                                <th class="px-3 py-2 text-left">Date Granted</th>
                                                <th class="px-3 py-2 text-left">Valid Until</th>
                                                <th class="px-3 py-2 text-left">Issuing Body</th>
                                                <th class="px-3 py-2 text-left">Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="accreditations.length === 0">
                                                <td class="px-3 py-4 text-center text-muted-foreground" colspan="5">
                                                    No accreditation records yet.
                                                </td>
                                            </tr>
                                            <tr v-for="accreditation in accreditations" :key="accreditation.id" class="border-t">
                                                <td class="px-3 py-2">{{ accreditation.level }}</td>
                                                <td class="px-3 py-2">{{ formatDate(accreditation.date_granted) }}</td>
                                                <td class="px-3 py-2">{{ formatDate(accreditation.valid_until) }}</td>
                                                <td class="px-3 py-2">{{ accreditation.issuing_body || 'CDA' }}</td>
                                                <td class="px-3 py-2">{{ accreditation.remarks || 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            <section class="rounded-xl border border-border bg-background p-5 shadow-sm">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Jurisdiction</h3>
                                <dl class="mt-4 space-y-3 text-base text-foreground">
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Province</dt>
                                        <dd class="font-semibold">{{ cooperative.province || 'N/A' }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">City/Municipality</dt>
                                        <dd class="font-semibold">{{ cooperative.city_municipality || 'N/A' }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Barangay</dt>
                                        <dd class="font-semibold">{{ cooperative.barangay || 'N/A' }}</dd>
                                    </div>
                                </dl>
                            </section>
                        </div>
                    </div>

                    <div v-show="activeTab === 'members'" class="space-y-4">
                        <CooperativeMemberListPanel
                            :members="members"
                            :filters="memberFilters"
                            :membership-types="membershipTypes"
                            :base-url="`${cooperativeBasePath}?tab=members`"
                            query-prefix="members_"
                        />
                    </div>

                    <div v-show="activeTab === 'officers'" class="space-y-4">
                        <CooperativeOfficerListPanel
                            :officers="officers"
                            :cooperatives="cooperatives"
                            :filters="officerFilters"
                            :base-url="`${cooperativeBasePath}?tab=officers`"
                            query-prefix="officers_"
                            :lock-coop-id="String(cooperative.id)"
                        />
                    </div>

                    <div v-show="activeTab === 'committees'" class="space-y-4">
                        <CooperativeCommitteeListPanel
                            :committee-members="committeeMembers"
                            :cooperatives="cooperatives"
                            :filters="committeeFilters"
                            :base-url="`${cooperativeBasePath}?tab=committees`"
                            query-prefix="committees_"
                            :lock-coop-id="String(cooperative.id)"
                        />
                    </div>

                    <div v-show="activeTab === 'loan-types'" class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-foreground">Loan Types</h2>
                                <p class="text-base text-muted-foreground">Define available loan products for this cooperative.</p>
                            </div>
                        </div>

                        <section v-if="canCreateLoanType" class="rounded-xl border border-border bg-background p-5 shadow-sm">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Add Loan Type</h3>
                            <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitAddLoanType">
                                <div class="md:col-span-1">
                                    <label class="mb-1 block text-sm font-medium">Name</label>
                                    <input v-model="addLoanTypeForm.name" type="text" class="w-full rounded-md border px-3 py-2 text-sm" placeholder="e.g., Emergency Loan" />
                                    <p v-if="addLoanTypeForm.errors.name" class="mt-1 text-xs text-red-600">{{ addLoanTypeForm.errors.name }}</p>
                                </div>
                                <div class="md:col-span-1">
                                    <label class="mb-1 block text-sm font-medium">Status</label>
                                    <select v-model="addLoanTypeForm.is_active" class="w-full rounded-md border px-3 py-2 text-sm">
                                        <option :value="true">Active</option>
                                        <option :value="false">Inactive</option>
                                    </select>
                                    <p v-if="addLoanTypeForm.errors.is_active" class="mt-1 text-xs text-red-600">{{ addLoanTypeForm.errors.is_active }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-medium">Classification</label>
                                    <select v-model="addLoanTypeForm.classification" class="w-full rounded-md border px-3 py-2 text-sm">
                                        <option value="">None</option>
                                        <option value="micro">Micro</option>
                                        <option value="small">Small</option>
                                        <option value="medium">Medium</option>
                                        <option value="large">Large</option>
                                    </select>
                                    <p class="mt-1 text-xs text-muted-foreground">Optional but recommended.</p>
                                    <p v-if="addLoanTypeForm.errors.classification" class="mt-1 text-xs text-red-600">{{ addLoanTypeForm.errors.classification }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-medium">Description</label>
                                    <textarea v-model="addLoanTypeForm.description" rows="3" class="w-full rounded-md border px-3 py-2 text-sm" placeholder="Optional description"></textarea>
                                    <p v-if="addLoanTypeForm.errors.description" class="mt-1 text-xs text-red-600">{{ addLoanTypeForm.errors.description }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <Button type="submit" class="gap-2" :disabled="addLoanTypeForm.processing">Add Loan Type</Button>
                                </div>
                            </form>
                        </section>

                        <section class="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/40">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Name</th>
                                        <th class="px-4 py-3 text-left">Description</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                        <th class="px-4 py-3 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loanTypes.length === 0">
                                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="4">
                                            No loan types defined yet. Add your first loan type to start creating loans.
                                        </td>
                                    </tr>
                                    <tr v-for="loanType in loanTypes" :key="loanType.id" class="border-t align-top">
                                        <template v-if="editingLoanTypeId === loanType.id">
                                            <td class="px-4 py-3">
                                                <input v-model="editLoanTypeForm.name" type="text" class="w-full rounded-md border px-3 py-2 text-sm" />
                                                <p v-if="editLoanTypeForm.errors.name" class="mt-1 text-xs text-red-600">{{ editLoanTypeForm.errors.name }}</p>
                                                <div class="mt-2">
                                                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Classification</label>
                                                    <select v-model="editLoanTypeForm.classification" class="w-full rounded-md border px-3 py-2 text-sm">
                                                        <option value="">None</option>
                                                        <option value="micro">Micro</option>
                                                        <option value="small">Small</option>
                                                        <option value="medium">Medium</option>
                                                        <option value="large">Large</option>
                                                    </select>
                                                    <p v-if="editLoanTypeForm.errors.classification" class="mt-1 text-xs text-red-600">{{ editLoanTypeForm.errors.classification }}</p>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <textarea v-model="editLoanTypeForm.description" rows="2" class="w-full rounded-md border px-3 py-2 text-sm"></textarea>
                                                <p v-if="editLoanTypeForm.errors.description" class="mt-1 text-xs text-red-600">{{ editLoanTypeForm.errors.description }}</p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <select v-model="editLoanTypeForm.is_active" class="w-full rounded-md border px-3 py-2 text-sm">
                                                    <option :value="true">Active</option>
                                                    <option :value="false">Inactive</option>
                                                </select>
                                                <p v-if="editLoanTypeForm.errors.is_active" class="mt-1 text-xs text-red-600">{{ editLoanTypeForm.errors.is_active }}</p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap gap-2">
                                                    <Button size="sm" @click="submitEditLoanType(loanType.id)" :disabled="editLoanTypeForm.processing">Save</Button>
                                                    <Button size="sm" variant="outline" @click="cancelEditLoanType">Cancel</Button>
                                                </div>
                                            </td>
                                        </template>
                                        <template v-else>
                                            <td class="px-4 py-3 font-medium">{{ loanType.name }}</td>
                                            <td class="px-4 py-3">
                                                <Badge class="font-medium" :class="loanTypeClassificationBadgeClass(loanType.classification)">
                                                    {{ loanTypeClassificationLabel(loanType.classification) || 'None' }}
                                                </Badge>
                                            </td>
                                            <td class="px-4 py-3">{{ loanType.description || 'N/A' }}</td>
                                            <td class="px-4 py-3">
                                                <Badge :class="loanType.is_active ? 'border border-emerald-200 bg-emerald-100 text-emerald-800' : 'border border-slate-200 bg-slate-100 text-slate-700'">
                                                    {{ loanType.is_active ? 'Active' : 'Inactive' }}
                                                </Badge>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap gap-2">
                                                    <Button v-if="canEditLoanType" size="sm" variant="outline" @click="startEditLoanType(loanType)">Edit</Button>
                                                    <Button v-if="canDeleteLoanType" size="sm" variant="destructive" @click="deleteLoanType(loanType.id)">Delete</Button>
                                                </div>
                                            </td>
                                        </template>
                                    </tr>
                                </tbody>
                            </table>
                        </section>
                    </div>

                    <div v-show="activeTab === 'activities-projects'" class="space-y-4">
                        <ActivityListPanel
                            :activities="activities"
                            :cooperatives="cooperatives"
                            :filters="activityFilters"
                            :base-url="`${cooperativeBasePath}?tab=activities-projects`"
                            query-prefix="activities_"
                            :lock-coop-id="String(cooperative.id)"
                            :show-view-action-in-rows="true"
                            :show-participant-action-in-rows="true"
                        />
                    </div>

                    <div v-show="activeTab === 'training-capacity'" class="space-y-4">
                        <TrainingListPanel
                            :trainings="trainings"
                            :cooperatives="cooperatives"
                            :filters="trainingFilters"
                            :base-url="`${cooperativeBasePath}?tab=training-capacity`"
                            query-prefix="trainings_"
                            :lock-coop-id="String(cooperative.id)"
                            :show-view-action-in-rows="true"
                            :show-participant-action-in-rows="true"
                        />
                    </div>

                    <div v-show="activeTab === 'finance'" class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-foreground">Finance</h2>
                                <p class="text-base text-muted-foreground">Finance records scoped to {{ cooperative.name }}.</p>
                            </div>
                        </div>

                        <LiftedTabs v-model="activeFinanceTab" :tabs="financeTabs" />

                        <div v-show="activeFinanceTab === 'loans'">
                            <FinanceLoanPanel :cooperative="cooperative" :loans="loans" />
                        </div>

                        <div v-show="activeFinanceTab === 'savings'">
                            <FinanceSavingsPanel :cooperative="cooperative" :savings="savings" />
                        </div>

                        <div v-show="activeFinanceTab === 'financial-records'">
                            <FinanceRecordsPanel :cooperative="cooperative" :records="financialRecords" />
                        </div>

                        <div v-show="activeFinanceTab === 'funding-sources'">
                            <FinanceFundingPanel :cooperative="cooperative" :funding-sources="fundingSourcesForPanel" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
.coop-detail-tabs :deep(button[role='tab']) {
    min-height: 44px;
    padding: 0.75rem 1.1rem;
    font-size: 0.98rem;
    line-height: 1.35;
}

.coop-detail-tabs :deep(button[role='tab'][aria-selected='true']) {
    font-weight: 700;
}
</style>
