<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { LifeBuoy, Plus, Pencil, Trash2, Search, ArrowLeft, Building2, Eye } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
    city_municipality?: string | null;
    province?: string | null;
    date_established?: string | null;
    types?: Array<{ id: number; name: string }>;
    latest_accreditation?: {
        id: number;
        cooperative_id: number;
        level: string;
        date_granted: string | null;
    } | null;
}

interface FinancialRecordOption {
    id: number;
    period: string;
    type: string;
    coop_id: number;
}

interface ExternalSupport {
    id: number;
    coop_id: number;
    support_type: string;
    provider_name: string;
    amount: string | null;
    date_granted: string | null;
    date_completed: string | null;
    status: string;
    remarks: string | null;
    cooperative: Cooperative;
    financial_record?: FinancialRecordOption | null;
}

interface Props {
    supports: {
        data: ExternalSupport[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: Cooperative[];
    financialRecords: FinancialRecordOption[];
    filters: {
        search?: string;
        support_type?: string;
        status?: string;
        coop_id?: string;
        per_page?: string;
        coop_search?: string;
        coop_status?: string;
        coop_type?: string;
        coop_classification?: string;
    };
    cooperative?: { id: number; name: string } | null;
}

const props = defineProps<Props>();

const filters = computed(() => props.filters);
const { allCooperativesLabel } = useCoopLabel();

const page = usePage();
const currentUrl = page.url || '';
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canCreate = computed(() => permissions.value.includes('create financial-&-support'));
const canEdit = computed(() => permissions.value.includes('update financial-&-support'));
const canDelete = computed(() => permissions.value.includes('delete financial-&-support'));
const canBulkDelete = computed(() => canDelete.value);
const showActions = computed(() => canEdit.value || canDelete.value);

const coopIdFromUrl = computed(() => {
    const param = new URLSearchParams(window.location.search).get('coop_id');
    return param ? parseInt(param) : null;
});

const coopSlug = computed(() => page.props.auth?.user?.coop_slug ?? 'my');

const isFromCoopContext = computed(() =>
    window.location.pathname.startsWith('/cooperatives/')
);

const selectedCoop = ref<{ id: number; name: string } | null>((() => {
    const param = new URLSearchParams(window.location.search).get('coop_id');
    if (!param) return null;
    return props.cooperatives?.find(c => c.id === parseInt(param))
        ?? props.cooperative
        ?? null;
})());

const activeCoop = computed(() => selectedCoop.value);

// Cooperative list filters
const coopSearchFilter = ref<string>(props.filters?.coop_search || '');
const coopStatusFilter = ref<string>('');
const coopTypeFilter = ref<string>('');
const coopClassificationFilter = ref<string>('');
const isSwitchingCoop = ref(false);

const isGlobalMode = computed(() => !props.cooperative);
const showCooperativesList = computed(() =>
    isGlobalMode.value && !activeCoop.value
);
const showSupportsList = computed(() =>
    isGlobalMode.value ? !!activeCoop.value : true
);

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

// Get unique cooperative classifications
const availableCoopClassifications = computed(() => {
    const classifications = new Set<string>();
    props.cooperatives?.forEach(coop => {
        if (coop.classification) classifications.add(coop.classification);
    });
    return Array.from(classifications).sort();
});

// Filter cooperatives by status, type, and classification
const filteredCooperatives = computed(() => {
    let filtered = props.cooperatives || [];
    
    if (coopSearchFilter.value.trim()) {
        const searchTerm = coopSearchFilter.value.trim().toLowerCase();
        filtered = filtered.filter(coop =>
            coop.name.toLowerCase().includes(searchTerm)
            || ((coop as Cooperative & { registration_number?: string }).registration_number || '').toLowerCase().includes(searchTerm)
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

const applyFilters = () => {
    const params: Record<string, any> = {};
    if (coopSearchFilter.value) params.coop_search = coopSearchFilter.value;
    if (coopStatusFilter.value) params.status = coopStatusFilter.value;
    if (coopTypeFilter.value) params.type = coopTypeFilter.value;
    if (coopClassificationFilter.value) params.classification = coopClassificationFilter.value;
    
    router.get('/finance/external-supports', params, { preserveScroll: true });
};

const resetFilters = () => {
    coopSearchFilter.value = '';
    coopStatusFilter.value = '';
    coopTypeFilter.value = '';
    coopClassificationFilter.value = '';
    router.get('/finance/external-supports', {}, { preserveScroll: true });
};

const selectCoop = (coop: { id: number; name: string }) => {
    selectedCoop.value = coop;
    isSwitchingCoop.value = true;
    router.get('/finance/external-supports', { coop_id: coop.id }, {
        preserveState: false,
        preserveScroll: false,
        onFinish: () => {
            isSwitchingCoop.value = false;
        },
    });
};

const backToCooperatives = () => {
    coopStatusFilter.value = '';
    coopTypeFilter.value = '';
    router.get(window.location.pathname, {}, {
        preserveState: false,
        preserveScroll: false,
    });
};

const search = ref(props.filters.search || '');
const supportType = ref(props.filters.support_type || 'all');
const status = ref(props.filters.status || 'all');
const coopId = ref(props.filters.coop_id || 'all');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.supports.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const supportTypes = ['Grant', 'Loan', 'Equipment', 'Training', 'Technical Assistance', 'Other'];
const statusOptions = ['Ongoing', 'Completed', 'Pending'];

const applySupportFilters = () => {
    router.get('/external-supports', {
        search: search.value,
        support_type: supportType.value === 'all' ? '' : supportType.value,
        status: status.value === 'all' ? '' : status.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
        per_page: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetSupportFilters = () => {
    search.value = '';
    supportType.value = 'all';
    status.value = 'all';
    coopId.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get('/external-supports');
};

const deleteSupport = async (support: ExternalSupport) => {
    if (!canDelete.value) return;
    const confirmed = await confirmAction({
        title: 'Delete external support?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/external-supports/${support.id}`, {
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

const statusBadgeClass = (status: string) => {
    switch ((status || '').toString().toLowerCase()) {
        case 'ongoing':
        case 'approved':
        case 'active':
        case 'completed':
            return 'bg-green-100 text-green-700 border-green-200';
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'inactive':
        case 'cancelled':
        case 'rejected':
            return 'bg-red-100 text-red-700 border-red-200';
        default:
            return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};

const recordLabel = (record?: FinancialRecordOption | null) => {
    if (!record) return 'Unlinked';
    return `${record.period} · ${record.type}`;
};

const visibleSupports = computed(() => props.supports.data);

const {
    allVisibleSelected,
    clearSelection,
    isSelected,
    selectedCount,
    selectedIds,
    toggleAll,
    toggleOne,
} = useBulkSelection(visibleSupports);

const bulkDeleteSupports = async () => {
    if (!selectedCount.value || !canBulkDelete.value) return;

    const confirmed = await confirmAction({
        title: 'Delete selected external supports?',
        text: `Delete ${selectedCount.value} selected support record(s)? This action cannot be undone.`,
        confirmButtonText: 'Delete selected',
    });

    if (!confirmed) return;

    const idsToDelete = [...selectedIds.value];
    await runBulkDelete(idsToDelete, (id) => `/external-supports/${id}`);
    clearSelection();
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
</script>

<template>
    <FinanceShellLayout active-tab="external-supports" :hide-tabs="isFromCoopContext">
        <div class="w-full space-y-6">
            <!-- Breadcrumb & Header -->
            <div class="space-y-4">
                <div v-if="isFromCoopContext" class="text-sm flex items-center gap-2">
                    <Link href="/cooperatives" class="text-primary hover:underline">Cooperatives</Link>
                    <span class="text-muted-foreground">/</span>
                    <Link :href="`/cooperatives/${activeCoop?.id || coopIdFromUrl}`" class="text-primary hover:underline">{{ activeCoop?.name || 'Cooperative' }}</Link>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-foreground">External Supports</span>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">External Supports</h1>
                        <p class="text-sm text-muted-foreground">Track government and external support</p>
                    </div>
                    <Link v-if="canCreate" :href="selectedCoop ? `/cooperatives/${selectedCoop.id}/finance/external-supports/create?return_to=${encodeURIComponent(currentUrl)}` : `/external-supports/create?return_to=${encodeURIComponent(currentUrl)}`">
                        <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                            <Plus class="h-4 w-4" />
                            Add Support
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
                                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Search</label>
                                    <div class="relative">
                                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                        <input
                                            v-model="coopSearchFilter"
                                            type="text"
                                            placeholder="Search cooperative name or registration no..."
                                            class="w-full rounded-md border border-input bg-background py-2 pl-8 pr-3 text-sm text-foreground"
                                            @keyup.enter="applyFilters"
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
                                <Button variant="default" class="gap-2" @click="applyFilters">
                                    <Filter class="h-4 w-4" />
                                    Apply Filters
                                </Button>
                                <Button variant="outline" @click="resetFilters">
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
                                    <TableRow v-if="isSwitchingCoop">
                                        <TableCell colspan="8" class="py-8 text-center text-muted-foreground">
                                            Loading cooperatives...
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-else-if="filteredCooperatives.length === 0">
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
                                    <TableRow v-for="coop in filteredCooperatives" :key="coop.id" class="cursor-pointer hover:bg-muted/50" @click="selectCoop(coop)">
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
                                        <TableCell class="text-sm text-muted-foreground">
                                            <div class="max-w-40 truncate">{{ coop.city_municipality || coop.province || 'N/A' }}</div>
                                            <div v-if="coop.city_municipality && coop.province" class="truncate text-xs text-muted-foreground">{{ coop.province }}</div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :class="getStatusColor(coop.status)" class="text-xs font-medium">
                                                {{ coop.status }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div v-if="coop.latest_accreditation" class="text-sm">
                                                <div class="text-foreground">{{ coop.latest_accreditation.level }}</div>
                                                <div class="text-xs text-muted-foreground">{{ coop.latest_accreditation.date_granted ? new Date(coop.latest_accreditation.date_granted).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A' }}</div>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">N/A</span>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ coop.date_established ? new Date(coop.date_established).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A' }}
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

            <div v-if="showSupportsList">
                 <div v-if="!isFromCoopContext && activeCoop"
                     class="mb-4 flex items-center gap-2">
                    <Button variant="outline" size="sm"
                            @click="backToCooperatives" class="gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Cooperatives
                    </Button>
                    <h2 class="text-lg font-semibold">
                        External Support for {{ activeCoop?.name }}
                    </h2>
                </div>

                <FilterPanel
                    title="Filters"
                    description="Show external support filters when ready."
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
                                    placeholder="Provider name..."
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
                            <label class="mb-2 block text-sm font-medium text-foreground/80">Support Type</label>
                            <Select v-model="supportType">
                                <SelectTrigger id="support_type_filter">
                                    <SelectValue placeholder="All Types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    <SelectItem v-for="option in supportTypes" :key="option" :value="option">
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
                        <Button @click="applySupportFilters" class="gap-2">
                            <Search class="h-4 w-4" />
                            Apply Filters
                        </Button>
                        <Button @click="resetSupportFilters" variant="outline">Clear Filters</Button>
                    </div>
                </FilterPanel>

                <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                            <TableRow>
                                <TableHead v-if="canBulkDelete" class="w-12">
                                    <Checkbox
                                        :model-value="allVisibleSelected"
                                        :disabled="supports.data.length === 0"
                                        aria-label="Select all external supports"
                                        @update:model-value="toggleAll"
                                    />
                                </TableHead>
                                <TableHead class="text-muted-foreground">Provider</TableHead>
                                <TableHead class="text-muted-foreground">Cooperative</TableHead>
                                <TableHead class="text-muted-foreground">Support Type</TableHead>
                                <TableHead class="text-muted-foreground">Amount</TableHead>
                                <TableHead class="text-muted-foreground">Granted</TableHead>
                                <TableHead class="text-muted-foreground">Status</TableHead>
                                <TableHead class="text-muted-foreground">Linked Record</TableHead>
                                <TableHead v-if="showActions" class="text-center text-muted-foreground">Actions</TableHead>
                            </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="supports.data.length === 0">
                                    <TableCell :colspan="(showActions ? 8 : 7) + (canBulkDelete ? 1 : 0)" class="py-8 text-center text-muted-foreground">
                                        No external support records found.
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="support in supports.data" :key="support.id">
                                    <TableCell v-if="canBulkDelete" class="w-12">
                                        <Checkbox
                                            :model-value="isSelected(support.id)"
                                            :aria-label="`Select ${support.provider_name}`"
                                            @update:model-value="(checked) => toggleOne(support.id, checked)"
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-teal-500/10 text-teal-700 dark:text-teal-300">
                                                <LifeBuoy class="h-4 w-4" />
                                            </div>
                                            <div>
                                                <div class="font-medium text-foreground">{{ support.provider_name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ support.support_type }}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ support.cooperative.name }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ support.support_type }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ formatAmount(support.amount) }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ formatDate(support.date_granted) }}</TableCell>
                                    <TableCell class="text-sm">
                                        <Badge class="text-sm font-medium" :class="statusBadgeClass(support.status)">{{ support.status }}</Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ recordLabel(support.financial_record) }}</TableCell>
                                    <TableCell v-if="showActions" class="text-center">
                                        <div class="flex flex-wrap justify-center gap-2">
                                            <Link v-if="canEdit" :href="currentUrl ? `/external-supports/${support.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/external-supports/${support.id}/edit`">
                                                <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                                    <Pencil class="h-4 w-4" />
                                                    Edit
                                                </Button>
                                            </Link>
                                            <Button
                                                v-if="canDelete"
                                                @click="deleteSupport(support)"
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

                    <div v-if="supports.last_page > 1" class="border-t border-border px-4 py-4 sm:px-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-muted-foreground">
                                Showing {{ (supports.current_page - 1) * supports.per_page + 1 }} to
                                {{ Math.min(supports.current_page * supports.per_page, supports.total) }} of
                                {{ supports.total }} supports
                            </div>
                            <div class="flex flex-wrap gap-2" aria-label="External supports pagination">
                                <Button
                                    v-for="page in supports.last_page"
                                    :key="page"
                                    variant="outline"
                                    size="sm"
                                    :disabled="page === supports.current_page"
                                    @click="router.get('/external-supports', { ...filters, page }, { preserveScroll: true, preserveState: true })"
                                >
                                    {{ page }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FinanceShellLayout>
</template>
