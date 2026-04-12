<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { Users, Plus, Pencil, Trash2, Search } from 'lucide-vue-next';
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

interface Activity {
    id: number;
    title: string;
    coop_id: number;
    cooperative?: Cooperative;
}

interface Member {
    id: number;
    full_name: string;
}

interface Participant {
    id: number;
    activity_id: number;
    member_id: number;
    role: string | null;
    date_joined: string | null;
    is_beneficiary: boolean;
    remarks: string | null;
    activity: Activity;
    member: Member;
}

interface Props {
    participants: {
        data: Participant[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    activities: Activity[];
    cooperatives: Cooperative[];
    filters: {
        search?: string;
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
const activityId = ref(props.filters.activity_id || 'all');
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

const applyFilters = () => {
    router.get('/activity-participants', {
        search: search.value,
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
    activityId.value = 'all';
    coopId.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get('/activity-participants');
};

const deleteParticipant = async (participant: Participant) => {
    if (!canDelete.value) return;
    const confirmed = await confirmAction({
        title: 'Remove participant?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Remove',
    });

    if (!confirmed) return;

    router.delete(`/activity-participants/${participant.id}`, {
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

const beneficiaryLabel = (value: boolean) => (value ? 'Yes' : 'No');

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
        title: 'Remove selected participants?',
        text: `Remove ${selectedCount.value} selected participant record(s)? This action cannot be undone.`,
        confirmButtonText: 'Remove selected',
    });

    if (!confirmed) return;

    const idsToDelete = [...selectedIds.value];
    await runBulkDelete(idsToDelete, (id) => `/activity-participants/${id}`);
    clearSelection();
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Activity Participants</h1>
                    <p class="text-sm text-muted-foreground">Track activity participation details</p>
                </div>
                <div class="flex items-center gap-2 self-start">
                    <div v-if="canBulkDelete && selectedCount > 0" class="flex items-center gap-2 rounded-md border border-border/70 bg-muted/40 px-2 py-1">
                        <span class="text-xs font-medium text-foreground">{{ selectedCount }} selected</span>
                        <Button size="sm" variant="destructive" class="h-8 gap-1.5" @click="bulkDeleteParticipants">
                            <Trash2 class="h-3.5 w-3.5" />
                            Remove Selected
                        </Button>
                        <Button size="sm" variant="outline" class="h-8" @click="clearSelection">
                            Clear
                        </Button>
                    </div>
                    <Link href="/activities" class="text-sm font-medium text-primary underline-offset-4 hover:underline">
                        View Activities
                    </Link>
                    <Link v-if="canCreate" href="/activity-participants/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Participant
                        </Button>
                    </Link>
                </div>
                </div>

                <FilterPanel
                    title="Filters"
                    description="Show activity participant filters when ready."
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
                                placeholder="Member or activity..."
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
                                    :disabled="participants.data.length === 0"
                                    aria-label="Select all activity participants"
                                    @update:model-value="toggleAll"
                                />
                            </TableHead>
                            <TableHead>Member</TableHead>
                            <TableHead>Activity</TableHead>
                            <TableHead>Cooperative</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Date Joined</TableHead>
                            <TableHead>Beneficiary</TableHead>
                            <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="participants.data.length === 0">
                            <TableCell :colspan="(showActions ? 7 : 6) + (canBulkDelete ? 1 : 0)" class="text-center text-muted-foreground">
                                No participants found.
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
                            <TableCell class="text-sm text-foreground">{{ participant.member.full_name }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ participant.activity.title }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ participant.activity.cooperative?.name || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ participant.role || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ formatDate(participant.date_joined) }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ beneficiaryLabel(participant.is_beneficiary) }}</TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link v-if="canEdit" :href="`/activity-participants/${participant.id}/edit`">
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
                                        Remove
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
                                @click="router.get('/activity-participants', { ...filters, page }, { preserveScroll: true, preserveState: true })"
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
