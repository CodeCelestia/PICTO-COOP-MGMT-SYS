<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Loader2,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    SearchX,
    SlidersHorizontal,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { runBulkDelete, useBulkSelection } from '@/composables/useBulkSelection';
import { useCoopLabel } from '@/composables/useCoopLabel';
import { confirmAction } from '@/lib/alerts';
import type { CommitteeMember, CooperativeSummary } from '@/types/models';

interface Props {
    committeeMembers: {
        data: CommitteeMember[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: CooperativeSummary[];
    filters: {
        search?: string;
        coop_id?: string;
        status?: string;
        per_page?: string;
    };
    baseUrl?: string;
    queryPrefix?: string;
    lockCoopId?: string;
}

const props = defineProps<Props>();

const baseUrl = computed(() => props.baseUrl || '/committee-members');
const queryPrefix = computed(() => props.queryPrefix || '');
const queryKey = (key: string) => `${queryPrefix.value}${key}`;
const hasCoopLock = computed(() => Boolean(props.lockCoopId));
const showCoopFilter = computed(() => !hasCoopLock.value);

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const { allCooperativesLabel } = useCoopLabel();
const canCreateCommittee = computed(() => permissions.value.includes('create officers-&-committees'));
const canEditCommittee = computed(() => permissions.value.includes('update officers-&-committees'));
const canDeleteCommittee = computed(() => permissions.value.includes('delete officers-&-committees'));
const canBulkDelete = computed(() => canDeleteCommittee.value);
const showActions = computed(() => canEditCommittee.value || canDeleteCommittee.value);

const search = ref(props.filters.search || '');
const coopId = ref(props.filters.coop_id || props.lockCoopId || 'all');
const status = ref(props.filters.status || 'all');
const presetPageSizes = ['10', '25', '50', '100'];
const initialPerPageRaw = props.filters.per_page || String(props.committeeMembers.per_page || 10);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : '10');
const filtersVisible = ref(true);
const isLoading = ref(false);
const SEARCH_DEBOUNCE_MS = 300;
let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

const currentPage = computed(() => props.committeeMembers.current_page || 1);
const totalPages = computed(() => Math.max(props.committeeMembers.last_page || 1, 1));
const showingFrom = computed(() => (props.committeeMembers.total ? (currentPage.value - 1) * props.committeeMembers.per_page + 1 : 0));
const showingTo = computed(() => (props.committeeMembers.total ? Math.min(currentPage.value * props.committeeMembers.per_page, props.committeeMembers.total) : 0));

const clearSearchTimer = () => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }
};

