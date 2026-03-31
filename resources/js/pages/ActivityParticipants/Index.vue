<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { Users, Plus, Pencil, Trash2, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
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
import AppLayout from '@/layouts/AppLayout.vue';
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
const auth = computed(() => page.props.auth as { roles?: string[]; isCoopAdmin?: boolean; user?: { account_type?: string } } | undefined);
const roles = computed<string[]>(() => auth.value?.roles || []);
const accountType = computed(() => auth.value?.user?.account_type as string | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const isProvincialAdmin = computed(() => roles.value.includes('Provincial Admin') || accountType.value === 'Provincial Admin');
const isOfficer = computed(() => roles.value.includes('Officer') || accountType.value === 'Officer');
const canCreate = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const canEdit = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value);
const canDelete = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
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
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Activity Participants</h1>
                    <p class="text-sm text-muted-foreground">Track activity participation details</p>
                </div>
                <div class="flex items-center gap-2 self-start">
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

            <div class="rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
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
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-[220px_1fr]">
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
            </div>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <Table>
                    <TableHeader>
                        <TableRow>
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
                            <TableCell :colspan="showActions ? 7 : 6" class="text-center text-muted-foreground">
                                No participants found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="participant in participants.data" :key="participant.id">
                            <TableCell class="text-sm text-foreground">{{ participant.member.full_name }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ participant.activity.title }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ participant.activity.cooperative?.name || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ participant.role || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ formatDate(participant.date_joined) }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ beneficiaryLabel(participant.is_beneficiary) }}</TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link v-if="canEdit" :href="`/activity-participants/${participant.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-2">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDelete"
                                        @click="deleteParticipant(participant)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-2 text-destructive hover:text-destructive"
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
                                @click="router.get('/activity-participants', { ...filters, page })"
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
