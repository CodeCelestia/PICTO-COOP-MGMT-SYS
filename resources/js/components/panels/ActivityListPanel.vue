<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import {
    ChevronsLeft,
    ChevronsRight,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Eye,
    FileText,
    HandCoins,
    Loader2,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    SearchX,
    SlidersHorizontal,
    Trash2,
    Users,
    Wallet,
} from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { runBulkDelete, useBulkSelection } from '@/composables/useBulkSelection';
import { useCoopLabel } from '@/composables/useCoopLabel';
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
    cooperatives_count?: number;
    cooperatives_participating_count?: number;
    title: string;
    description: string | null;
    category: string;
    date_started: string | null;
    date_ended: string | null;
    status: string;
    venue?: string | null;
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
    showParticipantActionInRows?: boolean;
    showViewActionInRows?: boolean;
}

const props = defineProps<Props>();

const baseUrl = computed(() => props.baseUrl || '/activities');
const queryPrefix = computed(() => props.queryPrefix || '');
const queryKey = (key: string) => `${queryPrefix.value}${key}`;
const lockedCoopId = computed(() => props.lockCoopId || '');
const isSidebarCreateView = computed(() => !lockedCoopId.value);
const showCoopFilter = computed(() => !lockedCoopId.value);

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const { allCooperativesLabel } = useCoopLabel();
const canCreate = computed(() => permissions.value.includes('create activities-&-projects'));
const canEdit = computed(() => permissions.value.includes('update activities-&-projects'));
const canDelete = computed(() => permissions.value.includes('delete activities-&-projects'));
const showParticipantActionInRows = computed(() => isSidebarCreateView.value || props.showParticipantActionInRows || false);
const showActions = computed(() => true);

const search = ref(props.filters.search || '');
const coopId = ref(props.filters.coop_id || 'all');
const status = ref(props.filters.status || 'all');
const category = ref(props.filters.category || 'all');
const canBulkDelete = computed(() => canDelete.value);
const presetPageSizes = ['10', '25', '50', '100'];
const initialPerPageRaw = props.filters.per_page || String(props.activities.per_page || 10);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : '10');
const filtersVisible = ref(true);
const isLoading = ref(false);
const SEARCH_DEBOUNCE_MS = 300;
let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

const currentPage = computed(() => props.activities.current_page || 1);
const totalPages = computed(() => Math.max(props.activities.last_page || 1, 1));
const showingFrom = computed(() => (props.activities.total ? (currentPage.value - 1) * props.activities.per_page + 1 : 0));
const showingTo = computed(() => (props.activities.total ? Math.min(currentPage.value * props.activities.per_page, props.activities.total) : 0));

const clearSearchTimer = () => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }
};

const hasActiveFilters = computed(() => (
    search.value.trim() !== ''
    || coopId.value !== 'all'
    || status.value !== 'all'
    || category.value !== 'all'
    || perPage.value !== '10'
));

const paginationItems = computed<Array<number | string>>(() => {
    const total = totalPages.value;
    const current = currentPage.value;

    if (total <= 7) {
        return Array.from({ length: total }, (_, index) => index + 1);
    }

    if (current <= 4) {
        return [1, 2, 3, 4, 5, 'ellipsis-right', total];
    }

    if (current >= total - 3) {
        return [1, 'ellipsis-left', total - 4, total - 3, total - 2, total - 1, total];
    }

    return [1, 'ellipsis-left', current - 1, current, current + 1, 'ellipsis-right', total];
});

if (lockedCoopId.value) {
    coopId.value = lockedCoopId.value;
}

const resolvedPerPage = () => {
    return perPage.value;
};

const resolvedCoopId = () => {
    if (lockedCoopId.value) return lockedCoopId.value;
    return coopId.value === 'all' ? '' : coopId.value;
};

