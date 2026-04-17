<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, Plus, Pencil, Trash2, Search, HandCoins, RotateCcw, Users } from 'lucide-vue-next';
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
import { useCoopLabel } from '@/composables/useCoopLabel';
import FilterPanel from '@/components/FilterPanel.vue';
import { confirmAction } from '@/lib/alerts';

interface Cooperative {
    id: number;
    name: string;
}

interface Officer {
    id: number;
    member: {
        full_name: string;
    };
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
    cooperative: Cooperative;
    responsible_officer?: Officer | null;
}

interface Props {
    activities: {
        data: Activity[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        coop_id?: string;
        status?: string;
        category?: string;
        per_page?: string;
    };
    baseUrl?: string;
    queryPrefix?: string;
    lockCoopId?: string;
}

const props = defineProps<Props>();

const baseUrl = computed(() => props.baseUrl || '/activities');
const queryPrefix = computed(() => props.queryPrefix || '');
const queryKey = (key: string) => `${queryPrefix.value}${key}`;
const lockedCoopId = computed(() => props.lockCoopId || '');
const showCoopFilter = computed(() => !lockedCoopId.value);

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const { allCooperativesLabel } = useCoopLabel();
const canCreate = computed(() => permissions.value.includes('create activities-&-projects'));
const canEdit = computed(() => permissions.value.includes('update activities-&-projects'));
const canDelete = computed(() => permissions.value.includes('delete activities-&-projects'));
const showActions = computed(() => canEdit.value || canDelete.value);

const search = ref(props.filters.search || '');
const coopId = ref(props.filters.coop_id || 'all');
const status = ref(props.filters.status || 'all');
const category = ref(props.filters.category || 'all');
const isArchivedView = computed(() => status.value === 'Archived');
const canBulkDelete = computed(() => canDelete.value && !isArchivedView.value);
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.activities.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

if (lockedCoopId.value) {
    coopId.value = lockedCoopId.value;
}

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const resolvedCoopId = () => {
    if (lockedCoopId.value) return lockedCoopId.value;
    return coopId.value === 'all' ? '' : coopId.value;
};

const statusOptions = ['Planned', 'In Progress', 'Completed', 'Cancelled'];
const categoryOptions = ['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'];

const buildQuery = (pageNumber?: number) => {
    const query: Record<string, string> = {
        [queryKey('search')]: search.value,
        [queryKey('coop_id')]: resolvedCoopId(),
        [queryKey('status')]: status.value === 'all' ? '' : status.value,
        [queryKey('category')]: category.value === 'all' ? '' : category.value,
        [queryKey('per_page')]: resolvedPerPage(),
    };

    if (pageNumber) {
        query.page = String(pageNumber);
    }

    return query;
};

const applyFilters = () => {
    router.get(baseUrl.value, buildQuery(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    coopId.value = lockedCoopId.value || 'all';
    status.value = 'all';
    category.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get(baseUrl.value);
};

const deleteActivity = async (activity: Activity) => {
    if (!canDelete.value) return;
    const confirmed = await confirmAction({
        title: 'Delete activity?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/activities/${activity.id}`, {
        preserveScroll: true,
    });
};

const restoreActivity = async (activity: Activity) => {
    if (!canDelete.value) return;
    const confirmed = await confirmAction({
        title: 'Restore activity?',
        text: `Restore ${activity.title} to active records?`,
        confirmButtonText: 'Restore',
    });

    if (!confirmed) return;

    router.post(`/activities/${activity.id}/restore`, {}, {
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

const formatOfficerName = (activity: Activity) => {
    return activity.responsible_officer?.member?.full_name || 'N/A';
};

const visibleActivities = computed(() => props.activities.data);

const {
    allVisibleSelected,
    clearSelection,
    isSelected,
    selectedCount,
    selectedIds,
    toggleAll,
    toggleOne,
} = useBulkSelection(visibleActivities);

const bulkDeleteActivities = async () => {
    if (!selectedCount.value || !canBulkDelete.value) return;

    const confirmed = await confirmAction({
        title: 'Delete selected activities?',
        text: `Delete ${selectedCount.value} selected activity record(s)? This action cannot be undone.`,
        confirmButtonText: 'Delete selected',
    });

    if (!confirmed) return;

    const idsToDelete = [...selectedIds.value];
    await runBulkDelete(idsToDelete, (id) => `/activities/${id}`);
    clearSelection();
};
</script>

<template>
    <div class="space-y-6 p-4 sm:p-6">
        <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Activities & Projects</h1>
                    <p class="text-sm text-muted-foreground">Track cooperative activities and projects</p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <div v-if="canBulkDelete && selectedCount > 0" class="flex items-center gap-2 rounded-md border border-border/70 bg-muted/40 px-2 py-1">
                        <span class="text-xs font-medium text-foreground">{{ selectedCount }} selected</span>
                        <Button size="sm" variant="destructive" class="h-8 gap-1.5" @click="bulkDeleteActivities">
                            <Trash2 class="h-3.5 w-3.5" />
                            Delete Selected
                        </Button>
                        <Button size="sm" variant="outline" class="h-8" @click="clearSelection">
                            Clear
                        </Button>
                    </div>
                    <Link href="/activity-participants">
                        <Button variant="outline" class="h-9 gap-2">
                            <Users class="h-4 w-4" />
                            Participants
                        </Button>
                    </Link>
                    <Link v-if="canCreate" href="/activities/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Activity
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="mt-6 border-t border-border/60 pt-6">
                <FilterPanel
                    title="Filters"
                    description="Show filter fields to refine activities and projects."
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
                                    placeholder="Title, funding, partner..."
                                    class="pl-9"
                                />
                            </div>
                        </div>
                        <div v-if="showCoopFilter">
                            <label class="mb-2 block text-sm font-medium text-foreground/80">Cooperative</label>
                            <Select v-model="coopId">
                                <SelectTrigger>
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
                            <label class="mb-2 block text-sm font-medium text-foreground/80">Category</label>
                            <Select v-model="category">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Categories" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Categories</SelectItem>
                                    <SelectItem v-for="option in categoryOptions" :key="option" :value="option">
                                        {{ option }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-foreground/80">Status</label>
                            <Select v-model="status">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem v-for="option in statusOptions" :key="option" :value="option">
                                        {{ option }}
                                    </SelectItem>
                                    <SelectItem value="Archived">Archived</SelectItem>
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
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="allVisibleSelected"
                                    :disabled="activities.data.length === 0"
                                    aria-label="Select all activities"
                                    @update:model-value="toggleAll"
                                />
                            </TableHead>
                            <TableHead>Activity</TableHead>
                            <TableHead>Cooperative</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Dates</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Responsible</TableHead>
                            <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="activities.data.length === 0">
                            <TableCell :colspan="(showActions ? 7 : 6) + (canBulkDelete ? 1 : 0)" class="text-center text-muted-foreground">
                                No activities found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="activity in activities.data" :key="activity.id">
                            <TableCell v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="isSelected(activity.id)"
                                    :aria-label="`Select ${activity.title}`"
                                    @update:model-value="(checked) => toggleOne(activity.id, checked)"
                                />
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <ClipboardList class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-foreground">{{ activity.title }}</div>
                                        <div class="text-xs text-muted-foreground">{{ activity.funding_source || 'No funding source' }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ activity.cooperative.name }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ activity.category }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ formatDate(activity.date_started) }} - {{ formatDate(activity.date_ended) }}
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ activity.status }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ formatOfficerName(activity) }}</TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link :href="`/activity-funding-sources?activity_id=${activity.id}`">
                                        <Button variant="ghost" size="sm" class="table-action-btn table-action-other gap-2">
                                            <HandCoins class="h-4 w-4" />
                                            Funding
                                        </Button>
                                    </Link>
                                    <Link v-if="!isArchivedView && canEdit" :href="`/activities/${activity.id}/edit`">
                                        <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="!isArchivedView && canDelete"
                                        @click="deleteActivity(activity)"
                                        variant="ghost"
                                        size="sm"
                                        class="table-action-btn table-action-delete gap-2 text-destructive hover:text-destructive"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Delete
                                    </Button>
                                    <Button
                                        v-if="isArchivedView && canDelete"
                                        @click="restoreActivity(activity)"
                                        variant="ghost"
                                        size="sm"
                                        class="table-action-btn table-action-other gap-2 text-emerald-700 hover:text-emerald-800"
                                    >
                                        <RotateCcw class="h-4 w-4" />
                                        Restore
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div v-if="activities.last_page > 1" class="border-t border-border px-4 py-4 sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ (activities.current_page - 1) * activities.per_page + 1 }} to
                        {{ Math.min(activities.current_page * activities.per_page, activities.total) }} of
                        {{ activities.total }} activities
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="pageNumber in activities.last_page"
                            :key="pageNumber"
                            variant="outline"
                            size="sm"
                            :disabled="pageNumber === activities.current_page"
                            @click="router.get(baseUrl, buildQuery(pageNumber), { preserveScroll: true, preserveState: true })"
                        >
                            {{ pageNumber }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
