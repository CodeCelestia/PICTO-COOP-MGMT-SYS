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
    coop_type: string;
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
            return 'bg-muted text-foreground border-border';
        case 'Inactive':
            return 'bg-muted text-foreground border-border';
        case 'Dissolved':
            return 'bg-muted text-foreground border-border';
        case 'Suspended':
            return 'bg-muted text-foreground border-border';
        default:
            return 'bg-muted text-foreground border-border';
    }
});

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 md:p-6">
            <Card class="border-border bg-gradient-to-br from-card via-card to-muted/25">
                <CardHeader class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted text-foreground">
                            <Building2 class="h-5 w-5" />
                        </div>
                        <div>
                            <CardTitle class="text-xl font-semibold text-foreground">
                                Cooperative Management
                            </CardTitle>
                            <p class="text-sm text-muted-foreground">Review and manage profile, members, officers, and committees</p>
                        </div>
                        <Badge class="ml-auto border" :class="statusBadgeClass">
                            {{ cooperative.status }}
                        </Badge>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/70 p-3 text-sm text-muted-foreground">
                        Selected cooperative:
                        <span class="font-semibold text-foreground">{{ cooperative.name }}</span>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">
                    <LiftedTabs v-model="activeTab" :tabs="tabs" />

                    <div v-show="activeTab === 'profile'" class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">Cooperative Profile</h2>
                                <p class="text-sm text-muted-foreground">Key registration and accreditation details.</p>
                            </div>
                            <Link v-if="canEditCoop" :href="`/cooperatives/${cooperative.id}/edit`">
                                <Button variant="outline" class="gap-2">
                                    <Pencil class="h-4 w-4" />
                                    Edit Cooperative
                                </Button>
                            </Link>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-lg border border-border bg-muted/40 p-4">
                                <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Registration</div>
                                <div class="mt-2 space-y-1 text-sm text-foreground">
                                    <div><strong>Name:</strong> {{ cooperative.name }}</div>
                                    <div><strong>Registration #:</strong> {{ cooperative.registration_number }}</div>
                                    <div><strong>Type:</strong> {{ cooperative.coop_type }}</div>
                                    <div><strong>Date Established:</strong> {{ formatDate(cooperative.date_established) }}</div>
                                </div>
                            </div>
                            <div class="rounded-lg border border-border bg-muted/40 p-4">
                                <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Contact</div>
                                <div class="mt-2 space-y-1 text-sm text-foreground">
                                    <div><strong>Email:</strong> {{ cooperative.email || 'N/A' }}</div>
                                    <div><strong>Phone:</strong> {{ cooperative.phone || 'N/A' }}</div>
                                    <div><strong>Address:</strong> {{ formatFullAddress(cooperative) }}</div>
                                </div>
                            </div>
                            <div class="rounded-lg border border-border bg-muted/40 p-4">
                                <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Accreditation</div>
                                <div class="mt-2 space-y-1 text-sm text-foreground">
                                    <div><strong>Status:</strong> {{ cooperative.accreditation_status || 'N/A' }}</div>
                                    <div><strong>Date:</strong> {{ formatDate(cooperative.accreditation_date) }}</div>
                                </div>
                            </div>
                            <div class="rounded-lg border border-border bg-muted/40 p-4">
                                <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Jurisdiction</div>
                                <div class="mt-2 space-y-1 text-sm text-foreground">
                                    <div><strong>Province:</strong> {{ cooperative.province }}</div>
                                    <div><strong>City/Municipality:</strong> {{ cooperative.city_municipality || 'N/A' }}</div>
                                    <div><strong>Barangay:</strong> {{ cooperative.barangay || 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'members'" class="space-y-4">
                        <MemberListPanel
                            :members="members"
                            :filters="memberFilters"
                            base-url="/cooperatives/my?tab=members"
                            query-prefix="members_"
                        />
                    </div>

                    <div v-show="activeTab === 'officers'" class="space-y-4">
                        <OfficerListPanel
                            :officers="officers"
                            :cooperatives="cooperatives"
                            :filters="officerFilters"
                            base-url="/cooperatives/my?tab=officers"
                            query-prefix="officers_"
                            :lock-coop-id="String(cooperative.id)"
                        />
                    </div>

                    <div v-show="activeTab === 'committees'" class="space-y-4">
                        <CommitteeListPanel
                            :committee-members="committeeMembers"
                            :cooperatives="cooperatives"
                            :filters="committeeFilters"
                            base-url="/cooperatives/my?tab=committees"
                            query-prefix="committees_"
                            :lock-coop-id="String(cooperative.id)"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
