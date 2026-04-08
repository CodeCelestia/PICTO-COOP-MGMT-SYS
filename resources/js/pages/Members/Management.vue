<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Building2, FolderKanban, GraduationCap, Handshake, Users } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import LiftedTabs, { type LiftedTab } from '@/components/LiftedTabs.vue';
import MemberListPanel from '@/components/panels/MemberListPanel.vue';
import ActivityListPanel from '@/components/panels/ActivityListPanel.vue';
import TrainingListPanel from '@/components/panels/TrainingListPanel.vue';
import ServiceAvailedListPanel from '@/components/panels/ServiceAvailedListPanel.vue';
import type { Member } from '@/types/models';
import type { BreadcrumbItem } from '@/types';

interface CooperativeSummary {
    id: number;
    name: string;
}

interface Activity {
    id: number;
    coop_id: number;
    title: string;
    description: string | null;
    category: string;
    date_started: string | null;
    date_ended: string | null;
    status: string;
    responsible_officer_id: number | null;
    funding_source: string | null;
    cooperative: CooperativeSummary;
    responsible_officer?: {
        id: number;
        member: {
            full_name: string;
        };
    } | null;
}

interface Training {
    id: number;
    coop_id: number;
    title: string;
    date_conducted: string | null;
    facilitator: string | null;
    target_group: string;
    status: string;
    cooperative: CooperativeSummary;
}

interface ServiceAvailed {
    id: number;
    service_type: string;
    service_detail: string | null;
    date_availed: string | null;
    amount: string | number | null;
    status: string;
    reference_no: string | null;
    remarks: string | null;
    recorded_by: string | null;
    member?: {
        id: number;
        full_name: string;
    } | null;
}

const props = defineProps<{
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
    services: ServiceAvailed[];
    activities: {
        data: Activity[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    activityFilters: {
        search?: string;
        status?: string;
        category?: string;
        coop_id?: string;
        per_page?: string;
    };
    trainings: {
        data: Training[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    trainingFilters: {
        search?: string;
        status?: string;
        target_group?: string;
        coop_id?: string;
        per_page?: string;
    };
    cooperatives: CooperativeSummary[];
}>();

const tabs: LiftedTab[] = [
    { id: 'members', label: 'Members', icon: Users },
    { id: 'services', label: 'Services', icon: Handshake },
    { id: 'activities', label: 'Activities and Projects', icon: FolderKanban },
    { id: 'trainings', label: 'Trainings', icon: GraduationCap },
];

const page = usePage();
const roles = computed<string[]>(() => (page.props.auth?.roles as string[]) || []);
const accountType = computed(() => page.props.auth?.user?.account_type as string | undefined);
const isCoopAdmin = computed(() => roles.value.includes('Coop Admin') || accountType.value === 'Coop Admin');
const activeTab = ref('members');

const resolveTab = (url: string) => {
    const queryString = url.includes('?') ? url.split('?')[1] : '';
    const params = new URLSearchParams(queryString);
    const tab = params.get('tab');
    return tabs.some((item) => item.id === tab) ? tab! : 'members';
};

const managementBasePath = computed(() => {
    const [path] = page.url.split('?');
    return path || '/members/management';
});

const cooperativeName = computed(() => props.cooperatives[0]?.name || 'Selected Cooperative');

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Members Management',
        href: '/members/management',
    },
    {
        title: cooperativeName.value,
        href: managementBasePath.value,
    },
]);

watch(
    () => page.url,
    (url) => {
        activeTab.value = resolveTab(url);
    },
    { immediate: true },
);

const coopId = computed(() => props.cooperatives[0]?.id?.toString() || '');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 md:p-6">
            <Card class="border-border bg-gradient-to-br from-card via-card to-muted/25">
                <CardHeader class="space-y-2">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="inline-flex items-center gap-2 rounded-full border border-border bg-background/80 px-3 py-1 text-xs font-semibold text-muted-foreground">
                                <Building2 class="h-3.5 w-3.5" />
                                Step 2 of 2
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted text-foreground">
                                    <Users class="h-5 w-5" />
                                </div>
                                <div>
                                    <CardTitle class="text-xl font-semibold text-foreground">
                                        Members Management
                                    </CardTitle>
                                    <p class="text-sm text-muted-foreground">Manage records for the selected cooperative</p>
                                </div>
                            </div>
                            <Badge variant="outline" class="w-fit text-xs">
                                Cooperative: {{ cooperativeName }}
                            </Badge>
                        </div>
                        <div v-if="!isCoopAdmin" class="flex items-center">
                            <Link href="/members/management/select" class="inline-flex items-center rounded-md border border-border bg-background px-3 py-2 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-muted">
                                Choose Another Cooperative
                            </Link>
                        </div>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/70 p-3 text-sm text-muted-foreground">
                        You are viewing cooperative-wide members, services, activities, and trainings for
                        <span class="font-semibold text-foreground">{{ cooperativeName }}</span>.
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">
                    <LiftedTabs v-model="activeTab" :tabs="tabs" />

                    <div v-show="activeTab === 'members'" class="space-y-4">
                        <MemberListPanel
                            :members="members"
                            :filters="memberFilters"
                            :base-url="`${managementBasePath}?tab=members`"
                            query-prefix="members_"
                        />
                    </div>

                    <div v-show="activeTab === 'services'" class="space-y-4">
                        <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
                            <div class="space-y-1">
                                <h2 class="text-xl font-semibold tracking-tight text-foreground">Services Availed</h2>
                                <p class="text-sm text-muted-foreground">Cooperative-wide services availed records for {{ cooperativeName }}</p>
                            </div>
                        </div>
                        <ServiceAvailedListPanel :services="services" show-member />
                    </div>

                    <div v-show="activeTab === 'activities'" class="space-y-4">
                        <ActivityListPanel
                            :activities="activities"
                            :cooperatives="cooperatives"
                            :filters="activityFilters"
                            :base-url="`${managementBasePath}?tab=activities`"
                            query-prefix="activities_"
                            :lock-coop-id="coopId"
                        />
                    </div>

                    <div v-show="activeTab === 'trainings'" class="space-y-4">
                        <TrainingListPanel
                            :trainings="trainings"
                            :cooperatives="cooperatives"
                            :filters="trainingFilters"
                            :base-url="`${managementBasePath}?tab=trainings`"
                            query-prefix="trainings_"
                            :lock-coop-id="coopId"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
