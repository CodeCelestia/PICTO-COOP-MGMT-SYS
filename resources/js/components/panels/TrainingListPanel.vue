<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { GraduationCap, Plus, Pencil, Trash2, Search, Users, Eye } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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

interface Training {
    id: number;
    coop_id: number;
    cooperatives_count?: number;
    cooperatives_participating_count?: number;
    title: string;
    date_conducted: string | null;
    facilitator: string | null;
    target_group: string;
    status: string;
    cooperative: Cooperative;
}

interface Props {
    trainings: {
        data: Training[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        status?: string;
        target_group?: string;
        coop_id?: string;
        per_page?: string;
    };
    baseUrl?: string;
    queryPrefix?: string;
    lockCoopId?: string;
    showParticipantActionInRows?: boolean;
    showViewActionInRows?: boolean;
}

const props = defineProps<Props>();

const baseUrl = computed(() => props.baseUrl || '/trainings');
const queryPrefix = computed(() => props.queryPrefix || '');
const queryKey = (key: string) => `${queryPrefix.value}${key}`;
const lockedCoopId = computed(() => props.lockCoopId || '');
const isSidebarCreateView = computed(() => !lockedCoopId.value);
const showCoopFilter = computed(() => !lockedCoopId.value);

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const { allCooperativesLabel } = useCoopLabel();
const canCreate = computed(() => permissions.value.includes('create training-&-capacity'));
const canEdit = computed(() => permissions.value.includes('update training-&-capacity'));
const canDelete = computed(() => permissions.value.includes('delete training-&-capacity'));
const canBulkDelete = computed(() => canDelete.value);
const showParticipantActionInRows = computed(() => isSidebarCreateView.value || props.showParticipantActionInRows || false);
const showViewActionInRows = computed(() => props.showViewActionInRows || false);
const showActions = computed(() => showViewActionInRows.value || showParticipantActionInRows.value || canEdit.value || canDelete.value);
const isViewDialogOpen = ref(false);
const selectedTraining = ref<Training | null>(null);

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const targetGroup = ref(props.filters.target_group || 'all');
const coopId = ref(props.filters.coop_id || 'all');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.trainings.per_page || 15);
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