const statusOptions = ['Planned', 'In Progress', 'Completed', 'Archived', 'Cancelled'];
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
    isLoading.value = true;
    router.get(baseUrl.value, buildQuery(), {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

watch(search, () => {
    clearSearchTimer();
    searchDebounceTimer = setTimeout(() => {
        applyFilters();
    }, SEARCH_DEBOUNCE_MS);
});

watch([coopId, status, category, perPage], () => {
    clearSearchTimer();
    applyFilters();
});

const resetFilters = () => {
    clearSearchTimer();
    search.value = '';
    coopId.value = lockedCoopId.value || 'all';
    status.value = 'all';
    category.value = 'all';
    perPage.value = '10';
    applyFilters();
};

const goToPage = (pageNumber: number) => {
    if (pageNumber < 1 || pageNumber > totalPages.value || pageNumber === currentPage.value) return;

    isLoading.value = true;
    router.get(baseUrl.value, buildQuery(pageNumber), {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

onUnmounted(() => {
    clearSearchTimer();
});

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


const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDateRange = (start: string | null, end: string | null) => {
    if (!start && !end) return 'N/A';
    if (!start) return formatDate(end);
    if (!end) return formatDate(start);
    if (start === end) return formatDate(start);
    return `${formatDate(start)} - ${formatDate(end)}`;
};

const formatOfficerName = (activity: Activity) => {
    return activity.responsible_officer?.member?.full_name || 'N/A';
};

const cooperativesParticipatingCount = (activity: Activity) => {
    return Number(activity.cooperatives_count ?? activity.cooperatives_participating_count ?? 0);
};

const cooperativeCountBadgeClass = (count: number) => {
    if (count === 0) {
        return 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200';
    }

    return 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/20 dark:text-blue-200';
};

const participantsHref = (activity: Activity) => {
    if (isSidebarCreateView.value) {
        return `/activities/${activity.id}/cooperatives-participating`;
    }

    return `/activity-participants?activity_id=${activity.id}&coop_id=${activity.coop_id}`;
};

const openActivityDetails = (activity: Activity) => {
    router.get(`/activities/${activity.id}`);
};

const openParticipants = (activity: Activity) => {
    if (!showParticipantActionInRows.value) {
        return;
    }

    router.get(participantsHref(activity));
};

const openFunding = (activity: Activity) => {
    router.get(`/activity-funding-sources?activity_id=${activity.id}`);
};

const openReport = (activity: Activity) => {
    window.open(`/activities/${activity.id}/report`, '_blank', 'noopener,noreferrer');
};

const openEdit = (activity: Activity) => {
    if (!canEdit.value) {
        return;
    }

    router.get(`/activities/${activity.id}/edit`);
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
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Activities & Projects</h1>
                        <Badge variant="secondary">{{ activities.total }} records</Badge>
                    </div>
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
                    <Button
                        v-if="showCoopFilter"
                        type="button"
                        variant="outline"
                        class="gap-2"
                        @click="filtersVisible = !filtersVisible"
                    >
                        <SlidersHorizontal class="h-4 w-4 transition-transform duration-200" :class="filtersVisible ? 'rotate-90' : 'rotate-0'" />
                        {{ filtersVisible ? 'Hide Filters' : 'Show Filters' }}
                    </Button>
                    <Link v-if="canCreate" :href="lockedCoopId ? `/activities/create?coop_id=${lockedCoopId}&coop_context=1` : '/activities/create'">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Activity
                        </Button>
                    </Link>
                </div>
            </div>

            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="showCoopFilter && filtersVisible" class="mt-6 border-t border-border/60 pt-6">
                    <div class="rounded-xl border border-border/80 bg-card p-4 shadow-sm">
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="min-w-60 flex-1 space-y-1">
                                <label class="text-sm font-medium text-foreground/80">Search</label>
                                <div class="relative">
                                    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        v-model="search"
                                        placeholder="Title, funding, partner..."
                                        class="pl-9"
                                    />
                                </div>
                            </div>

                            <div class="min-w-40 space-y-1">
                                <label class="text-sm font-medium text-foreground/80">Cooperative</label>
                                <Select v-model="coopId">
                                    <SelectTrigger>
                                        <SelectValue :placeholder="allCooperativesLabel" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Cooperatives</SelectItem>
                                        <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">
                                            {{ coop.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="min-w-40 space-y-1">
                                <label class="text-sm font-medium text-foreground/80">Category</label>
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

                            <div class="min-w-40 space-y-1">
                                <label class="text-sm font-medium text-foreground/80">Status</label>
                                <Select v-model="status">
                                    <SelectTrigger>
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

                            <Button
                                v-if="hasActiveFilters"
                                type="button"
                                variant="outline"
                                class="ml-auto gap-2"
                                @click="resetFilters"
                            >
                                <RotateCcw class="h-4 w-4" />
                                Clear Filters
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
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
                            <TableHead :class="isSidebarCreateView ? 'text-center' : ''">{{ isSidebarCreateView ? 'No. Cooperatives Participating' : 'Cooperative' }}</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Venue</TableHead>
                            <TableHead>Dates</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Responsible</TableHead>
                            <TableHead v-if="showActions" class="min-w-[220px] text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="isLoading">
                            <TableRow v-for="rowIndex in 6" :key="`activity-loading-${rowIndex}`">
                                <TableCell v-if="canBulkDelete" class="w-12">
                                    <div class="h-4 w-4 rounded bg-muted animate-pulse" />
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-muted animate-pulse" />
                                        <div class="space-y-2">
                                            <div class="h-4 w-44 rounded bg-muted animate-pulse" />
                                            <div class="h-3 w-28 rounded bg-muted animate-pulse" />
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell :class="['text-sm text-muted-foreground', isSidebarCreateView ? 'text-center' : '']">
                                    <div class="mx-auto h-5 w-14 rounded-full bg-muted animate-pulse" />
                                </TableCell>
                                <TableCell><div class="h-4 w-28 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-4 w-32 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-4 w-32 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-5 w-20 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-4 w-28 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell v-if="showActions"><div class="mx-auto h-8 w-52 rounded bg-muted animate-pulse" /></TableCell>
                            </TableRow>
                        </template>

                        <TableRow v-else-if="activities.data.length === 0">
                            <TableCell :colspan="(showActions ? 8 : 7) + (canBulkDelete ? 1 : 0)" class="text-center text-muted-foreground">
                                <div class="mx-auto max-w-md space-y-3 py-6">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                        <SearchX class="h-6 w-6" />
                                    </div>
                                    <p class="font-medium text-foreground">No results found</p>
                                    <p class="text-sm text-muted-foreground">Try adjusting your filters or search terms.</p>
                                    <Button v-if="hasActiveFilters" type="button" variant="outline" class="gap-2" @click="resetFilters">
                                        <RotateCcw class="h-4 w-4" />
                                        Clear Filters
                                    </Button>
                                </div>
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
                                    <div class="min-w-0">
                                        <TooltipProvider :delay-duration="150">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <div class="max-w-60 truncate font-medium text-foreground">{{ activity.title }}</div>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>{{ activity.title }}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <TooltipProvider :delay-duration="150">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <div class="max-w-60 truncate text-xs text-muted-foreground">{{ activity.funding_source || 'No funding source' }}</div>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>{{ activity.funding_source || 'No funding source' }}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell :class="['text-sm text-muted-foreground', isSidebarCreateView ? 'text-center' : '']">
                                <span
                                    v-if="isSidebarCreateView"
                                    :class="['inline-flex min-w-10 items-center justify-center rounded-full border px-3 py-1 text-xs font-semibold', cooperativeCountBadgeClass(cooperativesParticipatingCount(activity))]"
                                >
                                    {{ cooperativesParticipatingCount(activity) }}
                                </span>
                                <TooltipProvider v-else :delay-duration="150">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <span class="inline-block max-w-60 truncate align-middle">{{ activity.cooperative.name }}</span>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ activity.cooperative.name }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ activity.category }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ activity.venue || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ formatDateRange(activity.date_started, activity.date_ended) }}
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ activity.status }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ formatOfficerName(activity) }}</TableCell>
                            <TableCell v-if="showActions" class="text-center align-top">
                                <TooltipProvider :delay-duration="150">
                                    <div class="grid grid-cols-3 gap-2.5">

                                        <!-- ROW 1: Participants | Funding | Report -->

                                        <!-- Participants — Blue -->
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    @click="openParticipants(activity)"
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-8 w-full justify-center gap-1 px-2 text-xs transition-colors duration-150
                                                           border-blue-200 bg-blue-50 text-blue-700
                                                           hover:bg-blue-100 hover:border-blue-300
                                                           dark:border-blue-700 dark:bg-blue-950 dark:text-blue-300
                                                           dark:hover:bg-blue-900 dark:hover:border-blue-600
                                                           disabled:opacity-50 disabled:pointer-events-none"
                                                    :disabled="!showParticipantActionInRows"
                                                >
                                                    <Users class="h-3.5 w-3.5" />
                                                    Participants
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent><p>View participants for this activity</p></TooltipContent>
                                        </Tooltip>

                                        <!-- Funding — Green -->
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    @click="openFunding(activity)"
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-8 w-full justify-center gap-1 px-2 text-xs transition-colors duration-150
                                                           border-green-200 bg-green-50 text-green-700
                                                           hover:bg-green-100 hover:border-green-300
                                                           dark:border-green-700 dark:bg-green-950 dark:text-green-300
                                                           dark:hover:bg-green-900 dark:hover:border-green-600"
                                                >
                                                    <Wallet class="h-3.5 w-3.5" />
                                                    Funding
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent><p>View funding sources</p></TooltipContent>
                                        </Tooltip>

                                        <!-- Report — Violet -->
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    @click="openReport(activity)"
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-8 w-full justify-center gap-1 px-2 text-xs transition-colors duration-150
                                                           border-violet-200 bg-violet-50 text-violet-700
                                                           hover:bg-violet-100 hover:border-violet-300
                                                           dark:border-violet-700 dark:bg-violet-950 dark:text-violet-300
                                                           dark:hover:bg-violet-900 dark:hover:border-violet-600"
                                                >
                                                    <ClipboardList class="h-3.5 w-3.5" />
                                                    Report
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent><p>Generate report</p></TooltipContent>
                                        </Tooltip>

                                        <!-- ROW 2: View | Edit | Delete -->

                                        <!-- View — Sky -->
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    @click="openActivityDetails(activity)"
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-8 w-full justify-center gap-1 px-2 text-xs transition-colors duration-150
                                                           border-sky-200 bg-sky-50 text-sky-700
                                                           hover:bg-sky-100 hover:border-sky-300
                                                           dark:border-sky-700 dark:bg-sky-950 dark:text-sky-300
                                                           dark:hover:bg-sky-900 dark:hover:border-sky-600"
                                                >
                                                    <Eye class="h-3.5 w-3.5" />
                                                    View
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent><p>View activity details</p></TooltipContent>
                                        </Tooltip>

                                        <!-- Edit — Amber -->
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    @click="openEdit(activity)"
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-8 w-full justify-center gap-1 px-2 text-xs transition-colors duration-150
                                                           border-amber-200 bg-amber-50 text-amber-700
                                                           hover:bg-amber-100 hover:border-amber-300
                                                           dark:border-amber-700 dark:bg-amber-950 dark:text-amber-300
                                                           dark:hover:bg-amber-900 dark:hover:border-amber-600
                                                           disabled:opacity-50 disabled:pointer-events-none"
                                                    :disabled="!canEdit"
                                                >
                                                    <Pencil class="h-3.5 w-3.5" />
                                                    Edit
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent><p>Edit this activity</p></TooltipContent>
                                        </Tooltip>

                                        <!-- Delete — Red (soft, not filled destructive) -->
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    @click="deleteActivity(activity)"
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-8 w-full justify-center gap-1 px-2 text-xs transition-colors duration-150
                                                           border-red-200 bg-red-50 text-red-700
                                                           hover:bg-red-100 hover:border-red-300
                                                           dark:border-red-700 dark:bg-red-950 dark:text-red-300
                                                           dark:hover:bg-red-900 dark:hover:border-red-600
                                                           disabled:opacity-50 disabled:pointer-events-none"
                                                    :disabled="!canDelete"
                                                >
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                    Delete
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent><p>Delete this activity</p></TooltipContent>
                                        </Tooltip>

                                    </div>
                                </TooltipProvider>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="border-t border-border px-4 py-4 sm:px-6">
                <div class="mb-3 flex justify-center text-sm text-muted-foreground">
                    <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                    Showing {{ showingFrom }}–{{ showingTo }} of {{ activities.total }} results
                </div>

                <div class="grid gap-3 md:grid-cols-[1fr_auto_1fr] md:items-center">
                    <div class="flex items-center gap-2 md:justify-start">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <Select v-model="perPage">
                            <SelectTrigger class="w-24">
                                <SelectValue placeholder="Rows" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="10">10</SelectItem>
                                <SelectItem value="25">25</SelectItem>
                                <SelectItem value="50">50</SelectItem>
                                <SelectItem value="100">100</SelectItem>
                            </SelectContent>
                        </Select>
                        <span class="text-sm text-muted-foreground">per page</span>
                    </div>

                    <div class="flex justify-center mt-4">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="gap-1"
                                :disabled="currentPage <= 1"
                                :class="currentPage <= 1 ? 'cursor-not-allowed opacity-50' : ''"
                                @click="goToPage(1)"
                            >
                                <ChevronsLeft class="h-4 w-4" />
                                First
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="gap-1"
                                :disabled="currentPage <= 1"
                                :class="currentPage <= 1 ? 'cursor-not-allowed opacity-50' : ''"
                                @click="goToPage(currentPage - 1)"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                Previous
                            </Button>

                            <template v-for="item in paginationItems" :key="`activity-page-${item}`">
                                <span v-if="typeof item !== 'number'" class="px-1 text-sm text-muted-foreground">...</span>
                                <Button
                                    v-else
                                    type="button"
                                    :variant="item === currentPage ? 'default' : 'outline'"
                                    size="sm"
                                    class="min-w-9"
                                    @click="goToPage(item)"
                                >
                                    {{ item }}
                                </Button>
                            </template>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="gap-1"
                                :disabled="currentPage >= totalPages"
                                :class="currentPage >= totalPages ? 'cursor-not-allowed opacity-50' : ''"
                                @click="goToPage(currentPage + 1)"
                            >
                                Next
                                <ChevronRight class="h-4 w-4" />
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="gap-1"
                                :disabled="currentPage >= totalPages"
                                :class="currentPage >= totalPages ? 'cursor-not-allowed opacity-50' : ''"
                                @click="goToPage(totalPages)"
                            >
                                Last
                                <ChevronsRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div class="hidden md:block" />
                </div>
            </div>
        </div>

    </div>
</template>