<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { Users, Plus, Pencil, Trash2, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
import type { CooperativeSummary, Officer } from '@/types/models';

interface Props {
    officers: {
        data: Officer[];
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

const baseUrl = computed(() => props.baseUrl || '/officers');
const queryPrefix = computed(() => props.queryPrefix || '');
const queryKey = (key: string) => `${queryPrefix.value}${key}`;
const hasCoopLock = computed(() => Boolean(props.lockCoopId));

const page = usePage();
const currentUrl = page.url || '';
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const { allCooperativesLabel } = useCoopLabel();
const canCreateOfficer = computed(() => permissions.value.includes('create officers-&-committees'));
const canEditOfficer = computed(() => permissions.value.includes('update officers-&-committees'));
const canDeleteOfficer = computed(() => permissions.value.includes('delete officers-&-committees'));
const canBulkDelete = computed(() => canDeleteOfficer.value);
const showActions = computed(() => canEditOfficer.value || canDeleteOfficer.value);

const search = ref(props.filters.search || '');
const coopId = ref(props.filters.coop_id || props.lockCoopId || 'all');
const status = ref(props.filters.status || 'all');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.officers.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const applyFilters = () => {
    const coopValue = hasCoopLock.value ? props.lockCoopId : coopId.value;

    router.get(baseUrl.value, {
        [queryKey('search')]: search.value,
        [queryKey('coop_id')]: coopValue === 'all' ? '' : coopValue,
        [queryKey('status')]: status.value === 'all' ? '' : status.value,
        [queryKey('per_page')]: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(status, (newStatus, oldStatus) => {
    if (newStatus === oldStatus) return;

    if (newStatus === 'Archived' || oldStatus === 'Archived') {
        applyFilters();
    }
});

const resetFilters = () => {
    search.value = '';
    coopId.value = props.lockCoopId || 'all';
    status.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get(baseUrl.value);
};

const deleteOfficer = async (officer: Officer) => {
    if (!canDeleteOfficer.value) return;
    const confirmed = await confirmAction({
        title: 'Delete officer record?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/officers/${officer.id}`, {
        preserveScroll: true,
    });
};


const formatTerm = (start: string | null, end: string | null) => {
    if (!start && !end) return 'N/A';
    if (start && end) return `${start} - ${end}`;
    return start || end || 'N/A';
};

const visibleOfficers = computed(() => props.officers.data);

const {
    allVisibleSelected,
    clearSelection,
    isSelected,
    selectedCount,
    selectedIds,
    toggleAll,
    toggleOne,
} = useBulkSelection(visibleOfficers);

const bulkDeleteOfficers = async () => {
    if (!selectedCount.value || !canBulkDelete.value) return;

    const confirmed = await confirmAction({
        title: 'Delete selected officer records?',
        text: `Delete ${selectedCount.value} selected officer record(s)? This action cannot be undone.`,
        confirmButtonText: 'Delete selected',
    });

    if (!confirmed) return;

    const idsToDelete = [...selectedIds.value];
    await runBulkDelete(idsToDelete, (id) => `/officers/${id}`);
    clearSelection();
};
</script>

<template>
    <div class="space-y-6 p-4 sm:p-6">
        <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Officers & Committees</h1>
                    <p class="text-sm text-muted-foreground">Manage officer assignments and committees</p>
                </div>
                <div class="flex items-center gap-2 self-start">
                    <div v-if="canBulkDelete && selectedCount > 0" class="flex items-center gap-2 rounded-md border border-border/70 bg-muted/40 px-2 py-1">
                        <span class="text-xs font-medium text-foreground">{{ selectedCount }} selected</span>
                        <Button size="sm" variant="destructive" class="h-8 gap-1.5" @click="bulkDeleteOfficers">
                            <Trash2 class="h-3.5 w-3.5" />
                            Delete Selected
                        </Button>
                        <Button size="sm" variant="outline" class="h-8" @click="clearSelection">
                            Clear
                        </Button>
                    </div>
                    <Link v-if="canCreateOfficer" :href="currentUrl ? `/officers/create?return_to=${encodeURIComponent(currentUrl)}` : '/officers/create'">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Officer
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="mt-6 border-t border-border/60 pt-6">
                <FilterPanel
                    title="Filters"
                    description="Show officer filters when ready to refine results."
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
                                placeholder="Officer name..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Cooperative</label>
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
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Status</label>
                        <Select v-model="status">
                            <SelectTrigger>
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="Active">Active</SelectItem>
                                <SelectItem value="Retired">Retired</SelectItem>
                                <SelectItem value="Removed">Removed</SelectItem>
                                <SelectItem value="Resigned">Resigned</SelectItem>
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
                                    :disabled="officers.data.length === 0"
                                    aria-label="Select all officers"
                                    @update:model-value="toggleAll"
                                />
                            </TableHead>
                            <TableHead class="text-muted-foreground">Officer</TableHead>
                            <TableHead class="text-muted-foreground">Cooperative</TableHead>
                            <TableHead class="text-muted-foreground">Position</TableHead>
                            <TableHead class="text-muted-foreground">Committee</TableHead>
                            <TableHead class="text-muted-foreground">Term</TableHead>
                            <TableHead class="text-muted-foreground">Status</TableHead>
                            <TableHead v-if="showActions" class="text-center text-muted-foreground">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="officers.data.length === 0">
                            <TableCell :colspan="(showActions ? 7 : 6) + (canBulkDelete ? 1 : 0)" class="py-8 text-center text-muted-foreground">
                                No officer records found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="officer in officers.data" :key="officer.id">
                            <TableCell v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="isSelected(officer.id)"
                                    :aria-label="`Select ${officer.member.full_name}`"
                                    @update:model-value="(checked) => toggleOne(officer.id, checked)"
                                />
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <Users class="h-4 w-4" />
                                    </div>
                                    <span class="font-medium text-foreground">{{ officer.member.full_name }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ officer.cooperative.name }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ officer.position }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ officer.committee || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ formatTerm(officer.term_start, officer.term_end) }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ officer.status }}</TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link v-if="canEditOfficer" :href="currentUrl ? `/officers/${officer.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/officers/${officer.id}/edit`">
                                        <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDeleteOfficer"
                                        @click="deleteOfficer(officer)"
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

            <div v-if="officers.last_page > 1" class="border-t border-border px-4 py-4 sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ (officers.current_page - 1) * officers.per_page + 1 }} to
                        {{ Math.min(officers.current_page * officers.per_page, officers.total) }} of
                        {{ officers.total }} officers
                    </div>
                    <div class="flex flex-wrap gap-2" aria-label="Officer pagination">
                        <Button
                            v-for="page in officers.last_page"
                            :key="page"
                            @click="router.get(baseUrl, {
                                page,
                                [queryKey('search')]: search || '',
                                [queryKey('coop_id')]: (hasCoopLock ? props.lockCoopId : coopId) === 'all' ? '' : (hasCoopLock ? props.lockCoopId : coopId),
                                [queryKey('status')]: status === 'all' ? '' : status,
                                [queryKey('per_page')]: resolvedPerPage(),
                            }, { preserveScroll: true, preserveState: true })"
                            :variant="page === officers.current_page ? 'default' : 'outline'"
                            size="sm"
                        >
                            {{ page }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
