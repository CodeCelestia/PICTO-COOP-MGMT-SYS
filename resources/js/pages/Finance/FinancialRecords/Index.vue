<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2, ArrowLeft, Search, AlertCircle, Building2, Filter } from 'lucide-vue-next';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { computed, ref } from 'vue';
import { confirmAction } from '@/lib/alerts';

interface FinancialRecord {
    id: number;
    period: string;
    type: string;
    origin?: string | null;
    amount: string | null;
    source: string | null;
    purpose: string | null;
    date_recorded: string | null;
    cooperative?: { id: number; name: string };
}

interface Cooperative {
    id: number;
    name: string;
    registration_number?: string | null;
    status?: string;
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

interface Props {
    records: {
        data: FinancialRecord[];
        current_page?: number;
        last_page?: number;
        per_page?: number;
        total?: number;
        links?: any;
    };
    cooperative?: Cooperative | null;
    cooperatives?: Cooperative[];
    permissions: {
        can_create: boolean;
        can_edit: boolean;
        can_delete: boolean;
    };
    filters?: {
        search?: string;
        type?: string;
        coop_search?: string;
        coop_id?: string;
        coop_status?: string;
        coop_type?: string;
        coop_classification?: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const coopSlug = computed(() => page.props.auth?.user?.coop_slug ?? 'my');
const currentUrl = computed(() => `${window.location.pathname}${window.location.search}`);

const isFromCoopContext = computed(() => window.location.pathname.startsWith('/cooperatives/'));

const selectedCoop = ref<Cooperative | null>((() => {
    if (props.cooperative) return props.cooperative;
    const param = new URLSearchParams(window.location.search).get('coop_id');
    if (!param) return null;
    return props.cooperatives?.find(c => c.id === parseInt(param)) ?? null;
})());

const activeCoop = computed(() => selectedCoop.value);
const showCooperativesList = computed(() => !isFromCoopContext.value && !activeCoop.value);
const showRecordsList = computed(() => isFromCoopContext.value || !!activeCoop.value);

// Cooperative list filters
const coopSearchFilter = ref<string>(props.filters?.coop_search || '');
const coopStatusFilter = ref<string>(props.filters?.coop_status || '');
const coopTypeFilter = ref<string>(props.filters?.coop_type || '');
const coopClassificationFilter = ref<string>(props.filters?.coop_classification || '');

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || 'all');

const typeOptions = ['Income', 'Expense', 'Grant', 'Loan', 'Support', 'Capital'];

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

const applyFilters = () => {
    // If we're showing the cooperatives list, filter client-side and update URL (no server reload)
    if (showCooperativesList.value) {
        const params: Record<string, any> = {};
        if (coopSearchFilter.value) params.coop_search = coopSearchFilter.value;
        if (coopStatusFilter.value) params.coop_status = coopStatusFilter.value;
        if (coopTypeFilter.value) params.coop_type = coopTypeFilter.value;
        if (coopClassificationFilter.value) params.coop_classification = coopClassificationFilter.value;
        router.get('/finance/financial-records', params, { preserveState: true, preserveScroll: true });
        return;
    }

    // Otherwise we're viewing records: perform server-side filtering (search, type, coop_id)
    const params: Record<string, any> = {};
    if (search.value) params.search = search.value;
    if (typeFilter.value && typeFilter.value !== 'all') params.type = typeFilter.value;
    if (activeCoop.value) params.coop_id = activeCoop.value.id;

    router.get('/finance/financial-records', params, { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
    if (showCooperativesList.value) {
        coopSearchFilter.value = '';
        coopStatusFilter.value = '';
        coopTypeFilter.value = '';
        coopClassificationFilter.value = '';
        router.get('/finance/financial-records', {}, { preserveState: true, preserveScroll: true });
        return;
    }

    // Reset record filters and reload list
    search.value = '';
    typeFilter.value = 'all';
    router.get('/finance/financial-records', {}, { preserveState: true, preserveScroll: true });
};

const statusBadgeClass = (type: string | null | undefined) => {
    if (!type) return 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200';
    const typeLower = (type || '').toLowerCase();
    if (['income', 'grant'].includes(typeLower)) return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    if (['expense', 'loan'].includes(typeLower)) return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
};

const selectCoop = (coop: Cooperative) => {
    selectedCoop.value = coop;
    router.get('/finance/financial-records', { coop_id: coop.id }, { preserveState: false });
};

const backToCooperatives = () => {
    selectedCoop.value = null;
    coopSearchFilter.value = '';
    coopStatusFilter.value = '';
    coopTypeFilter.value = '';
    coopClassificationFilter.value = '';
    router.get('/finance/financial-records', {}, { preserveState: false });
};

const formatTypeLabel = (value: string | null | undefined) => {
    if (!value) return 'Unknown';
    return value.replace(/[_-]+/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
};

const formatDate = (value: string | null | undefined) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const recordDescription = (record: FinancialRecord) => {
    const description = (record.purpose || '').trim();
    return description || `Financial Record #${record.id}`;
};

const deleteRecord = async (recordId: number) => {
    if (!props.permissions.can_delete) return;
    const confirmed = await confirmAction({
        title: 'Delete financial record?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });
    if (!confirmed) return;
    router.delete(isFromCoopContext.value
        ? `/cooperatives/${coopSlug.value}/finance/financial-records/${recordId}`
        : `/finance/financial-records/${recordId}`,
        { preserveScroll: true }
    );
};

const viewHref = (recordId: number) => isFromCoopContext.value
    ? `/cooperatives/${coopSlug.value}/finance/financial-records/${recordId}`
    : `/finance/financial-records/${recordId}`;

const editHref = (recordId: number) => isFromCoopContext.value
    ? `/cooperatives/${coopSlug.value}/finance/financial-records/${recordId}/edit`
    : `/finance/financial-records/${recordId}/edit?return_to=${encodeURIComponent(currentUrl.value)}`;

const createHref = computed(() => {
    if (isFromCoopContext.value) {
        return `/cooperatives/${coopSlug.value}/finance/financial-records/create`;
    }
    return activeCoop.value
        ? `/finance/financial-records/create?coop_id=${activeCoop.value.id}&return_to=${encodeURIComponent(currentUrl.value)}`
        : '/finance/financial-records/create';
});

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
    <Head title="Finance - Financial Records" />

    <FinanceShellLayout active-tab="financial-records" :hide-tabs="isFromCoopContext">
        <div class="w-full space-y-6">
            <!-- Breadcrumb & Header -->
            <div class="space-y-4">
                <div v-if="isFromCoopContext" class="text-sm flex items-center gap-2">
                    <Link href="/cooperatives" class="text-primary hover:underline">Cooperatives</Link>
                    <span class="text-muted-foreground">/</span>
                    <Link :href="`/cooperatives/${coopSlug}`" class="text-primary hover:underline">{{ activeCoop?.name || 'Cooperative' }}</Link>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-foreground">Financial Records</span>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Financial Records</h1>
                        <p class="text-sm text-muted-foreground">Manual finance entries and ledger records</p>
                    </div>
                    <Link v-if="props.permissions.can_create" :href="createHref">
                        <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                            <Plus class="h-4 w-4" />
                            Add Record
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
                                            <Badge :class="getStatusColor(coop.status || '')" class="border">
                                                {{ coop.status || 'Unknown' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div v-if="coop.latest_accreditation" class="text-sm">
                                                <div class="text-foreground">{{ coop.latest_accreditation.level }}</div>
                                                <div class="text-xs text-muted-foreground">{{ formatDate(coop.latest_accreditation.date_granted) }}</div>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">N/A</span>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ formatDate(coop.date_established ?? null) }}
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

            <!-- Filters Card (shown when viewing records) -->
            <div v-if="showRecordsList" class="space-y-4">
                <!-- Back to Coops Button (Global Mode) -->
                <div v-if="!isFromCoopContext && activeCoop" class="flex items-center gap-2">
                    <Button variant="outline" size="sm" class="gap-2" @click="backToCooperatives">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Cooperatives
                    </Button>
                    <span class="text-sm font-medium">{{ activeCoop.name }}</span>
                </div>

                <!-- Filters Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Filters</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="search">Search</Label>
                                <div class="relative">
                                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        v-model="search"
                                        type="text"
                                        placeholder="Period, source, purpose..."
                                        class="pl-8"
                                        @keyup.enter="applyFilters"
                                    />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label for="type">Type</Label>
                                <Select v-model="typeFilter">
                                    <SelectTrigger id="type">
                                        <SelectValue placeholder="All types" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All types</SelectItem>
                                        <SelectItem v-for="type in typeOptions" :key="type" :value="type">
                                            {{ type }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button @click="applyFilters" class="bg-foreground text-background hover:bg-foreground/90">
                                Apply Filters
                            </Button>
                            <Button variant="outline" @click="resetFilters">
                                Reset
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Records Table -->
                <Card class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="font-semibold">Type</TableHead>
                                    <TableHead class="font-semibold">Period</TableHead>
                                    <TableHead class="font-semibold">Description</TableHead>
                                    <TableHead class="font-semibold">Source</TableHead>
                                    <TableHead class="text-right font-semibold">Amount</TableHead>
                                    <TableHead class="font-semibold">Date Recorded</TableHead>
                                    <TableHead class="text-center font-semibold">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="!props.records.data || props.records.data.length === 0">
                                    <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                                        <div class="flex flex-col items-center gap-2">
                                            <AlertCircle class="h-5 w-5" />
                                            <span>No financial records found</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="record in props.records.data" :key="record.id" class="hover:bg-muted/50">
                                    <TableCell class="font-medium">
                                        <Badge :class="statusBadgeClass(record.type)" class="w-fit">
                                            {{ formatTypeLabel(record.type) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm">{{ record.period }}</TableCell>
                                    <TableCell class="text-sm max-w-xs truncate">{{ recordDescription(record) }}</TableCell>
                                    <TableCell class="text-sm">{{ record.source || '—' }}</TableCell>
                                    <TableCell class="text-right font-mono text-sm">
                                        {{ formatPhilippinePeso(record.amount) }}
                                    </TableCell>
                                    <TableCell class="text-sm">{{ formatDate(record.date_recorded) }}</TableCell>
                                    <TableCell class="text-center">
                                        <div class="flex justify-center gap-1">
                                            <Link :href="viewHref(record.id)">
                                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Link v-if="props.permissions.can_edit" :href="editHref(record.id)">
                                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                    <Pencil class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Button
                                                v-if="props.permissions.can_delete"
                                                variant="ghost"
                                                size="sm"
                                                class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                                @click="deleteRecord(record.id)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>
        </div>
    </FinanceShellLayout>
</template>
