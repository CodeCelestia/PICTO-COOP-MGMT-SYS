<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { HandCoins, Plus, Pencil, Trash2, Search, ArrowLeft, Building2, Filter, Eye } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { runBulkDelete, useBulkSelection } from '@/composables/useBulkSelection';
import { useCoopLabel } from '@/composables/useCoopLabel';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import FilterPanel from '@/components/FilterPanel.vue';
import { confirmAction } from '@/lib/alerts';

interface Cooperative {
    id: number;
    name: string;
    registration_number?: string;
    status: string;
    classification?: string;
    members_count?: number;
    city_municipality?: string;
    province?: string;
    date_established?: string;
    types?: Array<{ id: number; name: string }>;
    latest_accreditation?: {
        id: number;
        cooperative_id: number;
        level: string;
        date_granted: string | null;
    } | null;
}

interface ActivityOption {
    id: number;
    title: string;
    coop_id: number;
}

interface ActivitySummary {
    id: number;
    title: string;
    cooperative?: Cooperative;
}

interface FundingSource {
    id: number;
    activity_id: number | null;
    category: 'activity' | 'project' | 'member_concern';
    coop_id: number;
    funder_name: string;
    funder_type: string;
    amount_allocated: string | null;
    amount_released: string | null;
    date_released: string | null;
    status: string;
    remarks: string | null;
    activity?: ActivitySummary;
    cooperative?: Cooperative;
}