const statusOptions = ['Planned', 'Completed', 'Archived', 'Cancelled', 'Follow-Up Pending'];
const targetGroups = ['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'Fishfolk', 'New Members', 'Other'];

const buildQuery = (pageNumber?: number) => {
    const query: Record<string, string> = {
        [queryKey('search')]: search.value,
        [queryKey('status')]: status.value === 'all' ? '' : status.value,
        [queryKey('target_group')]: targetGroup.value === 'all' ? '' : targetGroup.value,
        [queryKey('coop_id')]: resolvedCoopId(),
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
    status.value = 'all';
    targetGroup.value = 'all';
    coopId.value = lockedCoopId.value || 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get(baseUrl.value);
};

const deleteTraining = async (training: Training) => {
    if (!canDelete.value) return;
    const confirmed = await confirmAction({
        title: 'Delete training?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/trainings/${training.id}`, {
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

const cooperativesParticipatingCount = (training: Training) => {
    return Number(training.cooperatives_count ?? training.cooperatives_participating_count ?? 0);
};

const cooperativeCountBadgeClass = (count: number) => {
    if (count === 0) {
        return 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200';
    }

    return 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/20 dark:text-blue-200';
};

const participantsHref = (training: Training) => {
    if (isSidebarCreateView.value) {
        return `/trainings/${training.id}/cooperatives-participating`;
    }

    return `/training-participants?training_id=${training.id}&coop_id=${training.coop_id}`;
};

const openViewDialog = (training: Training) => {
    selectedTraining.value = training;
    isViewDialogOpen.value = true;
};

const visibleTrainings = computed(() => props.trainings.data);

const {
    allVisibleSelected,
    clearSelection,
    isSelected,
    selectedCount,
    selectedIds,
    toggleAll,
    toggleOne,
} = useBulkSelection(visibleTrainings);

const bulkDeleteTrainings = async () => {
    if (!selectedCount.value || !canBulkDelete.value) return;

    const confirmed = await confirmAction({
        title: 'Delete selected trainings?',
        text: `Delete ${selectedCount.value} selected training record(s)? This action cannot be undone.`,
        confirmButtonText: 'Delete selected',
    });

    if (!confirmed) return;

    const idsToDelete = [...selectedIds.value];
    await runBulkDelete(idsToDelete, (id) => `/trainings/${id}`);
    clearSelection();
};
</script>

<template>
    <div class="space-y-6 p-4 sm:p-6">
        <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Training & Capacity Building</h1>
                    <p class="text-sm text-muted-foreground">Track training programs and capacity building</p>
                </div>
                <div class="flex items-center gap-2 self-start">
                    <div v-if="canBulkDelete && selectedCount > 0" class="flex items-center gap-2 rounded-md border border-border/70 bg-muted/40 px-2 py-1">
                        <span class="text-xs font-medium text-foreground">{{ selectedCount }} selected</span>
                        <Button size="sm" variant="destructive" class="h-8 gap-1.5" @click="bulkDeleteTrainings">
                            <Trash2 class="h-3.5 w-3.5" />
                            Delete Selected
                        </Button>
                        <Button size="sm" variant="outline" class="h-8" @click="clearSelection">
                            Clear
                        </Button>
                    </div>
                    <Link v-if="canCreate" :href="lockedCoopId ? `/trainings/create?coop_id=${lockedCoopId}&coop_context=1` : '/trainings/create'">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Training
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="mt-6 border-t border-border/60 pt-6">
                <FilterPanel
                    title="Filters"
                    description="Show training filters to refine results."
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
                                placeholder="Title, facilitator, venue..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div v-if="showCoopFilter">
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
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Target Group</label>
                        <Select v-model="targetGroup">
                            <SelectTrigger id="target_group_filter">
                                <SelectValue placeholder="All Groups" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Groups</SelectItem>
                                <SelectItem v-for="option in targetGroups" :key="option" :value="option">
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
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="allVisibleSelected"
                                    :disabled="trainings.data.length === 0"
                                    aria-label="Select all trainings"
                                    @update:model-value="toggleAll"
                                />
                            </TableHead>
                            <TableHead>Training</TableHead>
                            <TableHead>{{ isSidebarCreateView ? 'No. Cooperatives Participating' : 'Cooperative' }}</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Target Group</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="trainings.data.length === 0">
                            <TableCell :colspan="(showActions ? 6 : 5) + (canBulkDelete ? 1 : 0)" class="text-center text-muted-foreground">
                                No training records found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="training in trainings.data" :key="training.id">
                            <TableCell v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="isSelected(training.id)"
                                    :aria-label="`Select ${training.title}`"
                                    @update:model-value="(checked) => toggleOne(training.id, checked)"
                                />
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <GraduationCap class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-foreground">{{ training.title }}</div>
                                        <div class="text-xs text-muted-foreground">{{ training.facilitator || 'No facilitator' }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                <span
                                    v-if="isSidebarCreateView"
                                    :class="['inline-flex min-w-10 items-center justify-center rounded-full border px-3 py-1 text-xs font-semibold', cooperativeCountBadgeClass(cooperativesParticipatingCount(training))]"
                                >
                                    {{ cooperativesParticipatingCount(training) }}
                                </span>
                                <span v-else>{{ training.cooperative.name }}</span>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ formatDate(training.date_conducted) }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ training.target_group }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ training.status }}</TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Button
                                        v-if="showViewActionInRows"
                                        @click="openViewDialog(training)"
                                        variant="outline"
                                        size="sm"
                                        class="gap-2"
                                    >
                                        <Eye class="h-4 w-4" />
                                        View
                                    </Button>
                                    <Link v-if="showParticipantActionInRows" :href="participantsHref(training)">
                                        <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-2">
                                            <Users class="h-4 w-4" />
                                            Participants
                                        </Button>
                                    </Link>
                                    <Link v-if="canEdit" :href="`/trainings/${training.id}/edit`">
                                        <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDelete"
                                        @click="deleteTraining(training)"
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

            <div v-if="trainings.last_page > 1" class="border-t border-border px-4 py-4 sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ (trainings.current_page - 1) * trainings.per_page + 1 }} to
                        {{ Math.min(trainings.current_page * trainings.per_page, trainings.total) }} of
                        {{ trainings.total }} trainings
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="pageNumber in trainings.last_page"
                            :key="pageNumber"
                            variant="outline"
                            size="sm"
                            :disabled="pageNumber === trainings.current_page"
                            @click="router.get(baseUrl, buildQuery(pageNumber), { preserveScroll: true, preserveState: true })"
                        >
                            {{ pageNumber }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:open="isViewDialogOpen">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ selectedTraining?.title || 'Training details' }}</DialogTitle>
                    <DialogDescription>
                        Full details for the selected training record.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedTraining" class="grid gap-4 text-sm sm:grid-cols-2">
                    <div class="space-y-1 sm:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Cooperative</p>
                        <p class="text-foreground">{{ selectedTraining.cooperative.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Date Conducted</p>
                        <p class="text-foreground">{{ formatDate(selectedTraining.date_conducted) }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</p>
                        <p class="text-foreground">{{ selectedTraining.status || 'N/A' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Facilitator</p>
                        <p class="text-foreground">{{ selectedTraining.facilitator || 'N/A' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Target Group</p>
                        <p class="text-foreground">{{ selectedTraining.target_group || 'N/A' }}</p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
