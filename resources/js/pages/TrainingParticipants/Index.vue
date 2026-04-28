<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { Users, Plus, Pencil, Trash2, Search, ArrowLeft } from 'lucide-vue-next';
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
import AppLayout from '@/layouts/AppLayout.vue';
import FilterPanel from '@/components/FilterPanel.vue';
import { confirmAction } from '@/lib/alerts';

interface Cooperative {
    id: number;
    name: string;
}

interface TrainingOption {
    id: number;
    title: string;
    coop_id: number;
}

interface MemberOption {
    id: number;
    name: string;
    coop_id: number;
}

interface OfficerOption {
    id: number;
    name: string | null;
    coop_id: number;
}

interface TrainingParticipant {
    id: number;
    training_id: number;
    member_id: number;
    officer_id: number | null;
    outcome: string | null;
    certificate_no: string | null;
    certificate_date: string | null;
    remarks: string | null;
    training: {
        id: number;
        title: string;
        cooperative: Cooperative;
    };
    member: {
        id: number;
        full_name: string;
    };
    officer?: {
        id: number;
        member?: {
            full_name: string;
        } | null;
    } | null;
}

interface Props {
    participants: {
        data: TrainingParticipant[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    trainings: TrainingOption[];
    members: MemberOption[];
    officers: OfficerOption[];
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        training_id?: string;
        coop_id?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const filters = computed(() => props.filters);
const { allCooperativesLabel } = useCoopLabel();

const page = usePage();
const currentUrl = page.url || '';
const auth = computed(() => page.props.auth as { permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreate = computed(() => permissions.value.includes('create training-&-capacity'));
const canEdit = computed(() => permissions.value.includes('update training-&-capacity'));
const canDelete = computed(() => permissions.value.includes('delete training-&-capacity'));
const canBulkDelete = computed(() => canDelete.value);
const showActions = computed(() => canEdit.value || canDelete.value);

const search = ref(props.filters.search || '');
const trainingId = ref(props.filters.training_id || 'all');
const coopId = ref(props.filters.coop_id || 'all');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.participants.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const selectedTraining = computed(() => props.trainings.find((training) => String(training.id) === trainingId.value));
const backCoopId = computed(() => {
    if (coopId.value && coopId.value !== 'all') return coopId.value;
    return selectedTraining.value?.coop_id ? String(selectedTraining.value.coop_id) : '';
});
const backToTrainingsHref = computed(() => (
    backCoopId.value
        ? `/cooperatives/${backCoopId.value}?tab=training-capacity`
        : '/trainings'
));

const applyFilters = () => {
    router.get('/training-participants', {
        search: search.value,
        training_id: trainingId.value === 'all' ? '' : trainingId.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
        per_page: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    trainingId.value = 'all';
    coopId.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get('/training-participants');
};

const deleteParticipant = async (participant: TrainingParticipant) => {
    if (!canDelete.value) return;
    const confirmed = await confirmAction({
        title: 'Delete participant?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/training-participants/${participant.id}`, {
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

const formatOfficerName = (participant: TrainingParticipant) => {
    return participant.officer?.member?.full_name || 'N/A';
};

const visibleParticipants = computed(() => props.participants.data);

const {
    allVisibleSelected,
    clearSelection,
    isSelected,
    selectedCount,
    selectedIds,
    toggleAll,
    toggleOne,
} = useBulkSelection(visibleParticipants);

const bulkDeleteParticipants = async () => {
    if (!selectedCount.value || !canBulkDelete.value) return;

    const confirmed = await confirmAction({
        title: 'Delete selected participants?',
        text: `Delete ${selectedCount.value} selected participant record(s)? This action cannot be undone.`,
        confirmButtonText: 'Delete selected',
    });

    if (!confirmed) return;

    const idsToDelete = [...selectedIds.value];
    await runBulkDelete(idsToDelete, (id) => `/training-participants/${id}`);
    clearSelection();
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Training Participants</h1>
                    <p class="text-sm text-muted-foreground">Track attendance and results for training sessions</p>
                </div>
                <div class="flex items-center gap-2 self-start">
                    <div v-if="canBulkDelete && selectedCount > 0" class="flex items-center gap-2 rounded-md border border-border/70 bg-muted/40 px-2 py-1">
                        <span class="text-xs font-medium text-foreground">{{ selectedCount }} selected</span>
                        <Button size="sm" variant="destructive" class="h-8 gap-1.5" @click="bulkDeleteParticipants">
                            <Trash2 class="h-3.5 w-3.5" />
                            Delete Selected
                        </Button>
                        <Button size="sm" variant="outline" class="h-8" @click="clearSelection">
                            Clear
                        </Button>
                    </div>
                    <Link :href="backToTrainingsHref">
                        <Button variant="outline" class="h-9 gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                    </Link>
                    <Link v-if="canCreate" href="/training-participants/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Participant
                        </Button>
                    </Link>
                </div>
                </div>

                <div class="mt-6 border-t border-border/60 pt-6">
                    <FilterPanel
                        title="Filters"
                        description="Show training participant filters when ready."
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
                                placeholder="Member or training..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Training</label>
                        <Select v-model="trainingId">
                            <SelectTrigger id="training_filter">
                                <SelectValue placeholder="All Trainings" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Trainings</SelectItem>
                                <SelectItem v-for="training in trainings" :key="training.id" :value="training.id.toString()">
                                    {{ training.title }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
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
                                    :disabled="participants.data.length === 0"
                                    aria-label="Select all training participants"
                                    @update:model-value="toggleAll"
                                />
                            </TableHead>
                            <TableHead>Participant</TableHead>
                            <TableHead>Training</TableHead>
                            <TableHead>Officer</TableHead>
                            <TableHead>Outcome</TableHead>
                            <TableHead>Certificate</TableHead>
                            <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="participants.data.length === 0">
                            <TableCell :colspan="(showActions ? 6 : 5) + (canBulkDelete ? 1 : 0)" class="text-center text-muted-foreground">
                                No training participants found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="participant in participants.data" :key="participant.id">
                            <TableCell v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="isSelected(participant.id)"
                                    :aria-label="`Select ${participant.member.full_name}`"
                                    @update:model-value="(checked) => toggleOne(participant.id, checked)"
                                />
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <Users class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-foreground">{{ participant.member.full_name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ participant.training.cooperative.name }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ participant.training.title }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ formatOfficerName(participant) }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ participant.outcome || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                <div>{{ participant.certificate_no || 'N/A' }}</div>
                                <div class="text-xs text-muted-foreground/80">{{ formatDate(participant.certificate_date) }}</div>
                            </TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link v-if="canEdit" :href="currentUrl ? `/training-participants/${participant.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/training-participants/${participant.id}/edit`">
                                        <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDelete"
                                        @click="deleteParticipant(participant)"
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

                <div v-if="participants.last_page > 1" class="border-t border-border px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ (participants.current_page - 1) * participants.per_page + 1 }} to
                            {{ Math.min(participants.current_page * participants.per_page, participants.total) }} of
                            {{ participants.total }} participants
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="page in participants.last_page"
                                :key="page"
                                variant="outline"
                                size="sm"
                                :disabled="page === participants.current_page"
                                @click="router.get('/training-participants', { ...filters, page }, { preserveScroll: true, preserveState: true })"
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