interface Props {
    fundingSources: {
        data: FundingSource[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    activities: ActivityOption[];
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        status?: string;
        funder_type?: string;
        activity_id?: string;
        coop_id?: string;
        per_page?: string;
        coop_search?: string;
        coop_status?: string;
        coop_type?: string;
        coop_classification?: string;
    };
}

const props = defineProps<Props>();

const filters = computed(() => props.filters);

const page = usePage();
const currentUrl = page.url || '';
const pathname = typeof window !== 'undefined' ? window.location.pathname : '';
const isCoopContext = computed(() => pathname.startsWith('/cooperatives/'));
const coopIdFromPath = computed(() => {
    const match = pathname.match(/^\/cooperatives\/([^/]+)/);
    return match ? match[1] : null;
});
const fundingSourceBasePath = computed(() => {
    if (isCoopContext.value && coopIdFromPath.value) {
        return `/cooperatives/${coopIdFromPath.value}/finance/funding-sources`;
    }

    return pathname.startsWith('/finance/funding-sources')
        ? '/finance/funding-sources'
        : '/activity-funding-sources';
});
const auth = computed(() => page.props.auth as { permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const { allCooperativesLabel } = useCoopLabel();
const canCreate = computed(() => permissions.value.includes('create activities-&-projects'));
const canEdit = computed(() => permissions.value.includes('update activities-&-projects'));
const canDelete = computed(() => permissions.value.includes('delete activities-&-projects'));
const canBulkDelete = computed(() => canDelete.value);
const showActions = computed(() => canEdit.value || canDelete.value);

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const funderType = ref(props.filters.funder_type || 'all');
const activityId = ref(props.filters.activity_id || 'all');
const coopId = ref(props.filters.coop_id || 'all');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.fundingSources.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const statusOptions = ['Released', 'Pending', 'Partially Released'];
const funderTypes = ['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'];

// Cooperative list filters
const coopSearchFilter = ref<string>(props.filters?.coop_search || '');
const coopStatusFilter = ref<string>('');
const coopTypeFilter = ref<string>('');
const coopClassificationFilter = ref<string>('');

const selectedCoop = ref<Cooperative | null>((() => {
    const param = new URLSearchParams(window.location.search).get('coop_id');
    if (!param) return null;
    return props.cooperatives?.find(c => c.id === parseInt(param)) ?? null;
})());
const isSwitchingCoop = ref(false);

const isGlobalMode = computed(() => !isCoopContext.value);
const showCooperativesList = computed(() => isGlobalMode.value && !selectedCoop.value);
const showFundingSourcesList = computed(() => isGlobalMode.value ? !!selectedCoop.value : true);

// Get unique cooperative statuses, types, and classifications for filtering
const availableCoopStatuses = computed(() => {
    const statuses = new Set<string>();
    props.cooperatives?.forEach(coop => {
        if (coop.status) statuses.add(coop.status);
    });
    return Array.from(statuses).sort();
});

const availableCoopTypes = computed(() => {
    const types = new Set<string>();
    props.cooperatives?.forEach(coop => {
        coop.types?.forEach(type => {
            types.add(type.name);
        });
    });
    return Array.from(types).sort();
});

const availableCoopClassifications = computed(() => {
    const classifications = new Set<string>();
    props.cooperatives?.forEach(coop => {
        if (coop.classification) classifications.add(coop.classification);
    });
    return Array.from(classifications).sort();
});

// Filter cooperatives by search, status, type, and classification
const filteredCooperatives = computed(() => {
    let filtered = props.cooperatives || [];

    if (coopSearchFilter.value.trim()) {
        const searchTerm = coopSearchFilter.value.trim().toLowerCase();
        filtered = filtered.filter(coop =>
            coop.name.toLowerCase().includes(searchTerm)
            || (coop.registration_number || '').toLowerCase().includes(searchTerm)
        );
    }
    
    if (coopStatusFilter.value) {
        filtered = filtered.filter(coop => coop.status === coopStatusFilter.value);
    }
    
    if (coopTypeFilter.value) {
        filtered = filtered.filter(coop => 
            coop.types?.some(type => type.name === coopTypeFilter.value)
        );
    }
    
    if (coopClassificationFilter.value) {
        filtered = filtered.filter(coop => coop.classification === coopClassificationFilter.value);
    }
    
    return filtered;
});

const applyCoopFilters = () => {
    const params: Record<string, any> = {};
    if (coopSearchFilter.value) params.coop_search = coopSearchFilter.value;
    if (coopStatusFilter.value) params.coop_status = coopStatusFilter.value;
    if (coopTypeFilter.value) params.coop_type = coopTypeFilter.value;
    if (coopClassificationFilter.value) params.coop_classification = coopClassificationFilter.value;
    
    router.get(fundingSourceBasePath.value, params, { preserveScroll: true });
};

const resetCoopFilters = () => {
    coopSearchFilter.value = '';
    coopStatusFilter.value = '';
    coopTypeFilter.value = '';
    coopClassificationFilter.value = '';
    router.get(fundingSourceBasePath.value, {}, { preserveScroll: true });
};

const selectCoop = (coop: Cooperative) => {
    selectedCoop.value = coop;
    isSwitchingCoop.value = true;
    clearSelection();
    router.get(fundingSourceBasePath.value, { coop_id: coop.id }, {
        preserveState: false,
        preserveScroll: true,
        onFinish: () => {
            isSwitchingCoop.value = false;
        },
    });
};

const backToCooperatives = () => {
    selectedCoop.value = null;
    coopSearchFilter.value = '';
    coopStatusFilter.value = '';
    coopTypeFilter.value = '';
    coopClassificationFilter.value = '';
    router.get(fundingSourceBasePath.value, {}, { preserveState: false });
};

const getStatusColor = (status: string): string => {
    switch (status) {
        case 'Active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'Inactive':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        case 'Dormant':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200';
    }
};

const applyFilters = () => {
    router.get(fundingSourceBasePath.value, {
        search: search.value,
        status: status.value === 'all' ? '' : status.value,
        funder_type: funderType.value === 'all' ? '' : funderType.value,
        activity_id: activityId.value === 'all' ? '' : activityId.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
        per_page: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    funderType.value = 'all';
    activityId.value = 'all';
    coopId.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get(fundingSourceBasePath.value);
};

const deleteFundingSource = async (source: FundingSource) => {
    if (!canDelete.value) return;
    const confirmed = await confirmAction({
        title: 'Delete funding source?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`${fundingSourceBasePath.value}/${source.id}`, {
        preserveScroll: true,
    });
};

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatAmount = (value: string | null) => {
    if (!value) return 'N/A';
    const numberValue = Number(value);
    if (Number.isNaN(numberValue)) return value;
    return numberValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const categoryLabel = (category: FundingSource['category']) => {
    if (category === 'member_concern') return 'Member Concern';
    if (category === 'project') return 'Project';
    return 'Activity';
};

const categoryBadgeClass = (category: FundingSource['category']) => {
    if (category === 'member_concern') {
        return 'bg-orange-100 text-orange-800 border-orange-200';
    }
    if (category === 'project') {
        return 'bg-green-100 text-green-800 border-green-200';
    }
    return 'bg-blue-100 text-blue-800 border-blue-200';
};

const statusBadgeClass = (status: string) => {
    switch ((status || '').toLowerCase()) {
        case 'released':
        case 'approved':
        case 'completed':
            return 'border border-green-200 bg-green-100 text-green-800';
        case 'pending':
        case 'draft':
            return 'border border-amber-200 bg-amber-100 text-amber-800';
        case 'inactive':
        case 'cancelled':
        case 'rejected':
            return 'border border-red-200 bg-red-100 text-red-800';
        default:
            return 'border border-gray-200 bg-gray-100 text-gray-800';
    }
};

const visibleFundingSources = computed(() => (isSwitchingCoop.value ? [] : props.fundingSources.data));

const {
    allVisibleSelected,
    clearSelection,
    isSelected,
    selectedCount,
    selectedIds,
    toggleAll,
    toggleOne,
} = useBulkSelection(visibleFundingSources);

const bulkDeleteFundingSources = async () => {
    if (!selectedCount.value || !canBulkDelete.value) return;

    const confirmed = await confirmAction({
        title: 'Delete selected funding sources?',
        text: `Delete ${selectedCount.value} selected funding source record(s)? This action cannot be undone.`,
        confirmButtonText: 'Delete selected',
    });

    if (!confirmed) return;

    const idsToDelete = [...selectedIds.value];
    await runBulkDelete(idsToDelete, (id) => `${fundingSourceBasePath.value}/${id}`);
    clearSelection();
};
</script>

<template>
    <FinanceShellLayout active-tab="funding-sources" :hide-tabs="isCoopContext">
        <div class="w-full space-y-6">
            <!-- Header & Title -->
            <div class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Activity Funding Sources</h1>
                        <p class="text-sm text-muted-foreground">Track funding sources per activity</p>
                    </div>
                    <Link v-if="canCreate && !showCooperativesList" :href="`${fundingSourceBasePath}/create?return_to=${encodeURIComponent(currentUrl)}`">
                        <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                            <Plus class="h-4 w-4" />
                            Add Funding Source
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Cooperatives Selection Grid (Global Mode) -->
            <div v-if="showCooperativesList" class="space-y-4">
                <!-- Filter Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Filter Cooperatives</CardTitle>
                        <CardDescription>Narrow cooperatives by status, type, and classification to quickly find what you need.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
                                <div class="lg:col-span-2">
                                    <Label for="coop-search">Search</Label>
                                    <div class="relative mt-2">
                                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="coop-search"
                                            v-model="coopSearchFilter"
                                            type="text"
                                            placeholder="Search cooperative name or registration no..."
                                            class="pl-8"
                                            @keyup.enter="applyCoopFilters"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-foreground">Status</label>
                                    <select v-model="coopStatusFilter" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                                        <option value="">All Statuses</option>
                                        <option v-for="status in availableCoopStatuses" :key="status" :value="status">{{ status }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-foreground">Type</label>
                                    <select v-model="coopTypeFilter" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                                        <option value="">All Types</option>
                                        <option v-for="type in availableCoopTypes" :key="type" :value="type">{{ type }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-foreground">Classification</label>
                                    <select v-model="coopClassificationFilter" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                                        <option value="">All Classifications</option>
                                        <option v-for="classification in availableCoopClassifications" :key="classification" :value="classification">{{ classification }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button variant="default" class="gap-2" @click="applyCoopFilters">
                                    <Filter class="h-4 w-4" />
                                    Apply Filters
                                </Button>
                                <Button variant="outline" @click="resetCoopFilters">
                                    Reset Filters
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Cooperatives List -->
                <Card>
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle class="text-lg">Cooperatives</CardTitle>
                                <CardDescription>{{ filteredCooperatives.length }} cooperative(s) available</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Cooperative</TableHead>
                                        <TableHead class="text-center">Members</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Location</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Accreditation</TableHead>
                                        <TableHead>Established</TableHead>
                                        <TableHead class="text-center">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="filteredCooperatives.length === 0">
                                        <TableCell colspan="8" class="py-10 text-center text-muted-foreground">
                                            <div class="mx-auto max-w-md space-y-3">
                                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                    <Building2 class="h-6 w-6" />
                                                </div>
                                                <p class="font-medium text-foreground">No cooperatives found</p>
                                                <p class="text-sm text-muted-foreground">Try adjusting your filters</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-for="coop in filteredCooperatives" :key="coop.id" class="cursor-pointer hover:bg-muted/50">
                                        <TableCell class="font-medium">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                    <Building2 class="h-5 w-5" />
                                                </div>
                                                <div>
                                                    <div class="max-w-48 truncate text-foreground">{{ coop.name }}</div>
                                                    <div v-if="coop.registration_number" class="text-xs text-muted-foreground">Reg. {{ coop.registration_number }}</div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <Badge class="rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ coop.members_count ?? 0 }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <Badge v-if="coop.types && coop.types.length > 0" variant="outline" class="text-xs">
                                                    {{ coop.types[0]?.name }}
                                                </Badge>
                                                <Badge v-if="coop.types && coop.types.length > 1" variant="secondary" class="text-xs">
                                                    +{{ coop.types.length - 1 }}
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-muted-foreground text-sm">
                                            <div class="max-w-40 truncate">{{ coop.city_municipality || coop.province || 'N/A' }}</div>
                                            <div v-if="coop.city_municipality && coop.province" class="text-xs text-muted-foreground truncate">{{ coop.province }}</div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :class="getStatusColor(coop.status || '')" class="border">
                                                {{ coop.status || 'Unknown' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div v-if="coop.latest_accreditation" class="text-sm">
                                                <div class="text-foreground">{{ coop.latest_accreditation.level }}</div>
                                                <div class="text-xs text-muted-foreground">{{ formatDate(coop.latest_accreditation.date_granted) }}</div>
                                            </div>
                                            <span v-else class="text-muted-foreground text-sm">N/A</span>
                                        </TableCell>
                                        <TableCell class="text-muted-foreground text-sm">
                                            {{ formatDate(coop.date_established) }}
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <Button variant="ghost" size="sm" class="gap-1" @click.stop="selectCoop(coop)">
                                                <Eye class="h-4 w-4" />
                                                View
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Funding Sources Section -->
            <div v-if="showFundingSourcesList">
                <!-- Back to Coops Button (Global Mode) -->
                <div v-if="!isCoopContext && selectedCoop" class="mb-4 flex items-center gap-2">
                    <Button variant="outline" size="sm" class="gap-2" @click="backToCooperatives">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Cooperatives
                    </Button>
                    <h2 class="text-lg font-semibold">
                        Funding Sources for {{ selectedCoop?.name }}
                    </h2>
                </div>

                <FilterPanel
                    title="Filters"
                    description="Show funding source filters when ready."
                    showLabel="Show filters"
                    hideLabel="Hide filters"
                >
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[repeat(auto-fit,minmax(220px,1fr))]">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Search</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Funder or activity..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Cooperative</label>
                        <Select v-model="coopId">
                            <SelectTrigger id="coop_filter">
                                <SelectValue :placeholder="allCooperativesLabel" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{{ allCooperativesLabel }}</SelectItem>
                                <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">
                                    {{ coop.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Activity</label>
                        <Select v-model="activityId">
                            <SelectTrigger id="activity_filter">
                                <SelectValue placeholder="All Activities" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Activities</SelectItem>
                                <SelectItem v-for="activity in activities" :key="activity.id" :value="activity.id.toString()">
                                    {{ activity.title }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Funder Type</label>
                        <Select v-model="funderType">
                            <SelectTrigger id="funder_type_filter">
                                <SelectValue placeholder="All Types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Types</SelectItem>
                                <SelectItem v-for="option in funderTypes" :key="option" :value="option">
                                    {{ option }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Status</label>
                        <Select v-model="status">
                            <SelectTrigger id="status_filter">
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem v-for="option in statusOptions" :key="option" :value="option">
                                    {{ option }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Rows Per Page</label>
                        <div class="flex gap-2">
                            <Select v-model="perPage">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select size" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="5">5</SelectItem>
                                    <SelectItem value="15">15</SelectItem>
                                    <SelectItem value="30">30</SelectItem>
                                    <SelectItem value="custom">Custom</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input
                                v-if="perPage === 'custom'"
                                v-model="customPerPage"
                                type="number"
                                min="1"
                                max="500"
                                placeholder="Enter"
                                class="w-28"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <Button @click="applyFilters" class="gap-2">
                        <Search class="h-4 w-4" />
                        Apply Filters
                    </Button>
                    <Button @click="resetFilters" variant="outline">Clear Filters</Button>
                </div>
            </FilterPanel>
            </div>

            <!-- Funding Sources Table Header -->
            <div v-if="showFundingSourcesList" class="flex items-center justify-between px-4 sm:px-6">
                <div v-if="canBulkDelete && selectedCount > 0" class="flex items-center gap-2 rounded-md border border-border/70 bg-muted/40 px-2 py-1">
                    <span class="text-xs font-medium text-foreground">{{ selectedCount }} selected</span>
                    <Button size="sm" variant="destructive" class="h-8 gap-1.5" @click="bulkDeleteFundingSources">
                        <Trash2 class="h-3.5 w-3.5" />
                        Delete Selected
                    </Button>
                    <Button size="sm" variant="outline" class="h-8" @click="clearSelection">
                        Clear
                    </Button>
                </div>
                <Link href="/activities" class="text-sm font-medium text-primary underline-offset-4 hover:underline">
                    View Activities
                </Link>
            </div>

            <div v-if="showFundingSourcesList" class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                        <TableRow>
                            <TableHead v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="allVisibleSelected"
                                    :disabled="visibleFundingSources.length === 0"
                                    aria-label="Select all funding sources"
                                    @update:model-value="toggleAll"
                                />
                            </TableHead>
                            <TableHead class="text-muted-foreground">Funder</TableHead>
                            <TableHead class="text-muted-foreground">Category</TableHead>
                            <TableHead class="text-muted-foreground">Activity</TableHead>
                            <TableHead class="text-muted-foreground">Cooperative</TableHead>
                            <TableHead class="text-muted-foreground">Allocated</TableHead>
                            <TableHead class="text-muted-foreground">Released</TableHead>
                            <TableHead class="text-muted-foreground">Date Released</TableHead>
                            <TableHead class="text-muted-foreground">Status</TableHead>
                            <TableHead v-if="showActions" class="text-center text-muted-foreground">Actions</TableHead>
                        </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="isSwitchingCoop">
                                <TableCell :colspan="(showActions ? 9 : 8) + (canBulkDelete ? 1 : 0)" class="py-8 text-center text-muted-foreground">
                                    Loading funding sources...
                                </TableCell>
                            </TableRow>
                            <TableRow v-else-if="visibleFundingSources.length === 0">
                                <TableCell :colspan="(showActions ? 9 : 8) + (canBulkDelete ? 1 : 0)" class="py-8 text-center text-muted-foreground">
                                    No funding sources found.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="source in visibleFundingSources" :key="source.id">
                                <TableCell v-if="canBulkDelete" class="w-12">
                                    <Checkbox
                                        :model-value="isSelected(source.id)"
                                        :aria-label="`Select ${source.funder_name}`"
                                        @update:model-value="(checked) => toggleOne(source.id, checked)"
                                    />
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                                            <HandCoins class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <div class="font-medium text-foreground">{{ source.funder_name }}</div>
                                            <div class="text-xs text-muted-foreground">{{ source.funder_type }}</div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="categoryBadgeClass(source.category)" class="border">
                                        {{ categoryLabel(source.category) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ source.activity?.title || 'N/A' }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ source.cooperative?.name || 'N/A' }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ formatAmount(source.amount_allocated) }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ formatAmount(source.amount_released) }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ formatDate(source.date_released) }}</TableCell>
                                <TableCell>
                                    <Badge :class="statusBadgeClass(source.status)">
                                        {{ source.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell v-if="showActions" class="text-center">
                                    <div class="flex flex-wrap justify-center gap-2">
                                        <Link v-if="canEdit" :href="currentUrl ? `${fundingSourceBasePath}/${source.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `${fundingSourceBasePath}/${source.id}/edit`">
                                            <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                                <Pencil class="h-4 w-4" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button
                                            v-if="canDelete"
                                            @click="deleteFundingSource(source)"
                                            variant="ghost"
                                            size="sm"
                                            class="table-action-btn table-action-delete gap-2 text-destructive hover:text-destructive"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            Delete
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="!isSwitchingCoop && fundingSources.last_page > 1" class="border-t border-border px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ (fundingSources.current_page - 1) * fundingSources.per_page + 1 }} to
                            {{ Math.min(fundingSources.current_page * fundingSources.per_page, fundingSources.total) }} of
                            {{ fundingSources.total }} funding sources
                        </div>
                        <div class="flex flex-wrap gap-2" aria-label="Funding sources pagination">
                            <Button
                                v-for="page in fundingSources.last_page"
                                :key="page"
                                variant="outline"
                                size="sm"
                                :disabled="page === fundingSources.current_page"
                                @click="router.get(fundingSourceBasePath, { ...filters, page }, { preserveScroll: true, preserveState: true })"
                            >
                                {{ page }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FinanceShellLayout>
</template>
