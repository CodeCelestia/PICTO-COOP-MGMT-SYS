<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Building2, Pencil, ShieldCheck, Users, UsersRound } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import LiftedTabs, { type LiftedTab } from '@/components/LiftedTabs.vue';
import MemberListPanel from '@/components/panels/MemberListPanel.vue';
import OfficerListPanel from '@/components/panels/OfficerListPanel.vue';
import CommitteeListPanel from '@/components/panels/CommitteeListPanel.vue';
import { Link, usePage } from '@inertiajs/vue3';
import type {
    CommitteeMember,
    CooperativeSummary,
    Member,
    Officer,
} from '@/types/models';
import type { BreadcrumbItem } from '@/types';

interface Cooperative {
    id: number;
    name: string;
    registration_number: string;
    classification: string | null;
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
    accreditation_status: string | null;
    accreditation_date: string | null;
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
        per_page?: string;
    };
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
    cooperatives: CooperativeSummary[];
}>();

const tabs: LiftedTab[] = [
    { id: 'profile', label: 'Cooperative Profile', icon: Building2 },
    { id: 'members', label: 'Members', icon: Users },
    { id: 'officers', label: 'Officers', icon: ShieldCheck },
    { id: 'committees', label: 'Committees', icon: UsersRound },
];

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canEditCoop = computed(() => permissions.value.includes('update coop-master-profile'));
const activeTab = ref('profile');

const cooperativeBasePath = computed(() => {
    const [path] = page.url.split('?');
    return path || `/cooperatives/${props.cooperative.id}`;
});

const cooperativeTypeNames = computed(() => props.cooperative.types?.map((type) => type.name) || []);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Cooperative Management',
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

watch(
    () => page.url,
    (url) => {
        activeTab.value = resolveTab(url);
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
                                    Cooperative Management  |  {{ cooperative.name }}
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
                    <div class="coop-detail-tabs">
                        <LiftedTabs v-model="activeTab" :tabs="tabs" />
                    </div>

                    <div v-show="activeTab === 'profile'" class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-foreground">Cooperative Profile</h2>
                                <p class="text-base text-muted-foreground">Key registration and accreditation details for quick review.</p>
                            </div>
                            <Link v-if="canEditCoop" :href="`/cooperatives/${cooperative.id}/edit`">
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
                                            <Badge variant="outline" class="font-medium">{{ cooperative.classification || 'N/A' }}</Badge>
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
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Accreditation</h3>
                                <dl class="mt-4 space-y-3 text-base text-foreground">
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Status</dt>
                                        <dd class="font-semibold">{{ cooperative.accreditation_status || 'N/A' }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[12rem_1fr] sm:items-start">
                                        <dt class="font-semibold text-muted-foreground">Date</dt>
                                        <dd class="font-semibold">{{ formatDate(cooperative.accreditation_date) }}</dd>
                                    </div>
                                </dl>
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
                        <MemberListPanel
                            :members="members"
                            :filters="memberFilters"
                            :base-url="`${cooperativeBasePath}?tab=members`"
                            query-prefix="members_"
                        />
                    </div>

                    <div v-show="activeTab === 'officers'" class="space-y-4">
                        <OfficerListPanel
                            :officers="officers"
                            :cooperatives="cooperatives"
                            :filters="officerFilters"
                            :base-url="`${cooperativeBasePath}?tab=officers`"
                            query-prefix="officers_"
                            :lock-coop-id="String(cooperative.id)"
                        />
                    </div>

                    <div v-show="activeTab === 'committees'" class="space-y-4">
                        <CommitteeListPanel
                            :committee-members="committeeMembers"
                            :cooperatives="cooperatives"
                            :filters="committeeFilters"
                            :base-url="`${cooperativeBasePath}?tab=committees`"
                            query-prefix="committees_"
                            :lock-coop-id="String(cooperative.id)"
                        />
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
