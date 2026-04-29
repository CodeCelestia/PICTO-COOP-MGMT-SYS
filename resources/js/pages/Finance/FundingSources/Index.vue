<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Plus, ArrowLeft } from 'lucide-vue-next';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { computed, ref } from 'vue';

const isFromCoopContext = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return !!coopId;
});

const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId) : null;
});
const currentUrl = window.location.pathname + window.location.search;

const isGlobalMode = computed(() => !coopIdFromUrl.value);
const showCooperativesList = computed(() => isGlobalMode.value && !selectedCoop.value);
const showFundingSourcesList = computed(() => isGlobalMode.value ? !!selectedCoop.value : true);

const selectCoop = (coop: Cooperative) => {
    selectedCoop.value = coop;
    router.get('/finance/funding-sources', {
        coop_id: coop.id,
    }, {
        preserveState: false,
        preserveScroll: false,
    });
};

const backToCooperatives = () => {
    selectedCoop.value = null;
    window.scrollTo(0, 0);
};

interface FundingSource {
    id: number;
    activity_id: number | null;
    category: 'activity' | 'project' | 'member_concern';
    funder_name: string;
    funder_type: string;
    status: string;
    amount_allocated: string | null;
    amount_released: string | null;
    activity?: { title?: string };
    cooperative?: { name?: string };
}

interface Cooperative {
    id: number;
    name: string;
}

defineProps<{
    fundingSources: {
        data: FundingSource[];
    };
    cooperative?: { id: number; name: string } | null;
    cooperatives?: Array<{ id: number; name: string; status: string }>;
    permissions: {
        can_create: boolean;
        can_edit: boolean;
        can_delete: boolean;
        can_approve: boolean;
    };
}>();

const selectedCoop = ref<Cooperative | null>(null);

const categoryLabel = (category: FundingSource['category']) => {
    if (category === 'member_concern') return 'Member Concern';
    if (category === 'project') return 'Project';
    return 'Activity';
};

const categoryBadgeClass = (category: FundingSource['category']) => {
    if (category === 'member_concern') return 'border border-orange-200 bg-orange-100 text-orange-800';
    if (category === 'project') return 'border border-green-200 bg-green-100 text-green-800';
    return 'border border-blue-200 bg-blue-100 text-blue-800';
};
</script>

<template>
    <Head title="Finance - Funding Sources" />

    <FinanceShellLayout active-tab="funding-sources" :hide-tabs="isFromCoopContext">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                    <div v-if="isFromCoopContext" class="mb-4 flex items-center gap-2 text-sm">
                        <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                        <span class="text-muted-foreground">/</span>
                        <a :href="`/cooperatives/${coopIdFromUrl}`" class="text-primary hover:underline">{{ cooperative?.name || 'Cooperative' }}</a>
                        <span class="text-muted-foreground">/</span>
                        <span class="text-foreground">Funding Sources</span>
                    </div>
                <h1 class="text-2xl font-semibold">Funding Sources</h1>
                <p class="text-sm text-muted-foreground">View all funding sources for the cooperative, including activity-linked funding sources, project support, and member concern entries.</p>
            </div>
            <Link v-if="permissions.can_create" :href="isFromCoopContext && coopIdFromUrl ? `/finance/funding-sources/create?coop_id=${coopIdFromUrl}` : (currentUrl ? `/finance/funding-sources/create?return_to=${encodeURIComponent(currentUrl)}` : '/finance/funding-sources/create')">
                <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                    <Plus class="h-4 w-4" />
                    New Funding Source
                </Button>
            </Link>
        </div>

        <!-- Global Mode: Cooperatives List -->
        <div v-if="showCooperativesList" class="mt-6">
            <h2 class="mb-4 text-lg font-semibold">Select a Cooperative</h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="coop in cooperatives" :key="coop.id" class="cursor-pointer rounded-lg border bg-card p-4 transition hover:border-primary hover:bg-primary/5" @click="selectCoop(coop)">
                    <h3 class="font-medium text-foreground">{{ coop.name }}</h3>
                    <p class="mt-1 text-xs text-muted-foreground">Click to view funding sources</p>
                </div>
            </div>
            <div v-if="!cooperatives || cooperatives.length === 0" class="rounded-lg border bg-card p-6 text-center text-muted-foreground">
                No cooperatives available.
            </div>
        </div>

        <!-- Funding Sources List (shown in coop context or after coop selection in global mode) -->
        <div v-if="showFundingSourcesList" class="mt-6">
            <div v-if="isGlobalMode && selectedCoop" class="mb-4 flex items-center gap-2">
                <Button variant="outline" size="sm" @click="backToCooperatives" class="gap-2">
                    <ArrowLeft class="h-4 w-4" />
                    Back to Cooperatives
                </Button>
                <h2 class="text-lg font-semibold">Funding Sources for {{ selectedCoop.name }}</h2>
            </div>

            <div class="overflow-hidden rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Funder</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-left">Activity</th>
                        <th class="px-4 py-3 text-left">Cooperative</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Allocated</th>
                        <th class="px-4 py-3 text-left">Released</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="fundingSources.data.length === 0">
                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="8">No funding sources found.</td>
                    </tr>
                    <tr v-for="item in fundingSources.data" :key="item.id" class="border-t">
                        <td class="px-4 py-3">{{ item.funder_name }} <span class="text-xs text-muted-foreground">({{ item.funder_type }})</span></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium" :class="categoryBadgeClass(item.category)">
                                {{ categoryLabel(item.category) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1">
                                <span class="inline-flex w-fit rounded-md border border-border bg-muted px-2 py-0.5 text-xs font-medium text-foreground">
                                    {{ item.activity_id && item.activity?.title ? `From Activity: ${item.activity.title}` : (item.activity_id === null ? 'General Fund' : 'Manual Entry') }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ item.activity_id && item.activity?.title ? item.activity.title : (item.activity_id === null ? 'Not tied to a specific activity' : 'No activity linked') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ item.cooperative?.name || 'N/A' }}</td>
                        <td class="px-4 py-3">
                            <Badge :class="[getFinanceStatusBadgeClass(item.status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                {{ item.status }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">{{ formatPhilippinePeso(item.amount_allocated) }}</td>
                        <td class="px-4 py-3">{{ formatPhilippinePeso(item.amount_released) }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <Link :href="currentUrl ? `/finance/funding-sources/${item.id}?return_to=${encodeURIComponent(currentUrl)}` : `/finance/funding-sources/${item.id}`">
                                    <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-2">
                                        <Eye class="h-4 w-4" />
                                        View
                                    </Button>
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </FinanceShellLayout>
</template>