const hasActiveFilters = computed(() => (
    search.value.trim() !== ''
    || status.value !== 'all'
    || (showCoopFilter.value && coopId.value !== 'all')
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

const resolvedPerPage = () => perPage.value;

const buildQuery = (pageNumber?: number) => {
    const coopValue = hasCoopLock.value ? props.lockCoopId : coopId.value;

    const query: Record<string, string> = {
        [queryKey('search')]: search.value,
        [queryKey('coop_id')]: coopValue === 'all' ? '' : String(coopValue || ''),
        [queryKey('status')]: status.value === 'all' ? '' : status.value,
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

watch([coopId, status, perPage], () => {
    clearSearchTimer();
    applyFilters();
});

const resetFilters = () => {
    clearSearchTimer();
    search.value = '';
    coopId.value = props.lockCoopId || 'all';
    status.value = 'all';
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

const deleteCommitteeMember = async (member: CommitteeMember) => {
    if (!canDeleteCommittee.value) return;

    const confirmed = await confirmAction({
        title: 'Delete committee member record?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/committee-members/${member.id}`, {
        preserveScroll: true,
    });
};

const visibleCommitteeMembers = computed(() => props.committeeMembers.data);

const {
    allVisibleSelected,
    clearSelection,
    isSelected,
    selectedCount,
    selectedIds,
    toggleAll,
    toggleOne,
} = useBulkSelection(visibleCommitteeMembers);

const bulkDeleteCommitteeMembers = async () => {
    if (!selectedCount.value || !canBulkDelete.value) return;

    const confirmed = await confirmAction({
        title: 'Delete selected committee records?',
        text: `Delete ${selectedCount.value} selected committee record(s)? This action cannot be undone.`,
        confirmButtonText: 'Delete selected',
    });

    if (!confirmed) return;

    const idsToDelete = [...selectedIds.value];
    await runBulkDelete(idsToDelete, (id) => `/committee-members/${id}`);
    clearSelection();
};
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold tracking-tight text-foreground sm:text-2xl">Committee Members</h2>
                        <Badge variant="secondary">{{ committeeMembers.total }} records</Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">Track committee participation and roles</p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <div v-if="canBulkDelete && selectedCount > 0" class="flex items-center gap-2 rounded-md border border-border/70 bg-muted/40 px-2 py-1">
                        <span class="text-xs font-medium text-foreground">{{ selectedCount }} selected</span>
                        <Button size="sm" variant="destructive" class="h-8 gap-1.5" @click="bulkDeleteCommitteeMembers">
                            <Trash2 class="h-3.5 w-3.5" />
                            Delete Selected
                        </Button>
                        <Button size="sm" variant="outline" class="h-8" @click="clearSelection">
                            Clear
                        </Button>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        class="gap-2"
                        @click="filtersVisible = !filtersVisible"
                    >
                        <SlidersHorizontal class="h-4 w-4 transition-transform duration-200" :class="filtersVisible ? 'rotate-90' : 'rotate-0'" />
                        {{ filtersVisible ? 'Hide Filters' : 'Show Filters' }}
                    </Button>
                    <Link v-if="canCreateCommittee" href="/committee-members/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Committee Member
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
                <div v-if="filtersVisible" class="mt-6 border-t border-border/60 pt-6">
                    <div class="rounded-xl border border-border/80 bg-card p-4 shadow-sm">
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="min-w-60 flex-1 space-y-1">
                                <label class="text-sm font-medium text-foreground/80">Search</label>
                                <div class="relative">
                                    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        v-model="search"
                                        placeholder="Committee member name..."
                                        class="pl-9"
                                    />
                                </div>
                            </div>

                            <div v-if="showCoopFilter" class="min-w-40 space-y-1">
                                <label class="text-sm font-medium text-foreground/80">Cooperative</label>
                                <Select v-model="coopId" :disabled="hasCoopLock">
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

                            <div class="min-w-44 space-y-1">
                                <label class="text-sm font-medium text-foreground/80">Status</label>
                                <Select v-model="status">
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Statuses</SelectItem>
                                        <SelectItem value="Active">Active</SelectItem>
                                        <SelectItem value="Inactive">Inactive</SelectItem>
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
                                    :disabled="committeeMembers.data.length === 0"
                                    aria-label="Select all committee members"
                                    @update:model-value="toggleAll"
                                />
                            </TableHead>
                            <TableHead>Member</TableHead>
                            <TableHead>Cooperative</TableHead>
                            <TableHead>Committee</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Service Duration</TableHead>
                            <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="isLoading">
                            <TableRow v-for="rowIndex in 6" :key="`committee-loading-${rowIndex}`">
                                <TableCell v-if="canBulkDelete" class="w-12"><div class="h-4 w-4 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-4 w-44 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-4 w-36 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-4 w-32 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-4 w-28 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-4 w-28 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell v-if="showActions"><div class="mx-auto h-8 w-36 rounded bg-muted animate-pulse" /></TableCell>
                            </TableRow>
                        </template>

                        <TableRow v-else-if="committeeMembers.data.length === 0">
                            <TableCell :colspan="(showActions ? 6 : 5) + (canBulkDelete ? 1 : 0)" class="py-8 text-center text-muted-foreground">
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

                        <TableRow v-for="committeeMember in committeeMembers.data" :key="committeeMember.id">
                            <TableCell v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="isSelected(committeeMember.id)"
                                    :aria-label="`Select ${committeeMember.member.full_name}`"
                                    @update:model-value="(checked) => toggleOne(committeeMember.id, checked)"
                                />
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <Users class="h-4 w-4" />
                                    </div>
                                    <TooltipProvider :delay-duration="150">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <span class="inline-block max-w-56 truncate font-medium text-foreground">{{ committeeMember.member.full_name }}</span>
                                            </TooltipTrigger>
                                            <TooltipContent><p>{{ committeeMember.member.full_name }}</p></TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ committeeMember.cooperative.name }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ committeeMember.committee_name }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ committeeMember.role_label }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ committeeMember.service_duration || 'N/A' }}</TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <TooltipProvider :delay-duration="150">
                                    <div class="flex flex-wrap justify-center gap-2">
                                        <Tooltip v-if="canEditCommittee">
                                            <TooltipTrigger as-child>
                                                <Link :href="`/committee-members/${committeeMember.id}/edit`">
                                                    <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                                        <Pencil class="h-4 w-4" />
                                                        Edit
                                                    </Button>
                                                </Link>
                                            </TooltipTrigger>
                                            <TooltipContent><p>Edit this record</p></TooltipContent>
                                        </Tooltip>
                                        <Tooltip v-if="canDeleteCommittee">
                                            <TooltipTrigger as-child>
                                                <Button
                                                    @click="deleteCommitteeMember(committeeMember)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="table-action-btn table-action-delete gap-2 text-destructive hover:text-destructive"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                    Delete
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent><p>Delete this record</p></TooltipContent>
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
                    Showing {{ showingFrom }}–{{ showingTo }} of {{ committeeMembers.total }} results
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

                    <div class="mt-4 flex justify-center">
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

                            <template v-for="item in paginationItems" :key="`committee-page-${item}`">
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
