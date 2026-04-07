<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { HandCoins, Plus, Pencil, Trash2, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
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
import AppLayout from '@/layouts/AppLayout.vue';
import FilterPanel from '@/components/FilterPanel.vue';
import { confirmAction } from '@/lib/alerts';

interface Cooperative {
    id: number;
    name: string;
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
    activity_id: number;
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
    };
}

const props = defineProps<Props>();

const filters = computed(() => props.filters);

const page = usePage();
const auth = computed(() => page.props.auth as { permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
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

const applyFilters = () => {
    router.get('/activity-funding-sources', {
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
    router.get('/activity-funding-sources');
};

const deleteFundingSource = async (source: FundingSource) => {
    if (!canDelete.value) return;
    const confirmed = await confirmAction({
        title: 'Delete funding source?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/activity-funding-sources/${source.id}`, {
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

const visibleFundingSources = computed(() => props.fundingSources.data);

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
    await runBulkDelete(idsToDelete, (id) => `/activity-funding-sources/${id}`);
    clearSelection();
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Activity Funding Sources</h1>
                    <p class="text-sm text-muted-foreground">Track funding sources per activity</p>
                </div>
                <div class="flex items-center gap-2 self-start">
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
                    <Link v-if="canCreate" href="/activity-funding-sources/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Funding Source
                        </Button>
                    </Link>
                </div>
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
                                <SelectValue placeholder="All Cooperatives" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Cooperatives</SelectItem>
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

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                        <TableRow>
                            <TableHead v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="allVisibleSelected"
                                    :disabled="fundingSources.data.length === 0"
                                    aria-label="Select all funding sources"
                                    @update:model-value="toggleAll"
                                />
                            </TableHead>
                            <TableHead class="text-muted-foreground">Funder</TableHead>
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
                            <TableRow v-if="fundingSources.data.length === 0">
                                <TableCell :colspan="(showActions ? 8 : 7) + (canBulkDelete ? 1 : 0)" class="py-8 text-center text-muted-foreground">
                                    No funding sources found.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="source in fundingSources.data" :key="source.id">
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
                                <TableCell class="text-sm text-muted-foreground">{{ source.activity?.title || 'N/A' }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ source.cooperative?.name || 'N/A' }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ formatAmount(source.amount_allocated) }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ formatAmount(source.amount_released) }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ formatDate(source.date_released) }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ source.status }}</TableCell>
                                <TableCell v-if="showActions" class="text-center">
                                    <div class="flex flex-wrap justify-center gap-2">
                                        <Link v-if="canEdit" :href="`/activity-funding-sources/${source.id}/edit`">
                                            <Button variant="ghost" size="sm" class="gap-2">
                                                <Pencil class="h-4 w-4" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button
                                            v-if="canDelete"
                                            @click="deleteFundingSource(source)"
                                            variant="ghost"
                                            size="sm"
                                            class="gap-2 text-destructive hover:text-destructive"
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

                <div v-if="fundingSources.last_page > 1" class="border-t border-border px-4 py-4 sm:px-6">
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
                                @click="router.get('/activity-funding-sources', { ...filters, page }, { preserveScroll: true, preserveState: true })"
                            >
                                {{ page }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
